<?php

final class RegistryModuleFactory extends ModuleFactory
{
	private static $Instance;
	
	private $ExtensionList = array();
	private $ModulesConfig = array();
	private $ModulesCache = array();
	
	/**
	 * @return RegistryModuleFactory
	 */
	public static function GetInstance ()
	{
		if (self::$Instance === null)
			self::$Instance = new RegistryModuleFactory();
			
		return self::$Instance;
	} 
	
	public function IsValidModule($name)
	{
		$interfaces = @class_implements("{$name}");
		$extended = @class_parents("{$name}");
		
		return (isset($interfaces["IRegistryModule"]) && 
				isset($extended["AbstractRegistryModule"]));
	}
	
	/**
	 * Return Registry by module name
	 *
	 * @param string $module_name
	 * @param bool $init_module
	 * @return Registry
	 */
	public function GetRegistryByName($module_name, $init_module = true)
	{
		$db = Core::GetDBInstance();

		$Manifest = new RegistryManifest("{$this->ModulesPath}/{$module_name}/module.xml");
		$module_codebase = $Manifest->GetModuleCodebase();
		if (!$module_codebase) 
		{
			$module_codebase = $module_name;
		}
		
		$this->LoadRegistryModule($module_codebase);
		
		//
		$reflect = new ReflectionClass("{$module_codebase}RegistryModule");
		$module = $reflect->newInstanceArgs(array($Manifest));
		
		if ($init_module)
		{
			// Get Config Config
			$Config = $this->LoadModuleConfig($reflect->getMethod("GetConfigurationForm")->invoke(NULL), $module_name);
			$allTLDs = $module->Manifest->GetExtensionList();
			
			$module->InitializeModule($allTLDs[0], $Config);	
		}
		
		return new Registry($module);
	}
	
	private $Registries = array();
	
	/**
	 * @param string $extension
	 * @return Registry
	 */
	public function FlyRegistryByExtension ($extension)
	{
		if (!array_key_exists($extension, $this->Registries))
		{
			$this->Registries[$extension] = $this->GetRegistryByExtension($extension);
		}
		return $this->Registries[$extension];
	} 
	
	public function ListModules()
	{
		if (!$this->Modules)
		{
			$db = Core::GetDBInstance();
			
			$extensions = $db->Execute("SELECT * FROM tlds");
			while($ext = $extensions->FetchRow())
				$this->Modules[$ext['TLD']] = $ext["modulename"];
				
			unset($db);
		}
		
		return $this->Modules;
	}
	
	/**
	 * @access public
	 * @param string $TLD TLD part, without dot
	 * @param bool $extended If true, return all TLD's exports by module
	 * @return Registry
	 */
	public function GetRegistryByExtension($extension, $db_check = true, $ignore_cache = false) 
	{
		$db = Core::GetDBInstance();
		
		$extensions = $this->GetExtensionList($db_check);

		if (in_array($extension, $extensions))
		{
			$this->ListModules();
			
			$module_name = $this->Modules[$extension];

			if (!$module_name)
				throw new Exception(sprintf(_("Module not defined for %s domain extension."), $extension));
			
			// Validate license for this module
			if (!License::IsModuleLicensed($module_name))
			{
				switch(CONTEXTS::$APPCONTEXT)
				{
					case APPCONTEXT::CRONJOB:
					case APPCONTEXT::REGISTRAR_CP:
						$message = "Your license does not permit module {$module_name}. For additional module purchases, please contact sales@webta.net";
						break;
						
					case APPCONTEXT::REGISTRANT_CP:
						$message = sprintf(_("Application license does not permit module %s. Please contact %s"), 
							$module_name, 
							UI::GetSupportEmailLink()
						);
						break;
						
					case APPCONTEXT::ORDERWIZARD:
						$message = "Your license does not permit module {$module_name}. For additional module purchases, please contact sales@webta.net";
						break;
				}
				// Validate license for this module
				if (!License::IsModuleLicensed($module_name))
					throw new LicensingException($message);
			}

	
			$Manifest = new RegistryManifest("{$this->ModulesPath}/{$module_name}/module.xml");
			$module_codebase = $Manifest->GetModuleCodebase();
			if (!$module_codebase) 
			{
				$module_codebase = $module_name;
			} 			

			$this->LoadRegistryModule($module_codebase);

			if (!$this->ModulesCache[$extension] || $ignore_cache)
			{
				$reflect = new ReflectionClass("{$module_codebase}RegistryModule");					
				$Module = $reflect->newInstance($Manifest);
				
				$Config = $this->LoadModuleConfig($Module->GetConfigurationForm(), $module_name);
				$Module->InitializeModule($extension, $Config);

				$this->ModulesCache[$extension] = $Module;
			}
		}
		else
			throw new Exception(sprintf(_("No modules configured to handle extension '%s'"), $extension));	
		
		return new Registry($this->ModulesCache[$extension]); 
	}
	
	private function LoadModuleConfig($Config, $module_name)
	{		
		if (!$this->ModulesConfig[$module_name])
		{
			$db = Core::GetDBInstance();
			$Crypto = Core::GetInstance("Crypto");
			
			if ($Config instanceof DataForm)
			{
				$fields = $Config->ListFields();
				foreach ($fields as &$field)
				{
					$val = $db->GetOne("SELECT `value` FROM modules_config 
										WHERE `key`=? AND module_name=?", 
						array($field->Name, $module_name)
					);
					
					if ($val)
						$field->Value = $Crypto->Decrypt($val, LICENSE_FLAGS::REGISTERED_TO);
				}
			}
			else 
				$Config = false;

			$this->ModuleConfigs[$module_name] = $Config;
		}
		
		return $this->ModuleConfigs[$module_name];
	}
	
	public function LoadRegistryModule($module_name)
	{
		if (!class_exists("{$module_name}RegistryModule"))
		{
			$module_path = "{$this->ModulesPath}/{$module_name}/class.RegistryModule.php";
			if (self::Load($module_path, "{$module_name}RegistryModule"))
			{
				if (!$this->IsValidModule("{$module_name}RegistryModule"))
					throw new APIException(sprintf(_("%s must contain %sRegistryModule class that extends AbstractRegistryModule, implements IRegistryModule."), $module_path, $module_name), E_ERROR);
					
				// Load Transport
				$transport_path = "{$this->ModulesPath}/{$module_name}/class.Transport.php";
				if (self::Load($transport_path, "{$module_name}Transport"))
				{
					if (!class_exists("{$module_name}Transport"))
						throw new APIException(sprintf(_("%s must contain %sTransport class"), $transport_path, $module_name), E_ERROR);
				}
			}
		}
	}
	
	/**
	 * Return all TLD's exported from all modules
	 *
	 * @param bool $extended
	 * @return array
	 */
	public function GetExtensionList($db_check_extension = true)
	{
		if (!$this->ExtensionList[(int)$db_check_extension])
		{
			$db = Core::GetDBInstance();
			
			if ($db_check_extension)
				$sql = " AND isactive='1'";
				
			$extensions = $db->Execute("SELECT * FROM tlds WHERE 1=1 {$sql} ORDER BY TLD");
			while ($extension = $extensions->FetchRow())
			{
				$this->ExtensionList[(int)$db_check_extension][] = $extension["TLD"];
			}
		}
		
		return $this->ExtensionList[(int)$db_check_extension];
	}

}

?>
