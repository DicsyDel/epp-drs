<?

	require_once (dirname(__FILE__) . "/class.RegistryModule.php");
	require_once (dirname(__FILE__) . "/class.Transport.php");

	class RRPPRoxyRegistryModuleMockup extends RRPProxyRegistryModule
	{
		public function __construct ()
		{
			$this->Manifest = new RegistryManifest(MODULES_PATH. "/registries/RRPProxy/module.xml");
			$this->Manifest->SetExtension("eu");
			$this->Transport = new RRPPRoxyTransportMockup();
			$this->ModuleName = "RRPProxy";			
			$this->Extension = "eu";
		}
	}
	
	class RRPPRoxyTransportMockup extends RRPProxyTransport
	{
		public function __construct(DataForm $ConnectionConfig)
		{
			
		}
		
		public function Connect ()
		{
			$this->IsConnected = true;
			return true;
		}
		
		function Request ($command, $data = array())
		{
			if ($command == "StatusDomain")
			{
				$response = "HTTP/1.1 200 OK
Date: Tue, 30 Jun 2009 10:05:14 GMT
Server: Apache
OPMODE: LIVE
Transfer-Encoding: chunked
Content-Type: text/plain

[RESPONSE]
code = 200
description = Command completed successfully
property[created by][0] = EXTERNAL
property[created date][0] = 2008-07-15 13:49:40.0
property[updated by][0] = EXTERNAL
property[updated date][0] = 2009-06-30 10:02:17.0
property[registrar][0] = nurv
property[registration expiration date][0] = 2010-06-30 10:02:28.0
property[registrar transfer date][0] = 2009-06-30 10:02:17.0
property[auth][0] =
property[renewalmode][0] = DEFAULT
property[transfermode][0] = DEFAULT
property[renewal date][0] = 2010-06-29 10:02:28.0
property[roid][0] = 3783052210758_DOMAIN-KEYSYS
property[domain][0] = temptationloft.eu
property[status][0] = ACTIVE
property[transfer lock][0] = 0
property[nameserver][0] = NS1.NURV.BE
property[nameserver][1] = NS2.NURV.BE
property[billing contact][0] = eu.c89406
property[owner contact][0] = eu.c10720031
property[admin contact][0] = eu.c10720031
property[tech contact][0] = P-JZW723
property[X-EU-OWNER][0] = NURV bvba
queuetime=0
runtime=0.533
EOF


0";
			}
			else if ($command == "StatusContact")
			{
				if ($data["contact"] == "P-JZW723")
				{
					$response = "HTTP/1.1 200 OK
Date: Tue, 30 Jun 2009 10:05:15 GMT
Server: Apache
OPMODE: LIVE
Transfer-Encoding: chunked
Content-Type: text/plain

[RESPONSE]
code = 200
description = Command completed successfully
property[created by][0] = nurv
property[created date][0] = 2009-06-03 07:23:54.0
property[updated by][0] = nurv
property[updated date][0] = 2009-06-03 07:23:54.0
property[contact][0] = P-JZW723
property[roid][0] = 45531723_CONTACT-KEYSYS
property[organization][0] = Key-Systems GmbH
property[first name][0] = Jens
property[last name][0] = WagnerJens Wagner
property[street][0] = Prager Ring 4-12
property[city][0] = Zweibrücken
property[zip][0] = 66482
property[country][0] = DE
property[phone][0] = +49.6332791850
property[fax][0] = +49.6332791851
property[email][0] = jwagner@key-systems.net
property[auto delete][0] = 0
queuetime=0
runtime=0.002
EOF


0";
				}
				else if (preg_match("/^eu/", $data["contact"]))
				{
					$response = "HTTP/1.1 200 OK
Date: Tue, 30 Jun 2009 10:05:15 GMT
Server: Apache
OPMODE: LIVE
Transfer-Encoding: chunked
Content-Type: text/plain

[RESPONSE]
code = 545
description = Entity reference not found
queuetime=0
runtime=0.001
EOF


0";
				}
			}
			
			
			
			$chunks = explode("[RESPONSE]", $response);
			$res = trim($chunks[1]);
			
			preg_match("/code[\s]*=[\s]*([0-9]+)/si", $res, $matches);
			$response_code = $matches[1];
						
			preg_match("/description[\s]*=[\s]*(.*?)\n/si", $res, $matches);
			$errmsg = $matches[1];
			
			if (!$errmsg)
			{
				preg_match_all("/<title>(.*?)<\/title>/si", $full_response, $matches);
				$errmsg = $matches[1];
				if (!$errmsg)
					$errmsg = "Unknown error. See log for more information.";
			}
			
			if ($response_code == 220 ||
				$response_code == 521 || 
				$response_code == 520 ||
				$response_code == 420)
				$this->Disconnect();
				
			if ($response_code == 545)
				throw new ObjectNotExistsException();
			
			$is_success = ((int)$response_code >= 200 && (int)$response_code <= 220);
					
			return new TransportResponse($response_code, $res, $is_success, $errmsg);			
		}
	}

	class RRPProxyRegistryTests extends UnitTestCase 
	{
		/**
		 * @var Registry
		 */
        private $Registry;
        
        private $contact_data;
		
        private $OnlineNIC;
        
        function setUp ()
        {
        	//$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension('com.ph');
        	//$this->Registry2 = RegistryModuleFactory::GetInstance()->GetRegistryByExtension('net');
        	
        	$this->OnlineNIC = RegistryModuleFactory::GetInstance()->GetRegistryByName('OnlineNIC');
        	
        	$this->contact_data = array(
        		'firstname' 	=> 'marat',
        		'lastname'		=> 'komarov',
        		'organization'	=> 'webta',
        		'country' 		=> 'UA',
        		'state' 		=> 'crimea',
        		'city' 			=> 'sebastopol',
        		'zip' 			=> '3212',
        		'street' 		=> 'bbfdgfd fds',
        		'phone' 		=> '+33-123-45678',
        		'fax' 			=> '+33-123-45678',
        		'email' 		=> 'marat@webta.net'
        	);
        }
        
        function _testDelete ()
        {
			$Domain = DBDomain::GetInstance()->LoadByName('webta2534', 'net');
        	
			////
			// Remove nameservers from domain 
			$ns_list = $Domain->GetNameserverList();
			if ($ns_list)
			{
				$Changes = $Domain->GetNameserverChangelist();
				foreach ($ns_list as $NS)
				{
					$Changes->Remove($NS);
				}
				$this->Registry->UpdateDomainNameservers($Domain, $Changes);
				$this->assertTrue(count($Domain->GetNameserverList()) == 0, 'Remove nameservers from domain');
			}
			
			////
			// Delete nameservers
			foreach ($ns_list as $NS)
			{
				$this->Registry->DeleteNameserverHost($NS);
			}
			$this->assertTrue(true, 'Delete nameservers');
			
        	
        	////
			// Delete domain
			$this->Registry->DeleteDomain($Domain);
			$this->assertTrue(true, 'Delete domain');
			
			////
			// Delete contacts
			$this->Registry->DeleteContact($Domain->GetContact(CONTACT_TYPE::REGISTRANT));
			$this->Registry->DeleteContact($Domain->GetContact(CONTACT_TYPE::TECH));
			$this->assertTrue(true, 'Delete contacts');
        }
        
        function testIngoingTransferEU ()
        {
        	$module = new RRPPRoxyRegistryModuleMockup();
        	$registry = new Registry($module);
        	
        	$poll_resp = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
        	$poll_resp->HostName = "temptationloft.eu";
        	$poll_resp->TransferStatus  = TRANSFER_STATUS::APPROVED;
        	
        	$registry->DispatchPollTransfer($poll_resp);
        }
        
        function _testBusy ()
        {
			$Module = $this->Registry->GetModule();
			$Module->Extension = "nu";
			
			$remote = $this->Registry->NewDomainInstance();
			$remote->Name = "pizza";
			
			$c = $this->Registry->NewContactInstanceByGroup("generic");
			$c->CLID = "be.We44";
			$remote->SetContact($c, CONTACT_TYPE::REGISTRANT);

			$c = $this->Registry->NewContactInstanceByGroup("generic");
			$c->CLID = "be.1453";
			$remote->SetContact($c, CONTACT_TYPE::BILLING);
			
			$c = $this->Registry->NewContactInstanceByGroup("generic");
			$c->CLID = "be.t4543";
			$remote->SetContact($c, CONTACT_TYPE::TECH);
			
			
			$local = $this->Registry->NewDomainInstance();
			$local->Name = "pizza";
			
			$c = $this->Registry->NewContactInstanceByGroup("generic");
			$c->CLID = "P-3324";
			$local->SetContact($c, CONTACT_TYPE::REGISTRANT);
			
			$c = $this->Registry->NewContactInstanceByGroup("generic");
			$c->CLID = "P-1577";
			$local->SetContact($c, CONTACT_TYPE::ADMIN);
			$local->SetContact($c, CONTACT_TYPE::BILLING);
			$local->SetContact($c, CONTACT_TYPE::TECH);
			
			
			$Module->UpdateContactsAfterTransfer($remote, $local);
			
			/*
			
			
			
			$remote = $this->Registry2->NewDomainInstance();
			$remote->Name = "kall";			
			
			
			$local = $this->Registry2->NewDomainInstance();
			$local->Name = "kall";
						
			$c = $this->Registry2->NewContactInstanceByGroup("generic");
			$c->CLID = "P-3324";
			$local->SetContact($c, CONTACT_TYPE::REGISTRANT);
			
			$c = $this->Registry2->NewContactInstanceByGroup("generic");
			$c->CLID = "P-1577";
			$local->SetContact($c, CONTACT_TYPE::ADMIN);
			$local->SetContact($c, CONTACT_TYPE::BILLING);
			$local->SetContact($c, CONTACT_TYPE::TECH);			
			
			$this->Registry2->GetModule()->UpdateContactsAfterTransfer($remote, $local);	

			var_dump($remote->GetContact(CONTACT_TYPE::REGISTRANT)->CLID);
			var_dump($remote->GetContact(CONTACT_TYPE::ADMIN)->CLID);
			var_dump($remote->GetContact(CONTACT_TYPE::BILLING)->CLID);
			var_dump($remote->GetContact(CONTACT_TYPE::TECH)->CLID);
			*/
			
			return;
			
			
			//$RrpModule->GetRemoteDomain($Domain);
        	//$Module = $this->Registry->GetModule();
        	
        	$RrpModule->PollTransfer($Domain);
        	
        	//$OlnModule = $this->OnlineNIC->GetModule();
        	//$OlnModule->GetRemoteDomain($Domain);
			
        	//$this->Registry->TransferRequest($Domain);
        	//$this->Registry->DispatchPendingOperations();
        }
        
        function _testEPP ()
        {
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'webta' . rand(1000, 9999);
        	
			var_dump($Domain->Name);
			
			////
			// Check domain
        	$ok = $this->Registry->DomainCanBeRegistered($Domain);
			$this->assertTrue($ok, 'Domain available for registration');
        	
			////
			// Create contact
			$Registrant = $this->Registry->NewContactInstanceByGroup('generic');
			$Registrant->SetFieldList($this->contact_data);
			$this->Registry->CreateContact($Registrant);
			$this->assertTrue(true, 'Create contact');

			////
			// Get remote contact
			$RRegistrant = $this->Registry->NewContactInstanceByGroup('generic');
			$RRegistrant->CLID = $Registrant->CLID;
			
			$this->Registry->GetRemoteContact($RRegistrant);
			
			$fields = $Registrant->GetFieldList();
			$rfields = $RRegistrant->GetFieldList();
			
			$this->assertTrue(
				$fields['name'] == $rfields['name'] &&
				$fields['email'] == $rfields['email'] &&
				$fields['voice'] == $rfields['voice'],
				'Get remote contact'
			);
			
			////
			// Create domain
			$Domain->SetContact($Registrant, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($Registrant, CONTACT_TYPE::ADMIN);
			$Domain->SetContact($Registrant, CONTACT_TYPE::TECH);
			$Domain->SetContact($Registrant, CONTACT_TYPE::BILLING);
			$Domain->SetNameserverList(array(
				new Nameserver('ns.hostdad.com'),
				new Nameserver('ns2.hostdad.com')
			));
			
			$this->Registry->CreateDomain($Domain, 2);
			$this->assertTrue(true, 'Create domain');
			
			////
			// Create nameservers 
			$ns1 = new NameserverHost('ns1.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
			$ns2 = new NameserverHost('ns2.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
			
			$this->Registry->CreateNameserverHost($ns1);
			$this->Registry->CreateNameserverHost($ns2);
			
			$this->assertTrue(true, 'Create nameservers');
			
			////
			// Attach nameservers to domain
			$nslist = $Domain->GetNameserverChangelist();
			$nslist->Add($ns1);
			$nslist->Add($ns2);
			
			$this->Registry->UpdateDomainNameservers($Domain, $nslist);
			
			$this->assertTrue(
				count($Domain->GetNameserverList()) == 4,
				'Attach nameservers to domain'
			);
			
			////
			// Create contact and update domain tech contact
			$Tech = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
			$Tech->SetFieldList(array_merge($this->contact_data, array(
				'firstname' => 'Nikolas',
				'lastname' => 'Toursky'
			)));
			$this->Registry->CreateContact($Tech);
			
			$this->Registry->UpdateDomainContact($Domain, CONTACT_TYPE::TECH, $Registrant, $Tech);
			$this->assertTrue(true, 'Attach contact to domain');
			
			////
			// Lock domain
			$ok = $this->Registry->LockDomain($Domain);
			$this->assertTrue($ok, 'Lock domain');
			
			////
			// Perform info to verify update
			$RDomain = $this->Registry->NewDomainInstance();
			$RDomain->Name = $Domain->Name;
			
			$RDomain = $this->Registry->GetRemoteDomain($RDomain);
			
			$this->assertTrue(
				$RDomain->Name == $Domain->Name &&
				date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
				date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
				count($RDomain->GetNameserverList()) == count($Domain->GetNameserverList()) &&
				$RDomain->GetContact(CONTACT_TYPE::TECH)->CLID == $Tech->CLID,
				'Get remote domain'
			);
			
			////
			// Unlock domain
			$ok = $this->Registry->UnlockDomain($Domain);
			$this->assertTrue($ok, 'Unlock domain');
			
			////
			// UPDATE one of the name server�s IP Address
			$ns1->IPAddr = gethostbyname('ns.hostdad.com');
			$Resp = $this->Registry->GetModule()->UpdateNameserverHost($ns1);
			$this->assertTrue($Resp->Result, 'Update domain nameserver');
			
			/*
			////
			// Renew domain for 2 years 
			$old_expire_date = $Domain->ExpireDate;
			$this->Registry->RenewDomain($Domain, $extra=array('period' => 2));
			$this->assertTrue(
				date('Ymd', $Domain->ExpireDate) == date('Ymd', strtotime('+2 year', $old_expire_date)),
				'Domain renewal'
			);
			*/
			
			////
			// Remove nameservers from domain 
			$ns_list = $Domain->GetNameserverList();
			$Changes = $Domain->GetNameserverChangelist();
			foreach ($ns_list as $NS)
			{
				$Changes->Remove($NS);
			}
			$this->Registry->UpdateDomainNameservers($Domain, $Changes);
			$this->assertTrue(count($Domain->GetNameserverList()) == 0, 'Remove nameservers from domain');
			
			////
			// Delete nameservers
			foreach ($ns_list as $NS)
			{
				if (preg_match('/'.$Domain->Name.'/', $NS->HostName))
				{
					$this->Registry->DeleteNameserverHost($NS);
				}
			}
			$this->assertTrue(true, 'Delete nameservers');
			
			////
			// Delete domain
			$this->Registry->DeleteDomain($Domain);
			$this->assertTrue(true, 'Delete domain');
			
			////
			// Delete contacts
			$this->Registry->DeleteContact($Registrant);
			$this->Registry->DeleteContact($Tech);
			$this->assertTrue(true, 'Delete contacts');
        }
        
        /*
        function testHosts()
        {
        	return;
        	
        	$host = new Nameserver();
        	$host->HostName = "ns1.eppdrs.com";
        	$host->IPAddr = "216.239.32.10";
        	
        	$res = $this->registry->CreateNameserverHost($host);
        	$this->assertTrue(($res instanceof CreateNameserverHostResponse && $res->Status == REGISTRY_RESPONSE_STATUS::FAILED), "Nameserver host successfully added");
        	
        	exit();
        }
		*/
        
        /*
		function testIsDomainAvailable ()
        {
        	return;
        	
        	$d = new Domain();
        	$d->Name = "webtatest".rand(500000,9999999);
        	
        	$res = $this->registry->DomainCanBeRegistered($d);
        	$this->assertTrue(($res instanceof DomainCanBeRegisteredResponse && $res->Result), 'Random webta domain is available');
        	
        	$d = new Domain();
        	$d->Name = 'berlin';
        	$res = $this->registry->DomainCanBeRegistered($d);
        	$this->assertTrue(($res instanceof DomainCanBeRegisteredResponse && !$res->Result), 'Domain berlin not available');
        }
        
        function testRegistryManifest()
        {
        	$Manifest = new RegistryManifest(dirname(__FILE__)."/module.xml");
        	$Manifest->SetExtension("net");
        	$fields = $Manifest->GetContactFields("registrant");
        	$this->assertTrue(is_array($fields), "Manifest successfully parsed");
        }
		*/
        
        /*
        function testContacts()
        {
        	return;
        	
        	$c = new Contact();
        	$c->Type = "registrant";
        	
        	
        	$c->Fields = array(
        		'firstname' 	=> 'marat',
        		'lastname'		=> 'komarov',
        		'organization'	=> 'wtf',
        		'country' 		=> 'UA',
        		'state' 		=> 'crimea',
        		'city' 		=> 'sebastopol',
        		'zip' 		=> '3212',
        		'street' 	=> 'bbfdgfd fds',
        		'phone' 	=> '+33.12345678',
        		'fax' 		=> '+33.12345678',
        		'email' 	=> 'marat@webta.net'
        					);
        	$res = $this->registry->CreateContact($c, array());
        	$this->assertTrue(($res instanceOf CreateContactResponse && $res->CLID), "Contact successfully created");
        	$c->CLID = $res->CLID;
        	
        	$info = $this->registry->GetRemoteContact($c);
        	$this->assertTrue(($info instanceOf GetRemoteContactResponse && $info->street), "GetRemoteContact success");
        	        	
        	$res = $this->registry->ContactCanBeCreated($c);
        	$this->assertTrue(($res instanceOf ContactCanBeCreatedResponse && $res->Result == 1), "ContactCanBeCreated success");        	
        }
		*/
        
        /*
        function testCheckTransferStatus()
        {
        	return;
        	$domain = new Domain();
        	$domain->Name = "berlin2343";
//        	$this->assertFalse($this->registry->DomainCanBeTransferred($domain), "No existing domain cannot be transfered");
        	
        	$domain->Name = "aimbo";
        	//$this->assertTrue($this->registry->DomainCanBeTransferred($domain), "Existing domain can be transfered");
        	
        	//$res = $this->registry->TransferRequest($domain, array("auth" => "SrNg6MIP"));
        	
        	$res = $this->registry->CheckTransferStatus($domain); 
        }
		*/
        
        /*
        function testDelegateDomain() 
        {
			$domain = new Domain();
			$domain->SetNameserverList(array(new Nameserver("ns.hostdad.com"), new Nameserver("ns2.hostdad.com")));
			
			$domain->Name = "eppdrs";
			$domain->Data["c_registrant"] = "P-MTK771";
			$domain->Data["c_admin"] = "P-MTK771";
			$domain->Data["c_billing"] = "P-MTK771";
			$domain->Data["c_tech"] = "P-MTK771";
			
			$res = $this->registry->DelegateDomain($domain, 1);
			$this->assertTrue(($res instanceof Domain), "Domain successfully delegated");
			
			$info = $this->registry->GetRemoteDomain($domain);
			$this->assertTrue(($info instanceof Domain), "Remote domain received");
			
			$res = $this->registry->LockDomain($domain);
			$this->assertTrue($res, "Domain locked");
			
			$res = $this->registry->UnLockDomain($domain);
			$this->assertTrue($res, "Domain unlocked");
			
			$res = $this->registry->RenewDomain($domain);
			$this->assertTrue(($res instanceof Domain), "Domain contact successfully renewed");
			
			//
			// Test nameserver hosts
			//
			$nshost = new NameserverHost("ns1.eppdrs.com", "70.84.240.139");
			
			$res = $this->registry->CreateNameserverHost($nshost);
			$this->assertTrue(($res instanceof NameserverHost), "Nameserverhost created");
						
			$nshost->IPAddr = "70.84.240.140";
			
			$res = $this->registry->UpdateNameserverHost($nshost);
			$this->assertTrue($res, "Nameserverhost updated");
			
			$res = $this->registry->DeleteNameserverHost($nshost);
			$this->assertTrue($res, "Nameserverhost deleted");
						
			//P-IJS193
			$oldContact = new Contact("P-MTK771");
			
			$newContact = new Contact();
			$newContact->Type = "registrant";
			$newContact->CLID = "P-IJS193";
			
			$res = $this->registry->UpdateDomainContact($domain, $oldContact, $newContact);
			$this->assertTrue($res, "Domain contact successfully updated");
			
			$chlist = $domain->GetNameserverChangelist();
			
			$chlist->Add(new Nameserver("ns2.google.com"));
			$chlist->Remove(new Nameserver("ns2.hostdad.com"));
			
			$res = $this->registry->UpdateDomainNameservers($domain, $chlist);
			$this->assertTrue($res, "Nameservers updated");
			
			$res = $this->registry->DeleteDomain($domain);
			$this->assertTrue($res, "Domain successfully deleted");
			
			//$info->SetData(array("userid" => 3));
			//$info->Save();
        }
		*/
    }
    
?>