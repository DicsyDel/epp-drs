<?php

/**
 * @name SRSPlus Registry module
 * @package    Modules
 * @subpackage RegistryModules
 * @author Marat Komarov <http://webta.net/company.html>
 */

class SRSPlusRegistryModule extends AbstractRegistryModule implements IRegistryModuleClientPollable 
{
	private $ContacttypeSRSMap = array();
	
	public function __construct(RegistryManifest $Manifest)
	{
		parent::__construct($Manifest);
		$this->ContacttypeSRSMap = array(
			CONTACT_TYPE::REGISTRANT=> 'RESPONSIBLE PERSON',
			CONTACT_TYPE::ADMIN 	=> 'ADMIN CONTACT',
			CONTACT_TYPE::TECH 		=> "TECHNICAL CONTACT",
			CONTACT_TYPE::BILLING 	=> "BILLING CONTACT" 
		);
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
		if (!is_writeable($post_values['GPGHomeDir']))
			$err[] = sprintf(_('GnuPG home directory %s is unwriteable'), $post_values['GPGHomeDir']);
			
		if (!is_executable($post_values['GPGPath']))
			$err[] = sprintf(_('GnuPG binary %s is unexecutable'), $post_values['GPGPath']);
			
		return (count($err) == 0) ? true : $err;
	}
	
    /**
     * Must return a DataForm object that will be used to draw a configuration form for this module.
     * @return DataForm object
     */
	public static function GetConfigurationForm()
	{
		$Conf = new DataForm();
		$Conf->AppendField(new DataFormField("Email", FORM_FIELD_TYPE::TEXT, "SRSPlus partner e-mail", 1));
		$Conf->AppendField(new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "SRSPlus partner ID", 1));
		$Conf->AppendField(new DataFormField("TestMode", FORM_FIELD_TYPE::CHECKBOX , "Test mode", 0, null, '1'));
		$Conf->AppendField(new DataFormField("Host", FORM_FIELD_TYPE::TEXT , "SRSPlus host", 1, null, 'testsrs.srsplus.com'));		
		$Conf->AppendField(new DataFormField("GPGPass", FORM_FIELD_TYPE::TEXT , "GPG password", 1));
		$Conf->AppendField(new DataFormField("GPGPath", FORM_FIELD_TYPE::TEXT , "Path to GPG", 1, null, '/usr/bin/gpg'));
		$Conf->AppendField(new DataFormField("GPGHomeDir", FORM_FIELD_TYPE::TEXT , "GPG Keys dir", 1, null, '/root/.gnupg'));
		
		return $Conf;
		
	}
	
	public function GetTestConfigurationForm ()
	{
		$Conf = new DataForm();
		$Conf->AppendField(new DataFormField("Email", FORM_FIELD_TYPE::TEXT, "SRSPlus partner e-mail", 1));
		$Conf->AppendField(new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "SRSPlus partner ID", 1));
		$Conf->AppendField(new DataFormField("Host", FORM_FIELD_TYPE::TEXT , "SRSPlus host", 1, null, 'testsrs.srsplus.com'));
		$Conf->AppendField(new DataFormField("GPGPass", FORM_FIELD_TYPE::TEXT , "GPG password", 1));		
		
		return $Conf;
	}
	
	public function RunTest (DataForm $DF)
	{
		$filename = '/tmp/eppdrs-srsplus-certtest-' . date('YmdHis') . '.log';
	    Log::RegisterLogger("File", "SRSPLUS", $filename);
		Log::SetDefaultLogger("SRSPLUS");
		
		$fields = $DF->ListFields();
		$conf_fields = $this->Config->ListFields();
		$conf_fields['Login']->Value 	= $fields['Login']->Value;
		$conf_fields['Email']->Value 	= $fields['Email']->Value;
		$conf_fields['Host']->Value 	= $fields['Host']->Value;
		$conf_fields['GPGPass']->Value 	= $fields['GPGPass']->Value;
		$conf_fields['TestMode']->Value = '1';
		
		$Module = new SRSPlusRegistryModule(new RegistryManifest(MODULES_PATH . "/registries/SRSPlus/module.xml"));
		$Module->InitializeModule('com', $this->Config);
		$Registry = new Registry($Module);
		
		$oplog = array();
		
		////
		// 1. Create contact
		
		$op = array('title' => "Create contact");
		try
		{
			$Contact = $Registry->NewContactInstanceByGroup('generic');
			$Contact->SetFieldList(array(
				"firstname"		=> "Marat",
				"lastname"		=> "Komarov",
				"org" 			=> "Webta",
				"street1" 		=> "Testing str. 80",
				"street2"		=> "Drive 54",
				"city"			=> "Testing",
				"pc"			=> "90210",
				"cc"			=> "US",
				"sp"			=> "TX",
				"voice"			=> "+333.1234567",
				"fax"			=> "+333.1234567",
				"email"			=> "marat@webta.net"
			));
			$Registry->CreateContact($Contact);
			
			$Contact2 = $Registry->NewContactInstanceByGroup('generic');
			$Contact2->SetFieldList(array(
				"firstname"		=> "Marat",
				"lastname"		=> "Komarov",
				"org" 			=> "Webta",
				"street1" 		=> "Testing str. 80",
				"street2"		=> "Drive 54",
				"city"			=> "Testing",
				"pc"			=> "90210",
				"cc"			=> "US",
				"sp"			=> "TX",
				"voice"			=> "+333.1234567",
				"fax"			=> "+333.1234567",
				"email"			=> "marat@webta.net"
			));
			$Registry->CreateContact($Contact2);
			
			
			$op['ok'] = true;
		}
		catch (Exception $e)
		{
			$op['ok'] = false;
			$op['fail_reason'] = $e->getMessage();
		}
		$oplog[] = $op;
		
		
		////
		// 2. Get remote contact 
		
		$op = array('title' => 'Get contact');
		try
		{
			$RContact = $Registry->NewContactInstanceByGroup('generic');
			$RContact->CLID = $Contact->CLID;
			
			$RContact = $Registry->GetRemoteContact($Contact);
			
			$contact_fields = $Contact->GetFieldList();
			$rcontact_fields = $RContact->GetFieldList();
			
			$op['ok'] = 
				$contact_fields['first_name'] == $rcontact_fields['first_name'] &&
				$contact_fields['last_name'] == $rcontact_fields['last_name'];
				
			if (!$op['ok'])
				$op['fail_reason'] = 'Invalid module behavoiur';
		}
		catch (Exception $e)
		{
			$op['ok'] = false;
			$op['fail_reason'] = $e->getMessage();
		}
		$oplog[] = $op;
		
		
		////
		// 3. Update contact
		
		$op = array('title' => 'Edit contact');
		try
		{
			$contact_fields['email'] = 'pupkin-vasiliy@mail.ru';
			$contact_fields['voice'] = '+554.233456';
			
			$Contact->SetFieldList($contact_fields);
			
			$Registry->UpdateContact($Contact);
			
			$op['ok'] = true;
		}
		catch (Exception $e)
		{
			$op['ok'] = false;
			$op['fail_reason'] = $e->getMessage();
		}
		$oplog[] = $op;
		
		
		////
		// 4. Create domain
		
		$op = array('title' => 'Create domain');
		try
		{
			$Domain = $Registry->NewDomainInstance();

			$Domain->Name = 'webta' . rand(100, 999);
			$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($Contact2, CONTACT_TYPE::TECH);
			$period = 1; 
			
			$Registry->CreateDomain($Domain, $period);
			
			$op['ok'] = true;
		}
		catch (Exception $e)
		{
			$op['ok'] = false;
			$op['fail_reason'] = $e->getMessage();
		}
		$oplog[] = $op;

		////
		// 5. Get remote domain
		
		$op = array('title' => 'Whois');
		try
		{
			$RDomain = $Registry->NewDomainInstance();
			$RDomain->Name = $Domain->Name;
			$RDomain = $Registry->GetRemoteDomain($RDomain);
			
			$op['ok'] = 	
				date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
				date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate);
			if (!$ok) 
			{
				$op['fail_reason'] = 'Invalid module behavoiur';				
			}
		}
		catch (Exception $e)
		{
			$op['ok'] = false;
			$op['fail_reason'] = $e->getMessage();
		}
		$oplog[] = $op;
		
		////
		// 6. Renew domain
		
		$op = array('title' => 'Renew domain');
		try
		{
			$old_expire_date = $Domain->ExpireDate;
			$Registry->RenewDomain($Domain, $extra=array('period' => 1));
				
			$op['ok'] = 
				date('Ymd', $Domain->ExpireDate) == date('Ymd', strtotime('+1 year', $old_expire_date));
			if (!$ok) 
			{
				$op['fail_reason'] = 'Invalid module behavoiur';				
			}
		}
		catch (Exception $e)
		{
			$op['ok'] = false;
			$op['fail_reason'] = $e->getMessage();
		}
		$oplog[] = $op;
				
		////
		// 7. Create nameserver host
		
		$op = array('title' => 'Create nameserver');
		try
		{
			$NSHost = new NameserverHost("ns.{$Domain->GetHostName()}", '216.168.229.190');
			$Registry->CreateNameserverHost($NSHost);
			
			$op['ok'] = true;
		}
		catch (Exception $e)
		{
			$op['ok'] = false;
			$op['fail_reason'] = $e->getMessage();
		}
		$oplog[] = $op;
				
		////
		// 8. Delete nameserver
		
		$op = array('title' => 'Delete nameserver');
		try
		{
			$Registry->DeleteNameserverHost($NSHost);
			
			$op['ok'] = true;
		}
		catch (Exception $e)
		{
			$op['ok'] = false;
			$op['fail_reason'] = $e->getMessage();
		}
		$oplog[] = $op;		
		
		////
		// 9. Delete domain
		
		$op = array('title' => 'Delete domain');
		try
		{
			$Registry->DeleteDomain($Domain);
			
			$op['ok'] = true;
		}
		catch (Exception $e)
		{
			$op['ok'] = false;
			$op['fail_reason'] = $e->getMessage();
		}
		$oplog[] = $op;		

		
		$passed = true;
		foreach ($oplog as $op)
			$passed = $passed && $op['ok'];
		
		$out_filename = sprintf('eppdrs-srsplus-certtest-%s.log', $passed ? 'passed' : 'failed'); 
		
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $out_filename . '"');
		
		foreach ($oplog as $i => $op)
		{
			$n = $i+1;
			print str_pad("{$n}. {$op['title']}", 60, ' ', STR_PAD_RIGHT);
			printf("[%s]\n", $op['ok'] ? 'OK' : 'FAIL');
			if (!$op['ok'])
			{
				print "fail reason: {$op['fail_reason']}\n";
			}
		}

		print "\n\n";
		
		print file_get_contents($filename);
		unlink($filename);
		die();

		
		
		return;
		
			$chk = strlen($contact4->ClID)>0;
			$this->assertTrue($chk, "Create billing contact");
			
			//Try to fetch contact info
			$info = $registry->GetContactInfo($contact1->ClID);
			$this->assertTrue(is_array($info), "Check Registrant contact ID");
			
			// Try to fetch contact info
			$info = $registry->GetContactInfo($contact2->ClID);
			$this->assertTrue(is_array($info), "Check Tech contact ID");
			
			// Try to fetch contact info
			$info = $registry->GetContactInfo($contact3->ClID);
			$this->assertTrue(is_array($info), "Check Admin contact ID");
			
			// Try to create domain name
			//$GRRegistry->SetCurrentTLD("gr");
			$domainname = "newwebtatest".rand(10000,99999);
			$domain = $registry->CreateDomain($domainname, $contact1->ClID, $contact2->ClID, $contact3->ClID, $contact4->ClID, "2", array("ns.hostdad.com", "ns2.hostdad.com"), array());
			$this->assertTrue(is_array($domain), "Create domain name");
			
			$domainlock = $registry->DomainLock($domainname, 1, array());
			$this->assertTrue($domainlock, "Domain locked");
			
			/*
			$hostav = $registry->CheckHost("ns1.{$domainname}.ws");
			if ($hostav == 1)
			{
				$hostcr = $registry->CreateHost("ns1.{$domainname}.ws", "66.235.185.21");
				$this->assertTrue($hostcr, "Cannot create host 1");
			}
			elseif (!$hostav)
			{
				$this->assertTrue($hostav, "Cannot check host 1");
			}
			*/
			//$d_info = $registry->TransferApprove("webtatestt105", array());
			//$d_info = $registry->TransferReject("webtatestt106", array());
			//$d_info = $registry->DomainInfo("webtatestt103");
			
			//$this->assertTrue(is_array($d_info), "Cannot get domain info");
			/*
			$c_chk = $registry->CheckContact($contactid);
			$this->assertTrue($c_chk>0, "Cannot check contact id");
			
			$updNS = $registry->UpdateDomainNS($domainname, array("ns.hostdad.com"), array("ns1.nsys.ws"));
			$this->assertTrue($updNS, "Cannot update domain NS");
			*/
			
			//$GRRegistry->SetCurrentTLD("gr");
			//$trnsf = $registry->UnCreateDomain("nwebtatest1", array("protocol"=>"5052"));		
			//$this->assertTrue($trnsf, "Cannot approve transfer");
			
			//$req = $registry->CheckTransferStatus("test", "1113383");
			//$this->assertTrue($req, "Transfer Status Checked");
						
			// Try to dissconnect
			$dsk = $registry->Disconnect();
			$this->assertTrue($dsk, "Disconnect from EPP Server");
			
			
			
			$registry->Disconnect();
			
			
			//$registry->Disconnect();
		
		
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
     * Checks transfer opportunity for domain
     *
     * @param Domain $domain
     * @return DomainCanBeTransferredResponse
     */
    public function DomainCanBeTransferred(Domain $domain) 
    {
    	$Grd = $this->GetRemoteDomain($domain);
    	
    	$ret = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	$ret->Result = !$Grd->Succeed();
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
    	$params = array(
    		'DOMAIN' => $domain->Name,
    		'TLD' => $this->Extension,
    		'DOMAIN PROTECT' => '1'
    	);
    	
    	$Resp = $this->Request('ALTER DOMAIN', $params);
    	
		$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new LockDomainResponse($status, $Resp->ErrMsg, $Resp->Code);
		$Ret->Result = $Resp->Succeed;
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
    	$params = array(
    		'DOMAIN' => $domain->Name,
    		'TLD' => $this->Extension,
    		'DOMAIN PROTECT' => '0'
    	);
    	
    	$Resp = $this->Request('ALTER DOMAIN', $params);
    	
		$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new UnLockDomainResponse($status, $Resp->ErrMsg, $Resp->Code);
		$Ret->Result = $Resp->Succeed;
		return $Ret;    	
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
    	// Not supported by SRSPlus
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
		$DomainInfoResp = $this->GetDomainInfo($domain);
		if ($DomainInfoResp->Data['DOMAIN STATUS'] != 'FIXED')
		{
			return new CreateDomainResponse(
				REGISTRY_RESPONSE_STATUS::FAILED, 
				'Domain is not available for registration'
			);
		}
		
			
		$params = array(
			'DOMAIN' => $domain->Name,
			'TLD' => $this->Extension,
			'TERM YEARS' => $period,
			'PRICE' => $DomainInfoResp->Data['PRICE']
		);
		
		// Nameservers
		$nslist = $domain->GetNameserverList();
		foreach ($nslist as $i => $ns)
			$params['DNS SERVER NAME ' . ($i+1)] = $ns->HostName;
			
		// Contacts
		$contacttype_srs_map = $this->ContacttypeSRSMap;
		foreach ($domain->GetContactList() as $contacttype => $contact)
			$params[$contacttype_srs_map[$contacttype]] = $contact->CLID;
			
		if ($extra && 
			$this->Manifest->GetDomainConfig()->registration->extra_fields->field) 
		{
			$additional_data = array();
			foreach ($extra as $k => $v)
				$additional_data[] = sprintf('%s:%s', $k, $v);
			$params['ADDITIONAL DATA'] = join(' ', $additional_data);
		}
		
		
		$Resp = $this->Request('REGISTER DOMAIN', $params);
		
		$status = $Resp->Succeed && $Resp->Data['REQUESTID'] ? 
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new CreateDomainResponse($status, $Resp->ErrMsg);
		
		if ($Ret->Succeed())
		{
			$Ret->CreateDate = time();
			$Ret->ExpireDate = (int)$Resp->Data['EXPIRATION DATE'];
			$Ret->AuthCode = '';
			$Ret->Protocol = $Resp->Data['REQUESTID'];
		}
		
		return $Ret;
	}
	
	/**
	 * This method request registry for information about domain
	 * 
	 * @param Domain $domain 
	 * @return GetRemoteDomainResponse
	 */
	public function GetRemoteDomain(Domain $domain) 
	{
		$Resp = $this->Whois($domain);
		
		$status = $Resp->Succeed ? 
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new GetRemoteDomainResponse($status, $Resp->ErrMsg);
		
		if ($Ret->Succeed())
		{
			$ns_arr = array();
			foreach ($Resp->Data as $k => $v)
			{
				if (stristr($k, "DNS SERVER"))
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
			}
			$Ret->SetNameserverList($ns_arr);
			
			$Ret->CRID = $this->Config->GetFieldByName('Login')->Value;
			$Ret->CLID = $this->Config->GetFieldByName('Login')->Value;
			$Ret->CreateDate = (int)$Resp->Data['REGISTRATION DATE'];
			$Ret->ExpireDate = (int)$Resp->Data['EXPIRATION DATE'];
			$Ret->RegistryStatus = 'ok';
			$Ret->RegistrantContact = $Resp->Data['RESPONSIBLE PERSON'];
			$Ret->BillingContact = $Resp->Data['BILLING CONTACT'];
			$Ret->AdminContact = $Resp->Data['ADMIN CONTACT'];
			$Ret->TechContact = $Resp->Data['TECHNICAL CONTACT'];
		}
		
		return $Ret;		
	}
	
	/**
	 * Performs epp host:info command. Returns host IP address
	 *
	 * @return string
	 */
	public function GetHostIpAddress ($hostname)
	{
		$params = array(
			'DNS SERVER NAME' => $hostname
		);
		$response = $this->Request('NAMESERVER INFO', $params);
		if (!$response->Succeed)
			throw new Exception($response->ErrMsg);
			
		return $response->Data['DNS SERVER IP'];
	}
	
	/**
	 * This method request regsitry to change domain contact
	 * In order to pending operation, response must have status REGISTRY_RESPONSE_STATUS::PENDING
	 * 
	 * @param Domain $domain Domain
	 * @param Contact $oldContact
	 * @param Contact $newContact
	 * @return UpdateDomainContactResponse
	 */
	public function UpdateDomainContact(Domain $domain, $contactType, Contact $oldContact, Contact $newContact) 
	{
		$params = array(
			'DOMAIN' => $domain->Name,
			'TLD' => $this->Extension,
			$this->ContacttypeSRSMap[$contactType] => $newContact ? $newContact->CLID : ' '
		);
		
		$Resp = $this->Request('ALTER DOMAIN', $params);
		
		$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new UpdateDomainContactResponse($status, $Resp->ErrMsg, $Resp->Code);
		$Ret->Result = $Resp->Succeed;
		return $Ret;    	
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
		$params = array(
			'DOMAIN' => $domain->Name,
			'TLD' => $this->Extension,
		);
		
		foreach (array_values($changelist->GetList()) as $i => $ns)
			$params['DNS SERVER NAME ' . ($i+1)] = $ns->HostName;

		$Resp = $this->Request('ALTER DOMAIN', $params);
		
		$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new UpdateDomainNameserversResponse($status, $Resp->ErrMsg, $Resp->Code);
		$Ret->Result = $Resp->Succeed;
		return $Ret;    	
	}
	
	/**
	 * This method request registry for ability to register domain
	 * 
	 * @param Domain $domain Domain
	 * @return DomainCanBeRegisteredResponse
	 */
	public function DomainCanBeRegistered(Domain $domain) 
	{
    	$Resp = $this->GetDomainInfo($domain);
    	
    	$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
    	$Ret = new DomainCanBeRegisteredResponse($status, $Resp->ErrMsg, $Resp->Code);
    	$Ret->Result = $Resp->Succeed && $Resp->Data['DOMAIN STATUS'] == 'FIXED';
		return $Ret; 
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
			'DOMAIN' => $domain->Name,
			'TLD' => $this->Extension
		);
		
		$Resp = $this->Request('RELEASE DOMAIN', $params);
		
    	$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
    	$Ret = new DeleteDomainResponse($status, $Resp->ErrMsg, $Resp->Code);
    	$Ret->Result = $Resp->Succeed;
		return $Ret; 
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
			'DOMAIN' => $domain->Name,
			'TLD' => $this->Extension,		
			'TERM YEARS' => $extra['period']
		);
		
		$Resp = $this->Request('RENEW DOMAIN', $params);
		
    	$status = $Resp->Succeed ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
    	$Ret = new RenewDomainResponse($status, $Resp->ErrMsg, $Resp->Code);
    	
    	if ($Ret->Succeed())
    	{
    		$Ret->ExpireDate = (int)$Resp->Data['EXPIRATION DATE'];
    	}
    	
    	return $Ret;
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
		$contact_list = $domain->GetContactList();
		
		$params = array(
			"DOMAIN" => $domain->Name,
			"TLD" => $this->Extension,
			"CURRENT ADMIN EMAIL" => $extra["temail"],
			"AUTH_CODE" => $extra["pw"]
		);
		foreach ($contact_list as $contacttype => $contact)
			$params[$this->ContacttypeSRSMap[$contacttype]] = $contact->CLID;
		
		$Resp = $this->Request('REQUEST TRANSFER', $params);
		
		$status = $Resp->Succeed && $Resp->Data['TRANSFERID'] ?
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new TransferRequestResponse($status, $Resp->ErrMsg);
		$Ret->TransferID = (string)$Resp->Data['TRANSFERID'];
		$Ret->Result = $Ret->Succeed();
		return $Ret; 
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
		$Resp = $this->OutboundTransferResponse($domain, 'ACCEPT');
		
		$status = $Resp->Succeed && $Resp->Data['REQUESTID'] ?
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new TransferApproveResponse($status, $Resp->ErrMsg);
		$Ret->Result = $Ret->Succeed();
		return $Ret; 
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
		$Resp = $this->OutboundTransferResponse($domain, 'DENY');
		
		$status = $Resp->Succeed && $Resp->Data['REQUESTID'] ?
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new TransferRejectResponse($status, $Resp->ErrMsg);
		$Ret->Result = $Ret->Succeed();
		return $Ret; 
	}
	
	private function OutboundTransferResponse (Domain $domain, $answer)
	{
		$params = array(
			"DOMAIN" => $domain->Name,
			"TLD" => $this->Extension,
			"TRANSFER RESPONSE" => strtoupper($answer)
		);
		
		return $this->Request('OUTBOUND TRANSFER RESPONSE', $params);
	}
	
	/**
	 * This method request registry for ability to create namserver
	 * 
	 * @param Nameserver $ns
	 * @return NameserverCanBeCreatedResponse
	 */
	public function NameserverCanBeCreated(Nameserver $ns) 
	{
		$Resp = $this->GetNameserverInfo($ns);
		
		$Ret = new NameserverCanBeCreatedResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
		$Ret->Result = !(bool)$Resp->Data['DNS SERVER NAME'];
		return $Ret;
	}
	
	/**
	 * This method request registry to create namserver
	 * 
	 * @param Nameserver $ns
	 * @return CreateNameserverResponse
	 */
	public function CreateNameserver (Nameserver $ns) 
	{
		$ipaddr = gethostbyname($ns->HostName);
		if ($ipaddr == $ns->HostName)
			throw new Exception(sprintf(_('Can\'t resolve %s IP address'), $ns->HostName));
		
		$params = array(
			"DNS SERVER NAME" => $ns->HostName,
			"DNS SERVER IP" => $ipaddr			
		);
		
		$Resp = $this->Request('REGISTER NAMESERVER', $params);
		
		$status = $Resp->Succeed && $Resp->Data['REQUESTID'] ?
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new CreateNameserverResponse($status, $Resp->ErrMsg);
		$Ret->Result = $Ret->Succeed();
		return $Ret;
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
			"DNS SERVER NAME" => $nshost->HostName,
			"DNS SERVER IP" => $nshost->IPAddr			
		);
		
		$Resp = $this->Request('REGISTER NAMESERVER', $params);
		
		$status = $Resp->Succeed && $Resp->Data['REQUESTID'] ?
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new CreateNameserverHostResponse($status, $Resp->ErrMsg);
		$Ret->Result = $Ret->Succeed();
		return $Ret;
	}
	
	/**
	 * This method request registry to create update namserver host
	 * 
	 * @param NameserverHost $ns
	 * @return UpdateNameserverHostResponse 
	 */
	public function UpdateNameserverHost(NameserverHost $ns) 
	{
		$params = array(
			'DNS SERVER NAME' => $ns->HostName,
			'DNS SERVER IP' => $ns->IPAddr
		);
		
		$Resp = $this->Request('MODIFY NAMESERVER', $params);
		
		$status = $Resp->Succeed && $Resp->Data['REQUESTID'] ?
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new UpdateNameserverHostResponse($status, $Resp->ErrMsg);
		$Ret->Result = $Ret->Succeed();
		return $Ret;
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
			'DNS SERVER NAME' => $ns->HostName
		);
		
		$Resp = $this->Request('RELEASE NAMESERVER', $params);
		
		$status = $Resp->Succeed && $Resp->Data['REQUESTID'] ?
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new DeleteNameserverHostResponse($status, $Resp->ErrMsg);
		$Ret->Result = $Ret->Succeed();
		return $Ret;
	}
	
	/**
	 * This method request registry for ability to create contact
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
	 * This method request registry to create contact
	 * 
	 * @param Contact $contact
	 * @return CreateContactResponse
	 */
	public function CreateContact(Contact $contact, $extra=array()) 
	{
		$fields = $contact->GetRegistryFormattedFieldList();
		$params = array(
			'TLD' 			=> $this->Extension,
			'FNAME' 		=> $fields['firstname'],
			'LNAME' 		=> $fields['lastname'],
			'ORGANIZATION' 	=> $fields["org"],
			'EMAIL' 		=> $fields["email"],
			'ADDRESS1' 		=> $fields["street1"],
			'ADDRESS2' 		=> $fields["street2"],
			'CITY'  		=> $fields["city"],
			//'PROVINCE' 		=> strlen($fields["sp"]) != 2 ? "Outsite US or Canada" : $fields["sp"],
			'PROVINCE'		=> $fields['sp'],
			'POSTAL CODE' 	=> $fields["pc"],
			'COUNTRY' 		=> $fields["cc"],
			'PHONE' 		=> $fields["voice"]
		);
		
		$Resp = $this->Request('CREATE CONTACT', $params);
		
		$status = $Resp->Succeed && $Resp->Data['CONTACTID'] ? 
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new CreateContactResponse($status, $Resp->ErrMsg);
		
		if ($Ret->Succeed())
		{
			$Ret->CLID = $Resp->Data['CONTACTID'];
		}
		
		return $Ret;
	}
	
	/**
	 * This method request registry for information about contact
	 * @access public
	 * @param Contact $contact
	 * @version GetRemoteContactResponse
	 */
	public function GetRemoteContact(Contact $contact) 
	{
		$Resp = $this->Request('GET CONTACT INFO', array('CONTACTID' => $contact->CLID));
		
		$status = $Resp->Succeed ?
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new GetRemoteContactResponse($status, $Resp->ErrMsg);

		if ($Ret->Succeed())
		{
			$d = $Resp->Data;
			
			$fields = array(
				'firstname' => $d['FNAME'],
				'lastname' => $d['LNAME'],
				'org' => $d['ORGANIZATION'],
				'street1' => $d['ADDRESS1'],
				'street2' => $d['ADDRESS2'],
				'city' => $d['CITY'], 
				'sp' => $d['PROVINCE'],
				'pc' => $d['POSTAL CODE'],
				'cc' => $d['COUNTRY'],
				'voice' => $d['PHONE'],
				'email' => $d['EMAIL']
			);
			foreach ($fields as $k => $v)
				$Ret->{$k} = $v;
		}
		
		return $Ret;
	}
		
	/**
	 * This method request registry to update contact
	 * 
	 * @param Contact $contact
	 * @return UpdateContactResponse
	 */
	public function UpdateContact(Contact $contact) 
	{
		$fields = $contact->GetRegistryFormattedFieldList();
		$params = array(
			'CONTACTID' => $contact->CLID,
			'FNAME' 		=> $fields['firstname'],
			'LNAME' 		=> $fields['lastname'],
			'ORGANIZATION' 	=> $fields["org"],
			'EMAIL' 		=> $fields["email"],
			'ADDRESS1' 		=> $fields["street1"],
			'ADDRESS2' 		=> $fields["street2"],
			'CITY'  		=> $fields["city"],
			//'PROVINCE' 		=> strlen($fields["sp"]) != 2 ? "Outsite US or Canada" : $fields["sp"],
			'PROVINCE'		=> $fields['sp'],
			'POSTAL CODE' 	=> $fields["pc"],
			'COUNTRY' 		=> $fields["cc"],
			'PHONE' 		=> $fields["voice"]
		);
		
		$Resp = $this->Request('EDIT CONTACT', $params);
		
		$status = $Resp->Succeed && $Resp->Data['CONTACTID'] ?
			REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$Ret = new UpdateContactResponse($status, $Resp->ErrMsg);
		$Ret->Result = $Ret->Succeed();
		return $Ret;
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
		// Not supported by SRSPlus
	}
	
	private function GetDomainInfo (Domain $domain)
	{
    	$params = array(
			"DOMAIN" 	=> $domain->Name, 
			"TLD" 		=> $this->Extension, 
			"ENCODING" 	=> 'UTF-8'    	
    	);
    	
    	return $this->Request('GET DOMAIN INFO', $params);
	}
	
	private function GetNameserverInfo (Nameserver $ns)
	{
		$params = array(
			'DNS SERVER NAME' => $ns->HostName
		);
		
		return $this->Request('GET NAMESERVER INFO', $params);
	}
	
	private function Whois (Domain $domain)
	{
		$params = array(
			'DOMAIN' => $domain->Name,
			'TLD' => $this->Extension
		);
		
		$Resp = $this->Request('WHOIS', $params);
		
		if ($Resp->Data['NOTE'] == 'NO WHOIS INFORMATION')
		{
			$Resp->ErrMsg = 'Object does not exist'; 
			$Resp->Succeed = false;
		}
		
		return $Resp;
	}
	
	/**
	 * Called by system when domain creation is pending
	 * Must return valid DomainCreatedResponse on operatation is finished, 
	 * or response with status = REGISTRY_RESPONSE_STATUS::PENDING if creation is still in progress
	 * 
	 * @param Domain $domain
	 * @return PollCreateDomainResponse
	 */
	public function PollCreateDomain (Domain $domain) {}
	
	/**
	 * Called by system when delete domain operation is pending.
	 * Must return valid DomainDeletedResponse on operatation is finished, 
	 * or response with status = REGISTRY_RESPONSE_STATUS::PENDING if delete is still in progress
	 * 
	 * @param Domain $domain
	 * @return PollDeleteDomainResponse
	 */
	public function PollDeleteDomain (Domain $domain) {}
	
	/**
	 * Called by system when change domain owner operation is pending.
	 * Must return valid DomainOwnerChangedResponse on operatation is finished, 
	 * or response with status = REGISTRY_RESPONSE_STATUS::PENDING if operation is still in progress
	 * 
	 * @param Domain $domain
	 * @return PollChangeDomainOwnerResponse
	 */
	public function PollChangeDomainOwner (Domain $domain) {}

	/**
	 * Called by system when domain transfer operation is pending.
	 * Must return valid PollDomainTransfered on operatation is finished, 
	 * or response with status = REGISTRY_RESPONSE_STATUS::PENDING if operation is still in progress
	 * 
	 * @param Domain $domain
	 * @return PollTransferResponse
	 */
	public function PollTransfer (Domain $domain)
	{
		$transferStatus = null;
		$respStatus = REGISTRY_RESPONSE_STATUS::SUCCESS;
		
		////
		// Check owner has approved transfer

		$params = array(
			'TRANSFERID' => $domain->TransferID
		);
		$Resp = $this->Request('QUERY TRANSFERID', $params);
		if (! $Resp->Succeed)
			return new PollTransferResponse(REGISTRY_RESPONSE_STATUS::FAILED, $Resp->ErrMsg);
			
		if ($Resp->Data['TRANSFER STATUS'] == 'REGISTRAR_TRANSFER_SUBMITTED')
		{
			$transferStatus = REGISTRY_RESPONSE_STATUS::SUCCESS;
		}
		else
		{
			////
			// Check owner has rejected transfer 
			
			$params = array(
				'TRANSFERID' => $domain->TransferID
			);
			$Resp = $this->Request('QUERY REJECTED TRANSFER', $params);
			if ($Resp->Succeed)
			{
				$transferStatus = TRANSFER_STATUS::DECLINED;
			}
			else
			{
				//// 
				// Search in pending transfered domains. 
				
				$params = array(
					'TRANSFER TYPE' => 'INBOUND'
				);
		
				$Resp = $this->Request('VIEW PENDING TRANSFER', $params);
				if (! $Resp->Succeed)
					return new PollTransferResponse(REGISTRY_RESPONSE_STATUS::FAILED, $Resp->ErrMsg);
				
				$i = 1; $in_pending = false; 
				while (array_key_exists("DOMAIN {$i}", $Resp->Data))
				{
					$hostname = sprintf('%s.%s', $Resp->Data["DOMAIN {$i}"], $Resp->Data["TLD {$i}"]);
					if ($hostname == $domain->GetHostName())
					{
						$in_pending = true;
						$respStatus = REGISTRY_RESPONSE_STATUS::PENDING;
						$transferStatus = TRANSFER_STATUS::PENDING;
						break;
					} 
					 
					$i++;
				}
				
				if (!$in_pending)
				{
					$transferStatus = TRANSFER_STATUS::FAILED;
				}
			}
		}
		
		$Ret = new PollTransferResponse($respStatus);
		$Ret->HostName = $hostname;
		$Ret->TransferStatus = $transferStatus;
		return $Ret;
	}
	
	/**
	 * Called by system when update domain operation is pending.
	 * Must return valid DomainUpdatedResponse on operatation is finished, 
	 * or response with status = REGISTRY_RESPONSE_STATUS::PENDING if update is still in progress
	 * 
	 * @param Domain $domain
	 * @return PollUpdateDomainResponse
	 */
	public function PollUpdateDomain (Domain $domain) {}
	
	/**
	 * Called by system when delete contact operation is pending
	 *
	 * @param Contact $contact
	 * @return PollDeleteContactResponse
	 */
	public function PollDeleteContact (Contact $contact) {}
	
	/**
	 * Called by system when delete namserver host operation is pending
	 *
	 * @param NamserverHost $nshost
	 * @return PollDeleteNamserverHostResponse
	 */
	public function PollDeleteNamserverHost (NamserverHost $nshost) {}	
}

?>
