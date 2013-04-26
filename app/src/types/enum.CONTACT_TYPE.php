<?php

	/**
	 * Contact types
	 * @package Modules
	 * @subpackage RegistryModules
	 * @sdk
	 */
	class CONTACT_TYPE
	{
		const BILLING 		= 'billing';
		const TECH 			= 'tech';
		const ADMIN 		= 'admin';
		const REGISTRANT 	= 'registrant';
		
		/**
		 * List all available properties through reflection
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @return Array or names
		 */
		public static function GetKeys()
		{ 
			$retval = array();
			$ReflectionClassThis = new ReflectionClass(__CLASS__);
			return($ReflectionClassThis->getConstants());
		}
	}
?>