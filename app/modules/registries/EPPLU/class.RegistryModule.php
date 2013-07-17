<?php

/**
 * @name EPPLU Registry module
 * @package    Modules
 * @subpackage RegistryModules
 * @author Alex Kovalyov <http://webta.net/company.html>
 * @author Igor Savchenko <http://webta.net/company.html>
 * @author Marat Komarov <http://webta.net/company.html>
 * 
 */

/**
 * Interface for registry module. 
 * 
 * Each registry module must implement IRegistryModule and one of poll interfaces
 * IRegistryModuleServerPollable or IRegistryModuleClientPollable 
 */
class EPPLURegistryModule extends AbstractRegistryModule implements IRegistryModuleServerPollable
{
	private $XmlNamespaces = array(
		'dnslu' 	=> 'http://www.dns.lu/xml/epp/dnslu-1.0',
		'contact' 	=> 'urn:ietf:params:xml:ns:contact-1.0',
		'domain' 	=> 'urn:ietf:params:xml:ns:domain-1.0',
		'host'		=> 'urn:ietf:params:xml:ns:host-1.0'
	);
	
	private $IdnSunriseStart;
	private	$IdnSunriseEnd;
	
	public function InitializeModule($extension, DataForm $config)
	{
		parent::InitializeModule($extension, $config);
		$this->IdnSunriseStart = strtotime("February 1 2010 08:00 UTC+2");
		$this->IdnSunriseEnd = strtotime("April 1 2010 00:00 UTC+2");
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
	
	/**
     * Must return a DataForm object that will be used to draw a configuration form for this module.
     * @return DataForm object
     */
	public static function GetConfigurationForm()
	{
		$ConfigForm = new DataForm();
		$ConfigForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT, "EPP Host", 1));
		$ConfigForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "EPP Login", 1));
		$ConfigForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT , "EPP Password", 1));
		$ConfigForm->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));
		$ConfigForm->AppendField( new DataFormField("NotificationEmailAddress", FORM_FIELD_TYPE::TEXT , "Notification email address", 0, null, null, null, "Used for IDN registrations during sunrise period February 1st 2010 &mdash; March 31st 2010"));
		
		return $ConfigForm;
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
	
	/**
     * Check transfer possibility for specific domain
     *
     * @param Domain $domain
     * @return DomainCanBeTransferredResponse
     */
    public function DomainCanBeTransferred(Domain $domain)
    {
    	try
    	{
    		$RDResponse = $this->GetRemoteDomain($domain);
    		
    		$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
    		$errmsg = $RDResponse->ErrMsg;
    		$code = $RDResponse->Code;
    	}
    	catch(Exception $e)
    	{
    		$status = REGISTRY_RESPONSE_STATUS::FAILED;
    		$errmsg = $e->getMessage();
    		$code = 0;
    	}
    	
    	$Resp = new DomainCanBeTransferredResponse(
    		$status, 
    		$errmsg, 
    		$code
    	);
    	
    	if ($Resp->IsFailed()) 
    		return $Resp;
    		
		if (in_array($RDResponse->RegistryStatus, array('ok', 'inactive', 'pendingDelete')) &&
			$RDResponse->CLID != $this->Config->GetFieldByName('Login')->Value)
			$Resp->Result = true;
		else
			$Resp->Result = false;
			
		return $Resp;
    }
    
    private function ValidateDomain (Domain $domain)
    {
    	if (!($Registrant = $domain->GetContact(CONTACT_TYPE::REGISTRANT)))
			throw new Exception(_('Domain registrant contact is undefined'));
			
    	if (!($Admin = $domain->GetContact(CONTACT_TYPE::ADMIN)))
			throw new Exception(_('Domain admin contact is undefined'));
			
    	if (!($Tech = $domain->GetContact(CONTACT_TYPE::TECH)))
			throw new Exception(_('Domain tech contact is undefined'));
    }
    
    /**
     * This method send domain trade request (Change owner).
     * 
     * @param Domain $domain Domain must have contacts and nameservers 
     * @param integer $period Domain delegation period
     * @param array $extra Some registry specific fields 
     * @return ChangeDomainOwnerResponse
     */
    public function ChangeDomainOwner(Domain $domain, $period=null, $extra=array())
    {
    	$this->ValidateDomain($domain);
    	
    	$Registrant = $domain->GetContact(CONTACT_TYPE::REGISTRANT);
		$Admin = $domain->GetContact(CONTACT_TYPE::ADMIN);
		$Tech = $domain->GetContact(CONTACT_TYPE::TECH);
		$nameserver_list = $domain->GetNameserverList();
    	
		$is_idn = $this->RegistryAccessible->IsIDNHostName($domain->GetHostName());
			
		// Prepare params for request
    	$params = array(
    		'name' 		=> $is_idn ? $this->RegistryAccessible->PunycodeEncode($domain->GetHostName()) : $domain->GetHostName(),
    		'registrant'=> $Registrant->CLID,
    		'tech' 		=> $Tech->CLID,
    		'admin' 	=> $Admin->CLID,
    		'pw'		=> rand(100000000, 999999999),
    		'ns' 		=> $this->GetNSXML($nameserver_list, 'dnslu'),
    		'flags' 	=> $this->GetFlagsXML($domain->GetFlagList(), false),
    		'idn' 		=> $is_idn ? "<dnslu:idn>{$domain->GetHostName()}</dnslu:idn>" : ''
    	);
    	
		$response = $this->Request("domain-trade-request", $params);
		
		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			$status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Code == RFC3730_RESULT_CODE::OK)
			$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;
		
		$ret = new ChangeDomainOwnerResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
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
    
    private static $EPPFlags = array(
    	'clientHold',
    	'inactive',
    	'clientUpdateProhibited',
    	'clientDeleteProhibited',
    	'clientTransferProhibited',
    	'clientRenewProhibited'
    );

    private static $ExtendedFlags = array(
    	'clientTradeProhibited'
    );
    
	private function GetFlagsXML($flags, $isExt = false, $prefix = "domain")
	{
		$retval = "";
	    foreach ($flags as $flag)
	    {
	        if (
	        	($isExt && in_array($flag, self::$ExtendedFlags)) || 
	        	(!$isExt && in_array($flag, self::$EPPFlags)) ||
	        	$isExt === null && (in_array($flag, self::$EPPFlags) || in_array($flag, self::$ExtendedFlags))
			)
			{
	        	$retval .= "<{$prefix}:status s=\"{$flag}\"/>";
			}
	    }
	    return $retval;
	}
	
	
	private function GetNSXML($nslist, $prefix = "domain")
	{
		$retval = "";
	
		foreach ($nslist as $ns)
			$retval .= "<{$prefix}:hostObj>" . ($this->RegistryAccessible->IsIDNHostName($hostname) ? 
					$this->RegistryAccessible->PunycodeEncode($ns->HostName) : 
					$ns->HostName) . "</{$prefix}:hostObj>";
		
		return $retval;
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
    	throw new NotImplementedException();
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
    	$hostname = $domain->GetHostName();
    	$is_idn = $this->RegistryAccessible->IsIDNHostName($hostname);
    	
	    $params = array (
			"name"          => $is_idn ? $this->RegistryAccessible->PunycodeEncode($hostname) : $hostname,
			"add_normal"    => $this->GetFlagsXML($changes->GetAdded()), 
			"rem_normal"    => $this->GetFlagsXML($changes->GetRemoved()),
			"add_ext"       => $this->GetFlagsXML($changes->GetAdded(), true, "dnslu"),
			"rem_ext"       => $this->GetFlagsXML($changes->GetRemoved(), true, "dnslu")
		);
							
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
    
    private $Mailer;
    
    private function GetMailer () 
    {
    	if ($this->Mailer === null)
    	{
    		$this->Mailer = new PHPMailer();
    		$this->Mailer->From = $this->Config->GetFieldByName("NotificationEmailAddress")->Value;
    	}
    	
    	return $this->Mailer;
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
    	$this->ValidateDomain($domain);
    	
    	
    	$Registrant = $domain->GetContact(CONTACT_TYPE::REGISTRANT);
		$Admin = $domain->GetContact(CONTACT_TYPE::ADMIN);
		$Tech = $domain->GetContact(CONTACT_TYPE::TECH);
    	if (!($nameserver_list = $domain->GetNameserverList()))
			throw new Exception(_("Domain nameservers list coldn't be empty"));
		$flags = $domain->GetFlagList();
		
    	$hostname = $domain->GetHostName();		
    	$is_idn = $this->RegistryAccessible->IsIDNHostName($hostname);		
		
		$params = array(
			"name"				=> $is_idn ? $this->RegistryAccessible->PunycodeEncode($hostname) : $hostname,
			"registrant"		=> $Registrant->CLID,
			"tech"				=> $Tech->CLID,
			"admin"				=> $Admin->CLID,
			"ns"				=> $this->GetNSXML($nameserver_list),
			"pw"				=> rand(100000000, 999999999),
			'idn'				=> $is_idn ? "<dnslu:idn>{$hostname}</dnslu:idn>" : '',
			'flags'				=> $this->GetFlagsXML($flags, null, 'dnslu')
		);
			
		// In IDN sunrise period (February 1 2010 - April 1 2010) <domain:create> command will have to be
		// submitted to the registry by email. The email must follow these rules:
		// - Recipient: idn@sunrise.dns.lu
		// - Sender: <your notification email address>
		// - Body: The raw XML of the EPP domain:create command
		
		$cur_time = time();
		if ($is_idn && $cur_time > $this->IdnSunriseStart && $cur_time < $this->IdnSunriseEnd)
		{
			$Mailer = $this->GetMailer();
			$Mailer->CharSet = "utf8";			
			$Mailer->Body = $this->Transport->ParseTemplate2("domain-create", $params);
			$Mailer->AddAddress("idn@sunrise.dns.lu");
			$Mailer->Send();
			
			$ret = new CreateDomainResponse(REGISTRY_RESPONSE_STATUS::PENDING);
			$ret->CreateDate = time();
			$ret->ExpireDate = strtotime("+1 year", $ret->CreateDate);
			return $ret;
		}
		else
		{
			$response = $this->Request("domain-create", $params);
			
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;		
				
			$ret = new CreateDomainResponse($status, $response->ErrMsg, $response->Code);
			if (!$ret->IsFailed())
			{
				$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
				$info = $info[0];
				
				$ret->CreateDate = strtotime((string)$info->crDate[0]);
				$ret->ExpireDate = strtotime("+1 year", $ret->CreateDate);
				$ret->AuthCode = '';
				$ret->Protocol = '';
			}
			
			return $ret;
		}
	}
	
	/**
	 * This method request registry for information about domain
	 * 
	 * @param Domain $domain 
	 * @return GetRemoteDomainResponse
	 */
	public function GetRemoteDomain(Domain $domain)
	{
		$hostname = $domain->GetHostName();
		$is_idn = $this->RegistryAccessible->IsIDNHostName($hostname);
		
		$params = array(
			'name' => $is_idn ? $this->RegistryAccessible->PunycodeEncode($hostname) : $hostname
		);
		$response = $this->Request("domain-info", $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new GetRemoteDomainResponse($status, $response->ErrMsg, $response->Code);
				
		if ($ret->Succeed())
		{
			$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);			
			$info = $info[0];
			
			$ret->CRID = (string)$info->crID[0];
			$ret->CLID = (string)$info->clID[0];
			
			if ($ret->CLID)
			{
				// Contacts
				$ret->RegistrantContact = (string)$info->registrant[0];
				$contact = $info->xpath('domain:contact[@type="admin"]');
				$ret->AdminContact = (string)$contact[0];
				$contact = $info->xpath('domain:contact[@type="tech"]');
				$ret->TechContact = (string)$contact[0];
				
				$ret->CreateDate = strtotime($info->crDate[0]);
				$ret->ExpireDate = strtotime($info->exDate[0]);
				
				// Nameservers
				$ns_arr = array();
				foreach ($info->xpath('domain:ns/domain:hostObj') as $hostObj)
				{
					$hostname = (string)$hostObj;
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
				$ret->SetNameserverList($ns_arr);
	
				// Flags
				$flags = array();
				if ($nodes = $info->xpath('domain:status/@s'))
					foreach ($nodes as $flag)
						$flags[] = (string)$flag;
				if ($nodes = $response->Data->xpath('//dnslu:domain/dnslu:status')) 		
					foreach ($nodes as $flag)
						$flags[] = (string)$flag;
						
				$ret->RegistryStatus = (string)$flags[0];
				
				$flags = array_filter($flags);					
				// Remove default 'ok' status from domain flags 
				if (($i = array_search("ok", $flags)) !== false) {
					array_splice($flags, $i, 1);
				}
				
				$ret->SetFlagList($flags);
				
			}

			$ret->AuthCode = '';
			$ret->Protocol = '';
		}
		
		return $ret;
	}
	
	/**
	 * Performs epp host:info command. Returns host IP address
	 *
	 * @return string
	 */
	public function GetHostIpAddress ($hostname)
	{
		$params = array(
			'name' => $hostname
		);
		$response = $this->Request('host-info', $params);
		if (!$response->Succeed)
			throw new Exception($response->ErrMsg);
			
		$result = $response->Data->response->resData->children("urn:ietf:params:xml:ns:host-1.0");
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
		$is_idn = $this->RegistryAccessible->IsIDNHostName($hostname);			
		
		$params = array(
			"name" 		=> $is_idn ? $this->RegistryAccessible->PunycodeEncode($hostname) : $domain->GetHostName()
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
	
	private function GetDisclosesXML ($discloses)
	{
		$ret = '';
		foreach ($discloses as $name => $showIt)
			$ret .= sprintf("<dnslu:%s flag =\"%s\"/>", $name, (int)(bool)$showIt);
		return $ret;
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
		$hostname = $domain->GetHostName();
		$is_idn = $this->RegistryAccessible->IsIDNHostName($hostname);
		
		$params = array(
			'name' 	=> $is_idn ? $this->RegistryAccessible->PunycodeEncode($hostname) : $hostname,
			'add' 	=> '',
			'rem' 	=> ''
		);
		
		$added = $changelist->GetAdded();
		$removed = $changelist->GetRemoved();
		
		if ($added)
			$params['add'] = "<domain:add><domain:ns>".$this->GetNSXML($added)."</domain:ns></domain:add>";

		if ($removed)
			$params['rem'] = "<domain:rem><domain:ns>".$this->GetNSXML($removed)."</domain:ns></domain:rem>";
			

		$response = $this->Request("domain-update-ns", $params);
			
		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
		    $status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Succeed)
		    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;		
		
		$ret = new UpdateDomainNameserversResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
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
		$is_idn = $this->RegistryAccessible->IsIDNHostName($domain->GetHostName());
		
		// During sunrise period domain:check command returns 'IDN domains currently disabled.'
		// Check WHOIS instead of EPP 
		$cur_time = time();
		if ($is_idn && $cur_time > $this->IdnSunriseStart && $cur_time < $this->IdnSunriseEnd)
		{
			$fp = fsockopen("whois.dns.lu", 43, $errno, $errmsg);
			if ($errmsg) 
			{
				throw new Exception("Cannot connect to whois.dns.lu. {$errmsg}", $errno);
			}
			fwrite($fp, $this->MakeNameIDNCompatible($domain->GetHostName()) . "\n");
			
			$whois_response = "";
			while (!feof($fp)) 
			{
				$whois_response .= fread($fp, 4096);
			}
			fclose($fp);
			
			
			$ret = new DomainCanBeRegisteredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$ret->Result = strpos($whois_response, "No such domain") !== false;
			return $ret;
		}
		else
		{
			$params = array(
				'name' 	=> $this->MakeNameIDNCompatible($domain->GetHostName())
			);
			$response = $this->Request("domain-check", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$ret = new DomainCanBeRegisteredResponse($status, $response->ErrMsg, $response->Code);
			
			if ($response->Succeed)
			{
				$avail = $response->Data->xpath('//domain:chkData/domain:cd/domain:name/@avail');
				$ret->Result = (bool)(string)$avail[0];
				$reason = $response->Data->xpath('//domain:chkData/domain:cd/domain:reason');
				$ret->Reason = (string)$reason[0];
			}
			else
				$ret->Result = false;
				
			return $ret;
		}
	}
	
	/**
	 * This method request registry to delete domain
	 * In order to pending delete, response must have status REGISTRY_RESPONSE_STATUS::PENDING
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
		
		$scheduled_delete = $executeDate !== null;
		
		if (!$scheduled_delete)
		{
			$params["type"] = "immediate";
			$params["delDate"] = "";
		}
		else
		{
		    $params["delDate"] = "<dnslu:delDate>".date("Y-m-d\TH:i:s", $executeDate).".0Z</dnslu:delDate>";
			$params["type"] = "setDate";
		}
			
		$response = $this->Request("domain-delete", $params);
		if ($response->Succeed)
		{
			$status = $scheduled_delete ? REGISTRY_RESPONSE_STATUS::PENDING : REGISTRY_RESPONSE_STATUS::SUCCESS;
			$result = true;
		}
		else
		{
			$status = REGISTRY_RESPONSE_STATUS::FAILED;
			$result = false;
		}
			
		$ret = new DeleteDomainResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $result;
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
			'name' => $domain->GetHostName()
		);
		
		$response = $this->Request("domain-undelete", $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new RenewDomainResponse($status, $response->ErrMsg, $response->Code);
		if ($ret->Succeed())
		{
			$ret->ExpireDate = $domain->ExpireDate + ($domain->Period*86400*365);
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
		$this->ValidateDomain($domain);
		
		
		$hostname = $domain->GetHostName();
		$is_idn = $this->RegistryAccessible->IsIDNHostName($hostname);
		
		$Registrant = $domain->GetContact(CONTACT_TYPE::REGISTRANT);
		$Admin = $domain->GetContact(CONTACT_TYPE::ADMIN);
		$Tech = $domain->GetContact(CONTACT_TYPE::TECH);
		$nameserver_list = array($extra["ns1"], $extra["ns2"]);
		$flags = $domain->GetFlagList();
		
		// Create nameservers if needed
		foreach ($nameserver_list as $ns)
		{
			try
			{
				$resp = $this->NameserverCanBeCreated($ns);
				if ($resp->Result)
				{
					$ns instanceof NameserverHost ? 
							$this->CreateNameserverHost($ns) : $this->CreateNameserver($ns);					
				}
			}
			catch (Exception $ignore) { }
		}
		
		$params = array(
			"name"				=> $is_idn ? $this->RegistryAccessible->PunycodeEncode($hostname) : $hostname,
			"registrant"		=> $Registrant->CLID,
			"tech"				=> $Tech->CLID,
			"admin"				=> $Admin->CLID,
			"ns"				=> $this->GetNSXML($nameserver_list, "dnslu"),
			'idn'				=> $is_idn ? "<dnslu:idn>{$hostname}</dnslu:idn>" : '',
			'flags'				=> $this->GetFlagsXML($flags, null, 'dnslu')
		);

		$response = $this->Request("domain-trans-request", $params);
		
		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
		    $status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Succeed)
		    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;		

		$ret = new TransferRequestResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
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
		$is_idn = $this->RegistryAccessible->IsIDNHostName($ns->HostName);
		
		$params = array(
			'name' => $is_idn ? $this->RegistryAccessible->PunycodeEncode($ns->HostName) : $ns->HostName
		);
		
		$response = $this->Request("host-check", $params); 
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new NameserverCanBeCreatedResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			$avail = $response->Data->xpath('//host:chkData/host:cd/host:name/@avail');
			$ret->Result = (bool)(string)$avail[0];
		}
		else
			$ret->Result = false;
			
		return $ret;
	}
	
	/**
	 * This method request registry to create namserver
	 * 
	 * @param Nameserver $ns
	 * @return CreateNameserverResponse
	 */
	public function CreateNameserver (Nameserver $ns)
	{
		$is_idn = $this->RegistryAccessible->IsIDNHostName($ns->HostName);
		
		$params = array(
			'name' => $is_idn ? $this->RegistryAccessible->PunycodeEncode($ns->HostName) : $ns->HostName,
		);
		$params["addr"] = "";
			
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
		$is_idn = $this->RegistryAccessible->IsIDNHostName($ns->HostName);
		
		$params = array(
			'name' => $is_idn ? $this->RegistryAccessible->PunycodeEncode($ns->HostName) : $ns->HostName,
		);
		if (stristr($ns->HostName, ".{$this->Extension}"))
			$params["addr"] = "<host:addr ip=\"v4\">{$ns->IPAddr}</host:addr>";
		else 
			$params["addr"] = "";
			
		$response = $this->Request("host-create", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new CreateNameserverHostResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $response->Succeed;
		return $ret;
	}
	
	private $OldIPAddr = null;	
	
	public function OnBeforeUpdateNameserverHost (NameserverHost $newNSHost, NameserverHost $oldNSHost)
	{
		$this->OldIPAddr = $oldNSHost->IPAddr;
	}	
	
	/**
	 * This method request registry to create update namserver host
	 * 
	 * @param NameserverHost $ns
	 * @return UpdateNameserverHostResponse 
	 */
	public function UpdateNameserverHost(NameserverHost $ns)
	{
		// Update it
		$params = array(
			'name' => $ns->HostName, 
			'ip_old' => $this->OldIPAddr, 
			'ip_new' => $ns->IPAddr
		);
		$this->OldIPAddr = null;
		
		$response = $this->Request('host-update', $params);
		
		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
		    $status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Succeed)
		    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;					
		
		$ret = new UpdateNameserverHostResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
		return $ret;
	}
	
	/**
	 * This method request registry to delete namserver host
	 * 
	 * @param NameserverHost $ns
	 * @return DeleteNameserverHostResponse 
	 */
	public function DeleteNameserverHost(NameserverHost $ns)
	{
		$is_idn = $this->RegistryAccessible->IsIDNHostName($ns->HostName);
		
		$params = array(
			'name' => $is_idn ? $this->RegistryAccessible->PunycodeEncode($ns->HostName) : $ns->HostName,
		);
		
		$response = $this->Request("host-delete", $params);

		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
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
		$params = array(
			'id' => $contact->CLID
		);
		$response = $this->Request('contact-check', $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new ContactCanBeCreatedResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			$avail = $response->Data->xpath('//contact:chkData/contact:cd/contact:id/@avail');
			$ret->Result = (bool)(string)$avail[0];
		}
		else
			$ret->Result = false;
		
		return $ret;
	}

	/**
	 * This method request registry to create contact
	 * 
	 * @param Contact $contact
	 * @return CreateContactResponse
	 */
	public function CreateContact(Contact $contact, $extra=array())
	{
		$params = array(
			'id' => $contact->CLID
		);
		$params = array_merge($params, $contact->GetRegistryFormattedFieldList());
		foreach ($params as &$param)
		{
			if (!is_array($param))
				$param = $this->EscapeXML($param);
		}
		
		$params['discloses'] = $this->GetDisclosesXML($contact->GetDiscloseList());
		
		if ($this->IsRegistrant($contact))
		{
			$params['type'] = $params['isorg'] == 1 ? "holder_org" : "holder_pers";			
			$response = $this->Request("contact-create-holder", $params);
		}
		else
			$response = $this->Request("contact-create", $params);

		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new CreateContactResponse($status, $response->ErrMsg, $response->Code);
		
		if ($ret->Succeed())
		{
			$creData = $response->Data->response->resData->children($this->XmlNamespaces['contact']);
			$creData = $creData[0];
		
			$ret->CLID = (string)$creData->id[0];			
		}
		
		return $ret;
	}
	
	private function IsRegistrant (Contact $contact)
	{
		return array_key_exists('isorg', $contact->GetRegistryFormattedFieldList());
	}
	
	/**
	 * This method request registry for information about contact
	 * @access public
	 * @param Contact $contact
	 * @version GetRemoteContactResponse
	 */
	public function GetRemoteContact(Contact $contact)
	{
		$params = array(
			'id' => $contact->CLID
		);
		
		$response = $this->Request("contact-info", $params);
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new GetRemoteContactResponse($status, $response->ErrMsg, $response->Code);
		
		if ($ret->Succeed())
		{
			$ContactInfo = $response->Data->response->resData->children($this->XmlNamespaces['contact']);
			$ContactInfo = $ContactInfo[0];
			$PostalInfo = $ContactInfo->postalInfo[0];
			
			$ret->CLID = (string)$ContactInfo->id[0];
			$ret->clID = (string)$ContactInfo->clID[0];
			$ret->crID = (string)$ContactInfo->crID[0];

			$ret->name 		= (string)$PostalInfo->name[0];
			$ret->org 		= (string)$PostalInfo->org[0];
			$ret->street1 	= (string)$PostalInfo->addr[0]->street[0];
			$ret->street2 	= (string)$PostalInfo->addr[0]->street[1];
			$ret->city 	  	= (string)$PostalInfo->addr[0]->city[0];
			$ret->sp      	= (string)$PostalInfo->addr[0]->sp[0];
			$ret->pc      	= (string)$PostalInfo->addr[0]->pc[0];
			$ret->cc      	= (string)$PostalInfo->addr[0]->cc[0];
			$ret->voice   	= (string)$ContactInfo->voice[0];
			$ret->fax	  	= (string)$ContactInfo->fax[0];
			$ret->email 	= (string)$ContactInfo->email[0];
			
			$type = $response->Data->xpath('//dnslu:contact/dnslu:type');
			$ret->type = (string)$type[0];
			if ($ret->type == 'holder_pers' || $ret->type == 'holder_org')
				$ret->isorg = (string)(int)($ret->type == 'holder_org');
			
			// Discloses
			$disclose_list = array();
			if ($xpath_result = $response->Data->xpath('//dnslu:disclose/dnslu:*[@flag=1]'))
				foreach ($xpath_result as $Disclose)
					$disclose_list[$Disclose->getName()] = 1;
			if ($xpath_result = $response->Data->xpath('//dnslu:disclose/dnslu:*[@flag=0]'))
				foreach ($xpath_result as $Disclose)
					$disclose_list[$Disclose->getName()] = 0;
			foreach ($disclose_list as $name => $showIt)	
				$ret->SetDiscloseValue($name, $showIt);
		}
		
		return $ret;
	}
		
	/**
	 * This method request registry to update contact
	 * 
	 * @param Contact $contact
	 * @return UpdateContactResponse
	 */
	public function UpdateContact(Contact $contact)
	{
		$params = array(
			'id' => $contact->CLID
		);
		
		$params = array_merge($params, $contact->GetRegistryFormattedFieldList());
		foreach ($params as &$param)
		{
			if (!is_array($param))
				$param = $this->EscapeXML($param);
		}
		
		$params['disclose_add'] = $this->GetDisclosesXML($contact->GetDiscloseList());	
		
		// TODO add disclose rem
		$params['disclose_rem'] = '';
			
			
		if ($this->IsRegistrant($contact))
			$response = $this->Request("contact-update-holder", $params);
		else
			$response = $this->Request("contact-update", $params);	
		
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
		$params = array(
			'id' => $contact->CLID
		);
		
		$response = $this->Request("contact-delete", $params);
		
		$status = $response->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new DeleteContactResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
		return $ret;
	}
	
	const MSG_DOMAIN_TRANSFER_EXECUTED 	= 1;
	const MSG_DOMAIN_TRANSFER_REJECTED 	= 2;
	const MSG_DOMAIN_TRANSFER_FAILED 	= 3;
	const MSG_DOMAIN_TRANSFER_RESTORE_EXECUTED  = 10;
	const MSG_DOMAIN_TRANSFER_RESTORE_REJECTED 	= 11;
	const MSG_DOMAIN_TRANSFER_RESTORE_FAILED 	= 12;
	
	const MSG_DOMAIN_TRADE_EXECUTED 	= 7;
	const MSG_DOMAIN_TRADE_REJECTED		= 8;
	const MSG_DOMAIN_TRADE_FAILED		= 9;
	
	const MSG_ACTIVE_DOMAIN_CREATION_EXECUTED = 13;
	const MSG_RESERVED_DOMAIN_CREATION_EXECUTED = 14;
	const MSG_DOMAIN_CREATION_REJECTED = 15;
	const MSG_DOMAIN_DELETED_SENT_TO_QUARANTINE = 16;

	const MSG_HOST_DELETED = 17;	
	const MSG_HOLDER_CONTACT_DELETED = 18;
	const MSG_CONTACT_DELETED = 19;
	
	const MSG_PENDING_DOMAIN_UPDATE_EXECUTED = 20;
	const MSG_PENDING_DOMAIN_UPDATE_FAILED = 21;
	const MSG_DOMAIN_RENEWED = 22;
	const MSG_DOMAIN_DELETED_FROM_QUARANTINE = 23;
	
	const MSG_DOMAIN_TRANSFERRED_AWAY = 26;
	
	/**
	 * Read server message queue, and return first unprocessed item
	 * 
	 * @return PendingOperationResponse False, when queue is empty
	 */
	public function ReadMessage ()
	{
		$response = $this->Request("poll-request", array());
	
		if ($response->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE)
		{
			$msgQ = (array)$response->Data->response->msgQ;
			$msgId = $msgQ['@attributes']['id'];
			
			$children = $response->Data->response->msgQ->msg->children($this->XmlNamespaces['dnslu']);
			$pollmsg = $children->pollmsg;
			$failreason = (string)$pollmsg->reason; 
			
			$attributes = $pollmsg->attributes();
			$msgType = (string)$attributes['type']; 
			
			$object = (string)$pollmsg->object;

			switch ($msgType)
			{
				// CreateDomain response
				case self::MSG_DOMAIN_CREATION_REJECTED:
				case self::MSG_ACTIVE_DOMAIN_CREATION_EXECUTED:
				case self::MSG_RESERVED_DOMAIN_CREATION_EXECUTED:
					
					if ($msgType == self::MSG_RESERVED_DOMAIN_CREATION_EXECUTED || $msgType == self::MSG_ACTIVE_DOMAIN_CREATION_EXECUTED)
						// domain creation executed
						$result = true;
					else
						// domain creation failed
						$result = false;
					
					$ret = new PollCreateDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS, $response->ErrMsg, $response->Code);
					$ret->MsgID = $msgId;
					$ret->HostName = $this->PunycodeDecodeIf($object);
					if ($result)
					{
						$ret->ExpireDate = strtotime((string)$pollmsg->exDate);
					}
					$ret->Result = $result;
					$ret->FailReason = $failreason;
					$ret->RawResponse = $response->Data;
					return $ret;
				
					
				// Transfer response
				case self::MSG_DOMAIN_TRANSFER_EXECUTED:
				case self::MSG_DOMAIN_TRANSFER_REJECTED:
				case self::MSG_DOMAIN_TRANSFER_FAILED:
				case self::MSG_DOMAIN_TRANSFER_RESTORE_EXECUTED:
				case self::MSG_DOMAIN_TRANSFER_RESTORE_REJECTED:
				case self::MSG_DOMAIN_TRANSFER_RESTORE_FAILED:
					
					
					if ($msgType == self::MSG_DOMAIN_TRANSFER_EXECUTED || 
						$msgType == self::MSG_DOMAIN_TRANSFER_RESTORE_EXECUTED)
						$transferStatus = TRANSFER_STATUS::APPROVED;
						
					else if ($msgType == self::MSG_DOMAIN_TRANSFER_REJECTED ||
						$msgType == self::MSG_DOMAIN_TRANSFER_RESTORE_REJECTED)						
						$transferStatus = TRANSFER_STATUS::DECLINED;
						
					else if ($msgType == self::MSG_DOMAIN_TRANSFER_FAILED ||
						$msgType == self::MSG_DOMAIN_TRANSFER_RESTORE_FAILED)
						$transferStatus = TRANSFER_STATUS::FAILED;
					
					$ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$ret->MsgID = $msgId;
					$ret->TransferStatus = $transferStatus;
					$ret->HostName = $this->PunycodeDecodeIf($object);
					$ret->FailReason = $failreason;
					$ret->RawResponse = $response->Data;
					return $ret;
					
					
				// ChangeDomainOwner response
				case self::MSG_DOMAIN_TRADE_EXECUTED:
				case self::MSG_DOMAIN_TRADE_REJECTED:
				case self::MSG_DOMAIN_TRADE_FAILED:
					
					if ($msgType == self::MSG_DOMAIN_TRADE_REJECTED || $msgType == self::MSG_DOMAIN_TRADE_FAILED)
						$result = false;
					else
						$result = true;
					
					$ret = new PollChangeDomainOwnerResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$ret->MsgID = $msgId;
					$ret->HostName = $this->PunycodeDecodeIf($object);
					$ret->Result = $result;
					$ret->FailReason = $failreason;
					$ret->RawResponse = $response->Data; 
					return $ret;
					
					
				// UpdateDomain response
				case self::MSG_PENDING_DOMAIN_UPDATE_EXECUTED:
				case self::MSG_PENDING_DOMAIN_UPDATE_FAILED:
					
					$ret = new PollUpdateDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$ret->MsgID = $msgId;
					$ret->HostName = $this->PunycodeDecodeIf($object);	
					$ret->Result = $msgType == self::MSG_PENDING_DOMAIN_UPDATE_EXECUTED;
					$ret->FailReason = $failreason;
					if ($pollmsg->ns[0])
					{
						foreach ($pollmsg->ns as $ns)
							$ret->FailReason .= ". ".$ns->attributes()->name." {$ns}";
					}
					$ret->RawResponse = $response->Data;
					return $ret;
					 
					
				// DeleteDomain response
				case self::MSG_DOMAIN_DELETED_FROM_QUARANTINE:
					$ret = new PollDeleteDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$ret->MsgID = $msgId;
					$ret->HostName = $this->PunycodeDecodeIf($object);					
					$ret->Result = true;
					$ret->FailReason = $failreason;
					$ret->RawResponse = $response->Data;
					return $ret;
					
				//
				case self::MSG_DOMAIN_TRANSFERRED_AWAY:
					$ret = new PollOutgoingTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$ret->MsgID = $msgId;
					$ret->HostName = $object;
					$ret->TransferStatus = OUTGOING_TRANSFER_STATUS::AWAY;
					$ret->FailReason = $failreason;
					$ret->RawResponse = $response->Data;
					return $ret;
					
					
				// DeleteContact response
				case self::MSG_HOLDER_CONTACT_DELETED:
				case self::MSG_CONTACT_DELETED:
					
					if ($msgType == self::MSG_HOLDER_CONTACT_DELETED)
						$CLID = "H{$object}";
					else if ($msgType == self::MSG_CONTACT_DELETED)
						$CLID = "C{$object}";

					$ret = new PollDeleteContactResponse (REGISTRY_RESPONSE_STATUS::SUCCESS);
					$ret->MsgID = $msgId;
					$ret->CLID = $CLID;
					$ret->FailReason = $failreason;
					$ret->RawResponse = $response->Data;
					return $ret;

				// DeleteNamserverHost response
				case self::MSG_HOST_DELETED:
					
					$ret = new PollDeleteNamserverHostResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$ret->MsgID = $msgId;
					$ret->HostName = $this->PunycodeDecodeIf($object);					
					$ret->FailReason = $failreason;
					$ret->RawResponse = $response->Data;
					return $ret;
					
				
				default:
					// Unknown message
					$ret = new PendingOperationResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$ret->MsgID = $msgId;
					$ret->RawResponse = $response->Data;
					return $ret;
			}
		}
		
		// Empty queue
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
}

?>