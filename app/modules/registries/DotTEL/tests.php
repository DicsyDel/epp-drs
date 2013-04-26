<?	
	require_once(dirname(__FILE__).'/class.Transport.php');
	require_once(dirname(__FILE__).'/class.RegistryModule.php');

	class DotTELRegistryTests extends UnitTestCase 
	{
		private $module_conf, $module_conf2;
		
		/**
		 * @var DotTELRegistryModule
		 */
		private $Module, $Module2;
		
		/**
		 * @var Registry
		 */
		private $Registry, $Registry2;
		
		private $Prefix;
		
		private $cur_exp_date, $create_date, $admin_clid;
		
		function setUp ()
		{
			$Factory = RegistryModuleFactory::GetInstance();
			$this->Registry = $Factory->GetRegistryByExtension("tel");
		
			/*
			
			old stuff for ote
		
			// User input and initialized by EPP-DRS in real test
			$TestConf = DotTELRegistryModule::GetTestConfigurationForm();
			$test_conf = $TestConf->ListFields();
			$test_conf['ServerHost']->Value  = 'web3.hostdad.com';
			$test_conf['ServerPort']->Value  = '707';
			$test_conf['Login-1']->Value 	 = 'NDN29Y';
			$test_conf['Password-1']->Value  = 'AwTkQy';
			$test_conf['Login-2']->Value 	 = 'NDN29Y2';
			$test_conf['Password-2']->Value  = 'AwTkQy';
			$test_conf['SSLCertPath']->Value = '/home/marat/cert/tel-certchain.pem';
			$test_conf['SSLCertPass']->Value = '';
			// 
			
			// Initialize registry
			$ModuleConf = DotTELRegistryModule::GetConfigurationForm();
			$module_conf = $ModuleConf->ListFields();
			$module_conf['ServerHost']->Value 	= $test_conf['ServerHost']->Value;
			$module_conf['ServerPort']->Value 	= $test_conf['ServerPort']->Value;
			$module_conf['Login']->Value 		= $test_conf['Login-1']->Value;
			$module_conf['Password']->Value 	= $test_conf['Password-1']->Value;
			$module_conf['SSLCertPath']->Value 	= $test_conf['SSLCertPath']->Value;
			$module_conf['SSLCertPass']->Value 	= $test_conf['SSLCertPass']->Value;
			
			$ModuleConf2 = DotTELRegistryModule::GetConfigurationForm();
			$module_conf2 = $ModuleConf2->ListFields();
			$module_conf2['ServerHost']->Value 	= $test_conf['ServerHost']->Value;
			$module_conf2['ServerPort']->Value 	= $test_conf['ServerPort']->Value;
			$module_conf2['Login']->Value 		= $test_conf['Login-2']->Value;
			$module_conf2['Password']->Value 	= $test_conf['Password-2']->Value;
			$module_conf2['SSLCertPath']->Value = $test_conf['SSLCertPath']->Value;
			$module_conf2['SSLCertPass']->Value = $test_conf['SSLCertPass']->Value;
			
        	$this->Module = new DotTELRegistryModule();
        	$this->Module2 = new DotTELRegistryModule();
        	$this->Module->InitializeModule("tel", $ModuleConf);
        	$this->Module2->InitializeModule("tel", $ModuleConf2);
			$this->Registry = new Registry($this->Module);
			$this->Registry2 = new Registry($this->Module2);
			$this->module_conf = $module_conf;
			$this->module_conf2 = $module_conf2;
			
			
        	// Read test preferences
			$prefs = parse_ini_file(MODULES_PATH . '/registries/DotTEL/testprefs');
			$prefs['num']++;
        	
			$this->Prefix = "absdef" . sprintf('%02d', $prefs['num']);
			
			// Save prefs to the next run
			$prefs_file = '';
			foreach ($prefs as $k => $v)
			{
				$prefs_file .= "{$k}={$v}\n";
			}
			file_put_contents(MODULES_PATH . '/registries/DotTEL/testprefs', $prefs_file);
			*/
		}
        
		function testCrDomain ()
		{
			$xmlstr = <<<A
<?xml version="1.0" encoding="UTF-8"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
<response>
<result code="1000">
<msg lang="en-US">Command completed successfully</msg>
<value>
<text>SRS Major Code: 2000</text>
</value>
<value>
<text>SRS Minor Code: 20001</text>
</value>
<value>
<text>--DOMAIN_SUCCESSFULLY_ADDED</text>
</value>
</result>
<resData>
<domain:creData xmlns="urn:ietf:params:xml:ns:domain-1.0" xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
<domain:name>KVENTIN-TARANTINO.TEL</domain:name>
<domain:crDate>2008-12-05T09:22:47.0Z</domain:crDate>
<domain:exDate>2011-12-04T23:59:59.0Z</domain:exDate>
</domain:creData>
</resData>
<extension>
<neulevel:extension xmlns="urn:ietf:params:xml:ns:neulevel-1.0" xmlns:neulevel="urn:ietf:params:xml:ns:neulevel-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:neulevel-1.0 neulevel-1.0.xsd">
<neulevel:unspec>ApplicationID=D99601-TEL</neulevel:unspec>
</neulevel:extension>
</extension>
<trID>
<clTRID>NDN29Y-1228468966</clTRID>
<svTRID>20081205092247021-109113035-26</svTRID>
</trID>
</response>
</epp>			
A;
			$Data = simplexml_load_string($xmlstr);
			$extension = $Data->response->extension->children("urn:ietf:params:xml:ns:neulevel-1.0");
			$extension = $extension[0];
			$pairs = explode(" ", (string)$extension->unspec);
			$unspec = array();
			foreach ($pairs as $pair)
			{
				list($key, $value) = explode("=", $pair);
				$unspec[$key] = $value;
			}
			

			var_dump($unspec);
			
			
		
		}
		
		function _testPoll ()
		{
			$Resp = $this->Module->ReadMessage();
			print_r($Resp);
			$this->Module->AcknowledgeMessage($Resp);
			
		}
		
        function _testOTE ()
        {
        	// Transport tests
        	
	       	// 1. hello
        	$this->hello();
        	// 2. login with First account
        	$this->login();
        	// 3. change password
        	$this->changePassword();
        	// 4. logout
        	$this->logout();
        	
        	// Contact tests
        	
        	// 5. create contact
        	$this->createContact();
        	// 6. check contact (contact known)
        	$this->checkExistedContact();
        	// 7. check contact (contact unknown)
        	$this->checkUnexistedContact();
        	// 8. <info> query contact
        	$this->infoQueryContact();
        	// 9. transfer contact request
        	$this->transferContactRequest();
        	// 10. query contact transfer status
        	$this->queryContactTransferStatus();
        	// 11. reject contact trnasfer
        	$this->rejectContactTransfer();
        	// 12. update contact (change element)
        	$this->updateContactChgElement();
        	// 13. update contact (remove element)
        	$this->updateContactRemElement();
        	// 14. delete contact
        	$this->deleteContact();
        	
        	// Host tests
        	
        	// 15. create domain contact
        	$this->createDomainContact();
        	// 16. create domain
        	$this->createDomain();
        	// 17. create out-of-zone host
        	$this->createNameserver();
        	// 18. create in-zone host
        	$this->createNameserverHost();
        	// 19. check host (host known)
        	$this->checkExistedHost();
        	// 20. check host (host unknown)
        	$this->checkUnexistedHost();        	
        	// 21. <info> query host
        	$this->infoQueryHost();
        	// 22. update host (remove IP address)
        	$this->updateHostRemIP();
        	// 23. update host
        	$this->updateHost();
        	// 24. delete host
        	$this->deleteHost();
        	
        	// Domain tests
			
        	// 25. create domain without nameservers and contacts
        	$this->createDomainWithoutAll();
        	// 26. create domain with nameservers
        	$this->createDomainWithNameserverHosts();
        	// 27. create domain with all required
        	$this->createDomainWithAllRequired();
        	// 28. create domain with maximum registration period
        	$this->createDomainWithMaxPeriod();
        	// 29. create domain with invalid name
        	$this->createDomainWithInvalidName();
        	// 30. check domain (domain not available)
        	$this->checkUnavailableDomain();
        	// 31. check domain (domain available)
        	$this->checkAvailableDomain();
        	// 32. <info> query domain
        	$this->infoQueryDomain();
        	// 33. delete domain
        	$this->deleteDomain();
        	// 34. renew domain
        	$this->renewDomain();
        	// 35. renew domain to max registration period
        	$this->renewDomainToMaxPeriod();
        	// 36. transfer domain request
        	$this->transferDomainRequest();
        	// 37. query domain transfer status
        	$this->queryDomainTransferStatus();
        	// 38. update domain nameservers
        	$this->updateDomainNameservers();
        	// 39. update domain contact
        	$this->updateDomainContact();
        	// 40. update domain status
        	$this->updateDomainStatus();

        }
        
        function hello () 
        {
        	$this->Module->GetTransport()->Connect();
        	$this->Module->GetTransport()->Request('hello', array());
        	$this->pass("hello");
        }
        
        function login ()
        {
        	$Resp = $this->Module->GetTransport()->Request('login', array(
        		'clid' 	=> $this->module_conf['Login']->Value,
        		'pw'	=> $this->module_conf['Password']->Value,
        		'clTRID'=> $this->Prefix . 'ote-testcase02cmd'
        	));
        	$this->assertTrue($Resp->Code == 1000, "login");
        }
        
        function changePassword ()
        {
        	$Resp = $this->Module->GetTransport()->Request('login-ex', array(
        		'clid' 	=> $this->module_conf['Login']->Value,
        		'pw'	=> $this->module_conf['Password']->Value,
        		'newPw'	=> 'qwerty99',
        		'clTRID'=> $this->Prefix . 'ote-testcase03cmd'
        	));
        	$this->assertTrue($Resp->Code == 1000, "change password");
        	
        	$this->module_conf['Password'] = 'qwerty99'; 
        }
        
        function logout ()
        {
        	$Resp = $this->Module->GetTransport()->Request('logout', array(
        		'clTRID'=> $this->Prefix . 'ote-testcase04cmd'
        	));
        	$this->assertTrue($Resp->Code == 1500, "logout");
        }
        
        function createContact ()
        {
        	$contact_data = array(
        		'id'		=> $this->Prefix . 'cont1',
        		'name' 		=> 'Jim L. Anderson',
        		'org' 		=> 'Cobolt Boats, Inc.',
        		'cc' 		=> 'US',
        		'sp' 		=> 'Florida',
        		'city' 		=> 'Clearwater',
        		'pc' 		=> '50398-001',
        		'street1' 	=> '3375 Clear Creek Drive',
				'street2' 	=> 'Building One',
        		'voice' 	=> '+1.7653455566',
        		'voice_ext'	=> '+12',
        		'fax' 		=> '+1.7653455568',
        		'email' 	=> 'janderson@worldnet.tld',
        		'pw' 		=> 'mysecret',
        		'clTRID'	=> $this->Prefix . 'ote-testcase05cmd'
        	);
        	$Resp = $this->Module->Request('test-contact-create', $contact_data);
        	$this->assertTrue($Resp->Code == 1000, "create contact");
        }
        
        function checkExistedContact ()
        {
			$Resp = $this->Module->Request('contact-check', array(
				'id' => $this->Prefix . 'cont1',
				'clTRID' => $this->Prefix . 'ote-testcase06cmd'
			));

			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:contact-1.0");
			$attr = $chkData[0]->cd[0]->id[0]->attributes();
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				(string)$attr['avail'] == 0,
				"check contact (contact known)"
			);
        }
        
        
        function checkUnexistedContact ()
        {
			$Resp = $this->Module->Request('contact-check', array(
				'id' => $this->Prefix . 'cont99',
				'clTRID' => $this->Prefix . 'ote-testcase07cmd'
			));

			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:contact-1.0");
			$attr = $chkData[0]->cd[0]->id[0]->attributes();
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				(string)$attr['avail'] == 1,
				"check contact (contact unknown)"
			);
        }
        
        function infoQueryContact ()
        {
			try {
	        	$Resp = $this->Module->Request('contact-info', array(
					'id' => $this->Prefix . 'cont1',
					'clTRID' => $this->Prefix . 'ote-testcase08cmd'
				));
	        	$this->assertTrue($Resp->Succeed, "<info> query contact");
			} catch (ObjectNotExistsException $e) {
				$this->fail("<info> query contact. " . $e->getMessage());
			}
        }
        
        function transferContactRequest ()
        {
        	$Resp = $this->Module2->Request('contact-trans-request', array(
				'id' => $this->Prefix . 'cont1',
        		'pw' => 'qwerty02',
				'clTRID' => $this->Prefix . 'ote-testcase09cmd'
        	));
        	
        	$this->assertTrue(
        		$Resp->Code == RFC3730_RESULT_CODE::OK_PENDING,
        		"transfer contact request"
        	);
        }
        
        function queryContactTransferStatus ()
        {
        	$Resp = $this->Module2->Request('contact-trans-query', array(
				'id' => $this->Prefix . 'cont1',
				'clTRID' => $this->Prefix . 'ote-testcase10cmd'
        	));

        	$trnData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:contact-1.0");
        	
        	$this->assertTrue(
        		(string)$trnData[0]->id == $this->Prefix . 'cont1' &&
        		(string)$trnData[0]->trStatus == 'pending',
        		"query contact transfer status"
        	);
        }
        
        function rejectContactTransfer ()
        {
			$Resp = $this->Module->Request('contact-trans-reject', array(
				'id' => $this->Prefix . 'cont1',
				'clTRID' => $this->Prefix . 'ote-testcase11cmd'
			));
        	$this->assertTrue(
        		$Resp->Code == RFC3730_RESULT_CODE::OK_PENDING, 
        		"reject contact transfer"
        	);
        }
        
        function updateContactChgElement ()
        {
        	$contact_data = array(
        		'id'		=> $this->Prefix . 'cont1',
        		'name' 		=> 'Jim L. Anderson',
        		'cc' 		=> 'US',
        		'sp' 		=> 'Florida',
        		'city' 		=> 'Clearwater',
        		'pc' 		=> '50398-001',
        		'sp' 		=> 'Crimea',
        		'street1' 	=> '3377 Clear Creek Drive',
				'street2' 	=> 'Pier 15',
        		'voice' 	=> '+1.7034444444',
        		'pw' 		=> 'mysecret',
        		'clTRID'	=> $this->Prefix . 'ote-testcase12cmd'
        	);
        	$Resp = $this->Module->Request('test-contact-update-chg', $contact_data);
        	
        	$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "update contact (change element)");
        }
        
        function updateContactRemElement ()
        {
        	$Resp = $this->Module->Request('test-contact-update-rem', array(
        		'id' => $this->Prefix . 'cont1',
        		'clTRID'	=> $this->Prefix . 'ote-testcase13cmd'
        	));
        	
        	$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "update contact (remove element)");
        }
        
        function deleteContact ()
        {
        	$Resp = $this->Module->Request('contact-delete', array(
        		'id' => $this->Prefix . 'cont1',
        		'clTRID'	=> $this->Prefix . 'ote-testcase14cmd'
        	));
        	
        	$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, "delete contact");
        }
        
        function createDomainContact ()
        {
        	$contact_data = array(
        		'id'		=> $this->Prefix . 'cont2',
        		'name' 		=> 'Jim L. Anderson',
        		'org' 		=> 'Cobolt Boats, Inc.',
        		'cc' 		=> 'US',
        		'sp' 		=> 'Florida',
        		'city' 		=> 'Clearwater',
        		'pc' 		=> '50398-001',
        		'sp' 		=> 'Crimea',
        		'street1' 	=> '3375 Clear Creek Drive',
				'street2' 	=> 'Building One',
        		'voice' 	=> '+1.7653455566',
        		'voice_ext'	=> '+12',
        		'fax' 		=> '+1.7653455568',
        		'email' 	=> 'janderson@worldnet.tld',
        		'pw' 		=> 'mysecret',
        		'clTRID'	=> $this->Prefix . 'ote-testcase15cmd'
        	);
        	
        	$Resp = $this->Module->Request('test-contact-create', $contact_data);
        	$this->assertTrue($Resp->Code == 1000, "create contact");
        }
        
        function createDomain () 
        {
        	$contact_clid = $this->Prefix . 'cont2';
        	
        	$domain_data = array(
				'name' =>  $this->Prefix . 'inzone.tel',
        		'period' => 2,
        		'registrant_id' => $contact_clid,
        		'contacts' => '<domain:contact type="admin">'.$contact_clid.'</domain:contact>' 
        			. '<domain:contact type="tech">'.$contact_clid.'</domain:contact>'
					. '<domain:contact type="billing">'.$contact_clid.'</domain:contact>',
				'ns' => '',
				'pw' => 'mysecret',
				'unspec' => 'WhoisType=LEGAL',
				'clTRID' => $this->Prefix . 'ote-testcase16cmd'
        	);
        	
        	$Resp = $this->Module->Request('domain-create', $domain_data);
        	$this->assertTrue($Resp->Code == 1000, "create domain");
        }
        
        function createNameserver ()
        {
			$Resp = $this->Module->Request('host-create', array(
				'name' => "ns1.{$this->Prefix}outzone.abc",
				'addr' => '',
				'clTRID' => $this->Prefix . 'ote-testcase17cmd'
			));
			
			$this->assertTrue($Resp->Code == 1000, "create out-of-zone host");
        }
        
        function createNameserverHost ()
        {
        	$Resp = $this->Module->Request('host-create', array(
        		'name' => "ns1.{$this->Prefix}inzone.tel",
        		'addr' => '<host:addr ip="v4">209.1.2.3</host:addr>' 
        			. '<host:addr ip="v4">209.1.2.4</host:addr>',
        		'clTRID' => $this->Prefix . 'ote-testcase18cmd'
        	));
			$this->assertTrue($Resp->Code == 1000, "create in-zone host");        	
        }
        
        function checkExistedHost ()
        {
        	$Resp = $this->Module->Request('host-check', array(
        		'hostname' => "ns1.{$this->Prefix}inzone.tel",
        		'clTRID' => $this->Prefix . 'ote-testcase19cmd'
        	));
        	
			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:host-1.0");
			$attr = $chkData[0]->cd[0]->name[0]->attributes();
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				(string)$attr['avail'] == 0,
				"check host (host known)"
			);
        }
        
        function checkUnexistedHost ()
        {
        	$Resp = $this->Module->Request('host-check', array(
        		'hostname' => "ns1.{$this->Prefix}abc.tld",
        		'clTRID' => $this->Prefix . 'ote-testcase20cmd'
        	));
        	
			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:host-1.0");
			$attr = $chkData[0]->cd[0]->name[0]->attributes();
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				(string)$attr['avail'] == 1,
				"check host (host unknown)"
			);
        }
        
        function infoQueryHost ()
        {
        	try {
	        	$Resp = $this->Module->Request('host-info', array(
	        		'hostname' => "ns1.{$this->Prefix}inzone.tld",
	        		'clTRID' => $this->Prefix . 'ote-testcase21cmd'
	        	));
	        	$this->assertTrue($Resp->Code == 1000, "<info> query host");
        	} catch (ObjectNotExistsException $e) {
        		$this->fail("<info> query host. " . $e->getMessage());
        	}
        }
        
        
		function updateHostRemIP ()
		{
			$Resp = $this->Module->Request('test-host-update', array(
        		'hostname' => "ns1.{$this->Prefix}inzone.tld",
				'add' => '',
				'rem' => '<host:rem><host:addr ip="v4">209.1.2.4</host:addr></host:rem>',
        		'clTRID' => $this->Prefix . 'ote-testcase22cmd'
        	));
        	
        	$this->assertTrue($Resp->Code == 1000, "update host (remove IP address)");
		}
		
		function updateHost ()
		{
			$Resp = $this->Module->Request('test-host-update', array(
        		'hostname' => "ns1.{$this->Prefix}inzone.tld",
				'add' => '<host:add><host:status s="clientUpdateProhibited"/></host:add>',
				'rem' => '',
        		'clTRID' => $this->Prefix . 'ote-testcase23cmd'
        	));
        	
        	$this->assertTrue($Resp->Code == 1000, "update host");
		}
		
		function deleteHost ()
		{
			$Resp = $this->Module->Request('host-delete', array(
        		'hostname' => "ns1.{$this->Prefix}inzone.tld",
        		'clTRID' => $this->Prefix . 'ote-testcase24cmd'
        	));
        	
        	$this->assertTrue($Resp->Code == 1000, "delete host");
		}
        
		
		function createDomainWithoutAll ()
		{
			$Resp = $this->Module->Request('domain-create', array(
				'name' =>  $this->Prefix . 'domain1.tel',
        		'period' => 2,
        		'registrant_id' => $this->Prefix . 'cont2',
        		'contacts' => '',
				'ns' => '',
				'pw' => 'mysecret',
				'unspec' => 'WhoisType=LEGAL',
				'clTRID' => $this->Prefix . 'ote-testcase25cmd'
			));
			
			$this->assertTrue($Resp->Code == 2003, "create domain without nameservers and contacts");
		}
		
		function createDomainWithNameserverHosts ()
		{
			// Create inzone host
        	$Resp = $this->Module->Request('host-create', array(
        		'name' => "ns2.{$this->Prefix}inzone.tel",
        		'addr' => '<host:addr ip="v4">209.1.2.3</host:addr>' 
        			. '<host:addr ip="v4">209.1.2.4</host:addr>',
        		'clTRID' => $this->Prefix . 'ote-testcase26cmd-1'
        	));			
			
			$Resp = $this->Module->Request('domain-create', array(
				'name' =>  $this->Prefix . 'domain1.tel',
        		'period' => 2,
        		'registrant_id' => $this->Prefix . 'cont2',
        		'contacts' => '',
				'ns' => '<domain:ns>'
					. '<domain:hostObj>ns1.'.$this->Prefix.'outzone.abc</domain:hostObj>'
					. '<domain:hostObj>ns2.'.$this->Prefix.'inzone.tld</domain:hostObj>'
					. '</domain:ns>',
				'pw' => 'mysecret',
				'unspec' => 'WhoisType=LEGAL',
				'clTRID' => $this->Prefix . 'ote-testcase26cmd'
			));

			$this->assertTrue($Resp->Code == 2003, "create domain with nameservers");
		}
		
		function createDomainWithAllRequired ()
		{
			$contact_clid = $this->Prefix . 'cont2';
			
			$Resp = $this->Module->Request('domain-create', array(
				'name' =>  $this->Prefix . 'domain1.tel',
        		'period' => 2,
        		'registrant_id' => $contact_clid,
        		'contacts' => '<domain:contact type="admin">'.$contact_clid.'</domain:contact>' 
        			. '<domain:contact type="tech">'.$contact_clid.'</domain:contact>'
					. '<domain:contact type="billing">'.$contact_clid.'</domain:contact>',
				'ns' => '<domain:ns>'
					. '<domain:hostObj>ns1.'.$this->Prefix.'outzone.abc</domain:hostObj>'
					. '<domain:hostObj>ns2.'.$this->Prefix.'inzone.tld</domain:hostObj>'
					. '</domain:ns>',
				'pw' => 'mysecret',
				'unspec' => 'WhoisType=LEGAL',
				'clTRID' => $this->Prefix . 'ote-testcase27cmd'
			));

			$this->assertTrue($Resp->Code == 1000, "create domain with all required");
		}
		
		function createDomainWithMaxPeriod ()
		{
			$contact_clid = $this->Prefix . 'cont2';
			
			$Resp = $this->Module->Request('domain-create', array(
				'name' =>  $this->Prefix . 'domain2.tel',
        		'period' => 10,
        		'registrant_id' => $contact_clid,
        		'contacts' => '<domain:contact type="admin">'.$contact_clid.'</domain:contact>' 
        			. '<domain:contact type="tech">'.$contact_clid.'</domain:contact>'
					. '<domain:contact type="billing">'.$contact_clid.'</domain:contact>',
				'ns' => '<domain:ns>'
					. '<domain:hostObj>ns1.'.$this->Prefix.'outzone.abc</domain:hostObj>'
					. '<domain:hostObj>ns2.'.$this->Prefix.'inzone.tld</domain:hostObj>'
					. '</domain:ns>',
				'pw' => 'mysecret',
				'unspec' => 'WhoisType=LEGAL',
				'clTRID' => $this->Prefix . 'ote-testcase28cmd'
			));

			$this->assertTrue($Resp->Code == 1000, "create domain with maximum registration period");
		}
		
		function createDomainWithInvalidName ()
		{
			$contact_clid = $this->Prefix . 'cont2';
			
			$Resp = $this->Module->Request('domain-create', array(
				'name' =>  'in-&amp;valid--.tel',
        		'period' => 2,
        		'registrant_id' => $contact_clid,
        		'contacts' => '<domain:contact type="admin">'.$contact_clid.'</domain:contact>' 
        			. '<domain:contact type="tech">'.$contact_clid.'</domain:contact>'
					. '<domain:contact type="billing">'.$contact_clid.'</domain:contact>',
				'ns' => '<domain:ns>'
					. '<domain:hostObj>ns1.in-&amp;valid--.tel</domain:hostObj>'
					. '<domain:hostObj>ns2.in-&amp;valid--.tel</domain:hostObj>'
					. '</domain:ns>',
				'pw' => 'mysecret',
				'unspec' => 'WhoisType=LEGAL',
				'clTRID' => $this->Prefix . 'ote-testcase29cmd'
			));

			$this->assertTrue($Resp->Code == 2005, "create domain with invalid name");
		}
		
		function checkUnavailableDomain ()
		{
			$Resp = $this->Module->Request('test-domain-check', array(
				'names' => '<domain:name>'.$this->Prefix.'domain1.tel</domain:name>'
					. '<domain:name>'.$this->Prefix.'domain2.tel</domain:name>'
					. '<domain:name>'.$this->Prefix.'domain3.tel</domain:name>',
				'clTRID' => $this->Prefix . 'ote-testcase30cmd'
			));
			
			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				$chkData[0]->cd[0]->name[0]->attributes()->avail == '0' &&
				$chkData[0]->cd[1]->name[0]->attributes()->avail == '0' &&
				$chkData[0]->cd[2]->name[0]->attributes()->avail == '0', 
				"check domain (domain not available)"
			);
		}
		
		function checkAvailableDomain ()
		{
			$Resp = $this->Module->Request('test-domain-check', array(
				'names' => '<domain:name>'.$this->Prefix.'domain97.tel</domain:name>'
					. '<domain:name>'.$this->Prefix.'domain98.tel</domain:name>'
					. '<domain:name>'.$this->Prefix.'domain99.tel</domain:name>',
				'clTRID' => $this->Prefix . 'ote-testcase31cmd'
			));
			
			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				$chkData[0]->cd[0]->name[0]->attributes()->avail == '1' &&
				$chkData[0]->cd[1]->name[0]->attributes()->avail == '1' &&
				$chkData[0]->cd[2]->name[0]->attributes()->avail == '1', 
				"check domain (domain available)"
			);
		}
		
		function infoQueryDomain ()
		{
			try {
				$Resp = $this->Module->Request('domain-info', array(
					'name' => $this->Prefix . 'domain1.tel',
					'clTRID' => $this->Prefix . 'ote-testcase32cmd'
				));
				$this->assertTrue($Resp->Code == 1000, "<info> query domain");
			} catch (ObjectNotExistsException $e) {
				$this->fail("<info> query domain. " . $e->getMessage());
			}
		}
		
		function deleteDomain ()
		{
			$Resp = $this->Module->Request('domain-delete', array(
				'name' => $this->Prefix . 'domain2.tel',
				'clTRID' => $this->Prefix . 'ote-testcase33cmd'
			));
			
			$this->assertTrue($Resp->Code == 1000, "delete domain");
			
		}
		
		function renewDomain ()
		{
			// First, get the Expiration Date of the Domain from the results of the query <info>
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = $this->Prefix . 'domain3';
			$Domain->Extension = 'tel';						
			try {
				$Resp = $this->Module->GetRemoteDomain($Domain);
				$this->cur_exp_date = $Resp->ExpireDate;
				$this->create_date = $Resp->CreateDate;
				$this->admin_clid = $Resp->AdminContact;
			} catch (ObjectNotExistsException $e) {
				$this->fail("renew domain. " . $e->getMessage());
				// TODO: remove in production
				$this->cur_exp_date = strtotime('2008-09-18');
				$this->create_date = strtotime('2006-05-04');
				$this->admin_clid = $this->Prefix . 'cont2';
				return;
			}
			
			$Resp = $this->Module->Request('domain-renew', array(
				'name' => $Domain->GetHostName(),
				'exDate' => date('Y-m-d', $this->cur_exp_date),
				'period' => 6,
				'clTRID' => $this->Prefix . 'ote-testcase34cmd'
			));
			$this->assertTrue($Resp->Code == 1000, "renew domain");
			
			$this->cur_exp_date = strtotime('+6 year', $this->cur_exp_date);
		}
		
		function renewDomainToMaxPeriod ()
		{
			// Max registration period = 10
			$period = 10 - ((int)date('Y', $this->cur_exp_date) - (int)date('Y', $this->create_date)); 
			
			$Resp = $this->Module->Request('domain-renew', array(
				'name' => $this->Prefix . 'domain3',
				'exDate' => date('Y-m-d', $this->cur_exp_date),
				'period' => $period,
				'clTRID' => $this->Prefix . 'ote-testcase35cmd'
			));
			$this->assertTrue($Resp->Code == 1000, "renew domain to max registration period");
		}
		
		function transferDomainRequest ()
		{
			$Resp = $this->Module2->Request('test-domain-trans-request', array(
				'name' => $this->Prefix . 'domain1.tel',
				'period' => 1,
				'pw' => 'mysecret',
				'clTRID' => $this->Prefix . 'ote-testcase36cmd'
			));
			$this->assertTrue($Resp->Code == 1001, "transfer a domain");
		}
		
		function queryDomainTransferStatus ()
		{
        	$Resp = $this->Module2->Request('domain-trans-query', array(
				'name' => $this->Prefix . 'domain1.tel',
				'clTRID' => $this->Prefix . 'ote-testcase37cmd'
        	));

        	$trnData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
        	
        	$this->assertTrue(
        		(string)$trnData[0]->trStatus == 'pending',
        		"query domain transfer status"
        	);			
		}
		
		function updateDomainNameservers ()
		{
			$Resp = $this->Module->Request('domain-update-ns', array(
				'name' => $this->Prefix . 'domain3.tel',
				'add' => '<domain:add><domain:ns>'
					. '<domain:hostObj>ns1.'.$this->Prefix.'outzone.abc</domain:hostObj>'
					. '</domain:ns></domain:add>',
				'del' => '<domain:rem><domain:ns>'
					. '<domain:hostObj>ns2.'.$this->Prefix.'inzone.tel</domain:hostObj>'
					. '<domain:hostObj>ns3.'.$this->Prefix.'inzone.tel</domain:hostObj>'					
					. '</domain:ns></domain:rem>',
				'clTRID' => $this->Prefix . 'ote-testcase38cmd'
			));
			
			$this->assertTrue($Resp->Code == 1000, "update domain nameservers");
		}
		
		function updateDomainContact ()
		{
			// Create new contact
        	$contact_data = array(
        		'id'		=> $this->Prefix . 'cont3',
        		'name' 		=> 'Jana Frank',
        		'org' 		=> 'Miumau',
        		'cc' 		=> 'US',
        		'sp' 		=> 'Florida',
        		'city' 		=> 'Clearwater',
        		'pc' 		=> '50398-001',
        		'street1' 	=> '3375 Clear Creek Drive',
				'street2' 	=> 'Building One',
        		'voice' 	=> '+1.7653455566',
        		'voice_ext'	=> '+12',
        		'fax' 		=> '+1.7653455568',
        		'email' 	=> 'janderson@worldnet.tld',
        		'pw' 		=> 'mysecret',
        		'clTRID'	=> $this->Prefix . 'ote-testcase39cmd-1'
        	);
        	$Resp = $this->Module->Request('test-contact-create', $contact_data);
			
        	// Update
			$Resp = $this->Module->Request('domain-update-contact', array(
				'name' => $this->Prefix . 'domain3.tel',
				'add' =>  '<domain:add><domain:contact type="admin">'.$this->Prefix.'cont3</domain:contact></domain:add>',
				'rem' =>  '<domain:rem><domain:contact type="admin">'.$this->admin_clid.'</domain:contact></domain:rem>',
				'change' => '',
				'clTRID' => $this->Prefix . 'ote-testcase39cmd'
			));
			$this->assertTrue($Resp->Code == 1000, "update domain contact");
		}
		
		function updateDomainStatus ()
		{
			$Resp = $this->Module->Request('domain-update-flags', array(
				'name' => $this->Prefix . 'domain3.tel',
				'add' => '<domain:add><domain:status s="clientTransferProhibited"/></domain:add>',
				'rem' => '',
				'clTRID' => $this->Prefix . 'ote-testcase40cmd'
			));
			$this->assertTrue($Resp->Code == 1000, "update domain status");
		}
		
		
		
        
        function _testBusy ()
        {
        	$DbDomain = DBDomain::GetInstance();
			$Domain = $DbDomain->LoadByName('kilo', 'tel');
			$this->Registry->CreateDomain($Domain, $Domain->Period);
        }
    }
?>