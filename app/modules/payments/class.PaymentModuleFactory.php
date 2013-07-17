<?php

	class PaymentModuleFactory extends ModuleFactory
	{		
		private static $Instance;
		
		private static $ModuleObservers = array();
		
		public static function AddModuleObserver(IPaymentObserver $observer, 
				$phace = EVENT_HANDLER_PHACE::BEFORE_SYSTEM)
		{
			if (!array_key_exists($phace, self::$ModuleObservers))
			{
				self::$ModuleObservers[$phace] = array();
			}
			
			if (array_search($observer, self::$ModuleObservers[$phace]) !== false)
				throw new Exception(_('Observer already attached to class <Invoice>'));
				
			self::$ModuleObservers[$phace][] = $observer;
		}
		
		public static function GetInstance ()
		{
			if (self::$Instance === null)
				self::$Instance = new PaymentModuleFactory();
				
			return self::$Instance;
		} 		
						
		/**
		 * Check module Validity
		 *
		 * @param string $name
		 * @return bool
		 */
		private function IsValidModule($name)
		{
			$interfaces = @class_implements("{$name}");
			$extended = @class_parents("{$name}");
			
			return (isset($interfaces["IDirectPaymentModule"]) || isset($interfaces["IPostBackPaymentModule"])) && 
					isset($extended["AbstractPaymentModule"]);
		}
		
		/**
		 * Return module instance by context
		 *
		 * @param array $request
		 * @return true
		 */
		public function GetModuleInstanceByContext($request)
		{
			foreach ($this->ListModules() as $module_name)
			{
				$m = &$this->GetModuleObjectByName($module_name);				
				$reflectionobject = new ReflectionObject($m);
				
				if ($reflectionobject->implementsInterface("IPostBackPaymentModule"))
				{
					if ($m->CheckSignature($request))
						return $m;
				}
			}
			
			return false;
		}
		
		/**
		 * List all modules
		 *
		 * @todo for alex: Add ZEND licence flags check.
		 * @return bool
		 */
		public function ListModules($include_disabled = false)
		{
			$db = Core::GetDBInstance();
			
			if (!$this->Modules || $include_disabled)
			{
				$this->Modules = array();			
				$driver_dirs = @glob("{$this->ModulesPath}/*", GLOB_ONLYDIR);
				
				$exclude_list = array(".svn","observers");
				
				foreach((array)$driver_dirs as $driver_dir)
				{
					if (in_array(basename($driver_dir), $exclude_list))
						continue;
						
					$pi = pathinfo($driver_dir);
					$pmodule_name = $pi["basename"];
					
					if (self::Load("{$driver_dir}/class.PaymentModule.php", "{$pmodule_name}PaymentModule"))
					{
						if (!$this->IsValidModule("{$pmodule_name}PaymentModule"))
							throw new APIException(sprintf(_("%s must contain %sPaymentModule class that extends AbstractPaymentModule and either implements IDirectPaymentModule or/and IPostBackPaymentModule."), "{$driver_dir}/class.PaymentModule.php", $pmodule_name), E_ERROR);
							
						if (!$include_disabled)
						{
							if ($db->GetOne("SELECT status FROM pmodules WHERE name=?", array($pmodule_name)) == 1)
								$this->Modules[] = $pmodule_name;
						}
						else
						{
							// if module exists add it to modules list
							$this->Modules[] = $pmodule_name;
						}
					}
				}
			}
			
			return $this->Modules;
		}
		
		/**
		 * Return module instance by module name
		 *
		 * @param string $name
		 * @return PaymentModule
		 */
		public function GetModuleObjectByName($name)
		{
			$db = Core::GetDBInstance();
			$Crypto = Core::GetInstance("Crypto");
			
			if (class_exists("{$name}PaymentModule"))
			{
				try
				{
					$classname = "{$name}PaymentModule";
					$module = new $classname();
			
					$Config = $module->GetConfigurationForm();
					
					if ($Config instanceof DataForm)
					{
						$fields = $Config->ListFields();
			
						if (count($fields) > 0)
							foreach ($fields as &$field)
							{
								$val = $db->GetOne("SELECT `value` FROM pmodules_config 
													WHERE `key`=? AND module_name=?", 
									array($field->Name, $name)
								);
								
								if ($val)
									$field->Value = $Crypto->Decrypt($val, LICENSE_FLAGS::REGISTERED_TO);
							} 
					}
					else 
						$Config = false;

	
					$module->InitializeModule($Config);
					
					// Attach global observers
					foreach (self::$ModuleObservers as $phace => $observers)
						foreach ($observers as $observer)
							$module->AttachObserver($observer, $phace);
				}
				catch (Exception $e)
				{
					throw new Exception(sprintf(_("GetModuleObjectByName failed: %s"), $e->getMessage()), E_ERROR);
				}
			}
			else 
				throw new Exception(sprintf(_("Payment module %s not found."), $name), E_ERROR);
				
			return $module;
		}
	}
?>