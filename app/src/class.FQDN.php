<?php
	
	/**
	 * Class for parsing and validating domain names
	 *
	 * @package Modules
	 * @subpackage RegistryModules
	 * @sdk-doconly
	 */
	class FQDN 
	{
		
		/**
		 * Extract domain name and extension from FQDN
		 *
		 * @param string $hostname
		 * @return array (name, extension)
		 */
		public static function Parse($domain)
		{
			$domain = FQDN::Sanitize($domain);
			
			list($name, $extension) = explode(".", $domain, 2);
			
			// FAIL11
			if (empty($name) || empty($extension))
				throw new Exception(sprintf(_("Failed to parse domain name: %s"), $domain));
			return (array($name, $extension));
		}	
		
		/**
		 * Remove leading and trailing spaces and dots from domain name 
		 *
		 * @param string $domain
		 * @return string
		 */
		public static function Sanitize($domain)
		{
			return strtolower(trim($domain, "."));
		}
		
		public static function IsSubdomain ($testname, $domainname)
		{
			return (bool)preg_match("/^(.*)\.{$domainname}$/", $testname);
		}
	}
?>