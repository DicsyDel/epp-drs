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
     * @package    NET_API
     * @subpackage SRSPlus
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
     * @name       SRSPlus
     * @category   LibWebta
     * @package    NET_API
     * @subpackage SRSPlus
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class SRSPlus extends Core
	{
	    /**
	     * Registrars E-mail address
	     *
	     * @var string
	     * @access private
	     */
		private $RegistrarEmail;
		
		/**
		 * Registrar ID
		 *
		 * @var string
		 * @access private
		 */
		private $RegistrarID;
		
		/**
		 * Enable Test mode
		 *
		 * @var bool
		 * @access private
		 */
		private $TestMode;
		
		/**
		 * GPG Password
		 *
		 * @var string
		 * @access private
		 */
		private $GPGPass;
		
		/**
		 * SRSPlus protocol version
		 *
		 * @var string
		 * @access private
		 */
		private $ProtocolVersion;
		
		/**
		 * Enable 7bit chars safe mode
		 *
		 * @var bool
		 * @access private
		 */
		private $Fields7BitSafe;
		
		/**
		 * SRSPlus test key
		 *
		 * @var string
		 * @access private
		 */
		private $SRSTestKey;
		
		/**
		 * SRSPlus key
		 *
		 * @var string
		 * @access private
		 */
		private $SRSKey;
		
		/**
		 * Last Error
		 *
		 * @var string
		 */
		public $Error;
		
		/**
		 * Data
		 *
		 * @var string
		 */
		public $Data;
		
		/**
		 * SRSPlus constructor
		 *
		 * @param array $args
		 * <pre>
		 * $args = array(
		 *                "email" => "", 
		 *                "id" => "", 
		 *                "testmode" => "", 
		 *                "gpgpass" => ""
		 *               )
		 * </pre>
		 */
		function __construct($args)
		{
			$this->RegistrarEmail = $args["email"];
			$this->RegistrarID = $args["id"];
			$this->TestMode = $args["testmode"];
			$this->GPGPass = $args["gpgpass"];
			$this->ProtocolVersion = '1.1';
			$this->Fields7BitSafe = "1";
			$this->Host = $args["host"];
			$this->GPG = new GnuPG($args["gpg_path"], $args["gpg_home_dir"]);
		}
		
		
		/**
		 * Query rejected transfer
		 *
		 * @param stirng $domain
		 * @param string $TLD
		 * @param string $transferid
		 * @return bool
		 */
		public function QueryRejectedTransfer($domain, $TLD, $transferid)
		{
		
			
			$request = $this->CreateMessage("QUERY REJECTED TRANSFER",
											array(
												"TRANSFERID" =>$transferid
												)
											);
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Query Transfer ID
		 *
		 * @param string $domain
		 * @param string $TLD
		 * @param string $transferid
		 * @return bool
		 */
		public function QueryTransferId($domain, $TLD, $transferid)
		{
		
			
			$request = $this->CreateMessage("QUERY TRANSFERID",
											array(
												"TRANSFERID" =>$transferid
												)
											);
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Outbound Transfer Response
		 *
		 * @param string $domain
		 * @param string $TLD
		 * @param string $response
		 * @return bool
		 */
		public function OutboundTransferResponse($domain, $TLD, $response)
		{
			$request = $this->CreateMessage("OUTBOUND TRANSFER RESPONSE", 
											array(
													"DOMAIN" => $domain,
													"TLD" => $TLD,
													"TRANSFER RESPONSE" => $response
												 )
											);
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Transfer request
		 *
		 * @param string $domain
		 * @param string $TLD
		 * @param array $extra
		 * @return bool
		 */
		public function TransferRequest($domain, $TLD, $extra)
		{
			$request = $this->CreateMessage("REQUEST TRANSFER", 
											array(
													"DOMAIN" => $domain,
													"TLD" => $TLD,
													"RESPONSIBLE PERSON" => $extra["registrant"],
													"TECHNICAL CONTACT" => $extra["tech"],
													"BILLING CONTACT" => $extra["billing"],
													"ADMIN CONTACT" => $extra["admin"],
													"CURRENT ADMIN EMAIL" => $extra["temail"],
													"AUTH_CODE" => $extra["pw"]
												 )
											);
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Delete domain name from server
		 *
		 * @param string $domain
		 * @param string $TLD
		 * @return array
		 */
		public function DeleteDomain($domain, $TLD)
		{
			$request = $this->CreateMessage("RELEASE DOMAIN", 
											array(
													"DOMAIN" => $domain,
													"TLD" => $TLD
												 )
											);
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		* 	Update nameserver
		* @param string $host
		* @param string $ip
		* @retrun array
		*/
		public function UpdateNameserver($host, $ip)
		{
			$request = $this->CreateMessage("MODIFY NAMESERVER", 
											array(
													"DNS SERVER NAME" => $host,
													"DNS SERVER IP"	  => $ip
												 )
											);
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Delete nameserver from server
		 *
		 * @param string $host
		 * @return array
		 */
		public function DeleteNameserver($host)
		{
			$request = $this->CreateMessage("RELEASE NAMESERVER", 
											array(
													"DNS SERVER NAME" => $host
												 )
											);
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Update domain name
		 *
		 * @param string $domain
		 * @param string $TLD
		 * @param array $newdata
		 * @return array
		 */
		public function DomainUpdate($domain, $TLD, $newdata)
		{
			//
			$newdata["DOMAIN"] = $domain;
			$newdata["TLD"] = $TLD;
		
			$request = $this->CreateMessage("ALTER DOMAIN", 
											$newdata
											);
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * return nameserver information
		 *
		 * @param string $host
		 * @return array
		 */
		public function GetNameserverInfo($host)
		{
			$request = $this->CreateMessage("GET NAMESERVER INFO", 
											array(
													"DNS SERVER NAME" => "{$host}"
												 )
											);
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Register new nameserver
		 *
		 * @param string $host
		 * @param string $ip
		 * @return array
		 */
		public function RegisterNameserver($host, $ip)
		{			
			$request = $this->CreateMessage("REGISTER NAMESERVER", 
											array(
													"DNS SERVER NAME" => "{$host}",
													"DNS SERVER IP" => $ip
												 )
											);
											
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			$retval = $this->Request($enc_request);
			
			if ($retval["REQUESTID"])
				return $retval;
			else
			{
				Log::Log("Cannot create nameserver host ({$host})!", E_USER_ERROR);
				return false;
			}
		}
		
		/**
		 * Renew domain name
		 *
		 * @param string $domain
		 * @param string $TLD
		 * @param integer $period
		 * @return array
		 */
		public function RenewDomain($domain, $TLD, $period)
		{
			// Create request
			
			$request = $this->CreateMessage("RENEW DOMAIN", 
											array(
													"DOMAIN" => $domain,
													"TLD" => $TLD,
													"TERM YEARS" => $period
												 )
											);
		
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			$retval = $this->Request($enc_request);
			
			if ($retval["REQUESTID"])
				return $retval;
			else
			{
				Log::Log("Cannot create domain name ({$domain}.{$TLD})!", E_USER_ERROR);
				return false;
			}
		
		}
		
		/**
		 * Create new domain
		 *
		 * @param string $domain
		 * @param string $TLD
		 * @param integer $period
		 * @param array $contacts
		 * @param array $ns
		 * @return array
		 */
		public function CreateDomain($domain, $TLD, $period, $contacts, $ns = array(), $extra = array())
		{
			$info = $this->DomainInfo($domain, $TLD);
			if (!$info)
			{
				Log::Log("Cannot get domain information ({$domain}.{$TLD}) for register.", E_USER_ERROR);
				return false;
			}
			
			if($info["DOMAIN STATUS"] == 'FIXED')
				$price = $info["PRICE"];
			else
			{
				Log::Log("Domain name ({$domain}.{$TLD}) is not avaiable.", E_USER_NOTICE);
				return false;
			}
		  
			if ($ns[0])
                $contacts["DNS SERVER NAME 1"] = $ns[0];
			 
			if ($ns[1])
                $contacts["DNS SERVER NAME 2"] = $ns[1];
                
			$request = $this->CreateMessage("REGISTER DOMAIN", 
											array_merge(array(   
													'DOMAIN' 			=> $domain,
													'TLD'   			=> $TLD,
													'TERM YEARS' 		=> $period,
													'PRICE'             => $price
												), $contacts, $extra)
											);
		
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			$retval = $this->Request($enc_request);
			
			if ($retval["REQUESTID"])
				return $retval;
			else
			{
				Log::Log("Cannot create domain name ({$domain}.{$TLD})!", E_USER_ERROR);
				return false;
			}
		}
		
		/**
		 * Edit contact
		 *
		 * @param integer $id
		 * @param array $newdata
		 * @return array
		 */
		public function EditContact($id, $newdata)
		{
			// Create request
			$newdata["CONTACTID"] = $id;
			
			$request = $this->CreateMessage("EDIT CONTACT", 
											$newdata
											);
		
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Get Conatct Information
		 *
		 * @param integer $id
		 * @return array
		 */
		public function GetContactInfo($id)
		{
			// Create request
			$request = $this->CreateMessage("GET CONTACT INFO", 
											array(
												"CONTACTID" => $id
												)
											);
		
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Create new contact
		 *
		 * @param string $data
		 * @return array
		 */
		public function CreateContact($data)
		{
			// Create request
			$request = $this->CreateMessage("CREATE CONTACT", 
											$data
											);
													
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Domain Whois
		 *
		 * @param string $domain
		 * @param string $TLD
		 * @return array
		 */
		public function Whois($domain, $TLD)
		{
			// Create request
			$request = $this->CreateMessage("WHOIS", 
											array(
													"DOMAIN"=>$domain, 
													"TLD" => $TLD
												)
											);
		
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Get Domain info
		 *
		 * @param string $domain
		 * @param string $TLD
		 * @param string $encoding
		 * @return array
		 */
		public function DomainInfo($domain, $TLD, $encoding = "UTF-8")
		{
			// Create request
			$request = $this->CreateMessage("GET DOMAIN INFO", 
											array(
													"DOMAIN"=>$domain, 
													"TLD" => $TLD, 
													"ENCODING" => $encoding
												)
											);
		
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		Get Account ballance
		* @access public
		* @return array
		*/
		public function GetBallance()
		{
			// Create request
			$request = $this->CreateMessage("QUERY ACCOUNT BALANCE", array("TLD" => "tv"));
		
			// Add GPG Sign to request
			$enc_request = $this->SignMessage($request);
			
			return $this->Request($enc_request);
		}
		
		/**
		 * Create message for request
		 *
		 * @param string $action
		 * @param array $args
		 * @return string
		 */
		private function CreateMessage($action, $args)
		{
			$headers = array(	
					"REGISTRAR" 		=> $this->RegistrarID,
					"REGISTRAR EMAIL" 	=> $this->RegistrarEmail,
					"PROTOCOL VERSION" 	=> $this->ProtocolVersion,
					"TIME"				=> time(),
					"ACTION"			=> $action,
					"TRANSACTION ID"	=> time(),
					"PLATFORM"			=> "EPP-DRS SRSPlus module",
					"SAFE CONTENTS"		=> $this->Fields7BitSafe
				  );
			$message = "";	  
			
			foreach ($headers as $k=>$v)
				$message .= $k.": ".$v."\n";
			
			$message .= "-----END HEADER-----\n";
			
			$keys = array_keys($args);
			foreach ((array)$keys as $key)
			{
				if($this->Fields7BitSafe)
					$message .= sprintf("%s: %s", $key, $args[$key])."\n";
				else 
				{
					$val = unpack("H*", $args[$key]);
					$message .= sprintf("%s: %s", $key, $val[1])."\n";
				}
			}
			
		   Log::Log("Request: ".$message, E_USER_NOTICE);
		   
		   return $message;
		}
		
		/**
		 * Add GPG sign to message
		 *
		 * @param string $message
		 * @return string
		 */
		private function SignMessage($message)
		{
			$mess = $this->GPG->MakeSign($this->RegistrarEmail, $this->GPGPass, $this->RegistrarEmail, $message);			
			
			$encoded_mes = unpack("H*", $mess);
			return $encoded_mes[1];
		}
		
		/**
		 * Send request to server
		 *
		 * @param string $message
		 * @return bool (or array if true)
		 */
		private function Request($message)
		{
			try
			{
				$ch = @curl_init();
				@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
				@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
				@curl_setopt($ch, CURLOPT_URL, "https://$this->Host/cgi-bin/registry.cgi");
				@curl_setopt($ch, CURLOPT_HEADER, 0);
				@curl_setopt($ch, CURLOPT_POST, 1);
				@curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				@curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				@curl_setopt($ch, CURLOPT_POSTFIELDS, "PAYLOAD=$message");
				
				$retval = @curl_exec($ch);
				
				$e = curl_error($ch);
				curl_close($ch);
	
				if ($e)
				{
					Log::Log($e, E_USER_ERROR);
					return false;
				}
				else
				{
				    return $this->ParseResponse($retval);
				}
			}
			catch(Exception $e)
			{
				$this->RaiseWarning("Cannot send request to server");
				return false;
			}
		}
		
		/**
		 * Verify signature in server response
		 *
		 * @param string $message
		 * @return bool
		 */
		private function VerifySRS($message)
		{
			return $this->GPG->VerifySign($message);
		}
		
		/**
		 * Parse server response
		 *
		 * @param string $message
		 * @return array
		 */
		private function ParseResponse($message)
		{
			Log::Log("Response: ".$message, E_USER_NOTICE);
			// check sign
			if ($this->VerifySRS($message))
			{
				// if sign ok, parse message
				$mess = explode("\n", $message);
				$headers = array();
				$body = array();
				$isbody = false;
				foreach ($mess as $string)
				{
					if (trim($string) != '')
					{
						if (stristr($string, "END HEADER"))
							$isbody = true;
							
						if (stristr($string, "BEGIN PGP SIGNATURE"))
							break;
							
						if ($string[0] == "-")
							continue;
							
						$pstring = explode(":", trim($string));	
						$key = trim($pstring[0]);
						$value = trim($pstring[1]);
						if ($key != '')
						{
							if ($isbody)
							{
								if ($headers["SAFE CONTENTS"] != '1')
								{
									$temp = pack("H*", $value);
									$body[$key] = $temp[1];
								}
								else
									$body[$key] = $value;
							}
							else
								$headers[$key] = $value;
						}
					}
				}
								
				// check message
				if ($headers["STATUS"] == "" || $headers["PROTOCOL VERSION"] == "")
					Log::Log("Did not get properly formatted request from server", E_USER_ERROR);
				else
				{
					// if status succes return result
					if ($headers["STATUS"] == "SUCCESS")
						return $body;
					else
					{
						foreach ($body as $k=>$v)
						{
    					    if(stristr($k, "ERROR"))
    					    {
    						    // else show error from server
    						    $have_errors = true;
        						$this->RaiseWarning($v);
        						return false;
    					    }
						}
						
						if (!$have_errors)
							$this->RaiseWarning("No details returned by registry.");
					}
				}
			}
			else
			{	
				// sign not property
				Log::Log("Response verification failed", E_USER_ERROR);
				return false;
			}
		}
	}

?>