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
     * @name       SPFDNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class SPFDNSRecord extends TXTDNSRecord
	{
		
		public $Version;
		public $BasicMechanisms;
		public $SenderMechanisms;
		public $Modifiers;
		private $Error;
		
		const DEFAULT_TEMPLATE = "v={version} {sendermechanisms} {basicmechanisms} {mod}";
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param arrsy $smechanisms
		 * @param arrsy $bmechanisms
		 * @param array $mods
		 * @param string $version
		 */
		function __construct($name, $smechanisms, $bmechanisms = false, $mods = false, $version = "spf1", $ttl = false)
		{
		    parent::__construct($name, "", $ttl);
		    
			$this->Version = $version;
			
			// Validate Sender mechanism
			if ($smechanisms)
			foreach ((array)$smechanisms as $mechanism)
			{
			   if (preg_match("/^[-+?~]{0,1}$/si", $mechanism[0]))
			   {
                    preg_match("/^(a|mx|ptr|ip4|ip6|exists)([:\/]*)/si", $mechanism[1], $matches);
                    $current_mechanism = $matches[1];
                    
                    switch($current_mechanism)
                    {
                        /**
                         * A verification mechanism:
                         * In its base form this uses the sender-domain to find an A RR(s) to verify the source. 
                         * This form relies on an A RR for the domain e.g.
                         * Syntax:
                                    a
                                    a:domain
                                    a:domain/cidr
                                    a/cidr

                         * MX verification mechanism:
                         * This basic form without any extensions uses the MX RR of the sender-domain to verify the 
                         * mail source-ip. The MX record(s) return a host name from which the A record(s) 
                         * can be obtained and compared with the source-ip. 
                         * The form mx/cidr applies the IP Prefix or slash range to the A RR address. 
                         * With any of the domain extensions the MX record of the designated (substituted) 
                         * domain is used for verification. The domain form may use macro-expansion features.
                         * Syntax:
                                    mx
                                    mx:domain
                                    mx:domain/cidr
                                    mx/cidr

                         */
                        case "mx":
                        case "a":
                                if ($mechanism[1] == $current_mechanism)
                                    $this->SenderMechanisms[] = $mechanism;
                                else 
                                {
                                    $chunks = explode(":", $mechanism[1]);
                                    if ($chunks[1])
                                    {
                                        if ($this->Validator->IsDomain($chunks[1]) || 
                                            $this->ValidateMacrosString($current_mechanism, $chunks[1]) ||
                                            $this->Validator->MatchesPattern($chunks[1], "/^([A-Za-z0-9]+[A-Za-z0-9-]*[A-Za-z0-9\.]{2,}){2,}\/[0-9]{1,3}$/si")
                                           )
                                            $this->SenderMechanisms[] = $mechanism;
                                        else 
                                        {
                                            self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                            $this->Error = true;
                                        }
                                    }
                                    else 
                                    {
                                        if ($this->Validator->MatchesPattern($mechanism[1], "/^{$current_mechanism}\/[0-9]{1,3}$/si"))
                                            $this->SenderMechanisms[] = $mechanism;
                                        else 
                                        {
                                            self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                            $this->Error = true;
                                        }
                                    }
                                }
                            break;
                        
                        /**
                         * In its basic form defines an explicit ipv4 address to verify the mail source-ip. 
                         * If the source-ip is the same as ipv4 the test passes. 
                         * May optionally take the form ipv4/cidr to define a valid IP address range. 
                         * Since this type incurs the least additional load on the DNS the current draft of 
                         * the proposed RFC recommends this format.
                         * Syntax:
                                    ip4:ipv4
                                    ip4:ipv4/cidr
                         */
                        case "ptr":
                                if ($mechanism[1] == $current_mechanism)
                                    $this->SenderMechanisms[] = $mechanism;
                                else 
                                {
                                    $chunks = explode(":", $mechanism[1]);
                                    if ($chunks[1])
                                    {
                                        if ($this->Validator->IsDomain($chunks[1]) ||
                                            $this->ValidateMacrosString($current_mechanism, $chunks[1])
                                           )
                                            $this->SenderMechanisms[] = $mechanism;
                                        else 
                                        {
                                            self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                            $this->Error = true;
                                        }
                                    }
                                    else 
                                    {
                                        self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                        $this->Error = true;
                                    }
                                }
                            break;
                        
                        /**
                         * In its basic form defines an explicit ipv4 address to verify the mail source-ip. 
                         * If the source-ip is the same as ipv4 the test passes. 
                         * May optionally take the form ipv4/cidr to define a valid IP address range. 
                         * Since this type incurs the least additional load on the DNS the current draft of the 
                         * proposed RFC recommends this format.
                         * Syntax:
                                    ip4:ipv4
                                    ip4:ipv4/cidr
                         */
                        case "ip4":
                                $chunks = explode(":", $mechanism[1]);
                                if ($chunks[1])
                                {
                                    if ($this->Validator->IsIPAddress($chunks[1]) || 
                                        $this->Validator->MatchesPattern($chunks[1], self::PAT_CIDR)
                                       )
                                        $this->SenderMechanisms[] = $mechanism;
                                    else 
                                    {
                                        self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                        $this->Error = true;
                                    }
                                }
                                else 
                                {
                                    self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                    $this->Error = true;
                                }
                            break;
                        
                        /**
                         * In its basic form defines an explicit ipv6 address to verify the mail source-ip. 
                         * If the source-ip is the same as ipv6 the test passes. 
                         * May optionally take the form ipv6/cidr to define a valid IP address range. 
                         * Since this type incurs the least additional load on the DNS the current draft 
                         * of the proposed RFC recommends this format
                         */
                        case "ip6":
                                //TODO: Add IPv6 support;
                            break;
                        
                        /**
                         * The existence (any valid A RR) of the specified domain allows the test to pass. 
                         * Domain may use macro-expansion features.
                         * Syntax:
                                    exists:domain
                         */
                        case "exists":
                                $chunks = explode(":", $mechanism[1]);
                                if ($chunks[1])
                                {
                                    if ($this->Validator->IsDomain($chunks[1]) ||
                                        $this->ValidateMacrosString($current_mechanism, $chunks[1])
                                        )
                                        $this->SenderMechanisms[] = $mechanism;
                                    else 
                                    {
                                        self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                        $this->Error = true;   
                                    }
                                }
                                else 
                                {
                                    self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                    $this->Error = true;
                                }
                            break;
                        
                        default:
                                self::RaiseWarning("Invalid sender verification mechanism '{$mechanism[1]}' in SPF Record");
                                $this->Error = true;
                            break;
                    }
			   }
			   else
			   { 
                    self::RaiseWarning("Invalid qualifier for sender verification mechanism '{$mechanism[1]}' in SPF Record");
                    $this->Error = true;
			   }
			}
			
			// Validate Basic verification mechanismes
			if ($bmechanisms)
			foreach ((array)$bmechanisms as $mechanism)
			{
			   if (preg_match("/^[-+?~]{0,1}$/si", $mechanism[0]))
			   {
			        preg_match("/^(include|all)([:]*)/si", $mechanism[1], $matches);
                    $current_mechanism = $matches[1];
                    $addAll = false;
                    switch($current_mechanism)
                    {
                        /**
                         * all - The all type terminates processing (but may be optionally followed by a 
                         * mod value). It is defined to be optional but it is a Good Thing� to include it. 
                         * It is normally present in the form -all to signify that if processing reaches 
                         * this point without a prior match the result will be fail. But if you are not 
                         * sure that the tests are conclusive you could use ?all which would allow mail 
                         * to be accepted even if all previous checks failed.
                         */
                        case "all":
                                if ($mechanism[1] == $current_mechanism)
                                    $addAll = $mechanism;
                                else 
                                {
                                    self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                    $this->Error = true;
                                }
                            break; 
                        
                        /**
                         * include - Recurse (restart) testing using supplied domain. 
                         * The sender-domain is replaced with the included domain name. 
                         * Syntax:
                                    include:domain
                         */
                        case "include":
                                $chunks = explode(":", $mechanism[1]);
                                if ($chunks[1])
                                {
                                    if ($this->Validator->IsDomain($chunks[1]) ||
                                        $this->ValidateMacrosString($current_mechanism, $chunks[1])
                                       )
                                        $this->BasicMechanisms[] = $mechanism;
                                    else 
                                    {
                                        self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                        $this->Error = true;   
                                    }
                                }
                                else 
                                {
                                    self::RaiseWarning("SPF Record syntax error in '{$mechanism[1]}' for mechanism '{$current_mechanism}'");
                                    $this->Error = true;
                                }
                            break;
                            
                        default:
                                self::RaiseWarning("Invalid basic verification mechanism '{$mechanism[1]}' in SPF Record");
                                $this->Error = true;
                            break;
                    }   
			   }
			   else 
			   {
			       self::RaiseWarning("Invalid qualifier for basic verification mechanism '{$mechanism[1]}' in SPF Record");
                   $this->Error = true;
			   }
			}
			
			if ($addAll)
                $this->BasicMechanisms[] = $addAll;
			
			// Validate modifiers
			if ($mods)
			foreach ($mods as $mod)
			{
			    switch($mod[0])
			    {
			        case "redirect":
			             if ($this->Validator->IsDomain($mod[1]) ||
			                 $this->ValidateMacrosString($mod[0], $mod[1])
			                )
			                 $this->Modifiers[] = $mod;
			             else
			             {
			                 self::RaiseWarning("Invalid syntax for '{$mod[0]}' modifier in SPF Record");
                             $this->Error = true;
			             }
			            break;
			            
			        case "exp":
			             if ($this->Validator->IsDomain($mod[1]) ||
			                 $this->ValidateMacrosString($mod[0], $mod[1])
			                )
			                 $this->Modifiers[] = $mod;
			             else
			             {
			                 self::RaiseWarning("Invalid syntax for '{$mod[0]}' modifier in SPF Record");
                             $this->Error = true;
			             }
			            break;
			    }
			}
			
			$this->__toString();
		}
		
		/**
		 * Validate Macros string
		 *
		 * @param string $mechanism
		 * @param string $string
		 * @return bool
		 */
		private function ValidateMacrosString($mechanism, $string)
		{
		    // Check all macro-expands
		    preg_match_all("/%\{(.*?)\}/", $string, $matches);
		    foreach($matches[1] as $macros)
		    {
		        if (!preg_match("/^(s|l|o|d|i|p|h|c|r|t|v)([0-9]*(r)?)?(\.|-|\+|,|\/|_|=)?$/", $macros))
		        {
		           self::RaiseWarning("Invalid syntax for '%{{$macros}}' macros in SPF Record");
		           return false;
		        }
		        else 
		          $string = str_replace("%{{$macros}}", "", $string);
		    }
		    
		    if (stristr($string, "%"))
		    {
		       self::RaiseWarning("SPF Record error: Invalid syntax for '{$string}'. Invalid macros.");
		       return false; 
		    }
		    
		    return true;
		}
		
		/**
		 * Magic function __toString
		 *
		 * @return string
		 */
		public function __toString()
		{
			if (!$this->Error)
			{
			    // Add version
				$spf = str_replace("{version}", $this->Version, self::DEFAULT_TEMPLATE);
				
				// Add sender verification mechanisms
				$sendermechanisms = "";
				foreach((array)$this->SenderMechanisms as $m)
				{
				    $sendermechanisms .="{$m[0]}{$m[1]} ";
				}
				$spf = str_replace("{sendermechanisms}", trim($sendermechanisms), $spf);
				
				// Add basic verification mechanisms
				$basicmechanisms = "";
				foreach((array)$this->BasicMechanisms as $m)
				{
				    $basicmechanisms .="{$m[0]}{$m[1]} ";
				}
				$spf = str_replace("{basicmechanisms}", trim($basicmechanisms), $spf);
				
				// Add Modifiers
				$mods = "";
				foreach((array)$this->Modifiers as $m)
				{
				    $mods .="{$m[0]}={$m[1]} ";
				}
				$spf = str_replace("{mod}", trim($mods), $spf);
				
				$this->Value = trim($spf);
				
			    return parent::__toString();
			}
			else 
				return "";
		}
	}
	
?>
