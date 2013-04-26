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
		
	Core::Load("Data/Validation/Validator");
	
	/**
     * @name       DNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class DNSRecord extends Core
	{
		
		/**
		 * Validator 
		 */
		protected $Validator;
		
		public $DefaultTTL;
		
		const PAT_NON_FDQN = '/^[A-Za-z0-9]+[A-Za-z0-9-]*[A-Za-z0-9]+$/';
		const PAT_CIDR = '/^([0-9]{1,3}\.){3}[0-9]{1,3}\/[0-9]{1,3}$/si';
		
		function __construct ()
		{
			$this->DefaultTTL = 14400;
			$this->Validator = new Validator();
		}
		
		function __destuct()
		{
			$this->Validator = null;
		}
		
		
		/**
		 * Return true if $domain is valid domain name
		 * @var string $domain Domain name
		 * @return bool
		 */
		function IsDomain($domain)
		{
			return ($domain == "*") || $this->Validator->IsDomain($domain);
		}
		
		
		
		/**
		* Reverses IP address string for PTR record creation needs
		*
		* @param string $ip Ip address string
		* @return string Reversed IP
		* @access public
		*/
		public function ReverseIP($ip)
		{
			$chunks = explode(".", $ip);
			$chunksr = array_reverse($chunks);
			$retval = implode(".", $chunksr);
			
			return ($retval);
		}
		
		
		
		/**
		* Convert a BIND-style time(1D, 2H, 15M) to seconds.
		*
		* @param string  $time Time to convert.
		* @return int    time in seconds on success, PEAR error on failure.
		*/
		function ParseTimeToSeconds($time)
		{
			
			if (is_numeric($time)) 
			{
				//Already a number. Return.
				return $time;
			} 
			else 
			{
				
				// TODO: Add support for multiple \d\s
				$split = preg_split("/([0-9]+)([a-zA-Z]+)/", $time, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
				
				if (count($split) != 2)
					Core::RaiseWarning(sprintf(_("Unable to parse time. %d"), $time));
				
				list($num, $what) = $split;
				
				switch (strtoupper($what))
				{
					case 'S': //Seconds
						$times = 1; 
						break;
						
					case 'M': //Minute
						$times = 1 * 60; 
						break;
						
					case 'H': //Hour
						$times = 1 * 60 * 60; 
						break;
						
					case 'D': //Day
						$times = 1 * 60 * 60 * 24; 
						break;
						
					case 'W': //Week
						$times = 1 * 60 * 60 * 24 * 7; 
						break;
						
					default:
						Core::RaiseWarning(sprintf(_("Unable to parse time %s"), $time));
						break;
				}
				$time = $num * $times;
				return $time;
			}
		}
	    
		
		/**
		* Append dot to the end of FQDN
		* @access public
		* @param string $domain Domain name
		* @return void
		*/ 
		public function Dottify($value)
		{
			$retval = $this->UnDottify($value);
			$retval .= ".";
			return $retval;
		}
		
		
		/**
		* Remove leading dot
		* @access public
		* @param string $domain Domain name
		* @return void
		*/ 
		public function UnDottify($domain)
		{
			$retval = rtrim($domain, ".");
			return $retval;
		}
	}
	
?>
