<?php

/**
 *
 * LICENSE
 *
 * 
 *
 * @copyright  Copyright (c) syrex.co.za
 * 
 * Debugging help:   Log::Log('Put a comment here and show content of $variable: '.json_encode($variable), E_USER_NOTICE);
 * 
 */


class EPPZARegistryModule extends GenericEPPRegistryModule implements IRegistryModuleServerPollable
{
	const NAMESERVERS_COUNT = 2;
	const HOST_NAMESPACE = 'urn:ietf:params:xml:ns:host-1.0';

	const ZACR_COZAD_EXTENSION = 'http://co.za/epp/extensions/cozadomain-1-0';
	const ZACR_MSG_TYPE_TRN = 'trnData';
	const ZACR_MSG_TYPE_PAN = 'panData';
	const ZACR_MSG_TYPE_REN = 'renData';

	private $skipHostCheck = true;

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
		$ConfigurationForm->AppendField( new DataFormField("UseSSLCert", FORM_FIELD_TYPE::CHECKBOX , "Use SSL Certificate", 1, "Our authenticaton to ZACR."));
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



	public function UpdateDomainFlags(Domain $domain, IChangelist $changes)
	{
		 
		$params = array (
				"name"   => $this->MakeNameIDNCompatible($domain->GetHostName())
		);
		 
		$params['add'] = '';
		$params['rem'] = '';
		$params['auto_renew'] = '';

		if ($changes->GetAdded())
		{
			$flags_added = $changes->GetAdded();
			if(in_array('autoRenew', $flags_added)) {
				$params['auto_renew'] = '<cozadomain:autorenew>true</cozadomain:autorenew>';
					
			}

			//ZACR supports only clientHold flag. So remove evrything else.
			foreach($flags_added as $key=>$val) {
				if($val != 'clientHold')
					unset($flags_added[$key]);
			}

			if(count($flags_added) > 0)
				$params['add'] = '<domain:add>' . $this->GetFlagsXML($flags_added) . '</domain:add>';

		}
		 

		if ($changes->GetRemoved())
		{
			$flags_removed = $changes->GetRemoved();
			if(in_array('autoRenew', $flags_removed)) {
				$params['auto_renew'] = '<cozadomain:autorenew>false</cozadomain:autorenew>';
			}

			//ZACR supports only clientHold flag. So remove evrything else.
			foreach($flags_removed as $key=>$val) {
				if($val != 'clientHold')
					unset($flags_removed[$key]);
			}

			if (count($flags_removed) > 0)
				$params['rem'] = '<domain:rem>' . $this->GetFlagsXML($flags_removed) . '</domain:rem>';

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
			'serverHold',
			'pendingDelete',
			'serverDeleteProhibited',
			'serverTransferProhibited',
			'serverUpdateProhibited'
	);



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
				"name"		=> $this->ConvertToFullDomainName($domain->Name),
				"new_id"	=> $owner->CLID,
				"request"	=> $extra["requesttype"]
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
		 
		$params = array(
				"name"		=> $this->ConvertToFullDomainName($domain->Name)
		);
		 
		$response = $this->Request("domain-lock", $params);
		 
		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			$status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Code == RFC3730_RESULT_CODE::OK)
		$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;
		 
		return new LockDomainResponse($status, $response->ErrMsg, $response->Code);
		 
		//throw new NotImplementedException();
	}

	public function UnlockDomain (Domain $domain, $extra = array())
	{
		 
		$params = array(
				"name"		=> $this->ConvertToFullDomainName($domain->Name)
		);

		$response = $this->Request("domain-unlock", $params);

		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			$status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Code == RFC3730_RESULT_CODE::OK)
		$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;

		return new UnlockDomainResponse($status, $response->ErrMsg, $response->Code);
		//throw new NotImplementedException();
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
				"name"				=> $this->ConvertToFullDomainName($domain->Name),
				"registrant_id"			=> $contact_list[CONTACT_TYPE::REGISTRANT]->CLID,
				"ns1"				=> $nameserver_list[0]->HostName,
				"ns2"				=> $nameserver_list[1]->HostName,
				"y"				=> $period,
				"pw"				=> "coza",	// rand(100000000, 999999999), //ZACR currently requires a static password
				"auto_renew"			=> "true"
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
				"name"	=> $this->ConvertToFullDomainName($domain->Name),
				"pw"	=> $pw
		));

		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$resp = new GetRemoteDomainResponse($status, $response->ErrMsg, $response->Code);

		if ($response->Succeed)
		{
			$eppTags = $response->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
			$info = $eppTags[0];
				
			$resp->CLID = (string)$info->clID[0];
				
			try
			{
				$resp->CRID = (string)$info->crID[0];
			}
			catch(Exception $e){
			}
				
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


				//Added to take care of NS passed within hostAttr tags
				foreach ($info->ns->hostAttr as $v)
				{
					$hostname = (string)$v->hostName;
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


				//Status flags

				$flags = array(	);

				foreach($info->status as $status) {
					$attrs = $status->attributes();
					if(isset($attrs['s']))
						$flags[] = (string)$attrs['s'];
				}


				//Check for auto renew flag separately. ZACR sends autorenew status inside COZAD extension
				$extCoza = $response->Data->response->extension->children(self::ZACR_COZAD_EXTENSION);
				if(isset($extCoza->infData->autorenew) && (string)$extCoza->infData->autorenew == 'true') {
					$flags[] = 'autoRenew';
				}

					
				$resp->SetFlagList($flags);

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
				"name" 		=> $domain->GetHostName(),
				"add"	=> "",
				"rem"	=>	"",
				"reg_contact" => ""
		);

		$params['reg_contact'] = '';
		if($contactType == 'registrant') {
				
			if($newContact) {
				$params['reg_contact'] = "<domain:registrant>{$newContact->CLID}</domain:registrant>";
			}
				

		} else {
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
		}
			
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
	/*public function UpdateDomainNameservers(Domain $domain, IChangelist $changelist)
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
	}*/
	function UpdateDomainNameservers(Domain $domain, IChangelist $changelist)
	{
		$info = $this->GetRemoteDomain($domain);
		if ($info->Succeed())
		{
			$added = $changelist->GetAdded();
			$removed = $changelist->GetRemoved();
			foreach ($added as $i => $ns)
			{
				$added[$i] = new NameserverHost($ns->HostName,
						$ns->IPAddr ? $ns->IPAddr : gethostbyname($ns->HostName));
			}
			foreach ($removed as $i => $ns)
			{
				foreach ($info->GetNameserverList() as $curr_ns)
				{
					if ($curr_ns->HostName == $ns->HostName)
					{
						$removed[$i] = new NameserverHost($ns->HostName,
								$curr_ns->IPAddr ? $curr_ns->IPAddr : gethostbyname($ns->HostName));
					}
				}
			}

			$changelist = new Changelist($removed);
			foreach ($added as $ns)
				$changelist->Add($ns);
			foreach ($removed as $ns)
				$changelist->Remove($ns);


			return parent::UpdateDomainNameservers($domain, $changelist);
		}
		else
		{
			return new UpdateDomainNameserversResponse($info->Status, $info->ErrMsg, $info->Code);
		}
	}

	private function DoUpdateDomainNameservers (Domain $domain, $added=array(), $removed=array())
	{

		$params = array(
				"name"	=> $this->ConvertToFullDomainName($domain->Name),
				"add" 	=> "",
				"del" 	=> ""
		);

		if ($added)
			$params['add'] = "<domain:add><domain:ns>".$this->GetNSXMLForNSUpdate($added)."</domain:ns></domain:add>";
			
		if ($removed)
			$params['del'] = "<domain:rem><domain:ns>".$this->GetNSXMLForNSUpdate($removed)."</domain:ns></domain:rem>";

		return $this->Request("domain-update-ns", $params);
	}

	protected function GetNSXMLForNSUpdate($nslist)
	{
		$ret = '';
		foreach ($nslist as $ns)
			//$ret .= "<domain:hostObj>{$ns->HostName}</domain:hostObj>\n";
			$ret .= "<domain:hostAttr><domain:hostName>{$ns->HostName}</domain:hostName></domain:hostAttr>\n";

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
				'name' => $this->ConvertToFullDomainName($domain->Name),
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
				'name'	=> $this->ConvertToFullDomainName($domain->Name),
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
					'name' => $this->ConvertToFullDomainName($domain->Name),
					'protocol' => $domain->Protocol
			);

			$response = $this->Request("domain-uncreate", $params);
		}
		$status = 0;

		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			$status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Succeed)
		$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		elseif ($response->Code == RFC3730_RESULT_CODE::ERR_OBJECT_NOT_EXISTS)
		$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;

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
				"name"		=> $this->ConvertToFullDomainName($domain->Name),
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
				"name"		=> $this->ConvertToFullDomainName($domain->Name),
				"pw"   		=> $this->EscapeXML($extra["pw"]),
				"new_pw"	=> $this->EscapeXML($new_pw),
				"registrant"	=> $registrant_clid
		);

		$response = $this->Request("domain-trans-request", $params);
		if($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			$status = REGISTRY_RESPONSE_STATUS::PENDING;
		else if($response->Succeed)
			$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;


		$domain->AuthCode = $new_pw;

		return new TransferRequestResponse($status, $response->ErrMsg, $response->Code);
	}

	function OnDomainTransferRequested (Domain $domain)
	{
		$ops = $this->RegistryAccessible->GetPendingOperationList(Registry::OBJ_DOMAIN, $domain->ID);
		foreach ($ops as $op) {
			if ($op->Type == Registry::OP_TRANSFER) {
				$resp = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$resp->HostName = $domain->GetHostName();
				$resp->TransferStatus = TRANSFER_STATUS::PENDING;
				$this->RegistryAccessible->DispatchPollTransfer($resp);

				$this->RegistryAccessible->RemovePendingOperation($op->ID);
				break;
			}
		}
	}
	
	
	public function OnDomainCreated (Domain $domain){
		try
		{
				
			$domain = $this->RegistryAccessible->GetRemoteDomain($domain);
			$domain->RenewDisabled = !($domain->HasFlag('autoRenew'));
			DBDomain::GetInstance()->Save($domain);
		}
		catch(Exception $e)
		{
			$errmsg = $e->getMessage();
		}
	}

	function OnDomainUpdated (Domain $domain) {
		$ops = $this->RegistryAccessible->GetPendingOperationList(Registry::OBJ_DOMAIN, $domain->ID);
		foreach ($ops as $op) {
			if ($op->Type == Registry::OP_TRADE) {
				$resp = new PollChangeDomainOwnerResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$resp->HostName = $domain->GetHostName();
				$resp->Result = true;

				$this->RegistryAccessible->DispatchPollChangeDomainOwner($resp);

				$this->RegistryAccessible->RemovePendingOperation($op->ID);
				break;
			}
		}
		$domain->RenewDisabled = !($domain->HasFlag('autoRenew'));
		DBDomain::GetInstance()->Save($domain);
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
				"name"		=> $this->ConvertToFullDomainName($domain->Name),
				"pw"		=> $this->EscapeXML($domain->AuthCode)
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
				"name"		=> $this->ConvertToFullDomainName($domain->Name),
				"pw"		=> $this->EscapeXML($domain->AuthCode)
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

		if($this->skipHostCheck)
			$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$resp = new NameserverCanBeCreatedResponse($status, $response->ErrMsg, $response->Code);

		if ($response->Succeed)
		{
			$result = $response->Data->response->resData->children("urn:ietf:params:xml:ns:host-1.0");
			$attr = $result[0]->cd[0]->name[0]->attributes();
				
			if($this->skipHostCheck)
				$resp->Result =  true;
			else
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
		throw new NotImplementedException();

			
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
		$domainName = $this->ExtractDomainFromHostName($nshost->HostName);
		$hostName = $this->ExtractHostFromHostName($nshost->HostName);
		$hostName = $hostName.'.'.$this->ConvertToFullDomainName($domainName);
		$params = array(
				'name' => $this->ConvertToFullDomainName($domainName),
				'host' => $nshost->HostName,//$hostName,
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
		$domainName = $this->ExtractDomainFromHostName($ns->HostName);
		if ($ipaddr != $ns->IPAddr)
		{
			// Update it
			$params = array(
					'name' => $this->ConvertToFullDomainName($domainName),
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

		//Extract the base host name.
		$domainName = $this->ExtractDomainFromHostName($ns->HostName);
		$hostName = $this->ExtractHostFromHostName($ns->HostName);
		$hostName = $hostName.'.'.$this->ConvertToFullDomainName($domainName);
		$params = array(
				'name' => $this->ConvertToFullDomainName($domainName),
				'host' => $hostName

		);

		$response = $this->Request("host-delete", $params);

		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		return new CreateNameserverHostResponse($status, $response->ErrMsg, $response->Code);

		/*
		 $params = array(
		 		'hostname' => $ns->HostName
		 );

		$response = $this->Request("host-delete", $params);
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		return new DeleteNameserverHostResponse($status, $response->ErrMsg, $response->Code);*/
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

		//New code for supporting ZACR contact extensions (domain list and checking balance)
		/*Currently commented out becuase of insufficient data/info
		 *
		* $params['domain_list'] = isset($contact->checkBalance) && $contact->checkBalance ? '<cozacontact:domainListing>true</cozacontact:domainListing>' : '';
		* $params['domain_list'] = isset($contact->listDomains) && $contact->listDomains ? '<cozacontact:balance>true</cozacontact:balance>' : '';
		*
		*/

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

		$status =  REGISTRY_RESPONSE_STATUS::FAILED;
		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			$status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Succeed)
		$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;

		return new UpdateContactResponse($status, $response->ErrMsg, $response->Code);
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

		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			$status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Code == RFC3730_RESULT_CODE::OK)
			$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;
				
		return new DeleteContactResponse($status, $response->ErrMsg, $response->Code);
	}

	public function ReadMessage ()
	{
		$Resp = $this->Request('poll-request', array());
		$responseObj =null;
		if ($Resp->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE)
		{
			$msgID = (string)$Resp->Data->response->msgQ->attributes()->id;

			$resData = $Resp->Data->response->resData;

			if ($resData)
			{
				$subject = '';
				try {
					$domData = $resData->children($this->XmlNamespaces['domain']);
					if($domData[0] == null) {
						$domData = $resData->children($this->XmlNamespaces['contact']);
						if($domData[0] != null) {
							$subject = 'contact';
						}
					} else {
						$subject = 'domain';
					}
						
				} catch(Exception $e) {
					return false;
				}

				if($subject == 'contact') {
					$responseObj = $this->HandleContactMessage($Resp->Data->response);
				}
				elseif ($subject == 'domain') {
					$domData = $domData[0];
					$msgType = (string)$domData->getName();

					switch($msgType) {

						case self::ZACR_MSG_TYPE_PAN:
							$msgTxt = (string)$Resp->Data->response->msgQ->msg;
							
							if(strpos($msgTxt, ' transfer ') !== FALSE) {
								
								$responseObj = $this->HandleTranferMessage($Resp->Data->response);
								break;
							}
							$responseObj = $this->HandleGeneralMessage($Resp->Data->response);
							break;
						case self::ZACR_MSG_TYPE_TRN:
							$responseObj = $this->HandleTranferMessage($Resp->Data->response);
							break;
						case self::ZACR_MSG_TYPE_REN:
							$responseObj = $this->HandleAutoRenewMessage($Resp->Data->response);
							break;

					}
				}

			}
			if($responseObj == null) {
				$responseObj = new PendingOperationResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			}
				
				
			$responseObj->MsgID = $msgID;
			$responseObj->RawResponse = $Resp->Data;
				
			return $responseObj;
		}

		return false;
	}

	/**
	 * 
	 * Handle messages related to Contacts
	 * 
	 * @param SimpleXmlElement $response
	 * @return PollUpdateContactResponse|NULL
	 * 
	 * @author Alwin Tom
	 */
	protected function HandleContactMessage($response) {

		$resData = $response->resData;
		$domData = $resData->children($this->XmlNamespaces['contact']);

		if(strpos($response->msgQ->msg, ' Update ') !== FALSE) {	//Update operation.
			$resp = new PollUpdateContactResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$resp->CLID = (string)$domData->panData->id;
			if((string)$domData->panData->id->attributes()['paResult'] == '1')
				$resp->Result = true;
			else
				$resp->Result = false;
			return $resp;
		}
		return null;
	}

	/**
	 * Handles general domain update messages.
	 * 
	 * @param SimpleXmlElement $response
	 * @return Ambigous <NULL, PollUpdateDomainResponse, PollDeleteDomainResponse>
	 * 
	 * @author Alwin Tom
	 */
	protected function HandleGeneralMessage($response) {
		$resData = $response->resData;
		$domData = $resData->children($this->XmlNamespaces['domain']);

		$domData = $domData[0];

		$ret = null;

		//Read the msg tag and find out what the message is about.
		$msgTxt = (string)$response->msgQ->msg;

		if (strpos($msgTxt, ' update ') !== FALSE) {				//UPDATE
			//Domain Update

			$ret = new PollUpdateDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$ret->HostName = (string)$domData->name;
				
			$ret->Result = true;

		} else if (strpos($msgTxt, ' Deletion ') !== FALSE ||
				strpos($msgTxt, ' Release ') !== FALSE) {//DELETE
			$ret = new PollDeleteDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$ret->HostName = (string)$domData->name;
			$ret->Result = true;
		} else if (strpos($msgTxt, ' canceled ') !== FALSE) {	//UPDATE canceled
			//Dont know what to do here. Better ignore it as we do not have an operationresponse class for this case.
				
		} else if (strpos($msgTxt, ' Nameserver failure ') !== FALSE) {	//name server update failed.
			$ret = new PollUpdateDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$ret->HostName = (string)$domData->name;
			$ret->Result = false;
		}
		return $ret;

	}

	/**
	 * Handles domain transfer messages.
	 * 
	 * @param SimpleXmlElement $response
	 * @return Ambigous <NULL, PollTransferResponse>|Ambigous <NULL, PollOutgoingTransferResponse, PollTransferResponse>
	 * 
	 * @author Alwin Tom
	 */
	protected function HandleTranferMessage($response) {
		
		$resData = $response->resData;
		$domData = $resData->children($this->XmlNamespaces['domain']);

		$domData = $domData[0];
		$ret = null;

		

		/* Special case. data is panData but indicates transfer request failure.*/
		
		if((string)$domData->getName() == self::ZACR_MSG_TYPE_PAN) {
			
			if((string)$domData->name->attributes()['paResult'] == '0') {
				$ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$ret->TransferStatus = TRANSFER_STATUS::DECLINED;
				$ret->HostName = (string)$domData->name;
				
				return $ret;
			}
				
		}
		
		$trStatus = (string)$domData->trStatus;
		//Four possibilities:
		//1. Outgoing transfer request (status will be pending)
		//2. Outgoing transfer complete (status will be clientApproved)	//msg will contain ' transfered away'
		//3. Incoming transfer complete (status will be clientApproved) //msg will contain ' transfer successful'
		//4. Incoming trsnafer rejected (status will be clientRejected)
		if ($trStatus == 'pending') {
			$ret = new PollOutgoingTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$ret->TransferStatus = OUTGOING_TRANSFER_STATUS::REQUESTED;
			$ret->HostName = (string)$domData->name;
				
		} else if($trStatus == 'clientApproved') {
			$msgTxt = (string)$response->msgQ->msg;
			if (strpos($msgTxt, ' transferred away') !== FALSE) {
				//Outgoing transfer

				/*Emulate a Tranfered away event to clear the notification (only if domain is still in outgoing_transfer state.*/
				$dName = $this->ExtractDomainFromHostName((string)$domData->name);
				$thisDomain = DBDomain::GetInstance()->LoadByName($dName, $this->Extension, $this->RegistryAccessible->GetManifest());

				if($thisDomain && $thisDomain->OutgoingTransferStatus)
					$this->EmulateOutgoingTransferApprove ((string)$domData->name);

				$ret = new PollOutgoingTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$ret->TransferStatus = OUTGOING_TRANSFER_STATUS::AWAY;
				$ret->HostName = (string)$domData->name;
			} else  {
				//Gained a domain
				$ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$ret->TransferStatus = TRANSFER_STATUS::APPROVED;
				$ret->HostName = (string)$domData->name;
			}
				
		} else if($trStatus == 'clientRejected' || $trStatus == 'serverCancelled') {
			//Transfer request rejected.
			$ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$ret->TransferStatus = TRANSFER_STATUS::DECLINED;
			$ret->HostName = (string)$domData->name;
		}
		return $ret;
	}

	/**
	 * 
	 * Handle auto renew message
	 * 
	 * @param SimpleXmlElement $response
	 * @return Ambigous <NULL, PollUpdateDomainResponse>
	 * 
	 * @author Alwin Tom
	 */
	protected function HandleAutoRenewMessage($response) {
		$resData = $response->resData;
		$domData = $resData->children($this->XmlNamespaces['domain']);

		$renData = $domData[0];
		$panData = $domData[1];

		$ret = null;

		//Find what the message is about.
		$msgTxt = (string)$response->msgQ->msg;

		if((string)$panData->name->attributes()['paResult'] == '1'){
			$ret = new PollUpdateDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$ret->HostName = (string)$renData->name;
				
			$paData = $panData->paTRID->children($this->XmlNamespaces['epp']);
			if (substr((string)$paData->svTRID, 0, 8) == 'AR_REPLY')
				$ret->Result = true;
			else
				$ret->Result = false;
		} else {
			$ret = new PollUpdateDomainResponse(REGISTRY_RESPONSE_STATUS::FAILED);
		}
		return $ret;
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
	 * A hack to emulate n outgoing transfer approve message. ZACR does not notify the losing regitrar if a transfer is approevd through Email.
	 * This function however sends out a fake message forcing the losing registrar to remove the 'Pending transfer' notification.
	 * @param unknown $domainName
	 *
	 * @author Alwin Tom
	 */
	protected function EmulateOutgoingTransferApprove ($domainName) {
		//Update domain status and mark outgoing_transfer_status to 0 to stop showing the yellow notification in dashboard.
		//Try to force a Poll transfer object.
		$resp = new PollOutgoingTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
		$resp->HostName = $domainName;
		$resp->TransferStatus = OUTGOING_TRANSFER_STATUS::APPROVED;
		$this->RegistryAccessible->DispatchPollOutgoingTransfer($resp);
	}

	/**
	 * Parse datetime description into a Unix timestamp
	 */
	public function StrToTime ($str)
	{
		return strtotime($str);
	}

	/**
	 * generates full domain name based on domain extension.
	 * @param String $name The domain name to be converted Eg: mydomain
	 * @return string The input domain converetd to safe name. If inTestMode is true, you will get {$name}.test.domainservices.{extension}. Otherwsie simply {$name}.{extension}
	 *
	 * @author Alwin Tom
	 */
	protected function ConvertToFullDomainName($name) {

		return "{$name}.{$this->Extension}";
	}

	/**
	 * Extracts the base domain name from a full host name. Eg: abcd.mydomain.co.za will return mydomain
	 * @param String $hostName the Full host name Eg: abcd.mydomain.co.za
	 * @return string
	 *
	 * @author Alwin Tom
	 */
	protected function ExtractDomainFromHostName($hostName) {
		$domain_name = $hostName;
		$domain_name = substr($domain_name, 0, strrpos($domain_name, $this->Extension)-1);	//removes the .co.za part

		$posLeftDot = strrpos($domain_name, '.');
		if($posLeftDot !== false)
			$domain_name = substr($domain_name, $posLeftDot+1);	//Extracts everything to the right of first '.'

		if(!$domain_name || $domain_name == '')
			return false;
		return $domain_name;
	}


	/**
	 * Extracts the sub-host name from a full host name. Eg: abcd.mydomain.co.za will return abcd
	 * @param String $hostName the Full host name Eg: abcd.mydomain.co.za
	 * @return string
	 *
	 * @author Alwin Tom
	 */
	protected function ExtractHostFromHostName($hostName) {
		$domain_name = $hostName;
		$domain_name = substr($domain_name, 0, strrpos($domain_name, $this->Extension)-1);	//removes the .co.za part
		$domain_name = substr($domain_name, 0, strrpos($domain_name, '.')-1);

		if(!$domain_name || $domain_name == '')
			return false;
		return $domain_name;
	}

	/**
	 * Converts a hostname into test friendly name. Eg: abcd.mydomain.co.za will return abcd.mydomain.test.domainservices.co.za (if in test mode) or return it without change.
	 * @param String $hostName The input hostname
	 * @return String converted hostname.
	 *
	 * @author Alwin Tom
	 */
	protected function ConvertToTestSafeHostName($hostName) {
		$domainName = $this->ExtractDomainFromHostName($hostName);
		$newHostName = $this->ExtractHostFromHostName($hostName);
		$newHostName = $newHostName.'.'.$this->ConvertToFullDomainName($domainName);
		return $newHostName;
	}

}


?>
 
