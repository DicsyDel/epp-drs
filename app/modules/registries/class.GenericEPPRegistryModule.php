<?php

	/**
	 * Generic EPP Registry Module. 
	 * You can extend this class instead of AbstractrgistryModule to produce basic fully-functional RFC-compliant EPP registry module.
	 * @name GenericEPPRegistryModule
	 * @category   EPP-DRS
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @sdk-doconly
	 * @author Marat Komarov <http://webta.net/company.html> 
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	
		
	abstract class GenericEPPRegistryModule extends AbstractRegistryModule implements IRegistryModule
	{
		protected $XmlNamespaces = array
		(
			'domain' 	=> 'urn:ietf:params:xml:ns:domain-1.0',
			'contact' 	=> 'urn:ietf:params:xml:ns:contact-1.0',
			'host' 		=> 'urn:ietf:params:xml:ns:host-1.0' 
		);
		
		/**
		 * This method is called to validate either user filled all fields of your configuration form properly.
		 * If you return true, all configuration data will be stored in database. If you return array, user will be presented with values of this array as errors. 
		 *
		 * @param array $post_values
		 * @return true or array of error messages.
		 */
		public static function ValidateConfigurationFormData($post_values)
		{
			return true;
		}

		public static function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
			$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Passsword", 1));
			$ConfigurationForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$ConfigurationForm->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
			
			return $ConfigurationForm;
		}
		
		/**
		 * This method must return current Registry CLID
		 *
		 * @return string
		 */
		public function GetRegistrarID()
		{
			return $this->Config->GetFieldByName("Login")->Value;
		}
		
		/* Check transfer availability for $domain_name Domain
	     *
	     * @param Domain $domain
	     * @return DomainCanBeTransferredResponse
	     * @version v1000
	     */
	    public function DomainCanBeTransferred(Domain $domain)
	    {
	    	$resp = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	
	    	$CanRegisteredResponse = $this->DomainCanBeRegistered($domain);
	    	if ($CanRegisteredResponse->Result == false)
	    	{ 	
		    	try
		    	{
		    		$GetRemoteDomainResponse = $this->GetRemoteDomain($domain);
		    		if ($GetRemoteDomainResponse->Code == RFC3730_RESULT_CODE::ERR_AUTHORIZE_ERROR)
		    		{
						$resp->Result = true;
		    		}
					elseif ($GetRemoteDomainResponse->Succeed())
						$resp->Result = false;
		    	}
		    	catch(ObjectNotExistsException $e)
		    	{
		    		$resp->Result = true;
		    	}
	    	}
	    	else
	    		$resp->Result = false;
	    	
	    	return $resp;
	    }
		
	     /**
	     * Update domain auth code.
	     *
	     * @param Domain $domain
	     * @param string $authcode A list of changes in domain flags for the domain
	     * @return UpdateDomainAuthCodeResponse
	     */
	    public function UpdateDomainAuthCode(Domain $domain, $authCode)
	    {
	    	$params = array(
				"name" 		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"change"	=> "",
				"add"		=> "",
				"rem"		=> "",
	    		"remove"	=> ""
			);
			
			$params["change"] = "<domain:chg><domain:authInfo><domain:pw>{$this->EscapeXML($authCode)}</domain:pw></domain:authInfo></domain:chg>";
			
			$this->BeforeRequest('domain-update', $params, __METHOD__, $domain, $authCode);
			$response = $this->Request("domain-update", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			return new UpdateDomainAuthCodeResponse($status, $response->ErrMsg, $response->Code);
	    }
	    
		/**
	     * Send Domain Trade request (Change owner)
	     *
	     * @param Domain $domain Domain must have contacts and nameservers 
	     * @param integer $period
	     * @param array $extra
	     * @return bool
	     * @version v1000
	     */
	    public function ChangeDomainOwner(Domain $domain, $period=null, $extra=array())
	    {    	
	    	throw new NotImplementedException();
	    }
	    
	    /**
	     * Lock Domain
	     *
	     * @param Domain $domain
	     * @param array $extra
	     * @return LockDomainResponse
	     */
	    public function LockDomain(Domain $domain, $extra = array())
	    {
	    	$changes = $domain->GetFlagChangelist();
	    	$changes->Add('clientTransferProhibited');
	    	
	    	$Udf = $this->UpdateDomainFlags($domain, $changes);
	    	$domain->SetFlagList($changes->GetList());
	    	
	    	$Ret = new LockDomainResponse($Udf->Status, $Udf->ErrMsg, $Udf->Code);
	    	$Ret->Result = $Udf->Succeed();
	    	return $Ret;
	    }

	    /**
	     * Unlock Domain
	     *
	     * @param Domain $domain
	     * @param array $extra Some extra data
	     * @return UnLockDomainResponse
	     */
	    public function UnlockDomain (Domain $domain, $extra = array())
	    {
	    	$changes = $domain->GetFlagChangelist();
	    	$changes->Remove('clientTransferProhibited');
	    	
	    	$Udf = $this->UpdateDomainFlags($domain, $changes);
	    	$domain->SetFlagList($changes->GetList());	    	
	    	
	    	$Ret = new UnLockDomainResponse($Udf->Status, $Udf->ErrMsg, $Udf->Code);
	    	$Ret->Result = $Udf->Succeed();
	    	return $Ret;
	    }
	    
		/**
	     * Update domain flags (options such as clientUpdateProhibited, clientDeleteProhibited)
	     *
	     * @param Domain $domain
	     * @param IChangelist $changes flags changes
	     * @version v1000
	     */
	    public function UpdateDomainFlags(Domain $domain, IChangelist $changes)
	    {
		    $params = array (
				"name"   => $this->MakeNameIDNCompatible($domain->GetHostName())
			);
	    	
	    	if ($changes->GetAdded())
	    	{
	    		$params['add'] = '<domain:add>' . $this->GetFlagsXML($changes->GetAdded()) . '</domain:add>';
	    	}
	    	else
	    	{
	    		$params['add'] = '';
	    	}
	    	
	    	if ($changes->GetRemoved())
	    	{
	    		$params['rem'] = '<domain:rem>' . $this->GetFlagsXML($changes->GetRemoved()) . '</domain:rem>';
	    	}
	    	else
	    	{
	    		$params['rem'] = '';
	    	}

	    	$this->BeforeRequest('domain-update-flags', $params, __METHOD__, $domain, $changes);
			$response = $this->Request("domain-update-flags", $params);
			
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
				$status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Code == RFC3730_RESULT_CODE::OK)
				$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
			 
			$ret = new UpdateDomainFlagsResponse($status, $response->ErrMsg, $response->Code);
			$ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
			return $ret;
	    }
	    	    
		protected static $EPPFlags = array(
	    	'clientHold',
	    	'inactive',
	    	'clientUpdateProhibited',
	    	'clientDeleteProhibited',
	    	'clientTransferProhibited',
	    	'clientRenewProhibited'
	    );
		    
		protected function GetFlagsXML($flags)
		{
			$retval = "";
		    foreach ($flags as $flag)
		    {
		        if (in_array($flag, self::$EPPFlags))
		        	$retval .= "<domain:status s=\"{$flag}\"/>";
		    }
		    return $retval;
		}
		
		protected function GetResponseStatusFromEPPResponse ($response)
		{
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
				
			return $status;			
		}
		
		/**
		 * Create domain and return transaction status
		 *  
		 *  Domain info is an array of the following structure:
		 *  Fill domain Data with this values
		 * 
		 *  		"crDate"   => string Creation DateTime,
		 *			"exDate"   => Expiration DateTime,
		 *			"status"   => string Status code,
		 *			"pw"	   => string Password generated by registry,
		 *			"protocol" => string Protocol
		 *	 
		 * @access public
		 * @param Domain $domain Domain name without TLD
		 * @param array $owner_contact Domain Owner contact array
		 * @param array $admin_contact Domain Admin contact array
		 * @param array $tech_contact Domain Tech contact array
		 * @param array $billing_contact Domain Billing contact array
		 * @param array $extra Domain Billing contact array
		 * @param integer $period Registration period, years
		 * @param array $nameservers Array of strings containing nameservers hostnames
		 *  
		 * @return CreateDomainResponse
		 */
		public function CreateDomain(Domain $domain, $period, $extra = array())
		{
			$contacts = $domain->GetContactList();
			$nameservers = $domain->GetNameserverList();
			
			$params = array(
				"name"				=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"registrant_id"		=> $contacts['registrant']->CLID,
				"ns1"				=> $nameservers[0]->HostName,
				"ns2"				=> $nameservers[1]->HostName,
				"period"			=> $period,
				"pw"				=> $domain->AuthCode ? $domain->AuthCode : rand(100000000, 999999999)
			);
			
			unset($contacts[CONTACT_TYPE::REGISTRANT]);
			$params['contacts'] = '';
			foreach ($contacts as $contact_type => $contact)
				$params['contacts'] .= '<domain:contact type="'.$contact_type.'">'.$contact->CLID.'</domain:contact>';

			$params['ns'] = count($nameservers) ? '<domain:ns>' . $this->GetNSXML($nameservers) . '</domain:ns>' : '';
			
			$this->BeforeRequest('domain-create', $params, __METHOD__, $domain, $period, $extra);
			$response = $this->Request("domain-create", $params);
		
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
			
			$resp = new CreateDomainResponse($status, $response->ErrMsg, $response->Code);
			$resp->RawResponse = $response->Data;
			
			if ($response->Succeed)
			{
				$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
				$info = $info[0];
				
				if ($date = $this->StrToTime((string)$info->crDate[0]))
					$resp->CreateDate = $date;
					
				if ($date = $this->StrToTime((string)$info->exDate[0])) 
					$resp->ExpireDate = $date;
				else 
					$resp->ExpireDate = strtotime("+{$period} year");
					
				$resp->AuthCode = "{$info->authInfo->pw}" ? "{$info->authInfo->pw}" : (string)$params["pw"];
			}
			
			return $resp;
		}
		
		/**
		 * This method request registry for information about domain
		 * 
		 * @param Domain $domain 
		 * @return GetRemoteDomainResponse
		 */
		public function GetRemoteDomain(Domain $domain)
		{			
			$params = array(
				"name"	=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				'authinfo' => ''			
			);
			if ($domain->AuthCode)
				$params['authinfo'] = "<domain:authInfo><domain:pw>{$domain->AuthCode}</domain:pw></domain:authInfo>";				
				
			$this->BeforeRequest('domain-info', $params, __METHOD__, $domain);
			$response = $this->Request("domain-info", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$resp = new GetRemoteDomainResponse($status, $response->ErrMsg, $response->Code);
			$resp->RawResponse = $response->Data;
	
			if ($response->Succeed)
			{
				$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
				$info = $info[0];
				
				$resp->CLID = (string)$info->clID[0];
				
				try
				{
					$resp->CRID = (string)$info->crID[0];
				}
				catch(Exception $e){}
				
				if ($resp->CRID)
				{
					$resp->AuthCode = ($info->authInfo[0]) ? (string)$info->authInfo[0]->pw[0] : "";
					
					$resp->CreateDate = $this->StrToTime((string)$info->crDate[0]);
					if ($info->exDate[0])
					{
						$resp->ExpireDate = $this->StrToTime((string)$info->exDate[0]);	
					}
					
					// Get contacts
					foreach ($info->contact as $k=>$v)
					{
						$attrs = $v->attributes();
						$ctype = (string)$attrs["type"];
						
						switch($ctype)
						{
							case "admin":
								$resp->AdminContact = (string)$v;
								break;
							case "tech":
								$resp->TechContact = (string)$v;
								break;
							case "billing":
								$resp->BillingContact = (string)$v;
								break;
						}
					}
					$resp->RegistrantContact = (string)$info->registrant[0];

					
					// Get nameservers
					$ns_arr = array();
					$registryOptionsConfig = $this->Manifest->GetRegistryOptions();
					if ((bool)$registryOptionsConfig->ability->hostattr)
					{
						// Iterate over hostAttr
						if ($info->ns->hostAttr)
						{
							foreach ($info->ns->hostAttr as $hostAttr)
							{
								$hostName = (string)$hostAttr->hostName;
								if ($hostAttr->hostAddr[0])
								{
									$ns = new NameserverHost($hostName, (string)$hostAttr->hostAddr[0]);
								}
								else
								{
									$ns = new Nameserver($hostName);
								}
								$ns_arr[] = $ns;
							}
						}
					}
					else
					{
						// Iterate over hostObj
						if ($info->ns->hostObj)
						{
							foreach ($info->ns->hostObj as $v)
							{
								$hostname = (string)strtolower($v);
								if (FQDN::IsSubdomain($hostname, $domain->GetHostName()))
								{
									try
									{
										$ip = $this->GetHostIpAddress($hostname);
										$ns_arr[] = new NameserverHost($hostname, $ip);
									}
									catch (Exception $e) 
									{
										$ns_arr[] = new NameserverHost($hostname, '');
									}
								}
								else
								{
									// nameserver
									$ns_arr[] = new Nameserver($hostname);			 
								}							
							}
						}
					}
					$resp->SetNameserverList($ns_arr);

					
					// Flags (Domain status)
					$flags = array();
					foreach ($info->status as $status)
					{
						$flags[] = (string)$status->attributes()->s;
					}
					
					$resp->RegistryStatus = (string)$flags[0];
					
					// Remove default 'ok' status from domain flags 
					if (($i = array_search("ok", $flags)) !== false) {
						array_splice($flags, $i, 1);
					}
					$resp->SetFlagList($flags);					
				}
			}
		
			return $resp;
		}
		
		/**
		 * Performs epp host:info command. Returns host IP address
		 *
		 * @return string
		 */
		public function GetHostIpAddress ($hostname)
		{
			$params = array(
				'hostname' => $this->MakeNameIDNCompatible($hostname)
			);
			$this->BeforeRequest('host-info', $params, __METHOD__);
			$response = $this->Request('host-info', $params);
			if (!$response->Succeed)
				throw new Exception($response->ErrMsg);
				
			$result = $response->Data->response->resData->children($this->XmlNamespaces['host']);
			return (string)$result[0]->addr;
		}
		
		/**
		 * This method request regsitry to change domain contact
		 * In order to pending operation, response must have status REGISTRY_RESPONSE_STATUS::PENDING
		 * 
		 * @param Domain $domain Domain
		 * @param string $contactType contact type @see CONTACT_TYPE::TYPE_*
		 * @param Contact $oldContact Old contact or NULL
		 * @param Contact $newContact
		 * @return UpdateDomainContactResponse
		 */
		public function UpdateDomainContact(Domain $domain, $contactType, Contact $oldContact, Contact $newContact)
		{
			if (!$newContact && !$oldContact)
				throw new Exception("At leat one contact (\$newContact or \$oldContact) must be passed into UpdateDomainContact");
			
			$params = array(
				"name" 		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"change"	=> "",
				"add"		=> "",
				"rem"		=> ""
			);
			
			if ($contactType == CONTACT_TYPE::REGISTRANT)
				$params["change"] = "<domain:chg><domain:registrant>{$newContact->CLID}</domain:registrant></domain:chg>";
			else
			{
				if ($newContact)
					$params['add'] = '<domain:add><domain:contact type="'.$contactType.'">'.$newContact->CLID.'</domain:contact></domain:add>';
				
				if ($oldContact) 
					$params['rem'] = '<domain:rem><domain:contact type="'.$contactType.'">'.$oldContact->CLID.'</domain:contact></domain:rem>';
			}
			
			$this->BeforeRequest('domain-update-contact', $params, __METHOD__, $domain, $contactType, $oldContact, $newContact);
			$response = $this->Request("domain-update-contact", $params);
			
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;		
			return new UpdateDomainContactResponse($status, $response->ErrMsg, $response->Code);
		}
		
		/**
		 * Update nameservers for domain
		 * @access public
		 * @param Domain $domain Domain
		 * @param IChangelist $changelist nameservers changelist
		 * @return UpdateDomainNameserversResponse 
		 * @version v1000
		 */
		public function UpdateDomainNameservers(Domain $domain, IChangelist $changelist)
		{
			$params = array(
				"name" 	=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"add" 	=> "",
				"del" 	=> ""
			);
			
			if ($changelist->GetAdded())
				$params['add'] = "<domain:add><domain:ns>".$this->GetNSXML($changelist->GetAdded())."</domain:ns></domain:add>";
				
			if ($changelist->GetRemoved())
				$params['del'] = "<domain:rem><domain:ns>".$this->GetNSXML($changelist->GetRemoved())."</domain:ns></domain:rem>";
			
			$this->BeforeRequest('domain-update-ns', $params, __METHOD__, $domain, $changelist);
			$response = $this->Request("domain-update-ns", $params);
			
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;		
			return new UpdateDomainNameserversResponse($status, $response->ErrMsg, $response->Code);
		}
		
		protected function GetNSXML($nslist, $nsprefix="domain")
		{
			$ret = '';
			$registryOptionsConfig = $this->Manifest->GetRegistryOptions();
			if ((bool)$registryOptionsConfig->ability->hostattr)
			{
				// hostAttr
				foreach ($nslist as $ns)
				{
					$ret .= "<$nsprefix:hostAttr>";
					$ret .= "<$nsprefix:hostName>".$this->MakeNameIDNCompatible($ns->HostName)."</$nsprefix:hostName>";
					if ($ns instanceof NameserverHost) 
						$ret .= "<$nsprefix:hostAddr ip=\"v4\">".$ns->IPAddr."</$nsprefix:hostAddr>";
					$ret .= "</$nsprefix:hostAttr>";					
				}
			}
			else
			{
				// hostObj
				foreach ($nslist as $ns)
				{
					$ret .= "<domain:hostObj>".$this->MakeNameIDNCompatible($ns->HostName)."</domain:hostObj>\n";
				} 
			}
			
			return $ret;
		}
		
		/**
		 * Check domain availability
		 * @access public
		 * @param Domain $domain Domain
		 * @return bool true when domain available for registration
		 * @version v1000 
		 */
		public function DomainCanBeRegistered(Domain $domain)
		{
			$params = array(
				'name' => $this->MakeNameIDNCompatible($domain->GetHostName())
			);
			
			$this->BeforeRequest('domain-check', $params, __METHOD__, $domain);
			$response = $this->Request("domain-check", $params);
			if (! $response->Succeed)
				throw new Exception($response->ErrMsg, $response->Code);
				
			$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);		
			$resp = new DomainCanBeRegisteredResponse($status, $response->ErrMsg, $response->Code);
			$avail = (string)$info[0]->cd[0]->name[0]->attributes()->avail;
			$resp->Result = $avail == "1" || $avail == "true";
			$resp->Reason = (string)$info[0]->cd[0]->reason;
			
			return $resp;
		}
				
		/**
		 * This method request registry to delete domain
		 * In order to pending delete or scheduled delete, response must have status REGISTRY_RESPONSE_STATUS::PENDING
		 *
		 * @param Domain $domain Domain
		 * @param int $executeDate Valid timestamp for scheduled delete. Null for immediately delete
		 * @return DeleteDomainResponse
		 */
		public function DeleteDomain(Domain $domain, $executeDate=null)
		{
			$params = array(
				'name' => $this->MakeNameIDNCompatible($domain->GetHostName())	
			);
			
			$this->BeforeRequest('domain-delete', $params, __METHOD__, $domain, $executeDate);
			$response = $this->Request("domain-delete", $params);
			
			$status = ($response->Succeed || $response->Code == RFC3730_RESULT_CODE::ERR_OBJECT_NOT_EXISTS) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			return new DeleteDomainResponse($status, $response->ErrMsg, $response->Code);
		}
		
		/**
		 * Renew domain
		 *
		 * @param string $domain Domain name without TLD
		 * @param array $extradata Extra fields
		 */
		public function RenewDomain(Domain $domain, $extra=array())
		{
			$params = array(
				"name" 		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"exDate" 	=> date("Y-m-d", $domain->ExpireDate),
				"period" 	=> $extra["period"]
			);
			
			$this->BeforeRequest('domain-renew', $params, __METHOD__, $domain, $extra);
			$response = $this->Request("domain-renew", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			$resp = new RenewDomainResponse($status, $response->ErrMsg, $response->Code);
			
			if ($response->Succeed)
			{
				$Info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
				$resp->ExpireDate = $this->StrToTime((string)$Info[0]->exDate[0]); 
			}
			
			return $resp;
		}
		
		
		/**
		 * Request domain transfer
		 *
		 * @param string $domain Domain required data: name, pw
		 * @param array $extradata Extra fields
		 * @return TransferRequestResponse
		 */	
		public function TransferRequest(Domain $domain, $extra=array())
		{
			$params = array(
				'name' 			=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"pw"   			=> $this->EscapeXML(trim($extra["pw"])),
				'period'		=> $extra['period'] ? "<domain:period unit=\"y\">{$extra['period']}</domain:period>" : ''
			);
			
			$this->BeforeRequest('domain-trans-request', $params, __METHOD__, $domain, $extra);
			
			$response = $this->Request("domain-trans-request", $params);
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			return new TransferRequestResponse($status, $response->ErrMsg, $response->Code);
		}
		
		/**
		 * Send domain transfer approval
		 *
		 * @param string $domain Domain required data: name, pw
		 * @param array $extradata Extra fields
		 * @return bool True on success of false on failure
		 */
		public function TransferApprove(Domain $domain, $extra=array())
		{
			$params = array(
				'name' 			=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"pw"   			=> $this->EscapeXML($domain->AuthCode)		
			);
			
			$this->BeforeRequest('domain-trans-approve', $params, __METHOD__, $domain, $extra);
			$response = $this->Request("domain-trans-approve", $params);
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			return new TransferApproveResponse($status, $response->ErrMsg, $response->Code);		
		}
		
		/**
		 * Send domain transfer rejection
		 *
		 * @param string $domain Domain required data: name, pw
		 * @param array $extradata Extra fields
		 * @return bool True on success of false on failure
		 */
		public function TransferReject(Domain $domain, $extra=array())
		{
			$params = array(
				'name' 			=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"pw"   			=> $this->EscapeXML($domain->AuthCode)		
			);		
			
			$this->BeforeRequest('domain-trans-reject', $params, __METHOD__, $domain, $extra);
			$response = $this->Request("domain-trans-reject", $params);
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			return new TransferRejectResponse($status, $response->ErrMsg, $response->Code);			
		}
		
		
		
		/**
		 * Check domain transfer status
		 *
		 * @param string $domain Domain name without TLD
		 * @param string $password Domain password
		 * @return TRANSFER_STATUS codes
		 */
		public function CheckTransferStatus(Domain $domain)
		{
			$RDResponse = $this->GetRemoteDomain($domain);
			
			//TODO: Check RegistryStatus
					
			if ($RDResponse->Succeed())
			{
				if ($RDResponse->AuthCode != '' && $RDResponse->CLID == $this->Config->GetFieldByName('CLID')->Value)
					$tstatus = TRANSFER_STATUS::APPROVED;
				else if ($RDResponse->RegistryStatus == 'ok')
					$tstatus = TRANSFER_STATUS::PENDING;
				else
					$tstatus = TRANSFER_STATUS::DECLINED;
					
				$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			}
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
	
			$resp = new CheckTransferStatusResponse($status, $RDResponse->ErrMsg, $RDResponse->Code);
			$resp->TransferStatus = $tstatus;
			
			return $resp;	
		}
			
		/**
		 * Check either nameserver needs to be registered 
		 * @access public
		 * @param string $hostname 
		 * @return int 
		 * 		1 - Avaiable for registration, 
		 * 		2 - Already registered, 
		 * 		3 - Cannot be used, 
		 * 		False on falure
		 * @version v1000
		 */
		public function NameserverCanBeCreated(Nameserver $ns)
		{
			$params = array(
				'name' => $ns->HostName			
			);
			
			$this->BeforeRequest('host-check', $params, __METHOD__, $ns);
			$response = $this->Request("host-check", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			$resp =  new NameserverCanBeCreatedResponse($status, $response->ErrMsg, $response->Code);
			
			if ($response->Succeed)
			{
				$result = $response->Data->response->resData->children("urn:ietf:params:xml:ns:host-1.0");
				$attr = $result[0]->cd[0]->name[0]->attributes();
				
				$resp->Result = ($attr['avail'] == 1 || $attr["avail"] == "true") ? true : false;
			}
			
			return $resp;
		}
		
		/**
		 * This method request registry to create namserver
		 * 
		 * @param Nameserver $ns
		 * @return CreateNameserverResponse
		 */
		public function CreateNameserver (Nameserver $ns)
		{
			$params = array(
				'name' => $this->MakeNameIDNCompatible($ns->HostName),
				'addr' => '' 
			);
				
			$this->BeforeRequest('host-create', $params, __METHOD__, $ns);
			$response = $this->Request("host-create", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$ret = new CreateNameserverResponse($status, $response->ErrMsg, $response->Code);
			$ret->Result = $response->Succeed;
			return $ret;
		}
		
		/**
		 * This method request registry to create namserver host
		 * 
		 * @param NameserverHost $nshost
		 * @return CreateNameserverHostResponse
		 */
		public function CreateNameserverHost (NameserverHost $ns)
		{
				$params = array(
				'name' => $this->MakeNameIDNCompatible($ns->HostName) 
			);
			if (stripos($ns->HostName, ".{$this->Extension}"))
				$params["addr"] = "<host:addr ip=\"v4\">{$ns->IPAddr}</host:addr>";
			else 
				$params["addr"] = "";
				
			$this->BeforeRequest('host-create', $params, __METHOD__, $ns);
			$response = $this->Request("host-create", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$ret = new CreateNameserverHostResponse($status, $response->ErrMsg, $response->Code);
			$ret->Result = $response->Succeed;
			return $ret;
		}
		
		/**
		 * Update nameserver host. update IP
		 * @access public
		 * @param Host $host Host
		 * @return bool true on success of false on failure
		 * @version v1000 
		 */
		public function UpdateNameserverHost(NameserverHost $ns)
		{
			$ipaddr = $this->GetHostIpAddress($ns->HostName);			
			if ($ipaddr != $ns->IPAddr) 
			{
				// Update it
				$params = array(
					'hostname' => $this->MakeNameIDNCompatible($ns->HostName), 
					'ip_old' => $ipaddr, 
					'ip_new' => $ns->IPAddr
				);
				
				$this->BeforeRequest('host-update', $params, __METHOD__, $ns);
				$response = $this->Request('host-update', $params);
				$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			}
			else
				$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			
			return new UpdateNameserverHostResponse($status, $response->ErrMsg, $response->Code);
		}
		
		/**
		 * Delete nameserver host
		 * @access public
		 * @param Host $host Host
		 * @return bool true on success of false on failure
		 * @version v1000 
		 */
		public function DeleteNameserverHost(NameserverHost $ns)
		{
			$params = array(
				'hostname' => $this->MakeNameIDNCompatible($ns->HostName)
			);
		
			$this->BeforeRequest('host-delete', $params, __METHOD__, $ns);
			$response = $this->Request("host-delete", $params);
	
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			return new DeleteNameserverHostResponse($status, $response->ErrMsg, $response->Code);		
		}
		
		/**
		 * Check contact availability
		 * @access public
		 * @param Contact $contact Contact registry ID
		 * @return bool 
		 */
		public function ContactCanBeCreated(Contact $contact)
		{
			$params = array(
				'id' => $contact->CLID
			);
			
			$response = $this->Request("contact-check", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			$resp = new ContactCanBeCreatedResponse($status, $response->ErrMsg, $response->Code);
				
			if ($response->Succeed)
			{
				$info = $response->Data->response->resData->children($this->XmlNamespaces['contact']);
				$attr = $info[0]->cd[0]->id[0]->attributes();
				$resp->Result = ((string)$attr['avail'] == 1);
			}
						
			return $resp;	
		}
		
		
		/**
		 * Create contact
		 * @access public
		 * @param Contact $contact
		 * @return Contact
		 */
		public function CreateContact(Contact $contact, $extra=array())
		{
			$cd = $contact->GetRegistryFormattedFieldList();
			$cd = array_merge($cd, array(
				'type' => $contact->Type,
				'id'   => $contact->CLID,
				'xml_street2' => $cd['street2'] ? "<contact:street>{$this->EscapeXML($cd["street2"])}</contact:street>" : ''	
			));
								
			if (!$cd["pw"]) 
				$cd["pw"] = $this->GeneratePassword();
			
			foreach ($cd as $k => &$v) 
				if ($k != 'xml_street2')
					$v = $this->EscapeXML($v);
			
			$cd['disclose_show'] = $this->GetDiscloseXML($contact->GetDiscloseList(), 1);
			$cd['disclose_hide'] = $this->GetDiscloseXML($contact->GetDiscloseList(), 0);

			
			$this->BeforeRequest('contact-create', $cd, __METHOD__, $contact);
			$response = $this->Request("contact-create", $cd);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			$resp = new CreateContactResponse($status, $response->ErrMsg, $response->Code);
			$resp->RawResponse = $response->Data;
			
			if ($response->Succeed)
			{
				// Process response
				$info = $response->Data->response->resData->children($this->XmlNamespaces['contact']);
				$resp->CLID = (string)$info[0]->id[0];
				$resp->AuthCode = $cd["pw"];
			}
			
			return $resp;
		}
		
		
		/**
		 * Get contact info by ID
		 * @access public
		 * @param string $CLID Contact registry ID
		 * @return array Contact info. See XML:config/contacts
		 * @version v1000
		 */
		public function GetRemoteContact(Contact $contact)
		{
			$params = array(
				'id' => $contact->CLID
			);
			
			$this->BeforeRequest('contact-info', $params, __METHOD__, $contact);
			$response = $this->Request("contact-info", $params);
	
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			$resp = new GetRemoteContactResponse($status, $response->ErrMsg, $response->Code);
			$resp->RawResponse = $response->Data;
			
			if ($response->Succeed)
			{
				$ContactInfo = $response->Data->response->resData->children($this->XmlNamespaces['contact']);
				
				$ContactInfo = $ContactInfo[0];
				$PostalInfo = $ContactInfo->postalInfo[0];
				
				$resp->CLID	  	= trim((string)$ContactInfo->id[0]);
				$resp->name	  	= trim((string)$PostalInfo->name[0]);
				$resp->org	  	= trim((string)$PostalInfo->org[0]);
				$resp->street1 	= trim((string)$PostalInfo->addr[0]->street[0]);
				$resp->street2 	= trim((string)$PostalInfo->addr[0]->street[1]);
				$resp->city 	= trim((string)$PostalInfo->addr[0]->city[0]);
				$resp->sp     	= trim((string)$PostalInfo->addr[0]->sp[0]);
				$resp->pc      	= trim((string)$PostalInfo->addr[0]->pc[0]);
				$resp->cc      	= trim((string)$PostalInfo->addr[0]->cc[0]);
				$resp->voice   	= trim((string)$ContactInfo->voice[0]);
				$resp->fax	  	= trim((string)$ContactInfo->fax[0]);
				$resp->email   	= trim((string)$ContactInfo->email[0]);
				$resp->clID	  	= trim((string)$ContactInfo->clID[0]);
				$resp->crID	  	= trim((string)$ContactInfo->crID[0]);
				$resp->pw      	= trim(($ContactInfo->authInfo[0]) ? (string)$ContactInfo->authInfo[0]->pw[0] : "");
			}
	
			return $resp;
		}
			
		protected function GetDiscloseXML($disclose_list, $flag)
		{
			$discloses = array();
			foreach ($disclose_list as $dname=>$dvalue)
			{
				if ($dvalue == $flag)
				{
					array_push($discloses, $dname);
				}
			}
			
			if (count($discloses) == 0)
				return "";
				
			else
			{
				$retval = '<contact:disclose flag="'.$flag.'">';
				foreach($discloses as $disclose)
				{
					if (in_array($disclose, array('name', 'org', 'addr')))
					{
						$retval .= '<contact:'.$disclose.' type="loc"/>';
					}
					else
					{
						$retval .= '<contact:'.$disclose.'/>';						
					}
				}
				$retval .= '</contact:disclose>';
			}
			
			return $retval;
		}
		
		/**
		 * Update contact
		 * @access public
		 * @param string $contact_id Contact registry ID
		 * @param array $contact Contact info.  See XML:config/contacts
		 * @return bool True on success of false on failure
		 */
		public function UpdateContact(Contact $contact)
		{
			$discloses_feature = count($contact->GetConfig()->disclose->children()) > 0;
			
			// First update contact info and disclose@flag=0
			$params = $contact->GetRegistryFormattedFieldList();
			foreach ($params as $k => &$v)
				$v = $this->EscapeXML($v);
			$params['id'] = $contact->CLID;
			$params["pw"] = $this->EscapeXML($contact->AuthCode);
			if ($discloses_feature) 
				$params['disclose'] = $this->GetDiscloseXML($contact->GetDiscloseList(), 0);
				
			$this->BeforeRequest("contact-update", $params, __METHOD__, $contact);
			$response = $this->Request("contact-update", $params);
			
			if ($discloses_feature && $response->Succeed)
			{
				$disclose = $this->GetDiscloseXML($contact->GetDiscloseList(), 1);
				if ($disclose)
				{
					// Update contact disclose@flag=1
					$params = array(
						"id" => $contact->CLID, 
						"disclose" => $disclose 
					);
					$this->BeforeRequest("contact-update-disclose", $params, __METHOD__, $contact);
					$response = $this->Request("contact-update-disclose", $params);
					
				}
			}

			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;			
			return new UpdateContactResponse($status, $response->ErrMsg, $response->Code);
		}		
		
		/**
		 * Delete contact
		 *
		 * @param Contact $contact Contact uniq CLID
		 * @param array $extra Extra fields
		 */
		public function DeleteContact(Contact $contact, $extra = array())
		{
			$params["id"] = $contact->CLID;
		
			$this->BeforeRequest("contact-delete", $params, __METHOD__, $contact, $extra);
			$response = $this->Request("contact-delete", $params);
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			return new DeleteContactResponse($status, $response->ErrMsg, $response->Code);
		}
		
		protected function BeforeRequest ($command, &$data, $method /** args */)
		{
			
		}
	
		const TRANSFER_CLIENT_APPROVED = "clientApproved";
	
		const TRANSFER_CLIENT_CANCELLED = "clientCancelled";
		
		const TRANSFER_CLIENT_REJECTED = "clientRejected";
		
		const TRANSFER_PENDING = "pending";
	
		const TRANSFER_SERVER_APPROVED = "serverApproved";
	
		const TRANSFER_SERVER_CANCELLED = "serverCancelled"; 
		
		/**
		 * Read server message queue, and return first unprocessed item
		 * 
		 * @return PendingOperationResponse False, when queue is empty
		 */
		public function ReadMessage ()
		{
			/*
			$Resp = new TransportResponse(
				RFC3730_RESULT_CODE::OK_ACK_DEQUEUE, 
				simplexml_load_file(dirname(__FILE__) . '/poll/domain-transfer.xml'),
				true,
				''
			);
			*/
			
			$Resp = $this->Request('poll-request', array());

			//print "<pre>";
			//print htmlspecialchars($Resp->Data->asXML());
			//print "</pre>";
	
			if ($Resp->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE)
			{
				$msgID = (string)$Resp->Data->response->msgQ->attributes()->id;
				$resData = $Resp->Data->response->resData;
				if ($resData && $trnData = $resData->children($this->XmlNamespaces['domain']))
				{
					// Domain transfer message
					$trnData = $trnData[0];
					$trStatus = (string)$trnData->trStatus;
					
					switch ($trStatus)
					{
						case self::TRANSFER_CLIENT_APPROVED:
						case self::TRANSFER_SERVER_APPROVED:
							$transfer_status = TRANSFER_STATUS::APPROVED;
							break;
	
						case self::TRANSFER_CLIENT_CANCELLED:
						case self::TRANSFER_SERVER_CANCELLED:
						case self::TRANSFER_CLIENT_REJECTED:
							$transfer_status = TRANSFER_STATUS::DECLINED;
							break;
							
						case self::TRANSFER_PENDING:
							$transfer_status = TRANSFER_STATUS::PENDING;
							break;
							
						default:
							$transfer_status = TRANSFER_STATUS::FAILED;
					}
					
					$Ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$Ret->MsgID = $msgID;
					$hostname = (string)$trnData->name;
					if (substr($hostname, 0, 4) == "xn--")
					{
						$hostname = $this->RegistryAccessible->PunycodeDecode($hostname);
					}					
					$Ret->HostName = $hostname;
					$Ret->TransferStatus = $transfer_status;
					$Ret->RawResponse = $Resp->Data;
					return $Ret;
				}
				else
				{
					$Ret = new PendingOperationResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$Ret->MsgID = $msgID;
					$Ret->RawResponse = $Resp->Data;
					return $Ret;
				}
			}
			
			return false;
		}
	
		/**
		 * Send message acknowledgement to server
		 *
		 * @param PendingOperationResponse $resp_message                            
		 */       
		public function AcknowledgeMessage (PendingOperationResponse $resp_message)
		{
			$params = array(
				'msgID' => $resp_message->MsgID
			);
			$this->Request('poll-ack', $params);
		}		
		
		/**
		 * Parse datetime description into a Unix timestamp Ignores timezone
		 */
		protected function StrToTime ($str)
		{
			setlocale(DEFAULT_LOCALE);
			$time = strlen($str) > 10 ? strtotime(substr($str, 0, -3)) : strtotime($str);
			setlocale(LOCALE);
			return $time;
		}	
	}
?>