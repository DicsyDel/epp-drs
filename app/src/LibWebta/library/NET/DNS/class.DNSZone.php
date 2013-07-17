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
	
	Core::load("NET/DNS/AbstractDNSZone");
	
	/**
     * @name       DNSZone
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @ignore
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class DNSZone extends AbstractDNSZone
	{
		
		/**
		* Zone FQDN
		* @var string
		* @access public
		*/
		public $Name;
		
		/**
		* Zone content
		* @var string
		* @access public
		*/
		public $Content;
		
		/**
		* Template for new zone creation
		* @var string
		* @access public
		*/
		public $Template;
		
		/**
		* SOA array
		* @var array
		* @access public
		*/
		public $SOA = array();
		
		/**
		* MX preferences
		* @var array
		* @access public
		*/
		public $MXPrefs;
		
		
		// Any values here will be replaced 
		// in generated zone text
		public $Tags = array();
		
		/**
		 * Zone records
		 *
		 * @var array
		 */
		public $records;
		
		function __construct($name = null)
		{
			Core::RaiseWarning("Usage of older DNS class schema is deprecated. Use class.DNSZone2.php instead!");
			// Start MX prefs with 10
			$this->MXPrefs = array(0);
			$this->Name = $name;
		}


		/**
		* Sets a specific value in the SOA field.
		*
		* This function updates the list of SOA data we have.
		* List of accepted key => value pairs:
		* <pre>
		* Array
		*   (
		*       [name] => example.com.
		*       [ttl] => 345600
		*       [class] => IN
		*       [origin] => ns1.example.com.
		*       [person] => hostmaster.example.com.
		*       [serial] => 204041514
		*       [refresh] => 14400
		*       [retry] => 1800
		*       [expire] => 86400
		*       [minimum] => 10800
		*   )
		* </pre>
		*
		* @param array  $values A list of key -> value pairs
		* @return bool  true on success, false failure.
		* @see SOA
		*/
		public function SetSOAValue($values)
		{
			Core::ClearWarnings();
			
			$soa = array();
			if (!is_array($values)) 
				Core::RaiseError(_("Unable to set SOA value"));
			
				
			$validKeys = array('name', 'ttl', 'class', 'origin', 'person', 
							'serial', 'refresh', 'retry', 'expire', 'minimum');
							
			foreach ((array)$values as $key => $value) 
			{
				if (array_search($key, $validKeys) === false) 
					Core::RaiseWarning(sprintf(_("Unable to set SOA value. %s not recognized"), $key));

				switch (strtolower($key)) {
				case 'person':
					$value = str_replace('@', '.', $value);
					$value = trim($value, '.') . '.';
				case 'name':
				case 'origin':
					$valid = '/^[A-Za-z0-9\-\_\.]*\.$/';
					
					$value = $this->Dottify($value);
					if (preg_match($valid, $value)) 
						$soa[$key] = $value;
					else 
						Core::RaiseWarning(sprintf(_("Unable to set SOA value. %s not valid"), $key));

					break;
				case 'class':
						$soa[$key] = $value;
						break;
				case 'ttl':
					$soa[$key] = $value;
					break;
				case 'serial':
					$value = $this->RaiseSerial($value);
					$soa[$key] = $value;
				case 'refresh':
				case 'retry':
				case 'expire':
				case 'minimum':
					// Normalize time
					$value = $this->ParseTimeToSeconds($value);
					
					if (is_numeric($value)) 
						$soa[$key] = $value;
					else 
						Core::RaiseWarning(sprintf(_("Unable to set SOA value. %s not recognized"), $key));
					break;
				}
				
				// Set zone name
				if (strtolower($key) == "name")
					$this->Name = $this->UnDottify($value);
					

			}
			
			// Default serial
			if (!$soa["serial"])
				$soa["serial"] = $this->RaiseSerial();
			
			//If all got parsed, save values.
			$this->SOA = array_merge($this->SOA, $soa);
			
			return !self::HasWarnings();
			
		}
		
		
		public function AddRecord($type, $record, $ttl=14440)
		{		
			Core::ClearWarnings();
				
			$ttl = $this->ParseTimeToSeconds($ttl);
			if ($ttl && $ttl != $this->SOA["ttl"])
				$record["ttl"] = $ttl;
				
			// Add
			$z = $record; // Moron
			if (!$z["ttl"])
				$z["ttl"] = $ttl;
				
			/// NS
			if ($type == "NS")
			{
				if (!$this->IsDomain($z[1]))
					Core::RaiseWarning(sprintf(_("Unable to add NS record. %s is not a valid subdomain."), $z[1]));
				else
				{
					if (empty($z[0]))
						$z[0] = $this->SOA["name"];
					$z[1] = $this->Dottify($z[1]);
					$this->tpl .= "{$z[0]}	{$z['ttl']}	IN NS	{$z[1]}\n";

					$this->records["$type"][] = array(
					"left" => $z[0],
					"right" => $z[1],
					"ttl" => $z['ttl'],
					);
				}
			}
			/// A
			elseif ($type == "A")
			{
				if (empty($z[0]))
					$z[0] = $this->Dottify($this->SOA["name"]);
				else
				{
					if (!$this->IsDomain($z[0]))
						Core::RaiseWarning(sprintf(_("Unable to add A record. %s is not valid subdomain."), $z[0]));
				}
					
				
				if (!$this->IsIP($z[1]))
					Core::RaiseWarning(sprintf(_("Unable to add A record. %s is not an IP address."), $z[1]));
				elseif (!$this->IsValidIP($z[1]) && !ALLOWINTIPS)
					Core::RaiseWarning(sprintf(_("Unable to add A record. %s is not valid IP address."), $z[1]));
				else
				{
					$this->tpl .= "{$z[0]}	{$z['ttl']}	IN A	{$z[1]}\n";
					
					$this->records["$type"][] = array(
					"left" => $z[0],
					"right" => $z[1],
					"ttl" => $z['ttl'],
					);
				}
			}
			/// PTR
			elseif ($type == "PTR")
			{
				
				$z[0] = (int)$z[0];
				if (empty($z[0]))
					Core::RaiseWarning(_("Invalid IP address"));
				else
				{
					if (!is_integer($z[0]) && $z[0] < 255 && $z[0] > 0)
						Core::RaiseWarning(sprintf(_("Unable to add PTR record. %s is not an integer."), $z[0]));
				}
					
				
				if (!$this->IsDomain($z[1]))
					Core::RaiseWarning(sprintf(_("Unable to add PTR record. %s is not an valid domain."), $z[1]));
				else
				{
					$z[1] = $this->Dottify($z[1]);
					
					$this->tpl .= "{$z[0]}	{$z['ttl']}	IN PTR	{$z[1]}\n";
					
					$this->records["$type"][] = array(
					"left" => $z[0],
					"right" => $z[1],
					"ttl" => $z['ttl'],
					);
				}
			}
			/// MX
			elseif ($type == "MX")
			{
				if ($this->isIP($z[1]))
					Core::RaiseWarning(sprintf(_("Unable to add MX record. %s cannot be IP address."), $z[1]));	
				else
				{
					$d = $z[0] ? $z[0] : $this->SOA["name"]; 
					$pref = $this->RaiseMXPref($z[2]);
					$z[1] = $this->Dottify($z[1]);
					$this->tpl .= "$d	{$z['ttl']}	IN MX	$pref	{$z[1]}\n";
					
					$this->records["$type"][] = array(
					"left" => $d,
					"right" => $z[1],
					"ttl" => $z['ttl'],
					"pref" => $pref,
					);
				}
			}
			/// CNAME
			elseif ($type == "CNAME")
			{
				if (!empty($z[1]))
					$d = $this->Dottify($z[1]);
				else
					$d = $this->SOA["name"];

				$z[0] = $this->undottify($z[0]);
			
				if (!$this->IsDomain($z[0]) || !$this->IsDomain($z[1]))
					Core::RaiseWarning(sprintf(_("Unable to add CNAME record. %s or %s are not valid domains."), $z[1], $z[0]));
				elseif (empty($z[0]))
					Core::RaiseWarning(sprintf(_("Unable to add CNAME record. %s is not valid subdomain name."), $z[1]));
				else
				{
					$this->tpl .= "{$z[0]}	{$z['ttl']}	IN CNAME	$d\n";
					$this->records["$type"][] = array(
					"left" => $z[0],
					"right" => $d,
					"ttl" => $z['ttl']
					);
				}
			}

			return !self::HasWarnings();
		}
		
		
		public function Generate($ptr = false)
		{
			//
			// Parse records
			//
			
			// Default Template
			if (!$this->Template)
				$this->Template = CF_DNSZONETPL;
			
			
			//
			// Parse SOA
			//
			foreach ((array)$this->SOA as $k=>$v)
				$tags["{".$k."}"] = $v;
			
			// FIXME: Tags are deprecated
			if (count($this->Tags))
				foreach ((array)$this->Tags as $k=>$v)
					$tags["{".$k."}"] = $v;
				
			//
			// Defaults soa values
			//
			$tags["{ttl}"] = $this->SOA["ttl"] ? $this->SOA["ttl"] : CF_DNS_TTL;
			$tags["{refresh}"] = $this->SOA["refresh"] ? $this->SOA["refresh"] : CF_DNS_REFRESH;
			$tags["{retry}"] = $this->SOA["retry"] ? $this->SOA["retry"] : CF_DNS_RETRY;
			$tags["{expire}"] = $this->SOA["expire"] ? $this->SOA["expire"] : CF_DNS_EXPIRE;
			$tags["{minimum}"] = $this->SOA["minimum"] ? $this->SOA["minimum"] : CF_DNS_MINIMUM;
			

			$this->Content = str_replace(
				array_keys($tags),
				array_values($tags),
				$this->Template
			);

			//
			// Generate records
			//
			$this->Content = str_replace("{records}", $this->tpl, $this->Content);
			
			return($this->Content);
		}
		
	}

?>