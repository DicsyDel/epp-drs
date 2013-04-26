<?php

class RRPProxyRegistryModule extends AbstractRegistryModule implements IRegistryModuleClientPollable 
{		
	
	private $PromotionCodes = array();
	
	public function InitializeModule($extension, DataForm $config)
	{
		parent::InitializeModule($extension, $config);
		
		$promo_code_lines = array_filter(array_map("trim", 
				explode("\n", $this->Config->GetFieldByName("PromotionCodes")->Value)));
		if ($promo_code_lines)
		{
			foreach ($promo_code_lines as $promo_code_line)
			{
				list($tld, $code) = explode(" ", $promo_code_line, 2);
				$this->PromotionCodes[trim($tld)] = trim($code);
			}
		}
	}
	
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
		$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
		$ConfigurationForm->AppendField( new DataFormField("APIUrl", FORM_FIELD_TYPE::TEXT , "API url", 1));
		$ConfigurationForm->AppendField( new DataFormField("Timeout", FORM_FIELD_TYPE::TEXT , "Timeout", 1));
		$ConfigurationForm->AppendField( new DataFormField("PromotionCodes", FORM_FIELD_TYPE::TEXTAREA , "Promotion codes", 0, array(), null, null, 
			"You may have a promotion code for several TLD registrations.<br>Enter TLD and it's promotion code separated by space (one pair per line).<br>
			<br><b>Example:</b><br>
			<pre>info infopromo711
tel telpromoX65</pre>"));
		
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
     * @return bool
     * @version v1000
     */
    public function DomainCanBeTransferred(Domain $domain)
    {
    	$resp = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	
    	$CanRegisteredResponse = $this->DomainCanBeRegistered($domain);
    	if ($CanRegisteredResponse->Result == false)
    	{
    		$resp->Result = true;
    	}
    	else
    	{   	
	    	try
	    	{
	    		$GetRemoteDomainResponse = $this->GetRemoteDomain($domain);
	    		$resp->Result = false;
	    	}
	    	catch(ObjectNotExistsException $e)
	    	{
	    		$resp->Result = true;
	    	}
    	}
    	
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
		$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
		$params["auth"] = $authCode;
		
		$response = $this->Request("ModifyDomain", $params);
		
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
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
    	
    	$contacts = $domain->ListContactCLIDs();
    	$params["ownercontact0"] = $contacts['registrant'];

    	if ($this->Extension == "it")
    	{
    		// Handle .IT trade policy    		
    		$params["X-IT-SECT3-LIABILITY"] = 1;
    		$params["X-IT-SECT5-PERSONAL-DATA-FOR-REGISTRATION"] = 1;
    		$params["X-IT-SECT6-PERSONAL-DATA-FOR-DIFFUSION"] = 1;
    		$params["X-IT-SECT7-EXPLICIT-ACCEPTANCE"] = 1;
    	}
    	
		$response = $this->Request("TradeDomain", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
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
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName()); 
    	$params["transferlock"] = 1;
		$response = $this->Request("ModifyDomain", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new LockDomainResponse($status, $response->ErrMsg, $response->Code);
    }
    
    public function UnlockDomain (Domain $domain, $extra = array())
    {
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
    	$params["transferlock"] = 0;
		$response = $this->Request("ModifyDomain", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new UnlockDomainResponse($status, $response->ErrMsg, $response->Code);
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
    	// Not Implemented on RRPProxy
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
	 */
	public function CreateDomain(Domain $domain, $period, $extra = array())
	{
		$contacts = $domain->GetContactList();
		
		if (!$contacts['registrant']) 
			throw new Exception(_('Registrant contact is  undefined'));
		
		if (!$contacts['admin']) 
			throw new Exception(_('Admin contact is  undefined'));
			
		if (!$contacts['tech']) 
			throw new Exception(_('Tech contact is  undefined'));
			
		if (!$contacts['billing']) 
			throw new Exception(_('Billing contact is  undefined'));
			
		$nameservers = $domain->GetNameserverList();
			
		$params = array(
			"ownercontact0"		=> $contacts['registrant']->CLID,
			"techcontact0"		=> ($contacts['tech']) ? $contacts['tech']->CLID : "",
			"admincontact0"		=> ($contacts['admin']) ? $contacts['admin']->CLID : "",
			"billingcontact0"	=> ($contacts['billing']) ? $contacts['billing']->CLID : "",
			"nameserver0"		=> $nameservers[0]->HostName,
			"nameserver1"		=> $nameservers[1]->HostName,
			"period"			=> $period,
			"auth"				=> $this->GeneratePassword(),
			"domain"			=> $this->MakeNameIDNCompatible($domain->GetHostName()) 
		);
		$params = array_merge($params, (array)$extra);
		if ($this->PromotionCodes[$this->Extension])
			$params["X-PROMOTION-CODE"] = $this->PromotionCodes[$this->Extension];
		
		$response = $this->Request("AddDomain", $params);	
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$resp = new CreateDomainResponse($status, $response->ErrMsg, $response->Code);
		
		if (!$resp->IsFailed())
		{
			// Match domain expiration date
			preg_match("/property\[registration expiration date\]\[0\][\s]*=[\s]*(.*?)\n/si", $response->Data, $matches);
			$time = strtotime($matches[1]);
			$resp->ExpireDate = $time;
			
			// Set create date as NOW
			$resp->CreateDate = time();
			
			// Auth code
			$resp->AuthCode = (string)$params["auth"];
			
			// Match domain renewal date
			// (Date when RRPPROXY will execute renewal cron if TLD supports auto renewal)
			$autorenew = (int)$this->Manifest->GetRegistryOptions()->ability->auto_renewal;
			if ($autorenew)
			{
				preg_match("/property\[renewal date\]\[0\][\s]*=[\s]*(.*?)\n/si", $response->Data, $matches);
				$domain->SetExtraField('RenewalDate', strtotime($matches[1]));
			}
			
			// Set renewal mode
			$mode = $autorenew ? 'AUTORENEW' : 'AUTOEXPIRE';
			$this->Request('SetDomainRenewalMode', array(
				'domain' => $params['domain'],
				'renewalmode' => $mode
			));
		}
		
		return $resp;
	}
	
	/**
	 * Return information about domain
	 * 
	 * @access public
	 * @param Domain $domain 
	 * @return Domain Domain info if the following format:
	 */
	public function GetRemoteDomain(Domain $domain)
	{
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName()); 
		$response = $this->Request("StatusDomain", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$resp = new GetRemoteDomainResponse($status, $response->ErrMsg, $response->Code);

		if ($response->Succeed)
		{
			mparse_str(str_replace("\n", "&", $response->Data), $parsed_response);
			$data = array();
			foreach($parsed_response["property"] as $fname=>$fvalue)
			{
				switch($fname)
				{
					case "owner contact":
						$resp->RegistrantContact = trim($fvalue[0]); 
					break;
					
					case "admin contact":
						$resp->AdminContact = trim($fvalue[0]); 
					break;
					
					case "tech contact":
						$resp->TechContact = trim($fvalue[0]); 
					break;
					
					case "billing contact":
						$resp->BillingContact = trim($fvalue[0]); 
					break;
					
					case "auth":
						$resp->AuthCode = trim($fvalue[0]);
					break;
					
					case "registrar":
						$resp->CLID = trim($fvalue[0]); 
						break;
						
					case "created by":
						$resp->CRID = trim($fvalue[0]);
						break;
						
					case "created date":
						$resp->CreateDate = strtotime(trim($fvalue[0]));
						break;
						
					case "registration expiration date":
						$resp->ExpireDate = strtotime(trim($fvalue[0]));
						break;
						
					case "nameserver":
						
						foreach($fvalue as $ns)
							$ns_arr[] = new Nameserver(trim(strtolower($ns)));
						
						$resp->SetNameserverList($ns_arr);
						
						break;
						
					case "status":
						$resp->RegistryStatus = trim($fvalue[0]);
						break;
				}
			}
			
			////
			// Process extra fields:
			//
			
			// Collect extra fields
			$domainConfig = $this->Manifest->GetDomainConfig();
			$extraFieldNames = array();
			if (count($domainConfig->extra_fields))
			{
				foreach ($domainConfig->extra_fields->field as $field)
					$extraFieldNames[] = strtoupper($field->attributes()->name);
			}
			
			// Iterate over response properties and fill extra fields
			$extraFields = array();
			foreach ($parsed_response["property"] as $fname=>$fvalue)
			{
				$fname = strtoupper($fname);
				if (in_array($fname, $extraFieldNames))
				{
					$extraFields[$fname] = trim($fvalue[0]);
				}
			}
			
			$resp->SetExtraData($extraFields);
			
			$resp->RawResponse = $parsed_response;
		}

		return $resp;
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
		switch($contactType)
		{
			case CONTACT_TYPE::REGISTRANT:
				$params["addownercontact0"] = $newContact->CLID;
				if ($oldContact) 
					$params["delownercontact0"] = $oldContact->CLID;
				break;
				
			case CONTACT_TYPE::ADMIN:
				$params["addadmincontact0"] = $newContact->CLID;
				if ($oldContact)
					$params["deladmincontact0"] = $oldContact->CLID;
				break;
				
			case CONTACT_TYPE::BILLING:
				$params["addbillingcontact0"] = $newContact->CLID;
				if ($oldContact)
					$params["delbillingcontact0"] = $oldContact->CLID;
				break;
				
			case CONTACT_TYPE::TECH:
				$params["addtechcontact0"] = $newContact->CLID;
				if ($oldContact)
					$params["deltechcontact0"] = $oldContact->CLID;
				break;
		}
			
		$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
		$response = $this->Request("ModifyDomain", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new UpdateDomainContactResponse($status, $response->ErrMsg, $response->Code);
	}
	
	public function UpdateContactsAfterTransfer (Domain $Domain, Domain $Saved)
	{
		$domainName = $this->MakeNameIDNCompatible($Saved->GetHostName());
		// Flags, indicates that saved contact will be assigned to transferred domain.
		$assignSavedContacts = array();
		foreach (CONTACT_TYPE::GetKeys() as $ctype)
			$assignSavedContacts[$ctype] = 1;
				
		if (in_array($this->Extension, array("be", "eu", "it", "nl")))
		{
			$GrdResponse = $this->GetRemoteDomain($Domain);
			
			foreach (CONTACT_TYPE::GetKeys() as $ctype)
			{
				$clid_property = ucfirst($ctype)."Contact"; // ex: RegistrantContact
				$clid = $GrdResponse->{$clid_property};
				
				// clid started from domain TLD means native registry contact, 
				// which is unoperational by rrpproxy api. 
				// ex: be.tr55322
				if (preg_match("/^{$this->Extension}/", $clid)) 
				{
					// Create dummy contact with non-strict fields and assign it to domain.
					$Contact = $this->RegistryAccessible->NewContactInstance($ctype);
					$Contact->CLID = $clid;
					$Contact->SetFieldList(array("firstname" => $clid), 0);
					
					$Domain->SetContact($Contact, $ctype);
				}
				
				// Контакты заданные при инициации трансфера не применяются к домену.
				$assignSavedContacts[$ctype] = 0;				
				
				
				// Возможна ситуация когда среди нативных контактов попадаются rrpproxy-вые,
				// они импортируются в БД ЕПП-ДРС.
				// ex: 
				//   property[billing contact][0] = eu.c89406
				//   property[owner contact][0] = eu.c10720031
				//   property[admin contact][0] = eu.c10720031
				//   property[tech contact][0] = P-JZW723
				// P-JZW723 будет запрошен операцией GetRemoteContact аплевел кодом;
				// для остальных контактов будут созданы заглушки. 
			}
		}
		else if ("nu" == $this->Extension)
		{
			// In .NU change registrant is a special operation that requires fax sending.
			// Sync registrant contact, update admin, tech, billing
			try 
			{
				$Registrant = $Domain->GetContact(CONTACT_TYPE::REGISTRANT);
				$Registrant = $this->RegistryAccessible->GetRemoteContact($Registrant);
				$Domain->SetContact($Registrant, CONTACT_TYPE::REGISTRANT);
				
				$assignSavedContacts[CONTACT_TYPE::REGISTRANT] = 0;
			} 
			catch (Exception $e) 
			{
				throw new Exception("Cannot synchronize registrant contact with registry. {$e->getMessage()}");
			}
			
			$params = array
			(
				'domain' => $domainName,			
			);
			foreach (array("admin", "billing", "tech") as $ctype)
			{
				$params["{$ctype}contact0"] = $Saved->GetContact($ctype)->CLID;				
			}
			$response = $this->Request("ModifyDomain", $params);

		}
		else
		{
			// Default behaviour:
			// Set new contacts in an atomic operation
			$params = array
			(
				'domain' => $domainName,
				'ownercontact0' => $Saved->GetContact(CONTACT_TYPE::REGISTRANT)->CLID
			);
			foreach (array("admin", "billing", "tech") as $ctype)
			{
				$params["{$ctype}contact0"] = $Saved->GetContact($ctype)->CLID;				
			}
			$response = $this->Request("ModifyDomain", $params);		
		}
		
		// Set updated contacts to domain.
		foreach ($Saved->GetContactList() as $ctype => $Contact)
		{
			if ($assignSavedContacts[$ctype])
				$Domain->SetContact($Contact, $ctype);	
		}
	}
	
	/**
	 * Update nameservers for domain
	 * @access public
	 * @param Domain $domain Domain
	 * @param IChangelist $changelist nameservers changelist
	 * @return bool 
	 */
	public function UpdateDomainNameservers(Domain $domain, IChangelist $changelist)
	{
		$nameservers = $changelist->GetList();
		
		foreach ((array)$changelist->GetAdded() as $k=>$v)
			$params["addnameserver{$k}"] = $v->HostName;
		
		foreach ((array)$changelist->GetRemoved() as $k=>$v)
			$params["delnameserver{$k}"] = $v->HostName;
		
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
    	
		$response = $this->Request("ModifyDomain", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new UpdateDomainNameserversResponse($status, $response->ErrMsg, $response->Code);
	}	
	
	/**
	 * Check domain availability
	 * @access public
	 * @param Domain $domain Domain
	 * @return bool true when domain available for registration
	 */
	public function DomainCanBeRegistered(Domain $domain)
	{
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());

    	$response = $this->Request("CheckDomain", $params);
	
		$resp = new DomainCanBeRegisteredResponse( REGISTRY_RESPONSE_STATUS::SUCCESS, $response->ErrMsg, $response->Code);
		$resp->Result = ($response->Code == 210) ? true : false;
		$resp->Reason = $response->ErrMsg;
		
		return $resp;
	}
	
	/**
	 * Delete domain name
	 *
	 * @param Domain $domain Domain name without TLD
	 * @param int $executeDate Valid timestamp for scheduled delete. Null for immediately delete
	 */
	public function DeleteDomain(Domain $domain, $executeDate=null)
	{
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
		$response = $this->Request("DeleteDomain", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
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
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
		$params["period"] = is_array($extra["period"]) ? $extra["period"][0] : $extra["period"];
		$params["expiration"] = date("Y", $domain->ExpireDate);
		
		$response = $this->Request("RenewDomain", $params);

		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp =  new RenewDomainResponse($status, $response->ErrMsg, $response->Code);
		if ($resp->Succeed())
		{
			preg_match("/property\[registration expiration date\]\[0\][\s]*=[\s]*(.*?)\n/si", $response->Data, $matches);
			$resp->ExpireDate = strtotime($matches[1]);
		}
		else
		{
			$options = $this->Manifest->GetRegistryOptions();
			if ((int)$options->ability->auto_renewal && 
				(preg_match('/explicit renewals not allowed for this TLD/', $resp->ErrMsg) ||
				preg_match('/Invalid command name/', $resp->ErrMsg) ||
				preg_match('/Invalid attribute value/', $resp->ErrMsg)))
			{
				$this->SetRenewalMode($domain, "AUTORENEW");
				$resp = new RenewDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$resp->ExpireDate = strtotime("+{$extra["period"]} year", $domain->ExpireDate);
			}
		}
				
		return $resp;
	}
	
	public function GetRenewalMode(Domain $domain)
	{
		
	}
	
	public function SetRenewalMode(Domain $domain, $renewalmode)
	{
		$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
		$params["renewalmode"] = strtoupper($renewalmode);
		$resp = $this->Request("SetDomainRenewalMode", $params);
		if (!$resp->Succeed)
		{
			throw new RegistryException($resp->ErrMsg, $resp->Code);
		}
	}
	
	public function OnDomainRenewed (Domain $domain)
	{
		if ((int)$this->Manifest->GetRegistryOptions()->ability->auto_renewal)
		{
			$domain->SetExtraField('RenewalDate', strtotime("+1 year", $domain->RenewalDate));
		}
	} 

	/**
	 * Request domain transfer
	 *
	 * @param string $domain Domain name without TLD
	 * @param array $extradata Extra fields
	 * @return bool Transaction status
	 */	
	public function TransferRequest(Domain $domain, $extra=array())
	{
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
		$params["action"] = "REQUEST";
		$params["auth"] = $extra["pw"];
		
		if ($domain->Extension == "eu") 
		{
			$params["techcontact0"] = $extra["tech"];
			$params["nameserver0"] = $extra["ns1"];
			$params["nameserver1"] = $extra["ns2"];
		}

		$response = $this->Request("TransferDomain", $params);
		if ($response->Code == 552 && strpos("usertransfer", $response->ErrMsg) != -1)
		{
			$params["action"] = "USERTRANSFER";
			$response = $this->Request("TransferDomain", $params);
		}
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new TransferRequestResponse($status, $response->ErrMsg, $response->Code);
	}
	
	/**
	 * Send domain transfer approval
	 *
	 * @param string $domain Domain name without TLD
	 * @param array $extradata Extra fields
	 * @return bool True on success of false on failure
	 */
	public function TransferApprove(Domain $domain, $extra=array())
	{
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
		$params["action"] = "APPROVE";
		$params = array_merge($params, $extra);
		
		$response = $this->Request("TransferDomain", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new TransferApproveResponse($status, $response->ErrMsg, $response->Code);
	}
	
	/**
	 * Send domain transfer rejection
	 *
	 * @param string $domain Domain name without TLD
	 * @param array $extradata Extra fields
	 * @return bool True on success of false on failure
	 */
	public function TransferReject(Domain $domain, $extra=array())
	{
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
		$params["action"] = "DENY";
		$params = array_merge($params, $extra);
		
		$response = $this->Request("TransferDomain", $params);
		
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
	 */
	public function NameserverCanBeCreated(Nameserver $nshost)
	{
		$response = $this->Request("CheckNameserver", array("nameserver" => $nshost->HostName));
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp = new NameserverCanBeCreatedResponse($status, $response->ErrMsg, $response->Code);
		
		$resp->Result = ($response->Code == 212) ? true : false;
		
		return $resp;
	}
	
	/**
	 * Create nameserver
	 * @access public
	 * @param string $host Hostname
	 * @return Nameserver
	 */
	public function CreateNameserver (Nameserver $ns)
	{
		throw new NotImplementedException();
	}
	
	/**
	 * Create ns host
	 * 
	 * @return NameserverHost
	 */
	public function CreateNameserverHost (NameserverHost $nshost)
	{
		$params = array(
			'nameserver' => $nshost->HostName,
			'ipaddress0' => $nshost->IPAddr
		);
				
		$response = $this->Request("AddNameserver", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new CreateNameserverHostResponse($status, $response->ErrMsg, $response->Code);
	}
	
	/**
	 * Update nameserver host. update IP
	 * @access public
	 * @param Host $host Host
	 * @param string $newIP New IP address
	 * @return bool true on success of false on failure
	 */
	public function UpdateNameserverHost(NameserverHost $nshost)
	{
		$params = array(
			'nameserver' => $nshost->HostName,
			'ipaddress0' => $nshost->IPAddr
		);
		
		$response = $this->Request("ModifyNameserver", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new UpdateNameserverHostResponse($status, $response->ErrMsg, $response->Code);
	}
	
	/**
	 * Delete nameserver host
	 * @access public
	 * @param Host $host Host
	 * @return bool true on success of false on failure
	 */
	public function DeleteNameserverHost(NameserverHost $nshost)
	{
		$params = array(
			'nameserver' => $nshost->HostName
		);
		
		$response = $this->Request("DeleteNameserver", $params);
		
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
		$response = $this->Request("CheckContact", array("contact" => $contact->CLID));
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp = new ContactCanBeCreatedResponse($status, $response->ErrMsg, $response->Code);
		
		$resp->Result = ($response->Code == 214) ? true : false;
		
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
		$response = $this->Request("AddContact", $contact->GetRegistryFormattedFieldList());

		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp = new CreateContactResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			preg_match("/property\[contact\]\[0\][\s]*=[\s]*(.*?)\n/si", $response->Data, $matches);
			$resp->CLID = (string)$matches[1];
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
		$response = $this->Request("StatusContact", array("contact" => $contact->CLID));
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp = new GetRemoteContactResponse($status, $response->ErrMsg, $response->Code);
		
		preg_match_all("/property\[(.*?)\]\[0\][\s]*=[\s]*(.*?)\n/", $response->Data, $matches);
		$data = array();
		
		$exclude_fields = array("createdby", "createddate", "updatedby", "updateddate", "autodelete");
		
		foreach($matches[1] as $i=>$field)
		{
			$field_name = str_replace(" ", "", $field);
			if (!in_array($feild_name, $exclude_fields))
				$resp->{$field_name} = $matches[2][$i];
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
		$params["contact"] = $contact->CLID;
		
		$response = $this->Request("ModifyContact", $params);		
		
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
		$response = $this->Request("DeleteContact", array("contact" => $contact->CLID));
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		return new DeleteContactResponse($status, $response->ErrMsg, $response->Code);
	}
	
	/**
	 * @param Domain $domain
	 * @return PollTransferResponse 
	 */
	public function PollTransfer (Domain $domain)
	{
    	$params["domain"] = $this->MakeNameIDNCompatible($domain->GetHostName());
		$response = $this->Request("StatusDomainTransfer", $params);

		if ($response->Succeed)
		{
			mparse_str(str_replace("\n", "&", $response->Data), $resp);
			$tstatus = trim($resp["property"]["transfer status"][0]);
	
			if (!$tstatus)
			{
				if (stripos($response->Data, "[SUCCESSFUL]") !== false)
				{
					$tstatus = TRANSFER_STATUS::APPROVED;
				}
				elseif (stripos($response->Data, "[FAILED]") !== false)
				{
					$tstatus = TRANSFER_STATUS::DECLINED;
				}
				else
					$tstatus = TRANSFER_STATUS::PENDING;
			}
			else
			{
				if ($tstatus == "SUCCESSFUL")
					$tstatus = TRANSFER_STATUS::APPROVED;
				elseif ($tstatus == "FAILED")
					$tstatus = TRANSFER_STATUS::DECLINED;
				else
					$tstatus = TRANSFER_STATUS::PENDING;
			}
			
			$resp = new PollTransferResponse($status, $response->ErrMsg, $response->Code); 
			$resp->HostName = $domain->GetHostName();
			$resp->TransferStatus = $tstatus;
			return $resp;
		}
		else
			throw new Exception(sprintf(_("StatusDomainTransfer failed: %s"), $response->ErrMsg));
	}
	 
	/**
	 * @param Domain $domain
	 * @return DomainCreatedResponse
	 */
	public function PollCreateDomain (Domain $domain)
	{
		// Not used
		return;
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
		// Not used
		return;
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
	
	/**
	 * Enter description here...
	 *
	 * @param Domain $domain
	 */
	public function OnDomainCreated(Domain $domain)
	{
		if ($domain->Extension == "de")
		{
			$admin_contact = $domain->GetContact(CONTACT_TYPE::ADMIN)->CLID;

			$resp = $this->GetRemoteDomain($domain);
			if ($resp->AdminContact != $admin_contact)
			{
				$contact = $this->RegistryAccessible->NewContactInstance(CONTACT_TYPE::ADMIN);
				$contact->CLID = $resp->AdminContact;				
				$contact->UserID = 0;
				$contact->ID = null;
				$contact->SetFieldList(array("firstname" => "Preassigned", "lastname" => "RRPProxy contact"), false);
				$domain->SetContact($contact, CONTACT_TYPE::ADMIN);
			}
		}
	}
	
	public function OnDomainTransferApproved (Domain $domain)
	{
		if ($domain->Extension == "be")
		{
			$this->UpdateDomainNameservers($domain, new Changelist($domain->GetNameserverList()));
		}
		
		$autorenew = (int)$this->Manifest->GetRegistryOptions()->ability->auto_renewal;
		$mode = $autorenew ? 'AUTORENEW' : 'AUTOEXPIRE';
		$this->Request('SetDomainRenewalMode', array(
			'domain' => $domain->GetHostName(),
			'renewalmode' => $mode
		));
	}
}

?>
