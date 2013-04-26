<?php
	require_once (dirname(__FILE__) . '/class.RegistryModule.php');
	require_once (dirname(__FILE__) . '/class.Transport.php');

	class AfiliasRegistryTests extends UnitTestCase
	{
		/**
		 * @var Registry
		 */
		private $Registry, $Registry2;
		
		/**
		 * @var GenericEPPRegistryModule
		 */
		private $Module, $Module2;
		
		private $module_conf, $module_conf2, $test_conf; 
		
		private $C2, $C3, $C4, $C5;
		
		private $NSList;
		
		private $eppinfodomain_admin_clid;
		
		function setUp ()
		{
			// User input and initialized by EPP-DRS in real test
			$TestConf = AfiliasRegistryModule::GetTestConfigurationForm();
			$test_conf = $TestConf->ListFields();
			$test_conf['RegistrarID']->Value = '5080-BD';
			$test_conf['ServerHost']->Value  = 'inforfcote2.afilias.net';
			//$test_conf['ServerHost']->Value  = 'inforfcote1.afilias.net';
			$test_conf['ServerPort']->Value  = '700';
			$test_conf['Login-1']->Value 	 = 'ClientB';
			$test_conf['Password-1']->Value  =  'foo-BAR2'; // 'foo-BAR2';
			//$test_conf['Login-1']->Value 	 = 'OTE5080-BD1';
			//$test_conf['Password-1']->Value  = '9ukUna2e';
			$test_conf['Password-1n']->Value  = 'bar-FOO2'; // New password
			
			$test_conf['Login-2']->Value 	 = 'OTE5080-BD2';
			$test_conf['Password-2']->Value  = 'zUp4jaF2';
			$test_conf['SSLCertPath']->Value = '/home/marat/cert/bindrop-certchain.pem';
			$test_conf['SSLCertPass']->Value = '';
			
			// Initialize registry
			$ModuleConf = AfiliasRegistryModule::GetConfigurationForm();
			$module_conf = $ModuleConf->ListFields();
			$module_conf['RegistrarID']->Value 	= $test_conf['RegistrarID']->Value;
			$module_conf['ServerHost']->Value 	= $test_conf['ServerHost']->Value;
			$module_conf['ServerPort']->Value 	= $test_conf['ServerPort']->Value;
			$module_conf['Login']->Value 		= $test_conf['Login-1']->Value;
			$module_conf['Password']->Value 	= $test_conf['Password-1']->Value;
			$module_conf['SSLCertPath']->Value 	= $test_conf['SSLCertPath']->Value;
			$module_conf['SSLCertPass']->Value 	= $test_conf['SSLCertPass']->Value;
			
			$ModuleConf2 = AfiliasRegistryModule::GetConfigurationForm();
			$module_conf2 = $ModuleConf2->ListFields();
			$module_conf2['RegistrarID']->Value = $test_conf['RegistrarID']->Value;
			$module_conf2['ServerHost']->Value 	= $test_conf['ServerHost']->Value;
			$module_conf2['ServerPort']->Value 	= $test_conf['ServerPort']->Value;
			$module_conf2['Login']->Value 		= $test_conf['Login-2']->Value;
			$module_conf2['Password']->Value 	= $test_conf['Password-2']->Value;
			$module_conf2['SSLCertPath']->Value = $test_conf['SSLCertPath']->Value;
			$module_conf2['SSLCertPass']->Value = $test_conf['SSLCertPass']->Value;			
			
			$manifest_path = MODULES_PATH . "/registries/Afilias/module.xml";
			
        	$this->Module = new AfiliasRegistryModule(new RegistryManifest($manifest_path));
        	$this->Module2 = new AfiliasRegistryModule(new RegistryManifest($manifest_path));
        	$this->Module->InitializeModule("info", $ModuleConf);
        	$this->Module2->InitializeModule("info", $ModuleConf2);
			$this->Registry = new Registry($this->Module);
			$this->Registry2 = new Registry($this->Module2);
			$this->module_conf = $module_conf;
			$this->module_conf2 = $module_conf2;
			$this->test_conf = $test_conf;
			
			$this->C2 = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
			$this->C2->CLID = 'EPPOTE-C2';
			$this->C3 = $this->Registry->NewContactInstance(CONTACT_TYPE::ADMIN);
			$this->C3->CLID = 'EPPOTE-C3';
			$this->C4 = $this->Registry->NewContactInstance(CONTACT_TYPE::BILLING);
			$this->C4->CLID = 'EPPOTE-C4';
			$this->C5 = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
			$this->C5->CLID = 'EPPOTE-C5';
			
			$this->NSList = array(
				new Nameserver('ns1.eppvalid.info'),
				new Nameserver('ns2.eppvalid.info')
			);
		}
		
	
		function testOTE ()
		{
			////
			// 1. Session Management
			
			// 1. Start session
			$this->startSession();
			// 2. Auth
			$this->login();
			// 3. Change password
			//$this->changePassword();

			////
			// 2. Domain name operations
			/*
			// 1. Create Domain with 4 Contacts and 2 Name Servers
			$this->createDomainWith4contacts2ns();
			// 2. Create Domain with Minimum Registration Period 
			$this->createDomainWithMinPeriod();
			// 3. Create Domain with Maximum Registration Period
			$this->createDomainWithMaxPeriod();
			// 4. Create Domain with Maximum Number of Name Servers
			$this->createDomainWithMaxNS();
			// 5. Create Domain with Minimum Length Domain Name (3 Chars + .info)
			$this->createDomainWithMinLengthName();
			// 6. Create Domain with Maximum Length Domain Name (63 Chars + .info)
			$this->createDomainWithMaxLengthName();
			// 7. Check Domain (Domain Not Available)
			$this->checkUnavailableDomain();
			// 8. Check Domain (Domain Available)
			$this->checkAvailableDomain();
			// 9. Check Domain with Maximum Length Domain Name (Domain Not Available)
			$this->checkDomainWithMaxLengthName();
			// 10. Query <info> Domain
			$this->queryDomainInfo();
			// 11. Query <info> Domain with Trademark
			$this->queryDomainInfoWithTM();
			// 12. Delete Domain
			$this->deleteDomain();
			// 13. Renew Domain
			$this->renewDomain();
			// 14. Renew Domain to Maximum Registration Period
			$this->renewDomainToMaxPeriod();
			// 15. Transfer a Domain
			$this->transferDomainRequest();
			// 16. Approve Domain Transfer
			$this->approveDomainTransfer();
			// 17. Reject Domain Transfer
			$this->rejectDomainTransfer();
			// 18. Change Domain Name Servers
			 * 
			 */
			$this->updateDomainNameservers();
			// 19. Change Domain Contact
			$this->updateDomainContact();
			// 20. Change Domain Status
			//$this->updateDomainStatus();

			/*
			////
			// 3. Name Server Operations
			
			// 1. Create Name Server
			$this->createNameserver();
			// 2. Create Name Server with Maximum Length Host Name (63 Chars + "." + 63 Chars + ".info")
			$this->createNameserverWithMaxLengthName();
			// 3. Create Name Server with Maximum Allowable IPs
			$this->createNamserverWithMaxIPNumber();
			// 4. Create Name Server (Foreign Registry)
			$this->createNameserverOutOfZone();
			// 5. Check Name Server (Name Server Unavailable)
			$this->checkUnavailableNameserver();
			// 6. Check Name Server (Name Server Available)
			$this->checkAvailableNameserver();
			// 7. Query Name Server
			$this->queryInfoNameserver();
			// 8. Delete Name Server
			$this->deleteNameserver();
			// 9. Change Name Server (Add IP Address)
			$this->updateNameserverAddIP();
			// 10. Change Name Server (Remove IP Address)
			$this->updateNameserverRemIP();
			
			////
			// 4. Contact operations
			//
			
			// 1. Create Contact
			$this->createContact();
			// 2. Check Contact (Contact Unavailable)
			$this->checkUnavailableContact();
			// 3. Check Contact (Contact Available)
			$this->checkAvailableContact();
			// 4. Query Contact
			$this->queryInfoContact();
			// 5. Transfer Contact
			$this->transferContactRequest();
			// 6. Query Contact Transfer Status
			$this->queryContactTransferStatus();
			// 7. Approve Contact Transfer
			$this->approveContactTransfer();
			// 8. Reject Contact Transfer
			$this->rejectContactTransfer();
			// 9. Change Contact (Change Element)
			$this->updateContactChgElement();
			// 10. Change Contact (Remove Element)
			$this->updateContactRemElement();
			// 11. Delete Contact
			$this->deleteContact();
			
			////
			// 5. Client Error Handing
			
			// 1. Correctly Handle 2003 Exception
			$this->error2003();
			// 2. Correctly Handle 2005 Exception
			$this->error2005();
			// 3. Correctly Handle 2306 Exception
			 * 
			 */
			$this->error2306();
			// 4. Correctly Handle 2002 Exception
//			$this->error2002();
			// 5. Correctly Handle 2303 Exception
			$this->error2303();
			// 6. Correctly Handle 2305 Exception
//			$this->error2305();
			// 7. Correctly Handle 2201 Exception
//			$this->error2201();
			
			return;
						
			////
			// 6. Empty Element Commands
			
			// 1. Keep Session Alive 
			$this->keepAlive();
			// 2. Request Message Queue Information
			$this->poll();
			// 3. Ack Queued Message
			$this->ack();
		}
		
		
		function startSession () 
		{
			$this->Module->GetTransport()->Connect();
			$this->pass("Start session");
		}
		
		function login ()
		{
        	$Resp = $this->Module->GetTransport()->Request('login', array(
        		'clid' 	=> $this->module_conf['Login']->Value,
        		'pw'	=> $this->module_conf['Password']->Value
        	));
        	$this->assertTrue(
        		$Resp->Code == RFC3730_RESULT_CODE::OK, 
        		"Login"
        	);
		}
		
		function changePassword ()
		{
        	$Resp = $this->Module->GetTransport()->Request('logout');
        	$this->Module->GetTransport()->Connect();
        	
        	$newPW = $this->test_conf['Password-1n']->Value;
        	
        	$Resp = $this->Module->GetTransport()->Request('test-change-pw', array(
        		'clid' 	=> $this->module_conf['Login']->Value,
        		'pw'	=> $this->module_conf['Password']->Value,
        		'newPw'	=> $newPW
        	));
        	$this->assertTrue($Resp->Code == 1000, "Change password");
        	
        	$this->module_conf['Password']->Value = $newPW; 
		}
		
		function createDomainWith4contacts2ns ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppvalid1';
			$Domain->SetNameserverList($this->NSList);
			$Domain->SetContact($this->C2, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($this->C3, CONTACT_TYPE::ADMIN);
			$Domain->SetContact($this->C4, CONTACT_TYPE::BILLING);
			$Domain->SetContact($this->C5, CONTACT_TYPE::TECH);
			$Domain->AuthCode = 'my secret';
			
			$Resp = $this->Module->CreateDomain($Domain, 2);
			
			$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "Create Domain with 4 Contacts and 2 Name Servers");
		}
		
		function createDomainWithMinPeriod ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'epp1year';
			$Domain->SetNameserverList($this->NSList);
			$Domain->SetContact($this->C2, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($this->C3, CONTACT_TYPE::ADMIN);
			$Domain->SetContact($this->C4, CONTACT_TYPE::BILLING);
			$Domain->SetContact($this->C5, CONTACT_TYPE::TECH);
			$Domain->AuthCode = 'my secret';
			
			$Resp = $this->Module->CreateDomain($Domain, 1);
			
			$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "Create Domain with Minimum Registration Period");
		}
		
		function createDomainWithMaxPeriod ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'epp10years';
			$Domain->SetNameserverList($this->NSList);
			$Domain->SetContact($this->C2, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($this->C3, CONTACT_TYPE::ADMIN);
			$Domain->SetContact($this->C4, CONTACT_TYPE::BILLING);
			$Domain->SetContact($this->C5, CONTACT_TYPE::TECH);
			$Domain->AuthCode = 'my secret';
			
			$Resp = $this->Module->CreateDomain($Domain, 10);
			
			$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "Create Domain with Maximum Registration Period");
		}
		
		function createDomainWithMaxNS ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppmaxhost';
			
			$ns_list = array();
			foreach (range(1, 13) as $i) 
			{
				$ns_list[] = new Nameserver("ns{$i}.eppvalid.info");
			} 			
			$Domain->SetNameserverList($ns_list);
			
			$Domain->SetContact($this->C2, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($this->C3, CONTACT_TYPE::ADMIN);
			$Domain->SetContact($this->C4, CONTACT_TYPE::BILLING);
			$Domain->SetContact($this->C5, CONTACT_TYPE::TECH);
			$Domain->AuthCode = 'my secret';
			
			$Resp = $this->Module->CreateDomain($Domain, null);
			
			$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "Create Domain with Maximum Number of Name Servers");
			
		}
		
		function createDomainWithMinLengthName ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'epp';
			$Domain->SetNameserverList($this->NSList);
			$Domain->SetContact($this->C2, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($this->C3, CONTACT_TYPE::ADMIN);
			$Domain->SetContact($this->C4, CONTACT_TYPE::BILLING);
			$Domain->SetContact($this->C5, CONTACT_TYPE::TECH);
			$Domain->AuthCode = 'my secret';
			
			$Resp = $this->Module->CreateDomain($Domain, null);
			
			$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "Create Domain with Minimum Length Domain Name");
		}
		
		function createDomainWithMaxLengthName ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefgh';
			$Domain->SetNameserverList($this->NSList);
			$Domain->SetContact($this->C2, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($this->C3, CONTACT_TYPE::ADMIN);
			$Domain->SetContact($this->C4, CONTACT_TYPE::BILLING);
			$Domain->SetContact($this->C5, CONTACT_TYPE::TECH);
			$Domain->AuthCode = 'my secret';
			
			$Resp = $this->Module->CreateDomain($Domain, null);
			
			$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "Create Domain with Maximum Length Domain Name");
		}
		
		
		function checkUnavailableDomain ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppvalid';
			
			$Resp = $this->Module->DomainCanBeRegistered($Domain);
			
			$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK && $Resp->Result == false, "Check Domain (Domain Not Available)");
		}
		
		function checkAvailableDomain ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppavailable';
			
			$Resp = $this->Module->DomainCanBeRegistered($Domain);
			
			$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK && $Resp->Result, "Check Domain (Domain Available)");
		}
		
		function checkDomainWithMaxLengthName ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefgh';
			
			$Resp = $this->Module->DomainCanBeRegistered($Domain);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK && $Resp->Result == false, 
				"Check Domain with Maximum Length Domain Name (Domain Not Available)"
			);
		}
		
		function queryDomainInfo ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppinfodomain';
			
			$Resp = $this->Module->GetRemoteDomain($Domain);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK &&
				$Resp->CLID == "ClientB" &&
				$Resp->RegistryStatus == "ok" &&
				$Resp->RegistrantContact = "EPPOTE-C2" &&
				$Resp->AdminContact = "EPPOTE-C3" &&
				$Resp->BillingContact = "EPPOTE-C4" &&
				$Resp->TechContact = "EPPOTE-C5" &&
				count($Resp->GetNameserverList()) == 2,
				"Query Domain"
			);
			
			$this->eppinfodomain_admin_clid = $Resp->AdminContact;
		}
		
		function queryDomainInfoWithTM ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'trademarkdomain';
			
			$Resp = $this->Module->GetRemoteDomain($Domain);
			
			$extra = $Resp->GetExtraData();
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK &&
				$Resp->CLID == "ClientB" &&
				$Resp->RegistryStatus == "ok" &&
				$Resp->RegistrantContact = "EPPOTE-C2" &&
				$Resp->AdminContact = "EPPOTE-C3" &&
				$Resp->BillingContact = "EPPOTE-C4" &&
				$Resp->TechContact = "EPPOTE-C5" &&
				count($Resp->GetNameserverList()) == 2 &&
				$extra['info-tm-name'] == 'Test Trademark' &&
				$extra['info-tm-number'] == '998877',
				"Query Domain with Trademark"
			);
			
		}
		
		function deleteDomain ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppdeleteme';
			
			$Resp = $this->Module->DeleteDomain($Domain);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK &&
				$Resp->Result == true,
				"Delete Domain"
			);
		}
		
		function renewDomain ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'epprenewable';
			
			// Get expiration date
			$Grd = $this->Module->GetRemoteDomain($Domain);
			$Domain->ExpireDate = $Grd->ExpireDate;
			
			// Renew domain
			$Resp = $this->Module->RenewDomain($Domain, array('period' => 5));
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK &&
				date('Ymd', strtotime("+5 year", $Grd->ExpireDate)) == date('Ymd', $Resp->ExpireDate),
				"Renew Domain"
			);
			
			$this->renewable_create_date = $Grd->CreateDate;
			$this->renewable_expire_date = $Resp->ExpireDate;
		}
		
		function renewDomainToMaxPeriod ()
		{
			// Max registration period = 10
			$period = 10 - ((int)date('Y', $this->renewable_expire_date) - (int)date('Y', $this->renewable_create_date)); 			
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'epprenewable';
			$Domain->ExpireDate = $this->renewable_expire_date;

			$Resp = $this->Module->RenewDomain($Domain, array('period' => $period));
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Renew Domain to Maximum Registration Period"
			);
		}
		
		function transferDomainRequest ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'epptransfer1';
			
			$Resp = $this->Module->TransferRequest($Domain, array('pw' => 'my secretY'));
			$this->AssertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK_PENDING,
				"Transfer a Domain"
			);
		}
		
		function approveDomainTransfer ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'epptransfer2';
			$Domain->AuthCode = 'my secretX';
			
			$Resp = $this->Module->TransferApprove($Domain);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Approve Domain Transfer"
			);
		}
		
		function rejectDomainTransfer ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'epptransfer3';
			$Domain->AuthCode = 'my secretX';
			
			$Resp = $this->Module->TransferReject($Domain);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Reject Domain Transfer"
			);
		}
		
		function updateDomainNameservers ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppinfodomain';
			
			$NSEx1 = new Nameserver('ns1.eppinfodomain.info');
			$NSEx2 = new Nameserver('ns2.eppinfodomain.info');
			$Changes = new Changelist(array($NSEx1, $NSEx2));
			$Changes->Add($this->NSList[0]);
			$Changes->Add($this->NSList[1]);
			$Changes->Remove($NSEx1);
			$Changes->Remove($NSEx2);
			
			$Resp = $this->Module->UpdateDomainNameservers($Domain, $Changes);
			
			$this->assertTrue(
				$Resp->Succeed(),
				'Change Domain Name Servers'
			);
		}
		
		function updateDomainContact ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppinfodomain';
			
			$OldContact = $this->Registry->NewContactInstance(CONTACT_TYPE::ADMIN);
			//$OldContact->CLID = $this->eppinfodomain_admin_clid;
			$OldContact->CLID = 'EPPOTE-C3';
			
			$Resp = $this->Module->UpdateDomainContact($Domain, CONTACT_TYPE::ADMIN, $OldContact, $this->C4);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Change Domain Contact"
			);
		}
		
		function updateDomainStatus ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'eppinfodomain';
			
			$Changes = new Changelist(array());
			$Changes->Add('clientUpdateProhibited');
			
			$Resp = $this->Module->UpdateDomainFlags($Domain, $Changes);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Change Domain Status"
			);
		}
		
		
		function createNameserver ()
		{
			$NSHost = new NameserverHost("ns1.eppnewname.info", "192.168.10.11");
			
			$Resp = $this->Module->CreateNameserverHost($NSHost);
			
			$this->assertTrue(
				$Resp->Succeed(),
				"Create Name Server"
			);
		}
		
		function createNameserverWithMaxLengthName ()
		{
			$NSHost = new NameserverHost(
				"abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijk" . 
				"." .
				"eppabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefgh" . 
				"." . 
				"info", 
				"192.168.10.12"
			);
			
			$Resp = $this->Module->CreateNameserverHost($NSHost);
			
			$this->assertTrue(
				$Resp->Succeed(),
				"Create Name Server with Maximum Length Host Name"
			);
			
		}
		
		function createNamserverWithMaxIPNumber ()
		{
			$hostname = "ns2.eppnewname.info";
			$addrs = array();
			foreach (range(1, 13) as $n)
			{
				$addrs[] = "<host:addr ip=\"v4\">192.168.10.{$n}</host:addr>";
			}
			
			$Resp = $this->Module->Request('host-create', array(
        		'name' => $hostname,
        		'addr' => join('', $addrs)
        	));
        	
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Create Name Server with Maximum Allowable IPs"
			);
		}
		
		function createNameserverOutOfZone ()
		{
			$NS = new Nameserver("ns1.eppvalid.com");
			
			$Resp = $this->Module->CreateNameserver($NS);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Create Name Server (Foreign Registry)"
			);
		}
		
		function checkUnavailableNameserver ()
		{
			$Resp = $this->Module->NameserverCanBeCreated(new Nameserver("ns1.eppvalid.info"));

			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK && !$Resp->Result,
				"Check Name Server (Name Server Unavailable)"
			);			
		}
		
		function checkAvailableNameserver ()
		{
			$Resp = $this->Module->NameserverCanBeCreated(new Nameserver("ns1.eppavailable.info"));
        	
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK && $Resp->Result,
				"Check Name Server (Name Server Available)"
			);
		}
		
		function queryInfoNameserver ()
		{
        	try {
	        	$Resp = $this->Module->Request('host-info', array(
	        		'hostname' => "ns1.eppvalid.info"
	        	));
	        	$this->assertTrue(
	        		$Resp->Code == RFC3730_RESULT_CODE::OK, 
	        		"Query Name Server"
	        	);
        	} catch (ObjectNotExistsException $e) {
        		$this->fail("Query Name Server. " . $e->getMessage());
        	}
		}
		
		function deleteNameserver ()
		{
			$NS = new NameserverHost("ns1.eppdelns.info", "");
			
			$Resp = $this->Module->DeleteNameserverHost($NS);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Delete Name Server"
			);
		}
		
		function updateNameserverAddIP ()
		{
			$Resp = $this->Module->Request("test-host-update", array(
				'hostname' => "ns12.eppvalid.info",
				'add' => '<host:add><host:addr ip="v4">192.1.2.3</host:addr></host:add>',
				'rem' => ''
			));
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Change Name Server (Add IP Address)"
			);
		}
		
		function updateNameserverRemIP ()
		{
			$Resp = $this->Module->Request("test-host-update", array(
				'hostname' => "ns12.eppvalid.info",
				'add' => '',
				'rem' => '<host:rem><host:addr ip="v4">192.1.2.3</host:addr></host:rem>',
			));
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Change Name Server (Remove IP Address)"
			);
		}
		
		function createContact ()
		{
			$contact_data = array(
        		'id'		=> 'EPPOTE-C6',
        		'name' 		=> 'John Doe',
        		'org' 		=> 'Example Corp. Inc',
        		'cc' 		=> 'CA',
        		'sp' 		=> 'Any Prov',
        		'city' 		=> 'Anytown',
        		'pc' 		=> 'A1A1A1',
        		'street1' 	=> '123 Example St.',
				'street2' 	=> 'Suite 100',
        		'voice' 	=> '+1.4165555555',
        		'voice_ext'	=> '1111',
        		'fax' 		=> '+1.4165555556',
        		'email' 	=> 'jdoe@eppvalid.info',
        		'pw' 		=> 'my secret'
        	);
        	
        	$Resp = $this->Module->Request('test-contact-create', $contact_data);
        	$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "Create Contact");			
		}
		
		function checkUnavailableContact ()
		{
			$Contact = $this->Registry->NewContactInstanceByGroup("generic");
			$Contact->CLID = 'EPPOTE-C6';
			
			$Resp = $this->Module->ContactCanBeCreated($Contact);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK &&
				!$Resp->Result,
				"Check Contact (Contact Unavailable)"
			);
		}
		
		function checkAvailableContact ()
		{
			$Contact = $this->Registry->NewContactInstanceByGroup("generic");
			$Contact->CLID = 'EPPOTE-C8';
			
			$Resp = $this->Module->ContactCanBeCreated($Contact);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK &&
				$Resp->Result,
				"Check Contact (Contact Available)"
			);
		}
		
		function queryInfoContact ()
		{
			$Contact = $this->Registry->NewContactInstanceByGroup("generic");
			$Contact->CLID = 'EPPOTE-C6';
			
			$Resp = $this->Module->GetRemoteContact($Contact);
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK &&
				$Resp->CLID == 'EPPOTE-C6' &&
				$Resp->name == 'John Doe',
				"Query Contact"
			);
		}
		
		function transferContactRequest ()
		{
        	$Resp = $this->Module->Request('test-contact-trans-request', array(
				'id' => 'EPPOTE-C7',
        		'pw' => 'my secret'
        	));
        	
        	$this->assertTrue(
        		$Resp->Code == RFC3730_RESULT_CODE::OK,
        		"Transfer Contact"
        	);
		}
		
		function queryContactTransferStatus ()
		{
        	$Resp = $this->Module->Request('test-contact-trans-query', array(
				'id' => 'EPPOTE-C7',
				'pw' => 'my secret'        	
        	));

        	$trnData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:contact-1.0");
        	
        	$this->assertTrue(
        		$trnData &&
        		(string)$trnData[0]->id == 'EPPOTE-C7' &&
        		(string)$trnData[0]->trStatus == 'pending',
        		"Query Contact Transfer Status"
        	);
		}
		
		function approveContactTransfer ()
		{
        	$Resp = $this->Module->Request('test-contact-trans-approve', array(
				'id' => 'EPPOTE-C1-approv',
				'pw' => 'my secret'
        	));
			
        	$this->assertTrue(
        		$Resp->Code == RFC3730_RESULT_CODE::OK,
        		"Approve Contact Transfer"
        	);
		}
		
		function rejectContactTransfer ()
		{
        	$Resp = $this->Module->Request('test-contact-trans-reject', array(
				'id' => 'EPPOTE-C1-reject',
				'pw' => 'my secret'
        	));
			
        	$this->assertTrue(
        		$Resp->Code == RFC3730_RESULT_CODE::OK,
        		"Reject Contact Transfer"
        	);
		}
		
		function updateContactChgElement ()
		{
        	$contact_data = array(
        		'id'		=> 'EPPOTE-C6',
        		'name' 		=> 'Mr. Contact',
        		'org' 		=> 'Example Corp. Inc',
        		'cc' 		=> 'CA',
        		'sp' 		=> 'Any Prov',
        		'city' 		=> 'Anytown',
        		'pc' 		=> 'A1A1A1',
        		'street1' 	=> '123 Example St.',
				'street2' 	=> 'Suite 100'
        	);
        	$Resp = $this->Module->Request('test-contact-update-chg', $contact_data);
        	
        	$this->assertTrue(
        		$Resp->Code == RFC3730_RESULT_CODE::OK, 
        		"Change Contact (Change Element)"
        	);			
		}
		
		function updateContactRemElement ()
		{
			$Resp = $this->Module->Request('test-contact-update-rem', array(
				'id' => 'EPPOTE-C6'
			));
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Change Contact (Remove Element)"
			);
		}
		
		function deleteContact ()
		{
			$title = "Delete Contact";
			
			$contact_data = array(
        		'id'		=> 'EPPOTE-C8',
        		'name' 		=> 'Delete Me',
        		'org' 		=> 'Example Corp. Inc',
        		'cc' 		=> 'CA',
        		'sp' 		=> 'Any Prov',
        		'city' 		=> 'Anytown',
        		'pc' 		=> 'A1A1A1',
        		'street1' 	=> '123 Example St.',
				'street2' 	=> 'Suite 100',
        		'voice' 	=> '+1.4165555555',
        		'voice_ext'	=> '1111',
        		'fax' 		=> '+1.4165555556',
        		'email' 	=> 'jdoe@eppvalid.info',
        		'pw' 		=> 'my secret'
        	);
        	
        	$Resp = $this->Module->Request('test-contact-create', $contact_data);

        	if ($Resp->Code == RFC3730_RESULT_CODE::OK)
        	{
        		$Resp = $this->Module->Request('contact-delete', array(
        			'id' => 'EPPOTE-C8'
        		));
        		
        		$this->assertTrue(
        			$Resp->Code == RFC3730_RESULT_CODE::OK,
        			$title
        		);
        	}
        	else
        	{
        		$this->fail($title);
        	}
		}
		
		
		function error2003 ()
		{
			$title = "Correctly Handle 2003 Exception";
			
			try {
				$Resp = $this->Module->Request("test-error2003", array());
				
				$this->assertTrue(
					$Resp->Code == RFC3730_RESULT_CODE::ERR_REQUIED_PARAM_MISS,
					$title
				);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function error2005 ()
		{
			$title = "Correctly Handle 2005 Exception";
			
			try {
				$Resp = $this->Module->Request("test-error2005", array());
				$this->assertTrue(
					$Resp->Code == RFC3730_RESULT_CODE::ERR_PARAM_SYNTAX,
					$title
				);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function error2306 ()
		{
			$title = "Correctly Handle 2306 Exception";
			
			try {
				$Resp = $this->Module->Request("test-error2306", array());
				$this->assertTrue(
					$Resp->Code == RFC3730_RESULT_CODE::ERR_PARAM_VALUE_POLICY,
					$title
				);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function error2002 ()
		{
			$title = "Correctly Handle 2002 Exception";
			
			try {
				$Resp = $this->Module->Request("test-error2002", array());
				$this->assertTrue(
					$Resp->Code == RFC3730_RESULT_CODE::ERR_CMD_USE,
					$title
				);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function error2303 ()
		{
			$title = "Correctly Handle 2303 Exception";
			
			try {
				$Resp = $this->Module->Request("test-error2303", array());
				$this->fail($title);
			} catch (ObjectNotExistsException $e) {
				$this->pass($title);
			}
		}
		
		function error2305 ()
		{
			$title = "Correctly Handle 2305 Exception";
			
			try {
				$Resp = $this->Module->Request("test-error2305", array());
				$this->assertTrue(
					$Resp->Code == RFC3730_RESULT_CODE::ERR_OBJECT_ASSOC_PROHIBITS_OP,
					$title
				);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function error2201 ()
		{
			$title = "Correctly Handle 2201 Exception";
			
			try {
				$Resp = $this->Module->Request("test-error2201", array());
				$this->assertTrue(
					$Resp->Code == RFC3730_RESULT_CODE::ERR_AUTHORIZE_ERROR,
					$title
				);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function keepAlive ()
		{
			// for 30 minutes each 1 minute send 'hello'
			for ($i=0; $i<30; $i++) 
			{
				$this->Module->Request('hello', array());
				sleep(60);
			}
		}
		
		function poll ()
		{
			$Resp = $this->Module->Request("poll-request", array());

			$this->poll_msg_id = (string)$Resp->Data->response->msgQ->attributes()->id;
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE,
				"Request Message Queue Information"
			);
		}
		
		function ack ()
		{
			$Resp = $this->Module->Request('poll-ack', array(
				'msgID' => $this->poll_msg_id
			));
			
			$this->assertTrue(
				$Resp->Code == RFC3730_RESULT_CODE::OK,
				"Ack Queued Message"
			);
		}
		
		
		function endSession ()
		{
        	$Resp = $this->Module->GetTransport()->Request('logout', array());
        	$this->assertTrue(
        		$Resp->Code == RFC3730_RESULT_CODE::OK_END_SESSION, 
        		"End Session"
        	);			
		}
	}
	

?>