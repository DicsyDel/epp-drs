<?php

/**
 * Interface for registry module. 
 * 
 * Each registry module must implement IRegistryModule and one of poll interfaces
 * IRegistryModuleServerPollable or IRegistryModuleClientPollable 
 */
class OnlineNICRegistryModule extends GenericEPPRegistryModule implements IRegistryModule
{
	protected $XmlNamespaces = array(
   		'contact' 	=> 'urn:iana:xml:ns:contact-1.0',
   		'domain' 	=> 'urn:iana:xml:ns:domain-1.0',
   		'host' 		=> 'urn:iana:xml:ns:host-1.0',
   		'svcsub' 	=> 'urn:iana:xml:ns:svcsub-1.0'	
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
	
    /**
     * Must return a DataForm object that will be used to draw a configuration form for this module.
     * @return DataForm object
     */
	public static function GetConfigurationForm()
	{
		$ConfigurationForm = new DataForm();
		$ConfigurationForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT, "EPP Host", 1));
		$ConfigurationForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "EPP Login", 1));
		$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT , "EPP Password", 1));
		$ConfigurationForm->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "EPP port", 1));	
		
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
	
	protected function BeforeRequest ($command, &$data, $method /** args */)
	{
		if ($command == "contact-create")
		{
			$data['type'] = $this->GetDomainType();	
			
			if ($this->Extension == "asia")
			{
				$ext = <<<ASIA
<extension>
  <asia:create xmlns:asia='urn:afilias:params:xml:ns:asia-1.0'
     xsi:schemaLocation='urn:afilias:params:xml:ns:asia-1.0 asia-1.0.xsd'>
     <asia:cedData>
       <asia:ccLocality>{$data["asia-ext-locality-cc"]}</asia:ccLocality>
       <asia:localitySp>{$data["asia-ext-locality-sp"]}</asia:localitySp>
       <asia:localityCity>{$data["asia-ext-locality-city"]}</asia:localityCity>
       <asia:legalEntityType>{$data["asia-ext-le-type"]}</asia:legalEntityType>
       <asia:identForm>{$data["asia-ext-identform"]}</asia:identForm>
       <asia:identNumber>{$data["asia-ext-ident-number"]}</asia:identNumber>
       <asia:otherLEType>{$data["asia-ext-other-le-type"]}</asia:otherLEType>
       <asia:otherIdentForm>{$data["asia-ext-other-identform"]}</asia:otherIdentForm>
     </asia:cedData>
  </asia:create>
</extension>
				
ASIA;
			}
			elseif ($this->Extension == "us")
			{
				$ext = <<<US
<unspec>AppPurpose={$data["us-ext-app-purpose"]} NexusCategory={$data["us-ext-nexus-category"]}</unspec>
US;
			}
			else
			{
				$ext = '';
			}
			$data["ext"] = $ext;
		}
	}
	
	/**
     * Checks transfer opportunity for domain
     *
     * @param Domain $domain
     * @return DomainCanBeTransferredResponse
     */
    public function DomainCanBeTransferred(Domain $domain) 
    {
    	$R = $this->DomainCanBeRegistered($domain);
    	if (!$R->Succeed())
    		return new DomainCanBeTransferredResponse($R->Status, $R->ErrMsg, $R->Code);
    		
    	$ret = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	$ret->Result = !$R->Result;
    	return $ret; 
    }
    
    
    /**
     * This method send domain trade request (Change owner).
     * In order to pending domain trade, response must have status REGISTRY_RESPONSE_STATUS::PENDING
     * 
     * @param Domain $domain Domain must have contacts and nameservers 
     * @param integer $period Domain delegation period
     * @param array $extra Some registry specific fields 
     * @return ChangeDomainOwnerResponse
     */
    public function ChangeDomainOwner(Domain $domain, $period=null, $extra=array()) 
    {
		throw new NotImplementedException();    	
    }
        
    /**
     * Lock Domain
     *
     * @param Domain $domain
     * @param array $extra Some registry specific fields 
     * @return LockDomainResponse
     */
    public function LockDomain(Domain $domain, $extra = array()) 
    {
		$operation = '<domain:add><domain:status s="clientTransferProhibited"/></domain:add>';
		
		$params = array(
			'name' => $domain->GetHostName(),
			'type' => $this->GetDomainType(),
			'operation' => $operation
		);
		
		$response = $this->Request('domain-lock', $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new LockDomainResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $response->Succeed;
		return $ret;
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
    	throw new NotImplementedException();
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
		$operation = '<domain:rem><domain:status s="clientTransferProhibited"/></domain:rem>';
		
		$params = array(
			'name' => $domain->GetHostName(),
			'type' => $this->GetDomainType(),
			'operation' => $operation
		);
		
		$response = $this->Request('domain-lock', $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new UnLockDomainResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $response->Succeed;
		return $ret;
    }
    
    /**
     * This method request registry to update domain flags (ex: clientUpdateProhibited, clientDeleteProhibited)
     * In order to pending flags update, response must have status REGISTRY_RESPONSE_STATUS::PENDING 
     *
     * @param Domain $domain
     * @param IChangelist $changes flags changes
     * @return UpdateDomainFlagsResponse
     */
    public function UpdateDomainFlags(Domain $domain, IChangelist $changes)
    {
		throw new NotImplementedException();     	
    }
    
    
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
			$resp->CLID = (string)$response->Data->response->resData->creData->id;
			$resp->AuthCode = $cd["pw"];
		}
		
		return $resp;
	}    
    
    
    /**
	 * This method send create domain request.
	 * In order to pending domain creation, response must have status REGISTRY_RESPONSE_STATUS::PENDING
	 *	 
	 * @param Domain $domain
	 * @param int $period Domain registration period
	 * @param array $extra Extra data
	 * @return CreateDomainResponse
	 */
	public function CreateDomain(Domain $domain, $period, $extra = array()) 
	{
		$Registrant = $domain->GetContact(CONTACT_TYPE::REGISTRANT);
		$Tech = $domain->GetContact(CONTACT_TYPE::TECH);
		$Admin = $domain->GetContact(CONTACT_TYPE::ADMIN);
		$Billing = $domain->GetContact(CONTACT_TYPE::BILLING);
		$nslist = $domain->GetNameserverList();
		
		$params = array(
			'name' 		=> $domain->GetHostName(),
			'type' 		=> $this->GetDomainType(),
			'registrant'=> $Registrant->CLID,
			'tech' 		=> $Tech->CLID,
			'admin' 	=> $Admin->CLID,
			'billing' 	=> $Billing->CLID,
			'ns1' 		=> $nslist[0]->HostName,
			'ns2' 		=> $nslist[1]->HostName,
			'period' 	=> $period,
			'pw' 		=> $domain->AuthCode ? $domain->AuthCode : rand(100000, 99999999)
		);
		
		$response = $this->Request('domain-create', $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new CreateDomainResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			$ret->CreateDate = time();
			$ret->ExpireDate = strtotime("+{$period} year");
			$ret->AuthCode = (string)$params['pw'];
		}
		
		return $ret;
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
			'name' => $domain->GetHostName(),
			'clID' => $this->Config->GetFieldByName('Login')->Value,
			'type' => $this->GetDomainType(),
		);
		
		$response = $this->Request('domain-info', $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new GetRemoteDomainResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			$info = $response->Data->response->resData;
			
			// nameservers
			$nslist = array(
				new Nameserver(trim((string)$info->domain_creData->dns1)),
				new Nameserver(trim((string)$info->domain_creData->dns2))				
			);
			$ret->SetNameserverList($nslist);
			
			$ret->CRID = $this->Config->GetFieldByName('Login')->Value;
			$ret->CLID = $this->Config->GetFieldByName('Login')->Value;
			$ret->CreateDate = strtotime($info->domain_creData->regdate);
			$ret->ExpireDate = strtotime($info->domain_creData->expdate);
			$ret->RegistryStatus = 'ok';
		}
		
		return $ret;
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
		throw new NotImplementedException();	
	}
	
	/**
	 * This method request registry to change namservers list for domain
	 * In order to pending operation, response must have status REGISTRY_RESPONSE_STATUS::PENDING 
	 * 
	 * @param Domain $domain Domain
	 * @param IChangelist $changelist nameservers changelist 
	 * @return UpdateDomainNameserversResponse
	 */
	public function UpdateDomainNameservers(Domain $domain, IChangelist $changelist) 
	{
		$nshost_list = $changelist->GetList();
		if (sizeof($nshost_list) != 2)
			throw new Exception(_('Domain must have 2 namservers'));
			
		$params = array(
			'name' 	=> $domain->GetHostName(),
			'type' 	=> $this->GetDomainType(),
			'ns1' 	=> strtolower($nshost_list[0]->HostName),
			'ns2' 	=> strtolower($nshost_list[1]->HostName),
			'pw' 	=> $domain->AuthCode 
		);
		
		$response = $this->Request('domain-update-ns', $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new UpdateDomainNameserversResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $response->Succeed;
		return $ret;
	}	
	
	/**
	 * This method request registry for ability to register domain
	 * 
	 * @param Domain $domain Domain
	 * @return DomainCanBeRegisteredResponse
	 */
	public function DomainCanBeRegistered(Domain $domain) 
	{
		$params = array(
			'name' => $domain->GetHostName(),
			'type' => $this->GetDomainType()
		);
		
		$response = $this->Request('domain-check', $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new DomainCanBeRegisteredResponse($status, $response->ErrMsg, $response->Code);
		if ($response->Succeed)
		{
			$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
			$ret->Result = $info[0]->cd[0]->attributes()->x == '-';		
		}
		else
			$ret->Result = false;
			
		return $ret;
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
			'name' => $domain->GetHostName(),
			'type' => $this->GetDomainType()
		);
		
		$response = $this->Request('domain-delete', $params);
		
		$status = ($response->Succeed || $response->Code == RFC3730_RESULT_CODE::ERR_OBJECT_NOT_EXISTS) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new DeleteDomainResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $status == REGISTRY_RESPONSE_STATUS::SUCCESS;  
		return $ret; 
	}
	
	/**
	 * This method request registry to renew domain
	 *
	 * @param string $domain Domain
	 * @param array $extradata Extra fields
	 * @return RenewDomainResponse
	 */
	public function RenewDomain(Domain $domain, $extra=array()) 
	{
		$params = array(
			'name' => $domain->GetHostName(),
			'type' => $this->GetDomainType(),
			'period' => $extra['period']
		);
		
		$response = $this->Request('domain-renew', $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$ret = new RenewDomainResponse($status, $response->ErrMsg, $response->Code);
		if ($response->Succeed)
		{
			$grd = $this->GetRemoteDomain($domain);
			$ret->ExpireDate = $grd->ExpireDate;
		}
		return $ret;
	}

	/**
	 * This method request registry to transfer domain 
	 * In order to pending operation, response must have status REGISTRY_RESPONSE_STATUS::PENDING 
	 *
	 * @param string $domain Domain
	 * @param array $extradata Extra fields
	 * @return TransferRequestResponse
	 */	
	public function TransferRequest(Domain $domain, $extra=array()) 
	{
		$params = array(
			'name' => $domain->GetHostName(),
			'pw' => $this->EscapeXML($extra["pw"]),
			'type' => $this->GetDomainType(),
		);
		
		$response = $this->Request('domain-trans-request', $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new TransferRequestResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $response->Succeed;
		return $ret;		
	}
	
	/**
	 * This method request registry to approve domain transfer
	 * In order to pending operation, response must have status REGISTRY_RESPONSE_STATUS::PENDING
	 *
	 * @param string $domain Domain
	 * @param array $extradata Extra fields
	 * @return TransferApproveResponse
	 */
	public function TransferApprove(Domain $domain, $extra=array()) 
	{
		throw new NotImplementedException();		
	}
	
	/**
	 * This method request registry to reject domain transfer
	 * In order to pending operation, response must have status REGISTRY_RESPONSE_STATUS::PENDING 
	 *
	 * @param string $domain Domain
	 * @param array $extradata Extra fields
	 * @return TransferRejectResponse
	 */
	public function TransferReject(Domain $domain, $extra=array()) 
	{
		throw new NotImplementedException();
	}
	
	/**
	 * This method request registry for ability to create namserver
	 * 
	 * @param Nameserver $ns
	 * @return NameserverCanBeCreatedResponse
	 */
	public function NameserverCanBeCreated(Nameserver $ns) 
	{
		throw new NotImplementedException();
	}
	
	/**
	 * This method request registry to create namserver
	 * 
	 * @param Nameserver $ns
	 * @return CreateNameserverResponse
	 */
	public function CreateNameserver (Nameserver $ns) 
	{
		/*
		$ipaddr = gethostbyname($ns->HostName);
		if ($ipaddr == $ns->HostName)
			throw new Exception(sprintf(_('Can\'t resolve %s IP address'), $ns->HostName));
		
		$nshost = new NameserverHost($ns->HostName, $ipaddr);
		$r = $this->CreateNameserverHost($nshost);
		*/
		
		//return new CreateNameserverResponse($r->Status, $r->ErrMsg, $r->Code);
		
		throw new NotImplementedException();
	}
	
	/**
	 * This method request registry to create namserver host
	 * 
	 * @param NameserverHost $nshost
	 * @return CreateNameserverHostResponse
	 */
	public function CreateNameserverHost (NameserverHost $nshost) 
	{
		$params = array(
			'host' => $nshost->HostName,
			'ip1' => $nshost->IPAddr,
			'type' => $this->GetDomainType()
		);
		
		$response = $this->Request('host-create', $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new CreateNameserverHostResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $response->Succeed;
		return $ret;
	}
	
	private $OldIPAddr = null;
	
	/**
	 * This method request registry to create update namserver host
	 * 
	 * @param NameserverHost $ns
	 * @return UpdateNameserverHostResponse 
	 */
	public function UpdateNameserverHost(NameserverHost $ns) 
	{
		$ip_old = gethostbyname($ns->HostName);
		
		$params = array(
			'host' => $ns->HostName,
			'ip_new' => $ns->IPAddr,
			'ip_old' => $this->OldIPAddr,
			'type' => $this->GetDomainType()
		);
		$this->OldIPAddr = null;
		
		$response = $this->Request('host-update', $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new UpdateNameserverHostResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $response->Succeed;
		return $ret;		
	}
	
	public function OnBeforeUpdateNameserverHost (NameserverHost $newNSHost, NameserverHost $oldNSHost)
	{
		$this->OldIPAddr = $oldNSHost->IPAddr;
	}
	
	/**
	 * This method request registry to delete namserver host
	 * 
	 * @param NameserverHost $ns
	 * @return DeleteNameserverHostResponse 
	 */
	public function DeleteNameserverHost(NameserverHost $ns) 
	{
		$params = array(
			'host' => $ns->HostName,
			'type' => $this->GetDomainType()
		);
		
		$response = $this->Request('host-delete', $params);

		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new DeleteNameserverHostResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $response->Succeed;
		return $ret;		
	}
	
	/**
	 * This method request registry for ability to create contact
	 * 
	 * @param Contact $contact
	 * @return ContactCanBeCreatedResponse 
	 */
	public function ContactCanBeCreated(Contact $contact) 
	{
		throw new NotImplementedException();
	}
	
	/**
	 * This method request registry for information about contact
	 * @access public
	 * @param Contact $contact
	 * @version GetRemoteContactResponse
	 */
	public function GetRemoteContact(Contact $contact) 
	{
		throw new NotImplementedException();
	}
		
	/**
	 * This method request registry to update contact
	 * 
	 * @param Contact $contact
	 * @return UpdateContactResponse
	 */
	public function UpdateContact(Contact $contact) 
	{
		$params = $contact->GetRegistryFormattedFieldList();
		
		$map = array(
			CONTACT_TYPE::ADMIN => 1,
			CONTACT_TYPE::TECH => 2,
			CONTACT_TYPE::BILLING => 3,
			CONTACT_TYPE::REGISTRANT => 4,			
		);
		$params['contacttype'] = $map[$contact->ExtraData['type']];
		$params['domainname'] = "{$contact->ExtraData['domainname']}.{$this->Extension}";
		$params['type'] = $this->GetDomainType();
		$params['pw'] = $contact->AuthCode;
		
		$response = $this->Request('contact-update', $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new UpdateContactResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
		return $ret;		
	}

	/**
	 * This method request registry to delete contact
	 *
	 * @param Contact $contact
	 * @param array $extra Extra fields
	 * @return DeleteContactResponse
	 */
	public function DeleteContact(Contact $contact, $extra = array()) 
	{
		throw new NotImplementedException();
	}
	
	private static $ExtensionTypeMap = array(
		'com' 	=> 0,
		'net' 	=> 0,
		'org' 	=> 0,
		'cn' 	=> 220,
		'ws' 	=> 302,
		'tv' 	=> 400,
		'cc' 	=> 600,
		'biz' 	=> 800,
		'info' 	=> 805,
		'us' 	=> 806,
		'in' 	=> 808,
		'eu' 	=> 902,
		'mobi' 	=> 903,
		'asia'  => 905,
		'me'	=> 906
	);
	
	private function GetDomainType ()
	{
		return self::$ExtensionTypeMap[$this->Extension];
	}
}


?>
