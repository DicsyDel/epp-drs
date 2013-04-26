<?php
	class License 
	{
		public static final function IsModuleLicensed($module_name)
		{
			$module_up = strtoupper($module_name);
			
			try
			{
				return self::IsFlagEnabled("MODULE_ENABLED_{$module_up}");
			}
			catch(Exception $ex)
			{
				//Log::Log("Unknown module {$module_name}. Considering it a custom one (licensed).", E_USER_NOTICE);
				return true;
			}
		}
		
		public static final function IsExtensionLicensed($extension_name)
		{
			try
			{
				return self::IsFlagEnabled($extension_name);
			}
			catch(Exception $ex)
			{
				Log::Log("Unknown extension {$extension_name}. Considering it a custom one (licensed).", E_USER_NOTICE);
				return true;
			}
		}
		
		public static final function IsFlagEnabled($flagname)
		{
			$retval = false;

			$const_value = EnumFactory::GetValue("LICENSE_FLAGS", $flagname);
			
			if (in_array($const_value, array('Yes', 'True', '1', true)))
				$retval = true;

			return $retval;
		}
	}
?>