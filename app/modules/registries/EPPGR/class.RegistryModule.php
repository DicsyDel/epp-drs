<?php

	/**
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
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */		

class EPPGRRegistryModule extends AbstractRegistryModule implements IRegistryModuleClientPollable
{
	const NAMESERVERS_COUNT = 2;
	const HOST_NAMESPACE = 'urn:ietf:params:xml:ns:host-1.0';
	
	
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
		$ConfigurationForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT, "EPP Host", 1));
		$ConfigurationForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "EPP Login", 1));
		$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT , "EPP Password", 1));
		$ConfigurationForm->AppendField( new DataFormField("ClientPrefix", FORM_FIELD_TYPE::TEXT , "Client prefix", 1, "3-digits number"));	
		$ConfigurationForm->AppendField( new DataFormField("UseSSLCert", FORM_FIELD_TYPE::CHECKBOX , "Use SSL Certificate", 1, "Only for OT&E registry. Must be disabled in production."));	
		$ConfigurationForm->AppendField( new DataFormField("SSLpwd", FORM_FIELD_TYPE::TEXT , "SSL Certificate password", 1, "Default is <b>devepp</b>"));	
		$ConfigurationForm->AppendField( new DataFormField("CLID", FORM_FIELD_TYPE::TEXT , "Registrar clID", 1));
		$ConfigurationForm->AppendField( new DataFormField("SSLCertPath", FORM_FIELD_TYPE::TEXT , "Path to SSL certificate", 1));
		
		return $ConfigurationForm;
	}	
	
	/**
	 * This method must return current Registry CLID
	 *
	 * @return string
	 */
	public function GetRegistrarID()
	{
		return $this->Config->GetFieldByName("CLID")->Value;
	}
	
    /**
     * Check transfer availability for $domain_name Domain
     *
     * @param Domain $domain
     * @return bool
     * @version v1000
     */
    public function DomainCanBeTransferred(Domain $domain)
    {    	
    	try
    	{
    		$GetRemoteDomainResponse = $this->GetRemoteDomain($domain);
    	}
    	catch(ObjectNotExistsException $e)
    	{
    		
    	}
    	
    	if ($GetRemoteDomainResponse)
	    	$resp = new DomainCanBeTransferredResponse(
	    		$GetRemoteDomainResponse->Status, 
	    		$GetRemoteDomainResponse->ErrMsg, 
	    		$GetRemoteDomainResponse->Code
	    	);
	    else
	    	$resp = new DomainCanBeTransferredResponse(
	    		REGISTRY_RESPONSE_STATUS::SUCCESS, 
	    		"", 
	    		1000
	    	);
    	
		if ($GetRemoteDomainResponse && ($GetRemoteDomainResponse->CLID != $this->Config->GetFieldByName('Login')->Value))
			$resp->Result = true;
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
			"name" 		=> $domain->GetHostName(),
			"change"	=> "",
			"add"		=> "",
			"rem"		=> "",
    		"remove"	=> ""
		);
		
		$params["change"] = "<domain:chg><domain:authInfo><domain:pw>{$this->EscapeXML($authCode)}</domain:pw></domain:authInfo></domain:chg>";
		
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
        //ownerNameChange,ownerChange
        $owner = $domain->GetContact(CONTACT_TYPE::REGISTRANT);
        if (!$owner)
        	throw new Exception(_('Domain registrant contact is undefined'));
    	
        $params = array(
			"name" 		=> "{$domain->Name}.{$this->Extension}",
			"new_id"	=> $owner->CLID,
			"request"   => $extra["requesttype"]
		);

		$response = $this->Request("domain-trade-request", $params);
		
		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			$status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Code == RFC3730_RESULT_CODE::OK)
			$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;
		
		return new ChangeDomainOwnerResponse($status, $response->ErrMsg, $response->Code);
    }
        
    /**
     * Lock or Unlock Domain name
     *
     * @param Domain $domain_name
     * @param array $extra Some extra data
     */
    public function LockDomain(Domain $domain, $extra = array())
    {
    	throw new NotImplementedException();
    }
    
    public function UnlockDomain (Domain $domain, $extra = array())
    {
    	throw new NotImplementedException();
    }
    
    /**
     * Update domain flags (options such as clientUpdateProhibited, clientDeleteProhibited)
     *
     * @param Domain $domain
     * @param IChangelist $changes flags changes
     */
    public function UpdateDomainFlags(Domain $domain, IChangelist $changes)
    {
		throw new NotImplementedException();
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
	 * @return Domain
	 * 
	 * @version v1000
	 */
	public function CreateDomain(Domain $domain, $period, $extra=array()) 
	{
		$contact_list = $domain->GetContactList();
		$nameserver_list = $domain->GetNameserverList(); 
			
		if (!$nameserver_list || count($nameserver_list) != self::NAMESERVERS_COUNT)
			throw new Exception(sprintf(_('This registry requires %d nameservers for domain'), self::NAMESERVERS_COUNT));

		$params = array(
			"name"				=> "{$domain->Name}.{$this->Extension}",
			"registrant_id"		=> $contact_list[CONTACT_TYPE::REGISTRANT]->CLID,
			"ns1"				=> $nameserver_list[0]->HostName,
			"ns2"				=> $nameserver_list[1]->HostName,
			"y"					=> $period,
			"pw"				=> rand(100000000, 999999999)
		);
		
		unset($contact_list[CONTACT_TYPE::REGISTRANT]);
		$params['contacts'] = '';
		foreach ($contact_list as $contact_type => $contact)
			$params['contacts'] .= '<domain:contact type="'.$contact_type.'">'.$contact->CLID.'</domain:contact>';
			
		
		$response = $this->Request("domain-create", $params);
		
		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
		    $status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Succeed)
		    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;
		
		$resp = new CreateDomainResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			$protocol_ext = $response->Data->response->extension->children("urn:ics-forth:params:xml:ns:extdomain-1.1");
			$resp->Protocol = (string)$protocol_ext[0]->protocol[0];
			
			$info = $response->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
			$info = $info[0];

			$resp->CreateDate = $this->StrToTime((string)$info->crDate[0]); 
			$resp->ExpireDate = $this->StrToTime((string)$info->exDate[0]); 
			
			$resp->AuthCode = (string)$params["pw"];
		}
		
		return $resp;
	}
	
	/**
	 * Return information about domain
	 * 
	 * @access public
	 * @param Domain $domain 
	 * @return GetRemoteDomainResponse
	 */
	public function GetRemoteDomain(Domain $domain)
	{
		if ($domain->AuthCode)
		{
			$pw = "<domain:authInfo>
						<domain:pw>".$this->EscapeXML($domain->AuthCode)."</domain:pw>
					</domain:authInfo>";
		}
		else
			$pw = '';
		
		$response = $this->Request("domain-info", array(
			"name"	=> "{$domain->Name}.{$this->Extension}", 
			"pw"	=> $pw
		));
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$resp = new GetRemoteDomainResponse($status, $response->ErrMsg, $response->Code);

		if ($response->Succeed)
		{
			$info = $response->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
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
				$resp->ExpireDate = $this->StrToTime((string)$info->exDate[0]); 
								
				$extdomain = $response->Data->response->extension->children("urn:ics-forth:params:xml:ns:extdomain-1.1");
				$resp->Protocol = (string)$extdomain->resData->protocol[0];
							
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
				// TODO: testit
				$ns_arr = array();
				foreach ($info->ns->hostObj as $v)
				{
					$hostname = (string)$v;
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
				
				$resp->SetNameserverList($ns_arr);
				
				if ($info->status[0])
				    $attrs = $info->status[0]->attributes();
				elseif ($info->status)
				    $attrs = $info->status->attributes();
				else 
				    $attrs["s"] = false;
				    
				$resp->RegistryStatus = (string)$attrs["s"];
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
			'hostname' => $hostname
		);
		$response = $this->Request('host-info', $params);
		if (!$response->Succeed)
			throw new Exception($response->ErrMsg);
			
		$result = $response->Data->response->resData->children(self::HOST_NAMESPACE);
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
		$params = array(
			"name" 		=> $domain->GetHostName()
		);
		
		if ($newContact)
			$params['add'] = '<domain:add><domain:contact type="'.$contactType.'">'.$newContact->CLID.'</domain:contact></domain:add>';
		else
			$params["add"] = '';
		
		if ($oldContact)
			$params['rem'] = '<domain:rem><domain:contact type="'.$contactType.'">'.$oldContact->CLID.'</domain:contact></domain:rem>';
		else
			$params['rem'] = '';
		
		if (!$newContact && !$oldContact)
			throw new Exception("At leat one contact (\$newContact or \$oldContact) must be passed into UpdateDomainContact");
			
		$response = $this->Request("domain-update-contact", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
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
		$added = $changelist->GetAdded();
		$removed = $changelist->GetRemoved();
		
		$re = '/The speficied host is not used as a nameserver for this domain. \[rem: ([^\]]+)\]/'; 
		$br = false;
		
		do
		{
			$response = $this->DoUpdateDomainNameservers($domain, $added, $removed);
			if (preg_match($re, $response->ErrMsg, $match) && $removed)
			{
				$dirty_removed_host = $match[1];
				foreach ($removed as $i => $ns)
				{
					if ($ns->HostName == $dirty_removed_host)
					{
						unset($removed[$i]);
						break;
					}
				}
			}
			else
			{
				$br = true;
			}
		}
		while (!$br);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		return new UpdateDomainNameserversResponse($status, $response->ErrMsg, $response->Code);
	}
	
	private function DoUpdateDomainNameservers (Domain $domain, $added=array(), $removed=array())
	{
		$params = array(
			"name" 	=> "{$domain->Name}.{$this->Extension}",
			"add" 	=> "",
			"del" 	=> ""
		);
		
		if ($added)
			$params['add'] = "<domain:add><domain:ns>".$this->GetNSXML($added)."</domain:ns></domain:add>";
			
		if ($removed)
			$params['del'] = "<domain:rem><domain:ns>".$this->GetNSXML($removed)."</domain:ns></domain:rem>";
		
		return $this->Request("domain-update-ns", $params);
	}

	private function GetNSXML($nslist)
	{
		$ret = '';
		foreach ($nslist as $ns)
			$ret .= "<domain:hostObj>{$ns->HostName}</domain:hostObj>\n"; 
		
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
			'name' => "{$domain->Name}.{$this->Extension}"
		);
		
		$response = $this->Request("domain-check", $params);
		if (! $response->Succeed)
			throw new Exception($response->ErrMsg, $response->Code);
			
		$info = $response->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");		
		$resp = new DomainCanBeRegisteredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS, $response->ErrMsg, $response->Code);
		$resp->Result = ($info[0]->cd[0]->name[0]->attributes()->avail == '1') ? true : false;
		$resp->Reason = (string)$info[0]->cd[0]->reason;
		
		return $resp;
	}
		
	/**
	 * Delete domain name
	 *
	 * @param Domain $domain Domain
	 * @param int $executeDate Valid timestamp for scheduled delete. Null for immediately delete
	 */
	public function DeleteDomain(Domain $domain, $executeDate=null)
	{
		$params = array(
			'name' => "{$domain->Name}.{$this->Extension}",
			'protocol' => $domain->Protocol,
			'pw' => $this->EscapeXML($domain->AuthCode)
		);
		
		try
		{
			$response = $this->Request("domain-delete", $params);
		}
		catch(ProhibitedTransformException $e)
		{
			$params = array(
				'name' => "{$domain->Name}.{$this->Extension}",
				'protocol' => $domain->Protocol
			);
		
			$response = $this->Request("domain-uncreate", $params);
		}
				
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
						"name" 		=> "{$domain->Name}.{$this->Extension}",
						"exDate" 	=> date("Y-m-d", $domain->ExpireDate),
						"period" 	=> $extra["period"]
					  );
		
		$response = $this->Request("domain-renew", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp = new RenewDomainResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			$Info = $response->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
			$resp->ExpireDate = $this->StrToTime((string)$Info[0]->exDate[0]); 
		}
		
		return $resp;
	}
	
	/**
	 * Request domain transfer
	 *
	 * @param string $domain Domain required data: name, pw
	 * @param array $extradata Extra fields
	 * @return bool Transaction status
	 */	
	public function TransferRequest(Domain $domain, $extra=array())
	{
		if ($extra['registrant'])
		{
			$registrant_clid = $extra['registrant']; 
		}
		else
		{
			$EmptyRegistrant = $this->RegistryAccessible->NewContactInstance(CONTACT_TYPE::REGISTRANT);
			$registrant_clid = $EmptyRegistrant->CLID;
			$domain->SetExtraField('x-gr-trn-registrant', $registrant_clid);
		}
		$new_pw = $this->GeneratePassword();

		
		$params = array(
			'name' 			=> "{$domain->Name}.{$this->Extension}",
			"pw"   			=> $this->EscapeXML($extra["pw"]),
			'new_pw'		=> $this->EscapeXML($new_pw),
			"registrant"	=> $registrant_clid	
		);
		
		$response = $this->Request("domain-trans-request", $params);
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;

		$domain->AuthCode = $new_pw;
		
		return new TransferRequestResponse($status, $response->ErrMsg, $response->Code);
	}
	
	function OnDomainTransferRequested (Domain $domain)
	{
		$ops = $this->RegistryAccessible->GetPendingOperationList(Registry::OBJ_DOMAIN, $domain->ID);
		foreach ($ops as $op) {
			if ($op->Type == Registry::OP_TRANSFER) {
				$resp = new PollTransferResponse();
				$resp->HostName = $domain->GetHostName();
				$resp->TransferStatus = TRANSFER_STATUS::APPROVED;
				$this->RegistryAccessible->DispatchPollTransfer($resp);
				
				$this->RegistryAccessible->RemovePendingOperation($op->ID);				
				break;
			}
		}
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
			'name' 			=> "{$domain->Name}.{$this->Extension}",
			"pw"   			=> $this->EscapeXML($domain->AuthCode)		
		);
		
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
			'name' 			=> "{$domain->Name}.{$this->Extension}",
			"pw"   			=> $this->EscapeXML($domain->AuthCode)		
		);		
		
		$response = $this->Request("domain-trans-reject", $params);
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new TransferRejectResponse($status, $response->ErrMsg, $response->Code);			
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
		$response = $this->Request("host-check", array(
			'name' => $ns->HostName
		));
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp = new NameserverCanBeCreatedResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			$result = $response->Data->response->resData->children("urn:ietf:params:xml:ns:host-1.0");
			$attr = $result[0]->cd[0]->name[0]->attributes();
			
			$resp->Result = ($attr['avail'] == 1) ? true : false;
		}
		
		return $resp;
	}
	
	/**
	 * Create nameserver
	 * @access public
	 * @param string $host Hostname
	 * @return Nameserver
	 * @version v1000 
	 */
	public function CreateNameserver (Nameserver $ns)
	{
		$ipaddr = gethostbyname($ns->HostName);
		if ($ipaddr == $ns->HostName)
			throw new Exception(sprintf(_('Can\'t resolve %s IP address'), $ns->HostName));
		
		$nshost = new NameserverHost($ns->HostName, $ipaddr);
		$r = $this->CreateNameserverHost($nshost);
		
		return new CreateNameserverResponse($r->Status, $r->ErrMsg, $r->Code);
	}
	
	/**
	 * Create ns host
	 * 
	 * @return NameserverHost
	 */
	public function CreateNameserverHost (NameserverHost $nshost)
	{
		$params = array(
			'host' => $nshost->HostName,
			'ip1' => $nshost->IPAddr
		);
		
		$response = $this->Request("host-create", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new CreateNameserverHostResponse($status, $response->ErrMsg, $response->Code);	
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
		// Get host info
		$params = array(
			'hostname' => $ns->HostName
		);
		
		$response = $this->Request('host-info', $params);
		if (!$response->Succeed)
			throw new Exception($response->ErrMsg);
			
		$result = $response->Data->response->resData->children(self::HOST_NAMESPACE);
		$ipaddr = (string)$result[0]->addr;
			
		if ($ipaddr != $ns->IPAddr) 
		{
			// Update it
			$params = array(
				'hostname' => $ns->HostName, 
				'ip_old' => $ipaddr, 
				'ip_new' => $ns->IPAddr
			);
			
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
			'hostname' => $ns->HostName
		);
	
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
			$info = $response->Data->response->resData->children("urn:ietf:params:xml:ns:contact-1.0");
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
			'phoneext' => '',
			'faxext' => '',
			'xml_street2' => $cd['street2'] ? "<contact:street>{$this->EscapeXML($cd["street2"])}</contact:street>" : ''	
		));
						
		if (!$cd["pw"]) 
			$cd["pw"] = $this->GeneratePassword();
		
		foreach ($cd as $k => &$v) 
			if ($k != 'xml_street2')
				$v = $this->EscapeXML($v);
				
		$cd['discloses'] = $this->GetDiscloseXML($contact->GetDiscloseList(), 1);
		
		$response = $this->Request("contact-create", $cd);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp = new CreateContactResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			// Process response
			$info = $response->Data->response->resData->children("urn:ietf:params:xml:ns:contact-1.0");
			$resp->CLID = (string)$info[0]->id[0];
			$resp->AuthCode = $cd['pw'];
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
		
		$response = $this->Request("contact-info", $params);

		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp = new GetRemoteContactResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			$ContactInfo = $response->Data->response->resData->children("urn:ietf:params:xml:ns:contact-1.0");
			
			$disclose_f1 = $ContactInfo[0]->xpath("contact:disclose[@flag = 1]");
			if ($disclose_f1[0] instanceof SimpleXMLElement)
			{
				$tags = $disclose_f1[0]->children("contact", true);
				$fields = array_keys((array)$tags);
				foreach($fields as $field)
					$resp->SetDiscloseValue($field, 1);
			}
						
			
			$disclose_f0 = $ContactInfo[0]->xpath("contact:disclose[@flag = 0]");
			if ($disclose_f0[0] instanceof SimpleXMLElement)
			{
				$tags = $disclose_f0[0]->children("contact", true);
				$fields = array_keys((array)$tags);
				foreach($fields as $field)
					$resp->SetDiscloseValue($field, 0);
			}
			
			$ContactInfo = $ContactInfo[0];
			$PostalInfo = $ContactInfo->postalInfo[0];
			
			$resp->CLID	  	= (string)$ContactInfo->id[0];
			$resp->AuthCode      	= ($ContactInfo->authInfo[0]) ? (string)$ContactInfo->authInfo[0]->pw[0] : "";
						
			$resp->name	  	= (string)$PostalInfo->name[0];
			$resp->org	  	= (string)$PostalInfo->org[0];
			$resp->street1 	= (string)$PostalInfo->addr[0]->street[0];
			$resp->street2 	= (string)$PostalInfo->addr[0]->street[1];
			$resp->city 	= (string)$PostalInfo->addr[0]->city[0];
			$resp->sp     	= (string)$PostalInfo->addr[0]->sp[0];
			$resp->pc      	= (string)$PostalInfo->addr[0]->pc[0];
			$resp->cc      	= (string)$PostalInfo->addr[0]->cc[0];
			$resp->voice   	= (string)$ContactInfo->voice[0];
			$resp->fax	  	= (string)$ContactInfo->fax[0];
			$resp->email   	= (string)$ContactInfo->email[0];
			$resp->clID	  	= (string)$ContactInfo->clID[0];
			$resp->crID	  	= (string)$ContactInfo->crID[0];
		}

		return $resp;
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
		$params = $contact->GetRegistryFormattedFieldList();
		foreach ($params as &$param)
		{
			if (!is_array($param))
				$param = $this->EscapeXML($param);
		}
		
		$params['id'] = $contact->CLID;
		$params["pw"] = $contact->AuthCode;
		$params["discloses"] = $this->GetDiscloseXML($contact->GetDiscloseList(), 1);
		
		$response = $this->Request("contact-update", $params);
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new UpdateContactResponse($status, $response->ErrMsg, $response->Code);
	}

	private function GetDiscloseXML($disclose_list, $flag)
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
				if ($disclose != 'addr')
					$retval .= "<contact:{$disclose}/>";
				else
					$retval .= '<contact:addr type="loc"/>';
			}
			$retval .= '</contact:disclose>';
		}
		
		return $retval;
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
	
		$response = $this->Request("contact-delete", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new DeleteContactResponse($status, $response->ErrMsg, $response->Code);
	}
	
	/**
	 * @param Domain $domain
	 * @return PollTransferResponse 
	 */
	public function PollTransfer (Domain $domain)
	{
		$params = array(
			'name' => $domain->GetHostName(),
			'pw' => $this->EscapeXML($domain->AuthCode)
		);
		$Resp = $this->Request('domain-trans-query', $params);
		if ($Resp->Succeed)
		{
			$trnInfo = $Resp->Data->response->resData->children('urn:ietf:params:xml:ns:domain-1.0');
			$eppTrStatus = (string)$trnInfo[0]->trStatus;
			if ($eppTrStatus == 'clientApproved' || $eppTrStatus == 'serverApproved') 
			{
				$trStatus = TRANSFER_STATUS::APPROVED;
			}
			else if ($eppTrStatus == 'clientCancelled' || $eppTrStatus == 'clientRejected' || $eppTrStatus == 'serverCancelled')
			{
				$trStatus = TRANSFER_STATUS::DECLINED;
			}
			else if ($eppTrStatus == 'pending')
			{
				$trStatus = TRANSFER_STATUS::PENDING;
			}
			else
			{
				throw new Exception(sprintf(_("Undefined transfer status: %s"), $trStatus));
			}
			
			$respStatus = $trStatus != TRANSFER_STATUS::PENDING ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::PENDING;
			$Ret = new PollTransferResponse($respStatus, $Resp->ErrMsg, $Resp->Code); 
			$Ret->HostName = $domain->GetHostName();
			$Ret->TransferStatus = $trStatus;
			if ($trStatus == TRANSFER_STATUS::DECLINED)
			{
				$Ret->FailReason = _("Transfer was rejected by the current domain owner.");
			} 
			
			return $Ret;
		}
		else
		{
			return new PollTransferResponse(REGISTRY_RESPONSE_STATUS::FAILED, $Resp->ErrMsg, $Resp->Code);
		}
	}
	 
	/**
	 * @param Domain $domain
	 * @return DomainCreatedResponse
	 */
	public function PollCreateDomain (Domain $domain)
	{
		try
		{
			$RDResponse = $this->GetRemoteDomain($domain);
			if ($RDResponse->Succeed())
			{
				$rs = $RDResponse->RegistryStatus;
				
				$status = $rs == 'ok' || $rs != 'pendingCreate' ? 
					REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::PENDING;
				$resp = new PollCreateDomainResponse($status);
				$resp->HostName = $domain->GetHostName();
				
				if ($rs == 'ok')
				{
					$resp->ExpireDate = $domain->ExpireDate;
					$resp->Result = true;
				}
				else if ($rs != 'pendingCreate')
				{
					$resp->Result = false;
				}
					
				return $resp;
			}
			else
			{
				return new PollCreateDomainResponse(
					REGISTRY_RESPONSE_STATUS::FAILED, 
					$RDResponse->ErrMsg, 
					$RDResponse->Code
				);
			}
		}
		catch (ObjectNotExistsException $e)
		{
			$resp = new PollCreateDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$resp->HostName = $domain->GetHostName();
			$resp->Result = false;
			$resp->FailReason = _("Domain registration declined by registry");
			return $resp;
		}
	}
	
	/**
	 * @param Domain $domain
	 * @return PollDeleteDomainResponse
	 */
	public function PollDeleteDomain (Domain $domain)
	{
		// Not used
		return;
	}
	
	/**
	 * @param Domain $domain
	 * @return PollChangeDomainOwner
	 */
	public function PollChangeDomainOwner (Domain $domain)
	{
		$RDResponse = $this->GetRemoteDomain($domain);
		if ($RDResponse->Succeed())
		{
			if ($RDResponse->RegistryStatus == 'ok')
			{
				$resp = new PollChangeDomainOwnerResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$resp->HostName = $domain->GetHostName();
				$resp->Result = true;
				return $resp;
			}
			else if ($RDResponse->RegistryStatus == 'pendingUpdate')
			{
				$resp = new PollChangeDomainOwnerResponse(REGISTRY_RESPONSE_STATUS::PENDING);
				$resp->HostName = $domain->GetHostName();
				return $resp;
			}
			else
			{
				$resp = new PollChangeDomainOwnerResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$resp->HostName = $domain->GetHostName();
				$resp->Result = false;
				return $resp;
			}
		}
		else
			return new PollChangeDomainOwnerResponse(REGISTRY_RESPONSE_STATUS::FAILED, $RDResponse->ErrMsg, $RDResponse->Code);
	}
	
	/**
	 * @param Domain $domain
	 * @return DomainUpdatedResponse
	 */
	public function PollUpdateDomain (Domain $domain)
	{
		// Not used
		return;
	}
	
	/**
	 * Called by system when delete contact operation is pending
	 *
	 * @param Contact $contact
	 * @return PollDeleteContactResponse
	 */
	public function PollDeleteContact (Contact $contact)
	{
		// Not used
		return;
	}
	
	/**
	 * Called by system when delete namserver host operation is pending
	 *
	 * @param NamserverHost $nshost
	 * @return PollDeleteNamserverHostResponse
	 */
	public function PollDeleteNamserverHost (NamserverHost $nshost)
	{
		// Not used
		return;
	}	
	

	/*
	public function OnDomainTransferApproved (Domain $domain) 
	{
		// Update registrant data
		$DbDomain = DBDomain::GetInstance();
		$SavedDomain = $DbDomain->GetInitialState($domain);
		
		$Registrant = $domain->GetContact(CONTACT_TYPE::REGISTRANT);
		$SavedRegistrant = $SavedDomain->GetContact(CONTACT_TYPE::REGISTRANT);

		try
		{
			$Registrant->SetFieldList($SavedRegistrant->GetFieldList());
		}
		catch (ErrorList $e)
		{
			Log::Log(join(", ", $e->GetAllMessages()), E_USER_ERROR);
		}
		
		$Registrant->SetDiscloseList($SavedRegistrant->GetDiscloseList());
		$this->UpdateContact($Registrant);
	}
	*/

	/**
	 * Parse datetime description into a Unix timestamp Ignores timezone
	 */
	protected function StrToTime ($str)
	{
		return strtotime(substr($str, 0, -3));
	}

}


?>
