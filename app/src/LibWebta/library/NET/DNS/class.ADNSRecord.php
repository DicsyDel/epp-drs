<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
		
	/**
     * @name       ADNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class ADNSRecord extends DNSRecord
	{
		
		public $Name;
		public $IP;
		public $TTL;
		public $Class;
		public $Content;
		private $Error;
		public $Type;
		
		const DEFAULT_TEMPLATE = "{name} {ttl} {class} A {ip}";
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $ip
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $ip, $ttl = false, $class = "IN")
		{
			parent::__construct();
			
			$this->Type = "A";
			
			// Name
			if (($this->Validator->MatchesPattern($name, self::PAT_NON_FDQN) || 
				$name == "@" || 
				$name === "" || 
				$name == "*" ||
				$this->Validator->IsDomain($name)) && !$this->Validator->IsIPAddress(rtrim($name, "."))
			   )
				$this->Name = $name;
			else 
			{
				self::RaiseWarning("'{$name}' is not a valid name for A record");
				$this->Error = true;
			}
				
				
			if (!$this->Validator->IsIPAddress($ip))
			{
				self::RaiseWarning("'{$ip}' is not a valid ip address for A record");
				$this->Error = true;	
			}
			else 
				$this->IP = $ip;
				
			$this->TTL = $ttl;
			
			$this->Class = $class;

		}
		
		function __toString()
		{
			if ($this->Error !== true)
			{
				$tags = array(	"{name}"		=> $this->Name,
								"{ttl}"			=> $this->TTL,
								"{ip}"			=> $this->IP,
								"{class}"		=> $this->Class
							);
				
				$this->Content = str_replace(
					array_keys($tags),
					array_values($tags),
					self::DEFAULT_TEMPLATE
				);
				
				return $this->Content;
			}
			else 
				return "";
		}
	}
	
?>
