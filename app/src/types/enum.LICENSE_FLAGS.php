<?
	/**
	 * @category EPP-DRS
	 * @package Common
	 */
	final class LICENSE_FLAGS
	{
		const REGISTERED_TO = "EPP-DRS Team";
		
		// Registry modules
		const MODULE_ENABLED_EPPGR = true;
		const MODULE_ENABLED_EPPEU = false;
		const MODULE_ENABLED_ONLINENIC = true;
		const MODULE_ENABLED_LU = true;
		const MODULE_ENABLED_SRSPLUS = true;
		const MODULE_ENABLED_VERISIGN = true;
		const MODULE_ENABLED_GENERICEPP = true;
		const MODULE_ENABLED_RRPPROXY = true;
		const MODULE_ENABLED_EPPCH = true;
		
		// Registry modules
		const MODULE_ENABLED_OFFLINEBANK = true;
		const MODULE_ENABLED_PAYPAL = true;
		const MODULE_ENABLED_2CO = true;
		const MODULE_ENABLED_EZBILL = true;
		const MODULE_ENABLED_PROXYPAY3 = true;
		const MODULE_ENABLED_BEANSTREAM = true;
		const MODULE_ENABLED_UNIONPAY = true;
		
		// Additional extensions
		const EXT_VERISIGN_PREREGISTRATION_DROPCATCHING = true;
		const EXT_MANAGED_DNS = true; 
		
		/**
		 * List all available properties through reflection
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @return array or names
		 */
		public static function GetKeys()
		{ 
			$retval = array();
			$ReflectionClassThis = new ReflectionClass(__CLASS__);
			foreach($ReflectionClassThis->getStaticProperties() as $Property)
			{
				$retval[] = $Property->name;
			}
			return($retval);
		}
		
		/**
		 * Get value of property by property name
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @param  $key
		 */
		public static function GetValues($key)
		{
			return get_class_vars(__CLASS__);
		}
		
		/**
		 * Get value of property by property name
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @param  $key
		 */
		public static function GetValue($key)
		{
			//property_exists
			$ReflectionClassThis = new ReflectionClass(__CLASS__);
			if ($ReflectionClassThis->hasConstant($key))
			{
				return $ReflectionClassThis->getConstant($key);
			}
			else 
			{
				throw new Exception(sprintf(_("Called %s::GetValue('{$key}') for non-existent property {$key}"), __CLASS__));
			}
		}
	}
?>