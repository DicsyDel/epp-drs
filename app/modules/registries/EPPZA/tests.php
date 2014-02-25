<?	
	class EPPZATests extends UnitTestCase 
	{
		/**
		 * @var Registry
		 */
		private $Registry;
		
		/**
		 * @var DBDomain
		 */
		private $DBDomain;
		
		private $contact_fields = array (
       		'name' 		=> 'David Herselman',
       		'org' 		=> 'Syrex (Pty) Ltd',
       		'cc' 		=> 'ZA',
       		'sp' 		=> 'Gauteng',
       		'city' 		=> 'Johannesburg',
       		'pc' 		=> '2193',
       		'street1' 	=> '27 7th Avenue',
		'street2' 	=> 'Parktown North',
       		'voice' 	=> '+27-11-721-1900',
       		'fax' 		=> '+27-11-721-1999',
       		'email' 	=> 'dns_syrex.co.za'      		
		);
		
        function setUp ()
        {
			$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension("co.za");
			$this->DBDomain = DBDomain::GetInstance();        	
        }
        
        function _testBusy ()
        {
			$Domain = DBDomain::GetInstance()->LoadByName('lair', 'co.za'); 
			$Domain = $this->Registry->GetRemoteDomain($Domain);
			
			print_r($Domain);			
			
			DBDomain::GetInstance()->Save($Domain);
          	return;
        }
        
        function testSetRemoteExpireDate ()
        {
        	$Domain = DBDomain::GetInstance()->LoadByName('lairbbs', 'co.za');
        	$this->Registry->SetRemoteExpireDate($Domain, strtotime("2008-12-24"));
        	var_dump(date("Y-m-d", $Domain->ExpireDate));
        }
        
        function _testEPP ()
        {
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'webta' . rand(1000, 9999);
			
			// check domain
			try
			{
				$ok = $this->Registry->DomainCanBeRegistered($Domain);
				$this->assertTrue($ok, 'Domain available for registration');
			}
			catch (Exception $e)
			{
				return $this->fail('Domain available for registration. Error: ' . $e->getMessage());
			}
			
			////
			// Create contact

			try
			{
				$Registrant = $this->Registry->NewContactInstanceByGroup('generic');
				$Registrant->SetFieldList($this->contact_fields);
				
				$this->Registry->CreateContact($Registrant);
				
				$this->assertTrue(true, 'Create contact');
			}
			catch (Exception $e)
			{
				return $this->fail('Create contact. Error: ' . $e->getMessage());
			}
			
			////
			// Get contact INFO
			
			try
			{
				$RRegistrant = $this->Registry->NewContactInstanceByGroup('generic');
				$RRegistrant->CLID = $Registrant->CLID;
				
				$this->Registry->GetRemoteContact($RRegistrant);
				
				$fields = $Registrant->GetFieldList();
				$rfields = $RRegistrant->GetFieldList();
				ksort($fields);
				ksort($rfields);
				
				$discloses = $Registrant->GetDiscloseList();
				$rdiscloses = $RRegistrant->GetDiscloseList();
				ksort($discloses);
				ksort($rdiscloses);
				
				$this->assertTrue(
					$fields['name'] == $rfields['name'] &&
					$fields['email'] == $rfields['email'] &&
					$fields['voice'] == $rfields['voice'] &&
					$discloses == $rdiscloses, 'Get remote contact');
				
			}
			catch (Exception $e)
			{
				return $this->fail('Get remote contact. Error: ' . $e->getMessage());
			}
			
			try
			{
				$Domain->SetContact($Registrant, CONTACT_TYPE::REGISTRANT);
				$Domain->SetNameserverList(array(
					new Nameserver('dns1.domain.co.za'),
					new Nameserver('dns2.domain.co.za')
				));
				
				$this->DBDomain->Save($Domain);
				
				$this->Registry->CreateDomain($Domain, 2, array('comment' => 'abc'));
				$this->assertTrue(true, 'Create domain');
			}
			catch (Exception $e)
			{
				return $this->fail('Create domain. Error: ' . $e->getMessage());
			}
			
			
        	$Obs = new TestRegistryObserver($this->Registry, $this);
        	$this->Registry->AttachObserver($Obs);
			$this->Registry->DispatchPendingOperations();
        }

        
        
    }
    

	class TestRegistryObserver extends RegistryObserverAdapter
	{
		private $TestCase;
		
		/**
		 * @var Registry 
		 */
		private $Registry;
		private $once_run = false;
		
		private $contact_fields = array (
			'name' 		=> 'David2 Herselman2',
			'org' 		=> 'Syrex (Pty) Ltd',
			'cc' 		=> 'ZA',
			'sp' 		=> 'Gauteng',
			'city' 		=> 'Johannesburg',
			'pc' 		=> '2193',
			'street1' 	=> '27 7th Avenue',
			'street2' 	=> 'Parktown North',
			'voice' 	=> '+27-11-721-1900',
			'fax' 		=> '+27-11-721-1999',
			'email' 	=> 'dns_syrex.co.za'      		
		);
		
		public function __construct ($Registry, $TestCase)
		{
			$this->Registry = $Registry;		
			$this->TestCase = $TestCase;
		}
		
		public function OnDomainCreated (Domain $Domain)
		{
			if ($this->once_run)
			{
				return;
			}
			
			$this->once_run = true;
	
	       	////
			// 3. CREATE 2 child name servers of newly created domain
			//
			try
			{
				$ns1 = new NameserverHost('dns1.' . $Domain->GetHostName(), gethostbyname('domain.co.za'));
				$ns2 = new NameserverHost('dns2.' . $Domain->GetHostName(), gethostbyname('domain.co.za'));
				
				$this->Registry->CreateNameserverHost($ns1);
				$this->Registry->CreateNameserverHost($ns2);
				
				$this->TestCase->assertTrue(true, 'Create nameservers');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Create nameservers. Error: ' . $e->getMessage());
			}
				
				
			////
			// 4. UPDATE Domain to attach child name servers to domain
			//
				
			try
			{
				$nslist = $Domain->GetNameserverChangelist();
				$nslist->Add($ns1);
				$nslist->Add($ns2);
				
				$this->Registry->UpdateDomainNameservers($Domain, $nslist);
				
				$this->TestCase->assertTrue(
					count($Domain->GetNameserverList()) == 4,
					'Attach nameservers to domain'
				);
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Attach nameservers to domain. Error: ' . $e->getMessage());
			}
				
			////
			// Create tech contact
			// 
			try
			{
				$Tech = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
				$Tech->SetFieldList($this->contact_fields);
				
				$this->Registry->CreateContact($Tech);
				
				$this->TestCase->assertTrue(true, 'Create another contact');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Create another contact. Error: ' . $e->getMessage());
			}
				
			////
			// Update domain contact
			try
			{
				$this->Registry->UpdateDomainContact($Domain, CONTACT_TYPE::TECH, null, $Tech);
				
				$this->TestCase->assertTrue(true, 'Attach contact to domain');
			}	
			catch (Exception $e)
			{
				return $this->TestCase->fail('Attach contact to domain. Error: ' . $e->getMessage());
			}
			
	
			////
			// 6. Perform an INFO command on the domain to verify update
			//
				
			try
			{
				$RDomain = $this->Registry->NewDomainInstance();
				$RDomain->Name = $Domain->Name;
				
				$RDomain = $this->Registry->GetRemoteDomain($RDomain);
				
				
				$this->TestCase->assertTrue(
					$RDomain->Name == $Domain->Name &&
					date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
					date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
					count($RDomain->GetNameserverList()) == count($Domain->GetNameserverList()) &&
					$RDomain->GetContact(CONTACT_TYPE::TECH)->CLID == $Tech->CLID,
					'Get remote domain'
				);
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Get remote domain. Error: ' . $e->getMessage());
			}
				
				
			////
			// 10. UPDATE one of the name server’s IP Address
			//
			
			try
			{
				$ns1->IPAddr = gethostbyname('ns.hostdad.com');
				$Resp = $this->Registry->GetModule()->UpdateNameserverHost($ns1);
				
				$this->TestCase->assertTrue($Resp->Result, 'Update domain nameserver');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Update domain nameserver. Error: ' . $e->getMessage());
			}
				
				
			////
			// 12. Renew Domain for 2 years
			//
				
			/*
			try
			{
				$this->Registry->RenewDomain($Domain, $extra=array('period' => 2));
				
				$this->TestCase->assertFalse(true, 'Domain renewal. Exception expected');
			}
			catch (Exception $e)
			{
				$this->TestCase->assertTrue($e->getMessage() == 'A domain can not be renewed earlier that 365 days from its expiration', 'Domain renewal');
			}
			*/
				
			
			// secondary registry
			$DataForm = new DataForm();
			$DataForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT, null, null, null, null, 'https://regphase3.dnservices.co.za:3121/epp/proxy'));
			$DataForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, null, null, null, null, 'eppuser'));
			$DataForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT , null, null, null, null, 'eppsecret'));
			$DataForm->AppendField( new DataFormField("UseSSLCert", FORM_FIELD_TYPE::CHECKBOX, null, null, null, null, '1'));	
			$DataForm->AppendField( new DataFormField("SSLpwd", FORM_FIELD_TYPE::TEXT, null, null, null, null, 'keypass'));	
			$DataForm->AppendField( new DataFormField("CLID", FORM_FIELD_TYPE::TEXT, null, null, null, null, 'cLID'));
			$DataForm->AppendField( new DataFormField("SSLCertPath", FORM_FIELD_TYPE::TEXT, null, null, null, null, '/opt/epp-drs/modules/registries/EPPZA/ssl/eppza.pem'));		
			
			
			$Module = new EPPZARegistryModule();
			$Module->InitializeModule('za', $DataForm);
			$Registry2 = new Registry($Module);		
			try
			{
				$ok = $Registry2->TransferRequest($Domain, array('pw' => $Domain->AuthCode));
				
				$this->TestCase->assertTrue($ok, 'Request domain transfer from another session');
			}
			catch (Exception $e)
			{
				$this->TestCase->fail('Request domain transfer from another session. Error: ' . $e->getMessage());
			}
			
			////
			// 17. Approve the Transfer using your OT&E1 account
			//
			
			try
			{
				$ok = $this->Registry->TransferApprove($Domain);
				
				$this->TestCase->assertTrue($ok, 'Approve transfer');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Approve transfer. Error: ' . $e->getMessage());
			}
			
			
			////
			// 19. Initiate the Transfer again using your OT&E1 account 
			//
			
			try
			{
				$ok = $this->Registry->TransferRequest($Domain, array('pw' => $Domain->AuthCode));
				
				$this->TestCase->assertTrue($ok, 'Initiate transfer account 1');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Initiate tranfer account 1. Error: ' . $e->getMessage());
			}
			
			
			////
			// 21. Reject the Transfer using your OT&E2 account
			//
			
			try
			{
				$ok = $Registry2->TransferReject($Domain);
				
				$this->TestCase->assertTrue($ok, 'Reject transfer account 2');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Reject transfer account 2. Error: ' . $e->getMessage());
			}
			
			
				
			////
			// Remove domain nameservers
			
			try
			{
				$ns_list = $Domain->GetNameserverList();
				$Changes = $Domain->GetNameserverChangelist();
				foreach ($ns_list as $NS)
				{
					$Changes->Remove($NS);
				}
				
				$Registry2->UpdateDomainNameservers($Domain, $Changes);
	
				$this->TestCase->assertTrue(count($Domain->GetNameserverList()) == 0, 'Remove nameservers from domain');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Remove nameservers from domain. Error: ' . $e->getMessage());
			}
				
			////
			// Delete nameservers
			
			try
			{
				$Registry2->DeleteNameserverHost($ns1);
				$Registry2->DeleteNameserverHost($ns2);
				
				$this->TestCase->assertTrue(true, 'Delete nameservers');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Delete nameservers. Error: ' . $e->getMessage());
			}
				
				
			////
			// Delete domain
			
			try
			{
				$Registry2->DeleteDomain($Domain);
				$this->TestCase->assertTrue(true, 'Delete domain');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Delete domain. Error: ' . $e->getMessage());
			}
	
			
			////
			/// Delete contact
			
			try
			{
				$Registry2->DeleteContact($Tech);
				$Registry2->DeleteContact($Domain->GetContact(CONTACT_TYPE::REGISTRANT));
				$this->TestCase->assertTrue(true, 'Delete contact');
			}
			catch (Exception $e)
			{
				return $this->TestCase->fail('Delete contact. Error: ' . $e->getMessage());
			}
		}
	}

?>
