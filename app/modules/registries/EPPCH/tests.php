<?	
	require_once(dirname(__FILE__).'/class.Transport.php');
	require_once(dirname(__FILE__).'/class.RegistryModule.php');

	class EPPCHTests extends UnitTestCase 
	{
		/**
		 * @var Registry
		 */
		private $registry;
		
        function EPPCHTests() 
        {
        	$this->UnitTestCase('EPPCH Module test');
        	/*            
        	try
			{
        		$this->registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension("ch");
        		//var_dump($this->registry);
        		//exit();
			}
			catch(Exception $e)
			{
				// TODO: use expectException in simpletests
				//$this->assertTrue(false, "Registry object received.");
				var_dump($e);	
			}
			*/ 
        }

		function setUp ()
		{
        	// Battle account init
        	
        	$this->registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension("ch");
			
        	return;
			
			
			// Test module init 
			
			$Config = EPPCHRegistryModule::GetConfigurationForm();
        	$fields = $Config->ListFields();
        	/*
        	$fields['Login']->Value = 'TEST-PARTNER-A';
        	//$fields['Password']->Value = 'TEST-PARTNER-A-p';
        	$fields['Password']->Value = 'qwerty123456_';
			*/
        	
        	/*
        	$fields['Login']->Value = 'TEST-PARTNER-B';
        	$fields['Password']->Value = 'epp-test-%2A';
			*/
        	
        	$fields['Login']->Value = 'TEST-PARTNER-C';
        	$fields['Password']->Value = 'epp-test-pwd';
        	
        	
        	$fields['ServerHost']->Value = 'diedomainer.com';
        	$fields['ServerPort']->Value = '7001';
        	
        	$Module = new EPPCHRegistryModule();
        	$Module->InitializeModule('ch', $Config);
        	
        	$this->registry = new Registry($Module);
		}
        
        function testBusy ()
        {
			$PollMessage = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$PollMessage->HostName = 'buerex.ch';
			$PollMessage->TransferStatus = TRANSFER_STATUS::APPROVED;
			$this->registry->DispatchPollTransfer($PollMessage);
        	
        	
        	return;
        	
        	// Fucked up domain.
        	
        	$Domain = $this->registry->NewDomainInstance();
        	$Domain->Name = 'rest-rosengarten';
        	$Domain->AuthCode = '%mQGtyNk6';
        	$Domain->SetNameserverList(array(
        		new Nameserver('ns1.setup.ch'),
        		new Nameserver('ns2.setup.ch'),
        		new Nameserver('ns3.setup.ch')
        	));
        	
        	$Contact = $this->registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
        	$Contact->CLID = 'N74158532';
        	$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
        	
        	$Contact = $this->registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
        	$Contact->CLID = 'L22589790';
        	$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
        	
       	
        	$Module = $this->registry->GetModule();
        	
        	$Resp = $Module->CreateDomain($Domain, 1);
        	print_r($Resp);
        	return;
        	
        	
        	/*
        	$Module->Request('domain-update-contact', array(
        		'name' => 'wellnessbad.ch',
        		'add' => '<domain:add><domain:contact type="tech">L22589790</domain:contact></domain:add>',
        		'rem' => '<domain:rem><domain:contact type="tech">11934643</domain:contact></domain:rem>',
        		'change' => ''
        	));
        	*/
        	
        	//$Contact = $this->registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
        	//$Contact->CLID = 'L22589790';
        	//$this->registry->GetRemoteContact($Contact);
        	//$DbContact->Save($Contact);
        }
        
        function _testEPPContactUpdate ()
        {

        }
        
        function _testPoll()
        {
        	$res = $this->registry->DispatchPendingOperations();
        	var_dump($res);
        	//$domain = DBDomain::GetInstance()->Load(99);
        	//$d = $this->registry->GetRemoteDomain($domain);
        	//var_dump($d);
        }
        
        function _testDomainTransfer()
        {
        	$domain = DBDomain::GetInstance()->Load(99);
        	$res = $this->registry->TransferRequest($domain);
        	$this->assertTrue($res, "Transfer requested");
        }
        
        function _testDomainObject()
        {
        	$domain = $this->registry->NewDomainInstance();
			$domain->SetNameserverList(array(new Nameserver("ns1.google.com"), new Nameserver("ns2.google.com")));
			
			$domain->Name = "webtatest-tran".rand(500000,9999999);
			
			$c1 = DBContact::GetInstance()->LoadByCLID("N37032765");
			$c1->Type = "registrant";
			$domain->SetContact($c1);
			
			$c3 = DBContact::GetInstance()->LoadByCLID("X87193892");
			$c3->Type = "tech";
			$domain->SetContact($c3);
			
			$res = $this->registry->CreateDomain($domain, 1);
			$this->assertTrue(($res instanceof Domain), "Domain delegated");
			
			//$domain = DBDomain::GetInstance()->Load(98);
			
			//$info = $this->registry->DeleteDomain($domain);
			//$this->assertTrue($info, "Domain removed");
			
			//$info = $this->registry->GetRemoteDomain($domain);			
			//$this->assertTrue(($info instanceof Domain), "Remote domain received");
			
			/*
			try
			{
				$nshost = new NameserverHost('ns2.google.com', "70.84.240.139");
				
				$res = $this->registry->CreateNameserverHost($nshost);
				$this->assertTrue(($res instanceof NameserverHost), "Nameserverhost created");
			}
			catch(Exception $e)
			{
				
			}
			
			$nshost = new NameserverHost('ns2.' . $domain->Name . '.ch', "70.84.240.139");
			
			try
			{
				$res = $this->registry->CreateNameserverHost($nshost);
				$this->assertTrue(($res instanceof NameserverHost), "Nameserverhost created");
			}
			catch(Exception $e)
			{
				
			}
						
			$nshost->IPAddr = "70.84.240.140";
			
			$res = $this->registry->UpdateNameserverHost($nshost);
			$this->assertTrue($res, "Nameserverhost updated");
			
			try
			{
				$res = $this->registry->DeleteNameserverHost($nshost);
				$this->assertTrue($res, "Nameserverhost deleted");
			}
			catch (Exception $e)
			{
				var_dump($e->getMessage());
				$this->assertTrue(false, "Nameserverhost deleted");
			}
						
			$chlist = $domain->GetNameserverChangelist();
			
			$chlist->Add(new Nameserver("ns3.google.com"));
			$chlist->Remove(new Nameserver("ns1.google.com"));
			
			$res = $this->registry->UpdateDomainNameservers($domain, $chlist);
			$this->assertTrue($res, "Nameservers updated");
			*/
			
        }
        
        function _testContactObject()
        {
        	// CreateContact
        	/*
        	try
        	{
	        	$c = $this->registry->NewContactInstance("registrant");
	        	$c->SetFieldList(array(
	        		'name' 		=> 'marat komarov',
	        		'org' 		=> 'MK1O',
	        		'cc' 		=> 'NO',
	        		'sp' 		=> 'crimea',
	        		'city' 		=> 'TRONDHEIM',
	        		'pc' 		=> '7491',
	        		'sp' 		=> 'Crimea',
	        		'street1' 	=> 'bbfdgfd fds',
					'street2' 	=> 'dsf fd d',
	        		'voice' 	=> '+33.12345678',
	        		'fax' 		=> '+33.12345678',
	        		'email' 	=> 'igor@webta.net'	
	        	));
        	}
        	catch(ErrorList $e)
        	{
        		var_dump($e);
        	}
        	
        	try 
        	{
        		//$this->registry->CreateContact($c);
        		//$this->assertTrue(true, 'Contact created');
        	}
        	catch (Exception $e)
        	{
        		$this->assertTrue(false, 'Contact created');
        		var_dump($e);
        	}
        	
        	try
        	{
	        	$c = $this->registry->NewContactInstance("tech");
	        	$c->SetFieldList(array(
	        		'name' 		=> 'marat komarov',
	        		'org' 		=> 'MK1O',
	        		'cc' 		=> 'NO',
	        		'sp' 		=> 'crimea',
	        		'city' 		=> 'TRONDHEIM',
	        		'pc' 		=> '7491',
	        		'sp' 		=> 'Crimea',
	        		'street1' 	=> 'bbfdgfd fds',
					'street2' 	=> 'dsf fd d',
	        		'voice' 	=> '+33.12345678',
	        		'fax' 		=> '+33.12345678',
	        		'email' 	=> 'igor@webta.net'	
	        	));
        	}
        	catch(ErrorList $e)
        	{
        		var_dump($e);
        	}
        	
        	try 
        	{
        		//$this->registry->CreateContact($c);
        		//$this->assertTrue(true, 'Contact created');
        	}
        	catch (Exception $e)
        	{
        		$this->assertTrue(false, 'Contact created');
        		var_dump($e);
        	}
        	
        	exit();
        	sleep(1);
        	
        	try
        	{
	        	$c->SetFieldList(array(
	        		'name' 		=> 'marat komarov2',
	        		'org' 		=> 'MK1O2',
	        		'cc' 		=> 'UA',
	        		'sp' 		=> 'crimea2',
	        		'city' 		=> 'TRONDHEIM2',
	        		'pc' 		=> '74912',
	        		'sp' 		=> 'Crimea2',
	        		'street1' 	=> 'bbfdgfd fds2',
					'street2' 	=> 'dsf fd d2',
	        		'voice' 	=> '+33.123456782',
	        		'fax' 		=> '+33.123456782',
	        		'email' 	=> 'igor@webta.net'	
	        	));
        	}
        	catch(ErrorList $e)
        	{
        		var_dump($e);
        		exit();
        	}
        	
        	try
        	{
        		//$this->registry->UpdateContact($c);        		
        	}
        	catch (Exception $e)
        	{
        		$this->assertTrue(false, 'Contact updated');
        		var_dump($e->getMessage());
        	}
        	
        	sleep(1);
        	*/
        	$c = DBContact::GetInstance()->LoadByCLID("11390275");
        	
        	try 
        	{
        		$c2 = $this->registry->GetRemoteContact($c);
        		var_dump($c2);
        		$this->assertTrue($c2, 'Remote contact received');
        	} 
        	catch (Exception $e) 
        	{
        		var_dump($e);
        		$this->assertTrue(false, 'remote contact received');
        	}
        		
        	try 
        	{
        		//$this->registry->DeleteContact($c2);
        	}
        	catch (Exception $e)
        	{
        		$this->assertTrue(false, 'Contact deleted');
        		var_dump($e->getMessage());        		
        	}
        }
        
        
    }
?>