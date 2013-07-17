<?php

	/**
	 * @author marat
	 */
	class EppDrs_Api_Service
	{
		/**
		 * @var RegistryModuleFactory
		 */
		protected $registry_factory;
		
		/**
		 * @var adodb_lite
		 */
		protected $db;

		// Access mode properties
		const ACCESS_MODE_ADMIN = "admin";
		const ACCESS_MODE_USER = "user";
		
		const INFO_MODE_LOCAL = "local";
		const INFO_MODE_REGISTRY = "registry";
		
		protected $access_mode;
		protected $user_id;
		
		protected $date_format = "Y-m-d H:i";
		
		function __construct ()
		{
			$this->db = Core::GetDBInstance();
			$this->registry_factory = RegistryModuleFactory::GetInstance();
		}
		
		protected function SplitNameAndTLD ($hostname)
		{
			$name_and_tld = explode(".", (string)$hostname, 2);
			if (!$name_and_tld[0] || !$name_and_tld[1])
				throw new Exception("Invalid domain name '$hostname'");
			return $name_and_tld;
		}		
		
		function SetUserAccessMode ($user_id)
		{
			$this->access_mode = self::ACCESS_MODE_USER;
			$this->user_id = $user_id;
		}
		
		function SetAdminAccessMode ()
		{
			$this->access_mode = self::ACCESS_MODE_ADMIN;
		}
		
		protected function CheckDomainAccess ($name, $tld)
		{
			if ($this->access_mode == self::ACCESS_MODE_USER)
			{
				$allow = $this->db->GetOne
				(
					"SELECT name FROM domains WHERE userid = ? AND name = ? AND TLD = ?", 
					array($this->user_id, $name, $tld)
				);
				if (!$allow)
					throw new Exception("Domain {$name}.{$tld} not found");
			}
		}

		protected function CheckContactAccess ($clid)
		{
			if ($this->access_mode == self::ACCESS_MODE_USER)
			{
				$allow = $this->db->GetOne
				(
					"SELECT clid FROM contacts WHERE userid = ? AND clid = ?",
					array($this->user_id, $clid)
				);
				if (!$allow)
					throw new Exception("Contact {$clid} not found");
			}
		}
	}
?>