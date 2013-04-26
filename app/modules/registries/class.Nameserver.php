<?php

	/**
	 * Nameserver object.
	 * @name Namserver
	 * @category   EPP-DRS
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @sdk
	 * @author Marat Komarov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	
	class Nameserver
	{
		/**
		 * Hostname
		 *
		 * @var string
		 */
		public $HostName;
		
		function __construct ($hostname) 
		{
			$this->HostName = strtolower($hostname);
		}
		
		/**
		 * Returns hostname->IP pair.
		 *
		 * @return array
		 */
		public function ToArray()
		{
			return array(
				'HostName' => $this->HostName,
				'IPAddr'   => $this->IPAddr
			); 
		}
		
		/**
		 * Returns hostname string
		 *
		 * @return unknown
		 */
		public function __toString()
		{
			return "{$this->HostName}";
		}
	}
	
	class NameserverHost extends Nameserver
	{
		/**
		 * ID in database
		 *
		 * @var int
		 */
		public $ID;
		
		/**
		 * IPv4 address
		 *
		 * @var string
		 */
		public $IPAddr;
	
		function __construct ($hostname, $ipaddr)
		{
			$this->HostName = $hostname;
			$this->IPAddr = $ipaddr;
		}
		
		function GetBaseName ()
		{
			$tmp = explode('.', $this->HostName);
			return $tmp[0];
		}
		
		public function __toString()
		{
			return "{$this->HostName}({$this->IPAddr})";
		}
	}

?>