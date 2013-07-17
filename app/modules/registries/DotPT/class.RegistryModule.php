<?php

class DotPTRegistryModule extends GenericEPPRegistryModule 
		implements IRegistryModuleClientPollable
{
	private $ExtDomain = "http://eppdev.dns.pt/schemas/ptdomain-1.0";
	private $ExtContact = "http://eppdev.dns.pt/schemas/ptcontact-1.0";
	
	function BeforeRequest ($command, &$data, $method)
	{
		switch ($command)
		{
			case "domain-info":
				$Domain = func_get_arg(3);
				$data["pt-ext-roid"] = $Domain->{"pt-ext-roid"};
				break;
			
			case "domain-create":
				$Domain = func_get_arg(3);				
				
				if (!in_array($data["period"], array(1, 3, 5)))
				{
					throw new Exception("Invalid registration period value: {$data["period"]}. "
							. "only 1, 3 and 5 years are allowed");
				}
				
				if ($this->Extension != "com.pt")
				{
					if (!$Domain->{"pt-ext-legitimacy"})
						throw new Exception("'Legitimacy' is required");
					if (!$Domain->{"pt-ext-registration-basis"})
						throw new Exception("'Registration basis' is required");
				}
				
				if ($Domain->{"pt-ext-legitimacy"})
					$ext .= '<ptdomain:legitimacy type="'.$Domain->{"pt-ext-legitimacy"}.'"/>';
				if ($Domain->{"pt-ext-registration-basis"})
					$ext .= '<ptdomain:registration_basis type="'.$Domain->{"pt-ext-registration-basis"}.'">';
					
				if ($Domain->{"pt-ext-brand-number"})
				{
					if (!$Domain->{"pt-ext-brand-date"})
						throw new Exception("'Brand register date' is required");
					if (!$Domain->{"pt-ext-brand-way-registry"})
						throw new Exception("'Brand way registry' is required");
						
					$ext .= '<ptdomain:brand date="'.date("m/d/Y", strtotime($Domain->{"pt-ext-brand-date"})).'" '
							. 'number="'.$Domain->{"pt-ext-brand-number"}.'" '
							. 'way_registry="'.$Domain->{"pt-ext-brand-way-registry"}.'"/>';
				}
				if ($Domain->{"pt-ext-registration-basis"})
					$ext .= "</ptdomain:registration_basis>";
				
				$data["ext"] = $ext ? '<extension>'
						. '<ptdomain:create xmlns:ptdomain="http://eppdev.dns.pt/schemas/ptdomain-1.0" '
						. 'xsi:schemaLocation="http://eppdev.dns.pt/schemas/ptdomain-1.0 ptdomain-1.0.xsd">'
						. $ext
						. '</ptdomain:create>'
						. '</extension>'
						: "";
				break;
				
			case "domain-update-contact":
			case "domain-update-flags":
			case "domain-update-ns":
			case "domain-update":
				$Domain = func_get_arg(3);	
				$data["pt-ext-roid"] = $Domain->{"pt-ext-roid"};
				break;
				
			case "domain-renew":
				if (!in_array($data["period"], array(1, 3, 5)))
				{
					throw new Exception("Invalid renewal period value: {$data["period"]}. "
							. "only 1, 3 and 5 years are allowed");
				}
				
				$Domain = func_get_arg(3);				
				$data["pt-ext-roid"] = $Domain->{"pt-ext-roid"};
				
				break;
				
			case "contact-create":
				$Contact = func_get_arg(3);
				if (!$Contact->AuthCode)
					$data["pw"] = $Contact->AuthCode = $this->GeneratePassword();
				
				$ext .= '<ptcontact:identification>'.$data["pt-ext-identification"].'</ptcontact:identification>';
				if ($data["pt-ext-mobile"])
					$ext .= '<ptcontact:mobile>+351.123456789</ptcontact:mobile>';
					
				$data["ext"] = '<extension>'
						. '<ptcontact:create xmlns:ptcontact="http://eppdev.dns.pt/schemas/ptcontact-1.0" '
						. 'xsi:schemaLocation="http://eppdev.dns.pt/schemas/ptcontact-1.0 ptcontact-1.0.xsd">'
						. $ext
						. '</ptcontact:create>'
						. '</extension>';
				break;
						
						
			case "contact-info":
				$Contact = func_get_arg(3);
				$data["pw"] = $Contact->AuthCode;
				break;
				
		}
	}
	
	public function GetTestConfigurationForm ()
	{
		$CF = new DataForm();
		$CF->AppendField(new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
		$CF->AppendField(new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
		$CF->AppendField(new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1, null, null, null, 'Your OT&E account'));
		$CF->AppendField(new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
	
		return $CF;
	}
	
	static function GetConfigurationForm()
	{
		$ConfigurationForm = new DataForm();
		$ConfigurationForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
		$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Passsword", 1));
		$ConfigurationForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
		$ConfigurationForm->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));
		$ConfigurationForm->AppendField( new DataFormField("RegistrarCLID", FORM_FIELD_TYPE::TEXT , "Registrar clID", 1));			
		
		return $ConfigurationForm;
	}	
	
	function GetRegistrarID()
	{
		return $this->Config->GetFieldByName("RegistrarCLID")->Value;
	}
	
	public function DomainCanBeTransferred(Domain $domain)
    {
    	$resp = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    
    	$CanRegisteredResponse = $this->DomainCanBeRegistered($domain);
    	if ($CanRegisteredResponse->Result == false)
    	{ 	
    		/*
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
	    	*/
    			$resp->Result = true;
	    	/*}*/
    	}
    	else
    		$resp->Result = false;
    	
    	return $resp;
    }
		
	function CreateDomain (Domain $domain, $period, $extra = array())
	{
		$origin_nameservers = $domain->GetNameserverList();
		$nameservers = array();
		foreach ($origin_nameservers as $origin_ns) {
			$nameservers[] = new NameserverHost($origin_ns->HostName, 
					$origin_ns->IPAddr ? $origin_ns->IPAddr : gethostbyname($origin_ns->HostName));
		}
		// XXX: Ugly HACK to send 1 ns
		$domain->SetNameserverList(array($nameservers[0]));
		
		$Resp = parent::CreateDomain($domain, $period, $extra);
		// XXX: Ugly hack to send 1 ns
		$domain->SetNameserverList(array($origin_nameservers[0]));
		
		if ($Resp->Succeed())
		{
			// Process extension 
			$eppExt = $Resp->RawResponse->response->extension->children($this->ExtDomain);
			if (count($eppExt) && $eppExt = $eppExt[0])
			{
				$domain->SetExtraField("pt-ext-roid", "{$eppExt->roid}");
			}
		}
		return $Resp;
	}
	
	function RenewDomain (Domain $domain, $extra=array())
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
				$resp->ExpireDate = strtotime("+{$params["period"]} year", $domain->ExpireDate); 
			}
			
			return $resp;
	}
	
	function UpdateDomainContact(Domain $domain, $contactType, Contact $oldContact, Contact $newContact)
	{
		if ($contactType == CONTACT_TYPE::REGISTRANT) 
		{
			throw new Exception("To change the registrant you must contact the DNS.PT services");
		}
		
		//$Resp = parent::UpdateDomainContact($domain, $contactType, $oldContact, null);
		//$Resp = parent::UpdateDomainContact($domain, $contactType, null, $newContact);
		
		return parent::UpdateDomainContact($domain, $contactType, $oldContact, $newContact);
		
		return $Resp;
	}
	
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
	
	function TransferRequest(Domain $domain, $extra=array())
	{
		$params = array(
			'name' 			=> $this->MakeNameIDNCompatible($domain->GetHostName()),
			'pt-ext-key'	=> $extra["pt-ext-key"],
			'pt-ext-roid'	=> $extra["pt-ext-roid"]
		);
		
		$response = $this->Request("domain-trans-request", $params);
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		$ret = new TransferRequestResponse($status, $response->ErrMsg, $response->Code);
		
		$ptExt = $response->Data->response->extension->children($this->ExtDomain);
		if (count($ptExt) && $ptExt = $ptExt[0])
		{
			$domain->SetExtraField("pt-ext-roid", "{$ptExt->roid}");
		}
		
		return $ret;
	}	
	
	function GetRemoteDomain (Domain $domain)
	{
		$Resp = parent::GetRemoteDomain($domain);
		if ($Resp->Succeed())
		{
			$eppExt = $Resp->RawResponse->response->extension->children($this->ExtDomain);
			if (count($eppExt) && $eppExt = $eppExt[0])
			{
				$v = "{$eppExt->legitimacy->attributes()->type}";
				if ($v)	$Resp->SetExtraField("pt-ext-legitimacy", $v);
				
				$v = "{$eppExt->registration_basis->attributes()->type}";
				if ($v) $Resp->SetExtraField("pt-ext-registration-basis", $v);
				
				$infData = $Resp->RawResponse->response->resData->children($this->XmlNamespaces["domain"]);
				$infData = $infData[0];
				$Resp->SetExtraField("pt-ext-roid", intval("{$infData->roid}"));
			}
		}
		
		return $Resp;
	}
	
	function GetRemoteContact (Contact $contact)
	{
		$Resp = parent::GetRemoteContact($contact);
		if ($Resp->Succeed())
		{
			// Process extension 
			$eppExt = $Resp->RawResponse->response->extension->children($this->ExtContact);
			if (count($eppExt) && $eppExt = $eppExt[0])
			{
				$Resp->{"pt-ext-identification"} = "{$eppExt->identification}";
				$Resp->{"pt-ext-mobile"} = "{$eppExt->mobile}";
			}
		}
		
		return $Resp;
	}
	
	public function NameserverCanBeCreated(Nameserver $ns)
	{
		throw new NotImplementedException();
	}
	
	public function CreateNameserver(Nameserver $ns)
	{
		throw new NotImplementedException();
	}
	
	public function CreateNameserverHost(NameserverHost $ns)
	{
		throw new NotImplementedException();
	}
	
	public function UpdateNameserverHost(NameserverHost $ns)
	{
		throw new NotImplementedException();
	}
	
	public function DeleteNameserverHost(NameserverHost $ns)
	{
		throw new NotImplementedException();			
	}
	
	
	public function PollCreateDomain (Domain $domain) {}
		
	public function PollDeleteDomain (Domain $domain) {}
		
	public function PollChangeDomainOwner (Domain $domain) {}
	
	public function PollTransfer (Domain $domain) 
	{
		$Grd = $this->GetRemoteDomain($domain);
		
		$status = TRANSFER_STATUS::PENDING;
		if ($Grd->CLID == $this->GetRegistrarID())
			$status = TRANSFER_STATUS::APPROVED;
			
		$ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
		$ret->TransferStatus = $status;
		$ret->HostName = $domain->GetHostName();
		
		return $ret;
	}
		
	public function PollUpdateDomain (Domain $domain) {}
		
	public function PollDeleteContact (Contact $contact) {}
		
	public function PollDeleteNamserverHost (NamserverHost $nshost) {}	
	
	
	public function RunTest ($DF)
	{
		$Runner = new OteTestRunner();
		$Runner->Run(new DotPTRegistryModule_OteTestSuite(), $DF);
	}	
}



class DotPTRegistryModule_OteTestSuite extends OteTestSuite 
{
	public function GetName ()
	{
		return "DotPT";
	}
	
	/**
	 * @var DotPTRegistryModule
	 */
	private $Module;
	
	/**
	 * @var Registry
	 */
	private $Registry;
	
	private $DomainName;
	
	private $DomainRoid;
	
	private $ContactCLID;
	
	private $ContactAuthCode;
	
	public function SetUp (DataForm $TestConf)
	{
		$test_conf = $TestConf->ListFields();
		$ModuleConf = DotPTRegistryModule::GetConfigurationForm();
		$module_conf = $ModuleConf->ListFields();
		$module_conf['ServerHost']->Value 	= $test_conf['ServerHost']->Value;
		$module_conf['ServerPort']->Value 	= $test_conf['ServerPort']->Value;
		$module_conf['Login']->Value 		= $test_conf['Login']->Value;
		$module_conf['Password']->Value 	= $test_conf['Password']->Value;
	
        $this->Module = new DotPTRegistryModule(new RegistryManifest(MODULES_PATH . "/registries/DotPT/module.xml"));
        $this->Module->InitializeModule("pt", $ModuleConf);
        $this->Registry = new Registry($this->Module);
	}
	
	public function Run ()
	{
		// 1 Auto login
		$this->login();
		// 2
		$this->createContact();
		// 3
		$this->infoContact();
		// 4. 
		$this->checkDomain();
		// 5. 
		$this->createDomain();
		// 6.
		$this->infoDomain();
		// 7.
		$this->logout();
	}
	
	function login () 
	{
		$title = "Login";
		try {
			$this->Module->GetTransport()->Connect();
			$b = $this->Module->GetTransport()->Login();
			$this->AssertTrue($b, $title);
		} catch (Exception $e) {
			$this->Fail($title);
		}
	}
	
	function createContact ()
	{
		$title = "Create contact";
		try {
			$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
			$Contact->SetFieldList(array(
				"name" => "Kris Slow",
				"org" => "",
				"street1" => "Novecento",
				"city" => "Bern",
				"pc" => "3000",
				"cc" => "CH",
				"voice" => "+41.445522344",
				"fax" => "+41.445522344",
				"email" => "me@kris.slow.name",
				"pt-ext-identification" => "1234567890"
			));
			
			$Resp = $this->Module->CreateContact($Contact);
			$this->AssertTrue($Resp->Code == 1000, $title);
			
			$this->ContactCLID = $Resp->CLID;
			$this->ContactAuthCode = $Contact->AuthCode;
			
		} catch (Exception $e) {
			$this->Fail($title);
		}
	}
	
	function infoContact ()
	{
		$title = "Info contact";
		if ($this->ContactCLID && $this->ContactAuthCode) {
			try {
				$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
				$Contact->CLID = $this->ContactCLID;
				$Contact->AuthCode = $this->ContactAuthCode;
				
				$Resp = $this->Module->GetRemoteContact($Contact);
				$this->AssertTrue($Resp->Code == 1000, $title);
			} catch (Exception $e) {
				$this->Fail($title);
			}
		}
		else {
			$this->Fail($title);
		}
	}
	
	function checkDomain () 
	{
		$title = "Check domain";
		try {
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = "test-" . rand(1000, 9999);
			$this->AssertTrue($this->Registry->DomainCanBeRegistered($Domain)->Result, $title);
			
			$this->DomainName = $Domain->Name;
		} catch (Exception $e) {
			$this->Fail($title);		
		}
	}
	
	function createDomain () 
	{
		$title = "Create domain";	
		try {
			$params = array(
				"name" => $this->DomainName . ".pt",
				"period" => 1,
				'registrant_id' => $this->ContactCLID,			
				'contacts' => '<domain:contact type="admin">'.$this->ContactCLID.'</domain:contact>'
					. '<domain:contact type="tech">'.$this->ContactCLID.'</domain:contact>',
				'ns' => '<domain:ns>
						<domain:hostAttr>
							<domain:hostName>ns.google.com</domain:hostName>
							<domain:hostAddr ip="v4">216.239.32.10</domain:hostAddr>
						</domain:hostAttr>
						<domain:hostAttr>
							<domain:hostName>ns2.google.com</domain:hostName>
							<domain:hostAddr ip="v4">216.239.34.10</domain:hostAddr>
						</domain:hostAttr>
					</domain:ns>',
				'pw' => "",
				'ext' => '<extension>
						<ptdomain:create 
						 xmlns:ptdomain="http://eppdev.dns.pt/schemas/ptdomain-1.0" 
						 xsi:schemaLocation="http://eppdev.dns.pt/schemas/ptdomain-1.0 ptdomain-1.0.xsd">
							<ptdomain:legitimacy type="7"/>
							<ptdomain:registration_basis type="6D"/>
						</ptdomain:create>
					</extension>'
			);
			$Resp = $this->Module->Request("domain-create", $params);
			
			$this->assertTrue($Resp->Code == 1000, $title);
			
			$ptExt = $Resp->Data->response->extension->children("http://eppdev.dns.pt/schemas/ptdomain-1.0");
			if (count($ptExt) && $ptExt = $ptExt[0])
			{
				$this->DomainRoid = "{$ptExt->roid}";
			}

		} catch (RegistryException $e) {
			$this->Fail($title);
		}
	}

	function infoDomain () 
	{
		$title = "Info domain";
		try {
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = $this->DomainName;
			$Domain->SetExtraField("pt-ext-roid", $this->DomainRoid);
			
			$Resp = $this->Module->GetRemoteDomain($Domain);
			
			$this->AssertTrue($Resp->Code == 1000, $title);
			
		} catch (Exception $e) {
			$this->Fail($title);
		}
	}

	function logout ()
	{
		$title = "Logout";
		
		try {
			$this->Module->GetTransport()->Disconnect();
			$this->Pass($title);
		} catch (Exception $e) {
			$this->Fail($title);
		}
	}
}
	