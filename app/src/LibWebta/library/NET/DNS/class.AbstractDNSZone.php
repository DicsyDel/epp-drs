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
     * @name       AbstractDNSZone
     * @abstract 
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	abstract class AbstractDNSZone extends Core
	{
		
		
		/**
		* Generate a new serial based on given one.
		*
		* This generates a new serial, based on the often used format
		* YYYYMMDDXX where XX is an ascending serial,
		* allowing up to 100 edits per day. After that the serial wraps
		* into the next day and it still works.
		*
		* @param int  $serial Current serial
		* @return int New serial
		*/
		static public function RaiseSerial($serial = 0)
		{
			$serial = (int)$serial;
			
		    if (substr($serial, 0, 8) == date('Ymd')) 
			{
				//Serial's today. Simply raise it.
				$serial = (int)$serial + 1;
			} 
			elseif ($serial > date('Ymd00')) 
			{
				//Serial's after today.
				$serial = (int)$serial + 1;
			} 
			else 
			{
				//Older serial. Generate new one.
				$serial = date('YmdH');
			}
			
			return intval($serial);
		}
		
		
		
		/**
		* Checks if a value is an IP address or not.
		*
		* @param string    Value to check.
		* @return bool     true or false.
		* @access public
		*/
		function IsIP($ip)
		{
			return (bool) preg_match('/^([0-9]{1,3}\.){4}$/', $ip . '.');
		}
		
		
		function IsDomain($domain)
		{
			if ($domain == "*")
				return true;
				
			return (bool) preg_match('/^[^\.]([a-zA-Z0-9\-]{0,}\.)+$/', $domain . ".");
		}
		
		
		
		/**
		* Checks if a value is a local network IP address or not.
		*
		* @param string    Value to check.
		* @return bool     true or false.
		* @access public
		*/
		function IsValidIP($ip)
		{
			$internal =  (bool) preg_match('/^192|10(\.[0-9]{1,3}){3}$/', $ip);
			$bcast =  (bool) preg_match('/^([0-9]{1,3}\.){3}255|0$/', $ip);
			
			return !($internal || $bcast);
		}
		
		
		
		/**
		* Reverses IP address string for PTR needs
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
		* Converts a BIND-style timeout(1D, 2H, 15M) to seconds.
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
				$pattern = '/([0-9]+)([a-zA-Z]+)/';
				$split = preg_split($pattern, $time, -1,
									PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
				if (count($split) != 2) {
					Core::RaiseWarning(sprintf(_("Unable to parse time. %d"), $time));
				}
				
				list($num, $what) = $split;
				
				switch (strtoupper($what))
				{
					case 'S':
						$times = 1; //Seconds
						break;
					case 'M':
						$times = 1 * 60; //Minute
						break;
					case 'H':
						$times = 1 * 60 * 60; //Hour
						break;
					case 'D':
						$times = 1 * 60 * 60 * 24; //Day
						break;
					case 'W':
						$times = 1 * 60 * 60 * 24 * 7; //Week
						break;
					default:
						Core::RaiseWarning(sprintf(_("Unable to parse time. %d"), $time));
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
		
		/**
		* Set current template
		* @access public
		* @param string $template DNS Zone template
		* @return void
		*/ 
		public function SetTemplate($template)
		{
			$this->Template = $template;
		}
		
		/**
		* Raise MX pref on 10
		*
		* @param string $pref Preferences
		* @return string Reversed IP
		* @access protected
		*/
		protected function RaiseMXPref($pref)
		{
			// Increase forcefully in case if this pref already assigned
			// to another MX record or pref is not set (default)
			if (count($this->MXPrefs))
			{
				if (in_array($pref, $this->MXPrefs) || !$pref)
					$retval = max($this->MXPrefs) + 10;
				else
					$retval = $pref;
			}
			else
				$retval = $pref;
				
			// Add this new pref to stack
			$this->MXPrefs[] = $retval;
			return($retval);
		}
	}
?>