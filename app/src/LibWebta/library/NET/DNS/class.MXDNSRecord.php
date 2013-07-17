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
     * @name       MXDNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class MXDNSRecord extends DNSRecord
	{
		public $Name;
		public $Rname;
		public $TTL;
		public $Class;
		public $Pref;
		public $Content;
		private $Error;
		public $Type;
		
		const DEFAULT_TEMPLATE = "{name} {ttl} {class} MX {pref} {rname}";
		
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $rname
		 * @param integer $pref
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $rname, $ttl = false, $pref = 10, $class = "IN")
		{
			parent::__construct();
			
			$this->Type = "MX";
			
			// Name
			if (($this->Validator->MatchesPattern($name, self::PAT_NON_FDQN) || 
				$name == "@" || 
				$name === "" || 
				$this->Validator->IsDomain($name)) && !$this->Validator->IsIPAddress(rtrim($name, "."))
			   )
				$this->Name = $name;
			else 
			{
				self::RaiseWarning("'{$name}' is not a valid name for MX record");
				$this->Error = true;
			}
				
				
			if (($this->Validator->MatchesPattern($rname, self::PAT_NON_FDQN) || 
				$this->Validator->IsDomain($rname)) && !$this->Validator->IsIPAddress(rtrim($rname, "."))
			   )
				$this->Rname = $rname;
			else 
			{
				self::RaiseWarning("'{$rname}' is not a valid value for MX record");
				$this->Error = true;
			}
			
			$this->Pref = $pref;
			
			$this->TTL = $ttl;
			
			$this->Class = $class;
		}
		
		/**
		 * Magic function __toString
		 *
		 * @return string
		 */
		function __toString()
		{
			if (!$this->Error)
			{
				$tags = array(	"{name}"		=> $this->Name,
								"{ttl}"			=> $this->TTL,
								"{rname}"		=> $this->Rname,
								"{class}"		=> $this->Class,
								"{pref}"		=> $this->Pref
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
