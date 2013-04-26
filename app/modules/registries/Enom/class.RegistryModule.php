<?php
	class EnomRegistryModule extends AbstractRegistryModule implements IRegistryModuleClientPollable 
	{
		private $contact_type_prefix_map = array(
			CONTACT_TYPE::ADMIN => 'Admin',
			CONTACT_TYPE::TECH => 'Tech',
			CONTACT_TYPE::BILLING => 'AuxBilling',
			CONTACT_TYPE::REGISTRANT => 'Registrant',			
		);

		private function PackContact (Contact $Contact, $as_type)
		{
			$std_fields = array(
				"FirstName", "LastName", "
				OrganizationName", "JobTitle", 
				"Address1", "Address2", 
				"City", "StateProvinceChoice", "StateProvince", 
				"PostalCode", "Country", "EmailAddress", "Phone", "Fax"
			);
			
			$prefix = $this->contact_type_prefix_map[$as_type];
			
			$data = array();
			foreach ($Contact->GetRegistryFormattedFieldList() as $fname => $fvalue)
			{
				$k = in_array($fname, $std_fields) ? "{$prefix}{$fname}" : $fname;
				$data[$k] = $fvalue;
			}
			return $data;
		}
		
		
		/**
		 * Called to validate either user filled all fields of your configuration form properly.
		 * If you return true, all configuration data will be saved in database. If you return array, user will be presented with values of this array as errors. 
		 *
		 * @param array $post_values
		 * @return True or array of error messages.
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
			$Form = new DataForm();
			$Form->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT, "API hostname", 1));
			$Form->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Reseller Login", 1));
			$Form->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT , "Reseller Password", 1));
			
			return $Form;
		}
		
		/**
		 * Must return current Registrar ID (CLID). Generally, you can return registrar login here.
		 * Used in transfer and some contact operations to determine either object belongs to current registrar.
		 *
		 * @return string
		 */
		public function GetRegistrarID()
		{
			return $this->Config->GetFieldByName('Login')->Value;
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
	    		'SLD' => $this->MakeNameIDNCompatible($domain->Name),
	    		'TLD' => $this->Extension,
	    		'DomainPassword' => $authCode
	    	);
	    	$Resp = $this->Request('SetPassword', $params);
	    	
			$status = $Resp->Succeed ? 
				REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			return new UpdateDomainAuthCodeResponse($status, $Resp->ErrMsg, $Resp->Code); 
		}
		
		/**
	     * Called to check either domain can be transferred at this time.
	     *
	     * @param Domain $domain
	     * @return DomainCanBeTransferredResponse
	     */
	    public function DomainCanBeTransferred(Domain $domain)
		{
			$params = array(
				'SLD' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD' => $this->Extension
			);
			$Resp = $this->Request('GetDomainStatus', $params);
			
			$ok = $Resp->Data->DomainStatus instanceof SimpleXMLElement;
			
			$Ret = new DomainCanBeTransferredResponse(
				$ok ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED,
				$Resp->ErrMsg,
				$Resp->Code
			);
			if ($ok)
			{
				$status = (string)$Resp->Data->DomainStatus->InAccount;
				$Ret->Result =
					// not in our database
					$status == '0' ||
					// in our database but in a different account than the one cited in this query 
					$status == '2';
			}
			else
			{
				$Ret->Result = false;
			}
			return $Ret;
		}
	    
	    
	    /**
	     * Send domain trade (change of the owner) request.
	     * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.  
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
			list($status, $errmsg, $code) = $this->SetDomainLock($domain, true);
			return new LockDomainResponse($status, $errmsg, $code);
		}
	    
	    /**
	     * Unlock Domain
	     *
	     * @param Domain $domain
	     * @param array $extra Some extra data
	     * @return UnLockDomainResponse
	     */
	    public function UnlockDomain(Domain $domain, $extra = array())
		{
			list($status, $errmsg, $code) = $this->SetDomainLock($domain, false);
			return new UnLockDomainResponse($status, $errmsg, $code);
		}
		
		private function SetDomainLock (Domain $domain, $lock)
		{
			$params = array(
				'SLD' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD' => $this->Extension,
				'UnlockRegistrar' => (int)(!$lock)
			);
			
			$Resp = $this->Request('SetRegLock', $params);
			
			// OK when operation executed or lock already exists
			$status = $Resp->Succeed || $Resp->Code == 540 ? 
				REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			return array($status, $Resp->ErrMsg, $Resp->Code);
		}
	    
	    /**
	     * Update domain flags.
	     * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollUpdateDomain().
	     *
	     * @param Domain $domain
	     * @param IChangelist $changes A list of changes in domain flags for the domain
	     * @return UpdateDomainFlagsResponse
	     */
	    public function UpdateDomainFlags(Domain $domain, IChangelist $changes)
		{
			throw new NotImplementedException();
		}
	    
	    /**
		 * Register domain.
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollCreateDomain().
		 *	 
		 * @param Domain $domain
		 * @param int $period Domain registration period
		 * @param array $extra Extra fields
		 * @return CreateDomainResponse
		 */
		public function CreateDomain(Domain $domain, $period, $extra = array())
		{
			$params = array(
				'SLD' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD' => $this->Extension,
				//'ExtendedAttributes' => '',
				'NumYears' => $period,
				'DomainPassword' => $domain->AuthCode ? $domain->AuthCode : rand(100000000, 9999999999)
			);
			
			// IDN tag
			$is_idna = $this->RegistryAccessible->IsIDNHostName($domain->Name);			
			if ($is_idna)
			{
				$params['IDNCode'] = $domain->IDNLanguage;
			}

			// NS
			$nameservers = $domain->GetNameserverList();			
			$n = 1;
			foreach ($nameservers as $ns)
			{
				$params['NS' . $n] = $ns->HostName;
				$n++;
			}
			
			// Contacts
			$contacts = $domain->GetContactList();
			foreach ($contacts as $t => $contact)
			{
				$params = array_merge($params, $this->PackContact($contact, $t));
			}
			
			// TLD specific extra fields
			$params = array_merge($params, $extra);
			
			
			// Request enom.com
			$Resp = $this->Request('Purchase', $params);
			
			if ($Resp->Code == 200)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			elseif ($Resp->Code == 1300 && strtolower($Resp->Data->IsRealTimeTLD) == 'false')
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
			
			$Ret = new CreateDomainResponse($status, $Resp->ErrMsg, $Resp->Code);
			
			if ($Resp->Succeed)
			{
				$domain->SetExtraField('OrderID', $Resp->Data->OrderID);
				
				$Ret->CreateDate = time();
				$Ret->ExpireDate = time()+86400*365*$period;
				
				$Ret->AuthCode = (string)$params['DomainPassword'];
			}
			
			return $Ret;
		}
		
		/**
		 * Obtain information about specific domain from registry   
		 * 
		 * @param Domain $domain 
		 * @return GetRemoteDomainResponse
		 */
		public function GetRemoteDomain(Domain $domain)
		{
			$params = array(
				'SLD' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD' => $this->Extension
			);
			
			$Resp = $this->Request('GetDomainInfo', $params);
			
			if (!$Resp->Succeed)
			{
				$Ret = new GetRemoteDomainResponse(REGISTRY_RESPONSE_STATUS::FAILED, $Resp->ErrMsg, $Resp->Code);
				return $Ret;
			}
			
			$Ret = new GetRemoteDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS, $Resp->ErrMsg, $Resp->Code);
			$Ret->CLID = (string)$Resp->Data->GetDomainInfo->status->{'belongs-to'};
			$Ret->CRID = (string)$Resp->Data->GetDomainInfo->status->{'belongs-to'};
			$Ret->ExpireDate = strtotime($Resp->Data->GetDomainInfo->status->{'expiration'});
			$Ret->RegistryStatus = (string)$Resp->Data->GetDomainInfo->status->{'registrationstatus'};
			

			$Resp = $this->Request('GetWhoisContact', $params);
			$cr_date = strtotime($Resp->Data->GetWhoisContacts->{'rrp-info'}->{'created-date'});
			# FIXME:  
			# GetWhoisContact is the only one method to get domain create date, 
			# and holy fuck!!! it doesn't returns it for .eu domain names:
			# "EURid Regulations have strict guidelines regarding the display of .EU Whois information. 
			# Please check www.whois.eu for domain information."
			# Set it to 1 year before expiration date, and collect bug reports.
			# Thank you Enom.
			$Ret->CreateDate = $cr_date ? $cr_date : strtotime("-1 year", $Ret->ExpireDate); 
			
			
			// Get Auth Code
			$Resp = $this->Request('GetPasswordBit', $params);
			$Ret->AuthCode = (string)$Resp->Data->DomainPassword;
			
			
			// Get DNS
			$Resp = $this->Request('GetDNS', $params);
			foreach ($Resp->Data->dns as $ns)
			{
				$list[] = new Nameserver((string)$ns);
			}
			$Ret->SetNameserverList($list);
			// TODO: process ns hosts
			
			// Get Lock
			$Resp = $this->Request('GetRegLock', $params);
			$Ret->IsLocked = (string)$Resp->Data->{'reg-lock'} == '1';
			
			// Get Contacts
			$Resp = $this->Request('GetContacts', $params);
			$Ret->RegistrantContact = (string)$Resp->Data->GetContacts->Registrant->RegistrantPartyID;
			
			if (($AuxBilling = $Resp->Data->GetContacts->AuxBilling))
			{
				$Ret->BillingContact = (string)$AuxBilling->AuxBillingPartyID;
			}
			if (($Tech = $Resp->Data->GetContacts->Tech))
			{
				$Ret->TechContact = (string)$Tech->TechPartyID;
			}
			if (($Admin = $Resp->Data->GetContacts->Admin))
			{
				$Ret->AdminContact = (string)$Admin->AdminPartyID;	
			}
			
			return $Ret;
		}
		
		/**
		 * Performs epp host:info command. Returns host IP address
		 *
		 * @return string
		 */
		/*
		public function GetHostIpAddress ($hostname)
		{
			$params = array(
				'CheckNSName' => $hostname
			);
			$response = $this->Request('CheckNSStatus', $params);
			if (!$response->Succeed)
				throw new Exception($response->ErrMsg);
				
			$result = $response->Data->response->resData->children(self::NAMESPACE);
			return (string)$result[0]->addr;
		}
		*/
		
		/**
		 * Swap domain's existing contact with another one
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollCreateDomain().
		 * 
		 * @param Domain $domain Domain
		 * @param string $contactType contact type. Should be one of CONTACT_TYPE members.
		 * @param Contact $oldContact Old contact or NULL
		 * @param Contact $newContact
		 * @return UpdateDomainContactResponse
		 */
		public function UpdateDomainContact(Domain $domain, $contactType, Contact $oldContact, Contact $newContact)
		{
			$params = $this->PackContact($newContact, $contactType);
			$params['ContactType'] = $this->contact_type_prefix_map[$contactType];
			$params['SLD'] = $this->MakeNameIDNCompatible($domain->Name);
			$params['TLD'] = $this->Extension;
		
			$Resp = $this->Request('Contacts', $params);

			$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$Ret = new UpdateDomainContactResponse($status, $Resp->ErrMsg, $Resp->Code);
			$Ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
			return $Ret;		
		}
		
		/**
		 * Change nameservers for specific domain 
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollUpdateDomain().
		 * 
		 * @param Domain $domain Domain
		 * @param IChangelist $changelist Changes in a list of nameservers 
		 * @return UpdateDomainNameserversResponse
		 */
		public function UpdateDomainNameservers(Domain $domain, IChangelist $changelist)
		{
			$nameservers = $changelist->GetList();
			if (!$nameservers)
			{
				throw new Exception(_("Enom can't assign empty list of nameservers to domain"));
			}
			
			$params = array(
				'SLD' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD' => $this->Extension
			);
			
			$n = 1;
			foreach ($nameservers as $nameserver)
			{
				if ($nameserver->HostName)
				{
					$params["NS{$n}"] = $nameserver->HostName;
					$n++;
				}
			}
			
			$Resp = $this->Request('ModifyNS', $params);
			
	    	$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
	    	$Ret = new UpdateDomainNameserversResponse($status, $Resp->ErrMsg, $Resp->Code);
	    	$Ret->Result = ($Resp->Code == 200);
			return $Ret;
		}	
		
		/**
		 * Called to check either domain can be registered
		 * 
		 * @param Domain $domain Domain
		 * @return DomainCanBeRegisteredResponse
		 */
		public function DomainCanBeRegistered(Domain $domain)
		{
			$params = array(
				'SLD' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD' => $this->Extension
			);
			
			$Resp = $this->Request('Check', $params);
			
	    	$Ret = new DomainCanBeRegisteredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS, $Resp->ErrMsg, $Resp->Code);
	    	$Ret->Result = ($Resp->Code == 210);
	    	$Ret->Reason = "{$Resp->ErrMsg}";
			return $Ret;
		}

	
		
		/**
		 * Completely delete domain from registry if it is delegated or  
		 * recall domain name application if it was not yet delegated.
		 * @param Domain $domain Domain
		 * @param int $executeDate Unix timestamp for scheduled delete. Null for immediate delete.
		 * @return DeleteDomainResponse
		 * @throws ProhibitedTransformException 
		 */
		public function DeleteDomain(Domain $domain, $executeDate=null)
		{
			$params = array(
				'SLD' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD' => $this->Extension,
				//'EndUserIP' => getenv('REMOTE_ADDR')
				//'EndUserIP' => '91.124.146.53' 
			);
			
			$Resp = $this->Request('DeleteRegistration', $params);
			
			$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$Ret = new DeleteDomainResponse($status, $Resp->ErrMsg, $Resp->Code);
			return $Ret;
		}
		
		/**
		 * Send renew domain request
		 *
		 * @param string $domain Domain
		 * @param array $extradata Extra fields
		 * @return RenewDomainResponse
		 */
		public function RenewDomain(Domain $domain, $extra=array())
		{
			$params = array(
				'SLD' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD' => $this->Extension,
				'NumYears' => $extra['period']
			);
			
			$Resp = $this->Request('Extend', $params);
			
			$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$Ret = new RenewDomainResponse($status, $Resp->ErrMsg, $Resp->Code);
			if ($Ret->Succeed())
			{
				$Ret->ExpireDate = strtotime("+{$extra['period']} year", $domain->ExpireDate);
			}
			
			return $Ret;
		}
	
		/**
		 * Send a request for domain transfer
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollTransfer().
		 * 
		 * @param string $domain Domain
		 * @param array $extradata Extra fields
		 * @return TransferRequestResponse
		 */	
		public function TransferRequest(Domain $domain, $extra=array())
		{
			$params = array(
				'DomainCount' => 1,
				'OrderType' => 'Autoverification',
				'SLD1' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD1' => $this->Extension,
				'AuthInfo1' => $extra['pw'],
				'UseContacts' => 0
				//'EndUserIP' => getenv('REMOTE_ADDR'),
			);
			
			$Resp = $this->Request('TP_CreateOrder', $params);
			
			$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$Ret = new TransferRequestResponse($status, $Resp->ErrMsg, $Resp->Code);
			$Ret->TransferID = (string)$Resp->Data->transferorder->transferorderid; 
			return $Ret;
		}
		
		/**
		 * Approve domain transfer
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
		 * Reject domain transfer
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
		 * Called to check either this nameserver is a valid nameserver.
		 * This method request registry for ability to create namserver
		 * 
		 * @param Nameserver $ns
		 * @return NameserverCanBeCreatedResponse
		 */
		public function NameserverCanBeCreated(Nameserver $ns)
		{
			$params = array(
				'CheckNSName' => $ns->HostName			
			);
			$Resp = $this->Request('CheckNSStatus', $params);
			
			$valid_codes = array(
				545, // RRP entity reference not found
				541	 // Parameter value range error;Host name does not exist.
			);
			
			$status = $Resp->Succeed || in_array($Resp->Code, $valid_codes) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$Ret = new NameserverCanBeCreatedResponse($status, $Resp->ErrMsg, $Resp->Code);
			$Ret->Result = in_array($Resp->Code, $valid_codes);
			return $Ret;
		}
		
		/**
		 * Create namserver
		 * 
		 * @param Nameserver $ns
		 * @return CreateNameserverResponse
		 */
		public function CreateNameserver (Nameserver $ns)
		{
			throw new NotImplementedException();
		}
		
		/**
		 * Create nameserver host (Nameserver derived from our own domain)
		 * 
		 * @param NameserverHost $nshost
		 * @return CreateNameserverHostResponse
		 */
		public function CreateNameserverHost (NameserverHost $nshost)
		{
			$params = array(
				'Add' => 'true',
				'NSName' => $nshost->HostName,
				'IP' => $nshost->IPAddr
			);
			
			$Resp = $this->Request('RegisterNameServer', $params);
			
	    	$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
	    	$Ret = new CreateNameserverHostResponse($status, $Resp->ErrMsg, $Resp->Code);
	    	$Ret->Result = ($Resp->Data->ErrCount == 0);
			return $Ret;
		}
		
		/**
		 * Update nameserver host
		 * 
		 * @param NameserverHost $ns
		 * @return UpdateNameserverHostResponse 
		 */
		public function UpdateNameserverHost(NameserverHost $ns)
		{
			$params = array(
				'CheckNSName' => $ns->HostName			
			);
			$Resp = $this->Request('CheckNSStatus', $params);
			if (!$Resp->Succeed)
			{
				return new UpdateNameserverHostResponse(
					REGISTRY_RESPONSE_STATUS::FAILED, 
					$Resp->ErrMsg, 
					$Resp->Code
				);
			}
			
			$old_ip_addr = (string)$Resp->Data->CheckNsStatus->ipaddress;
			$params = array(
				'NS' => $ns->HostName,
				'NewIP' => $ns->IPAddr,
				'OldIP' => $old_ip_addr
			);
			$Resp = $this->Request('UpdateNameServer', $params);
			
			$Ret = new UpdateNameserverHostResponse(
				$Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED,
				$Resp->ErrMsg,
				$Resp->Code
			);
			$Ret->Result = $Resp->Succeed && (int)$Resp->Data->RegisterNameserver->NsSuccess;
			return $Ret;
		}
		
		/**
		 * Delete namserver host from registry
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollDeleteNameserverHost().
		 * 
		 * @param NameserverHost $ns
		 * @return DeleteNameserverHostResponse 
		 * @throws ProhibitedTransformException 
		 */
		public function DeleteNameserverHost(NameserverHost $ns)
		{
			$params = array(
				'NS' => $ns->HostName
			);
			
			$Resp = $this->Request('DeleteNameServer', $params);
			
			$Ret = new DeleteNameserverHostResponse(
				$Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED,
				$Resp->ErrMsg,
				$Resp->Code
			);
			return $Ret;
		}
		
		/**
		 * Called to check either specific contact can be created 
		 * 
		 * @param Contact $contact
		 * @return ContactCanBeCreatedResponse 
		 */
		public function ContactCanBeCreated(Contact $contact)
		{
			$Ret = new ContactCanBeCreatedResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$Ret->Result = true;
			return $Ret;
		}
	
		/**
		 * Create contact
		 * 
		 * @param Contact $contact
		 * @return CreateContactResponse
		 */
		public function CreateContact(Contact $contact, $extra=array())
		{
			$params = $this->PackContact($contact, CONTACT_TYPE::REGISTRANT);
			$Resp = $this->Request('AddContact', $params);
			
			$status = $Resp->Succeed || $Resp->Data->Contact->RegistrantPartyID ? 
				REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$Ret = new CreateContactResponse($status, $Resp->ErrMsg, $Resp->Code);
			if ($Ret->Succeed())
			{
				$Ret->CLID = (string)$Resp->Data->Contact->RegistrantPartyID;
			}
			return $Ret;
		}

		/**
		 * Must return detailed information about contact from registry
		 * @access public
		 * @param Contact $contact
		 * @version GetRemoteContactResponse
		 */
		public function GetRemoteContact(Contact $contact)
		{
			//throw new NotImplementedException();
			
			$npages = null;
			$pagesize = 100;
			$page = 1;
			do
			{
				$params = array(
					'PageSize' => $pagesize,
					'Page' => $page
				);
				$Resp = $this->Request('GetAddressBook', $params);
				if (!$Resp->Succeed)
				{
					return new GetRemoteContactResponse(
						REGISTRY_RESPONSE_STATUS::FAILED,
						$Resp->ErrMsg,
						$Resp->Code
					);
				}
				
				if ($npages === null)
				{
					// Init npages var
					$npages = (int)$Resp->Data->AddressBook->TotalPages;
				}
				
				// Find for contact
				foreach ($Resp->Data->AddressBook->Address as $Address)
				{
					if ((string)$Address->PartyId == $contact->CLID)
					{
						// Found!
						
						// Grab contact data from xml
						$contact_data = array();
						foreach ($Address->children() as $XNode)
						{
							$node_name = $XNode->getName();
							$value = (string)$XNode;
							
							if ($node_name == 'Email')
							{
								$contact_data['EmailAddress'] = $value;
							}
							else
							{
								$contact_data[$node_name] = $value;
							}
						}
						
						// 
						$Ret = new GetRemoteContactResponse(
							REGISTRY_RESPONSE_STATUS::SUCCESS, '', 0
						);
						foreach ($contact_data as $k => $v)
						{
							$Ret->{$k} = $v;
						}
						return $Ret;
					}
				}
				
			}
			while (++$page <= $npages);
			
			return new GetRemoteContactResponse(
				REGISTRY_RESPONSE_STATUS::FAILED,
				'Contact not found',
				0
			);
		}
			
		/**
		 * Update contact fields
		 * 
		 * @param Contact $contact
		 * @return UpdateContactResponse
		 */
		public function UpdateContact(Contact $contact)
		{
			$params = $this->PackContact($contact, $contact->ExtraData['type']);
			$params['ContactType'] = $this->contact_type_prefix_map[$contact->ExtraData['type']];
			$params['SLD'] = $this->MakeNameIDNCompatible($contact->ExtraData['domainname']);
			$params['TLD'] = $this->Extension;
		
			$Resp = $this->Request('Contacts', $params);

			$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$Ret = new UpdateContactResponse($status, $Resp->ErrMsg, $Resp->Code);
			$Ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
			return $Ret;		
		}
	
		/**
		 * Delete contact
		 * 
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollDeleteContact().
		 *
		 * @param Contact $contact
		 * @param array $extra Extra fields
		 * @return DeleteContactResponse
		 * @throws ProhibitedTransformException
		 */
		public function DeleteContact(Contact $contact, $extra = array())
		{
			$params = array(
				'RegistrantPartyID' => $contact->CLID
			);
			
			$Resp = $this->Request('DeleteContact', $params);
			
			$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$Ret = new DeleteContactResponse($status, $Resp->ErrMsg, $Resp->Code);
			$Ret->Result = $Resp->Succeed;
			return $Ret;			
		}
		
		
		/**
		 * Called by the system if CreateDomainResponse->Status was REGISTRY_RESPONSE_STATUS::PENDING in IRegistryModule::CreateDomainResponse() 
		 * EPP-DRS calls this method to check the status of domain registration operation.
		 *  
		 * Must return one of the following:
		 * 1. An object of type DomainCreatedResponse if operation is completed 
		 * 2. PollCreateDomainResponse with Status set to REGISTRY_RESPONSE_STATUS::PENDING, if domain creation is still in progress
		 * 
		 * @param Domain $domain
		 * @return PollCreateDomainResponse
		 */
		public function PollCreateDomain (Domain $domain)
		{
			/*
			$params = array(
				'OrderID' => $domain->OrderID
			);
			$Resp = $this->Request('GetOrderDetail', $params);
			if ($Resp->Succeed)
			{
				
			}
			
			if (!$Resp->Succeed || strtolower($Resp->Data->Order->Result) == 'false')
			{
				$Ret = new PollCreateDomainResponse(REGISTRY_RESPONSE_STATUS::FAILED, $Resp->ErrMsg, $Resp->Code);
				$Ret->HostName = $domain->GetHostName();
				return $Ret;
			}
			else
			{
				
			}
			*/
		}
		
		
		/**
		 * EPP-DRS calls this method to check the status of DeleteDomain() operation.
		 *  
		 * Must return one of the following:
		 * 1. An object of type DeleteDomainResponse if operation is completed. 
		 * 2. DeleteDomainResponse with Status set to REGISTRY_RESPONSE_STATUS::PENDING, if domain creation is still in progress
		 *  
		 * @param Domain $domain
		 * @return PollDeleteDomainResponse
		 */
		public function PollDeleteDomain (Domain $domain)
		{
			
		}
		
		/**
		 * Called by system when change domain owner operation is pending.
		 * Must return valid DomainOwnerChangedResponse if operatation is completed, 
		 * or response with Status = REGISTRY_RESPONSE_STATUS::PENDING if operation is still in progress
		 * 
		 * @param Domain $domain
		 * @return PollChangeDomainOwnerResponse
		 */
		public function PollChangeDomainOwner (Domain $domain)
		{
			
		}
	
		/**
		 * Transfer status constants
		 * 
		 * @see http://resellertest.enom.com/resellers/APICommandCatalog.pdf#TP_GetOrder
		 */
		const TRANSFERSTATUS_CANCELLED = 2;
		const TRANSFERSTATUS_COMPLETE = 3;
		
		/**
		 * Called by system when domain transfer operation is pending.
		 * Must return valid PollDomainTransfered on operatation is completed, 
		 * or response with Status = REGISTRY_RESPONSE_STATUS::PENDING if operation is still in progress
		 * 
		 * @param Domain $domain
		 * @return PollTransferResponse
		 */
		public function PollTransfer (Domain $domain)
		{
			$params = array(
				'SLD' => $this->MakeNameIDNCompatible($domain->Name),
				'TLD' => $this->Extension,
				'TransferOrderID' => $domain->TransferID
			);
			$Resp = $this->Request('TP_GetOrder', $params);
			
			if ($Resp->Succeed)
			{
				$statusid = (int)$Resp->Data->transferorder->statusid;
				if ($statusid == self::TRANSFERSTATUS_COMPLETE)
				{
					$tstatus = TRANSFER_STATUS::APPROVED;
				}
				else if ($statusid == self::TRANSFERSTATUS_CANCELLED)
				{
					$tstatus = TRANSFER_STATUS::DECLINED;
				}
				else
				{
					$tstatus = TRANSFER_STATUS::PENDING;
				}
				
				$Ret = new PollTransferResponse(
					$tstatus != TRANSFER_STATUS::PENDING ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::PENDING, 
					$Resp->ErrMsg, 
					$Resp->Code
				);
				$Ret->HostName = $domain->GetHostName();
				$Ret->TransferStatus = $tstatus;
				return $Ret;
			}
			else
			{
				return new PollTransferResponse(
					REGISTRY_RESPONSE_STATUS::FAILED, 
					$Resp->ErrMsg,
					$Resp->Code
				);
			}
		}
		
		/**
		 * Called by system when update domain operation is pending.
		 * Must return valid DomainUpdatedResponse on operatation is completed, 
		 * or response with Status = REGISTRY_RESPONSE_STATUS::PENDING if update is still in progress
		 * 
		 * @param Domain $domain
		 * @return PollUpdateDomainResponse
		 */
		public function PollUpdateDomain (Domain $domain)
		{
			
		}
		
		/**
		 * Called by system when delete contact operation is pending
		 *
		 * @param Contact $contact
		 * @return PollDeleteContactResponse
		 */
		public function PollDeleteContact (Contact $contact)
		{
			
		}
		
		/**
		 * Called by system when delete nameserver host operation is pending
		 *
		 * @param NamserverHost $nshost
		 * @return PollDeleteNamserverHostResponse
		 */
		public function PollDeleteNamserverHost (NamserverHost $nshost)
		{
			
		}		
	}
?>