<?php

require_once (dirname(__FILE__) . '/class.Transport.php');
require_once (dirname(__FILE__) . '/class.RegistryModule.php');

class VerisignRegistryTests extends UnitTestCase 
{
	/**
	 * @var Registry
	 */
	private $Registry, $Registry2, $ProdRegistry;
	
	private $ContactFields, $RccContactFields;
	
	private $Contact;
	
	private $Domain;
	
	function __construct() 
	{
		$this->UnitTestCase('Verisign registry test');
		
		$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension("com");
/*
		// OT&E1
		$DataForm = new DataForm();
		$DataForm->AppendField(new DataFormField('ServerHost', 'text', null, null, null, null, 'web3.hostdad.com'));
		$DataForm->AppendField(new DataFormField('Login', 'text', null, null, null, null, 'bindrop1-admin'));
		$DataForm->AppendField(new DataFormField('Password', 'text', null, null, null, null, 'fR3#w$9aT*'));
		$DataForm->AppendField(new DataFormField('ServerPort', 'text', null, null, null, null, '703'));
		$DataForm->AppendField(new DataFormField('SSLCertPath', 'text', null, null, null, null, '/home/marat/webdev/epp-drs/branches/v1000/app/modules/registries/Verisign/ssl/certchain.pem'));
		$DataForm->AppendField(new DataFormField('SSLCertPass', 'text', null, null, null, null, ''));
		
		$Module = new VerisignRegistryModule();
		$Module->InitializeModule('com', $DataForm);
		$this->Registry = new Registry($Module);
		
		// OT&E2
		$DataForm2 = new DataForm();
		$DataForm2->AppendField(new DataFormField('ServerHost', 'text', null, null, null, null, 'web3.hostdad.com'));
		$DataForm2->AppendField(new DataFormField('Login', 'text', null, null, null, null, 'bindrop2-admin'));
		$DataForm2->AppendField(new DataFormField('Password', 'text', null, null, null, null, 'KUGHf78e3hfna#'));
//		$DataForm2->GetFieldByName('Login')->Value = 'bindrop2-admin';
//		$DataForm2->GetFieldByName('Password')->Value = 'KUGHf78e3hfna#';
		$DataForm2->AppendField(new DataFormField('ServerPort', 'text', null, null, null, null, '703'));
		$DataForm2->AppendField(new DataFormField('SSLCertPath', 'text', null, null, null, null, '/home/marat/webdev/epp-drs/branches/v1000/app/modules/registries/Verisign/ssl/certchain.pem'));
		$DataForm2->AppendField(new DataFormField('SSLCertPass', 'text', null, null, null, null, ''));
		
		$Module2 = new VerisignRegistryModule();
		$Module2->InitializeModule('com', $DataForm2);
		$this->Registry2 = new Registry($Module2);

		
		$DataForm3 = new DataForm();
		$DataForm3->AppendField(new DataFormField('ServerHost', 'text', null, null, null, null, 'web3.hostdad.com'));
		$DataForm3->AppendField(new DataFormField('Login', 'text', null, null, null, null, 'bindrop-admin'));
		$DataForm3->AppendField(new DataFormField('Password', 'text', null, null, null, null, '9WcV5!AC'));
		$DataForm3->AppendField(new DataFormField('ServerPort', 'text', null, null, null, null, '700'));
		$DataForm3->AppendField(new DataFormField('SSLCertPath', 'text', null, null, null, null, '/home/marat/webdev/epp-drs/branches/v1000/app/modules/registries/Verisign/ssl/certchain.pem'));
		$DataForm3->AppendField(new DataFormField('SSLCertPass', 'text', null, null, null, null, ''));
		
		$Module3 = new VerisignRegistryModule();
		$Module3->InitializeModule('com', $DataForm3);
		$this->ProdRegistry = new Registry($Module3);
		
		//$this->Registry = $this->ProdRegistry;
		*/
	}
	
	function setUp ()
	{
		$this->Skip = array(
			'ContactLifeCircle',
			'DomainLifeCircle',
			'NameserverLifeCircle'
		);
		
		$this->ContactFields = array(
			'name' => 'Marat Komarov',
			'email' => 'marat@webta.net',
			'street' => 'Test street',
			'city' => 'Sebastopol',
			'pc' => '99011',
			'sp' => 'Crimea',
			'cc' => 'UA',
			'voice' => '+380-434-4343223',
			'fax' => '+380-424-5546433'
		);
		
		$this->ContactFieldsBill = array(
			'name' => 'Volosatoe seklo',
			'email' => 'marat@webta.net',
			'street' => 'Giii',
			'city' => 'Sebastopol',
			'pc' => '54535',
			'sp' => 'Crimea',
			'cc' => 'UA',
			'voice' => '+380-434-4343223',
			'fax' => '+380-424-5546433'
		);
		
		$this->ContactFieldsNewTech = array(
			'name' => 'Gajko',
			'email' => 'marat@webta.net',
			'street' => 'Giii',
			'city' => 'Simferopol',
			'pc' => '54535',
			'sp' => 'Crimea',
			'cc' => 'UA',
			'voice' => '+380-434-4343223',
			'fax' => '+380-424-5546433'
		);
		
	}
	
	function _testBusy ()
	{
		$Contact = DBContact::GetInstance()->LoadByCLID('VRSGN3');
		
		//$Module = $this->Registry->GetModule();
		//$Domain = $this->Registry->NewDomainInstance();
		//$Domain->Name = "webta";
		//$Module->CreateDomain($Domain, 2);
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = "webta-".rand(1000, 9999);
		$Domain->UserID = 1;
		$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
		$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
		$Domain->SetContact($Contact, CONTACT_TYPE::TECH);

		$this->Registry->CreateDomain($Domain, 2);

		//$ns1 = new NameserverHost('ns1.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
		//$ns2 = new NameserverHost('ns2.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));

		/*
		$this->Registry->CreateNameserverHost($ns1);
		$this->Registry->CreateNameserverHost($ns2);
		
		$Changes = $Domain->GetNameserverChangelist();
		$Changes->Add($ns1);
		$Changes->Add($ns2);
		$this->Registry->UpdateDomainNameservers($Domain, $Changes);
		*/
	}
	
	function testPoll ()
	{
		$Module = $this->Registry->GetModule();
		$Resp = $Module->ReadMessage();
		var_dump($Resp);
	}
	
	function _testLock ()
	{
		return;
		
		$Domain = DBDomain::GetInstance()->LoadByName('webta-test', 'net');
		
		$Domain = $this->Registry->GetRemoteDomain($Domain);
		DBDomain::GetInstance()->Save($Domain);

//		$this->Registry->UnlockDomain($Domain);
//		$Domain = $this->Registry->GetRemoteDomain($Domain);
		
		$this->Registry->LockDomain($Domain);
		$Domain = $this->Registry->GetRemoteDomain($Domain);
		
		$this->Registry->UnlockDomain($Domain);
		$Domain = $this->Registry->GetRemoteDomain($Domain);
		
		die();
		
		
	}
	
	function _testGrace ()
	{
		return;
		
		////
		// 6. Perform an INFO command on the domain to verify update
		//
		
		try
		{
			$RDomain = $this->Registry->NewDomainInstance();
			$RDomain->Name = 'webta-test';
			
			$RDomain = $this->Registry->GetRemoteDomain($RDomain);
			var_dump($RDomain->GetFlagList());
		}
		catch (Exception $e)
		{
			return $this->fail('Get remote domain. Error: ' . $e->getMessage());
		}
		
	}
	
	function _testContact ()
	{
		return;
/*
		$Contact = $this->Registry->NewContactInstanceByGroup('generic');
		$Contact->SetFieldList($this->ContactFields);
		$this->Registry->CreateContact($Contact);
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'webta' . rand(100, 999);
		
		var_dump($Domain->Name);
		
		$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
		$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
		$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
		
		$this->Registry->CreateDomain($Domain, 2);
		
		exec("whois -h 192.168.1.200 -T domain {$Domain->GetHostName()}", $out);
		var_dump($out);
		
		$Billing = $this->Registry->NewContactInstanceByGroup('generic');
		$Billing->SetFieldList($this->ContactFieldsBill);
		$this->Registry->CreateContact($Billing);
		
		$this->Registry->UpdateDomainContact($Domain, CONTACT_TYPE::BILLING, $Contact, $Billing);
		*/
		
		$Domain = DBDomain::GetInstance()->LoadByName('webta426', 'net');
		
//		$ns = new NameserverHost('ns2.' . $Domain->GetHostName(), gethostbyname('ns2.hostdad.com'));
//		$this->Registry->CreateNameserverHost($ns);
		
		/*
		$nslist = $Domain->GetNameserverList();
		$changes = $Domain->GetNameserverChangelist();
		$changes->Remove($nslist[1]);
		$this->Registry->UpdateDomainNameservers($Domain, $changes);
		*/

		$changes = $Domain->GetNameserverChangelist();
		$changes->SetChangedList(array());
		$this->Registry->UpdateDomainNameservers($Domain, $changes);
		
		foreach ($Domain->GetNameserverHostList() as $nshost)
		{
			$this->Registry->DeleteNameserverHost($nshost);
		}
		
		$this->Registry->DeleteDomain($Domain);

		exec("whois -h 192.168.1.200 -T domain {$Domain->GetHostName()}", $out);
		var_dump($out);
	}
	
	function _testEPP ()
	{
		return;
		
		//return;
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'webta' . rand(100, 999);
		Log::Log('Register ' . $Domain->GetHostName() . ' on production Verisign server', E_USER_NOTICE);
		
		var_dump($Domain->GetHostName());
		
		////
		// 1. Perform a CHECK command on domain name(s) until you
		// receive domain available response
		
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
		// 2. Create registrant contact for domain
		//
		$Registrant = $this->Registry->NewContactInstanceByGroup('generic');
		$Registrant->SetFieldList($this->ContactFields);

		try
		{
			$this->Registry->CreateContact($Registrant);
			$this->assertTrue(true, 'Create contact');
		}
		catch (Exception $e)
		{
			return $this->fail('Create contact. Error: ' . $e->getMessage());
		}
		
		
		////
		// 3. Perform info command on the contacts to verify create
		//
		
		try
		{
			$RContact = $this->Registry->NewContactInstanceByGroup('generic');
			$RContact->CLID = $Registrant->CLID;
			
			$RContact = $this->Registry->GetRemoteContact($RContact);
			
			$rfields = $RContact->GetFieldList();
			$fields = $Registrant->GetFieldList();
			asort($rfields); 
			asort($fields);
			
			$this->assertTrue($rfields == $fields, 'Get remote contact');
		}
		catch (Exception $e)
		{
			return $this->fail('Get remote contact. Error: ' . $e->getMessage());
		}
		

		////
		// 4. CREATE the Domain name using the CREATE command, term of registration should be
		// 2 years
		
		try
		{
			$Billing = $this->Registry->NewContactInstanceByGroup('generic');
			$Billing->SetFieldList($this->ContactFieldsBill);
			$this->Registry->CreateContact($Billing);
	
			$Tech = $Registrant;
			
			$Domain->SetContact($Registrant, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($Billing, CONTACT_TYPE::BILLING);
			$Domain->SetContact($Tech, CONTACT_TYPE::TECH);

			$Domain->SetNameserverList(array(
				new Nameserver('ns.hostdad.com')
			));
			
			$this->Registry->CreateDomain($Domain, 2);
			$this->assertTrue(true, 'Create domain');
		}
		catch (Exception $e)
		{
			return $this->fail('Create domain. Error: ' . $e->getMessage());
		}
		
		
		////
		// 5. CREATE 2 child name servers of newly created domain
		//
		
		try
		{
			$ns1 = new NameserverHost('ns1.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
			$ns2 = new NameserverHost('ns2.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
			
			$this->Registry->CreateNameserverHost($ns1);
			$this->Registry->CreateNameserverHost($ns2);
			
			$this->assertTrue(true, 'Create nameservers');
		}
		catch (Exception $e)
		{
			return $this->fail('Create nameservers. Error: ' . $e->getMessage());
		}
		
		
		////
		// 6. UPDATE Domain to attach child name servers to domain
		//
		
		try
		{
			$nslist = $Domain->GetNameserverChangelist();
			$nslist->Add($ns1);
			$nslist->Add($ns2);
			
			$this->Registry->UpdateDomainNameservers($Domain, $nslist);
			
			$this->assertTrue(
				count($Domain->GetNameserverList()) == 3,
				'Attach nameservers to domain'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Attach nameservers to domain. Error: ' . $e->getMessage());
		}
		
		
		////
		// 7. UPDATE Domain�s status to 
		// clientHold, clientUpdateProhibited, clientDeleteProhibited, and clientTransferProhibited 
		// within one command
		
		try
		{
			$flag_list = $Domain->GetFlagChangelist();
			$flag_list->SetChangedList(array(
				'clientHold', 
				'clientUpdateProhibited',
				'clientDeleteProhibited', 
				'clientTransferProhibited'
			));
			
			$this->Registry->UpdateDomainFlags($Domain, $flag_list);
			
			$this->assertTrue(
				count($Domain->GetFlagList()) == count($flag_list->GetList()),
				'Update domain status'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Update domain status. Error: ' . $e->getMessage());
		}
		
		
		////
		// 8. Perform an INFO command on the domain to verify update
		//
		
		try
		{
			$RDomain = $this->Registry->NewDomainInstance();
			$RDomain->Name = $Domain->Name;
			
			$RDomain = $this->Registry->GetRemoteDomain($RDomain);
			
			$flags = $Domain->GetFlagList();
			$rflags = $RDomain->GetFlagList();
			sort($flags);
			sort($rflags);
			
			$this->assertTrue(
				$RDomain->Name == $Domain->Name &&
				date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
				date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
				$rflags == $flags,
				
				'Get remote domain'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Get remote domain. Error: ' . $e->getMessage());
		}
		
		
		////
		// 9. UPDATE Domain�s status to OK
		//
		
		try
		{
			$changes = $Domain->GetFlagChangelist();
			foreach ($RDomain->GetFlagList() as $flag)
				$changes->Remove($flag);
			
			$this->Registry->UpdateDomainFlags($Domain, $changes);
			
			//$changes = $Domain->GetFlagChangelist();
			//$changes->Add('ok');
			//$this->Registry->UpdateDomainFlags($Domain, $changes);
			$this->assertTrue(
				$Domain->GetFlagList() == array(),
				'Update domain status'
			);
			
			$Domain->SetFlagList(array('ok')); // ok flag set automatical when all other were removed
			// ^our bug ?			
		}
		catch (Exception $e)
		{
			return $this->fail('Update domain status. Error: ' . $e->getMessage());
		}
		
		
		////
		// 10. Perform an INFO command on the domain to verify update
		//
		
		try
		{
			$RDomain = $this->Registry->NewDomainInstance();
			$RDomain->Name = $Domain->Name;
			
			$RDomain = $this->Registry->GetRemoteDomain($RDomain);
			
			$this->assertTrue(
				$RDomain->Name == $Domain->Name &&
				date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
				date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
				$RDomain->GetFlagList() == array('ok'),
				
				'Get remote domain'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Get remote domain. Error: ' . $e->getMessage());
		}
		

		/*
		////
		// 11. UPDATE one of the name server�s IP Address
		//
		
		try
		{
			$ns1->IPAddr = gethostbyname('ns.hostdad.com');
			$this->Registry->UpdateNameserverHost($ns1);
			
			$this->assertTrue(true, 'Update domain nameserver');
		}
		catch (Exception $e)
		{
			return $this->fail('Update domain nameserver. Error: ' . $e->getMessage());
		}
		*/
		
		
		////
		// 12. UPDATE one of the domain contacts
		//
		
		try
		{
			$NewTech = $this->Registry->NewContactInstanceByGroup('generic');
			$NewTech->SetFieldList($this->ContactFieldsNewTech);
			$this->Registry->CreateContact($NewTech);
			
			$this->Registry->UpdateDomainContact($Domain, CONTACT_TYPE::TECH, $Tech, $NewTech);
			
			$RDomain = $this->GetRemoteDomainCopy($Domain);
			
			$this->assertTrue(
				$RDomain->GetContact(CONTACT_TYPE::TECH) &&
				$RDomain->GetContact(CONTACT_TYPE::TECH)->CLID == $NewTech->CLID,
				
				'Update domain contact'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Update domain contact. Error: ' . $e->getMessage());
		}
		
		
		////
		// 13. Delete domain
		//
		
		try
		{
			$changes = $Domain->GetNameserverChangelist();
			$changes->SetChangedList(array());
			$this->Registry->UpdateDomainNameservers($Domain, $changes);
			
			foreach ($Domain->GetNameserverHostList() as $nshost)
			{
				$this->Registry->DeleteNameserverHost($nshost);
			}
			
			$this->Registry->DeleteDomain($Domain);
	
			$this->assertTrue(
				preg_match("/NO OBJECT FOUND!\nobject:\s+{$Domain->GetHostName()}/m", $this->JWhois($Domain)),
				'Delete domain'
			);
		}
		catch (Exception $e)
		{
			$this->fail('Delete domain. Error: ' . $e->getMessage());
			
		}
		
	}
	
	private function JWhois ($Domain)
	{
		exec("whois -h 192.168.1.200 -T domain {$Domain->GetHostName()}", $out);
		return join("\n" .  $out);
	}
	
	function _testOTECertification ()
	{
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'webta' . rand(1000, 9999);
		
		////
		// 1. Using your OT&E1 account perform a CHECK command on domain name(s) until you
		// receive domain available response
		
		try
		{
			$ok = $this->Registry->DomainCanBeRegistered($Domain);
			$this->assertTrue($ok, 'Domain available for registration');
		}
		catch (Exception $e)
		{
			$this->fail('Domain available for registration. Error: ' . $e->getMessage());
			//return;
		}
	
		////
		// 2. CREATE the Domain name using the CREATE command, term of registration should be
		// 2 years
		
		try
		{
			$Contact = $this->Registry->NewContactInstanceByGroup('generic');
			$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
			$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
			
			$this->Registry->CreateDomain($Domain, 2);
			$this->assertTrue(true, 'Create domain');
		}
		catch (Exception $e)
		{
			return $this->fail('Create domain. Error: ' . $e->getMessage());
		}

		
		////
		// 3. CREATE 2 child name servers of newly created domain
		//
		
		try
		{
			$ns1 = new NameserverHost('ns1.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
			$ns2 = new NameserverHost('ns2.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
			
			$this->Registry->CreateNameserverHost($ns1);
			$this->Registry->CreateNameserverHost($ns2);
			
			$this->assertTrue(true, 'Create nameservers');
		}
		catch (Exception $e)
		{
			return $this->fail('Create nameservers. Error: ' . $e->getMessage());
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
			
			$this->assertTrue(
				count($Domain->GetNameserverList()) == 2,
				'Attach nameservers to domain'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Attach nameservers to domain. Error: ' . $e->getMessage());
		}
		
		
		////
		// 5. UPDATE Domain�s status to 
		// clientHold, clientUpdateProhibited, clientDeleteProhibited, and clientTransferProhibited 
		// within one command
		
		try
		{
			$flag_list = $Domain->GetFlagChangelist();
			$flag_list->SetChangedList(array(
				'clientHold', 
				'clientUpdateProhibited',
				'clientDeleteProhibited', 
				'clientTransferProhibited'
			));
			
			$this->Registry->UpdateDomainFlags($Domain, $flag_list);
			
			$this->assertTrue(
				count($Domain->GetFlagList()) == count($flag_list->GetList()),
				'Update domain status'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Update domain status. Error: ' . $e->getMessage());
		}
		
		
		////
		// 6. Perform an INFO command on the domain to verify update
		//
		
		try
		{
			$RDomain = $this->Registry->NewDomainInstance();
			$RDomain->Name = $Domain->Name;
			
			$RDomain = $this->Registry->GetRemoteDomain($RDomain);
			
			$flags = $Domain->GetFlagList();
			$rflags = $RDomain->GetFlagList();
			sort($flags);
			sort($rflags);
			
			$this->assertTrue(
				$RDomain->Name == $Domain->Name &&
				date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
				date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
				$rflags == $flags,
				
				'Get remote domain'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Get remote domain. Error: ' . $e->getMessage());
		}
		
		
		////
		// 7. UPDATE Domain�s status to OK
		//
		
		try
		{
			$changes = $Domain->GetFlagChangelist();
			foreach ($RDomain->GetFlagList() as $flag)
				$changes->Remove($flag);
			
			$this->Registry->UpdateDomainFlags($Domain, $changes);
			
			//$changes = $Domain->GetFlagChangelist();
			//$changes->Add('ok');
			//$this->Registry->UpdateDomainFlags($Domain, $changes);
			$this->assertTrue(
				$Domain->GetFlagList() == array(),
				'Update domain status'
			);
			
			$Domain->SetFlagList(array('ok')); // ok flag set automatical when all other were removed
			// ^our bug ?			
		}
		catch (Exception $e)
		{
			return $this->fail('Update domain status. Error: ' . $e->getMessage());
		}
		
		
		////
		// 8. Perform an INFO command on the domain to verify update
		//
		
		try
		{
			$RDomain = $this->Registry->NewDomainInstance();
			$RDomain->Name = $Domain->Name;
			
			$RDomain = $this->Registry->GetRemoteDomain($RDomain);
			
			$this->assertTrue(
				$RDomain->Name == $Domain->Name &&
				date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
				date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
				$RDomain->GetFlagList() == array('ok'),
				
				'Get remote domain'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Get remote domain. Error: ' . $e->getMessage());
		}
		
		
		////
		// 9. UPDATE Domain�s AUTH INFO Code
		//
		
		try
		{
			// ���������� AUTH ����������� �������������� � �����-�������� API
			
			$VerisignModule = $this->Registry->GetModule();

			$Domain->AuthCode = rand(1000000, 9999999);			
			$params = array(
				'name' => $Domain->GetHostName(),
				'subproduct' => 'dot' . strtoupper($Domain->Extension),
				'add' => '',
				'remove' => '',
				'change' => "<domain:chg><domain:authInfo><domain:pw>{$Domain->AuthCode}</domain:pw></domain:authInfo></domain:chg>"
			);
			
			$Resp = $VerisignModule->Request('domain-update', $params);
			$success = $Resp->Succeed || $Resp->Code == RFC3730_RESULT_CODE::OK_PENDING;
			
			$this->assertTrue($success, 'Update domain auth code');
		}
		catch (Exception $e)
		{
			return $this->fail('Update domain auth code. Error: ' . $e->getMessage());
		}
		
		
		////
		// 10. UPDATE one of the name server�s IP Address
		//
		
		try
		{
			$ns1->IPAddr = gethostbyname('ns.hostdad.com');
			$Resp = $VerisignModule->UpdateNameserverHost($ns1);
			
			$this->assertTrue($Resp->Result, 'Update domain nameserver');
		}
		catch (Exception $e)
		{
			return $this->fail('Update domain nameserver. Error: ' . $e->getMessage());
		}
		
		
		////
		// 11. Perform a HELLO command  
		//
		
		try
		{
			$VerisignModule = $this->Registry->GetModule();
			
			$Resp = $VerisignModule->Request('hello', $params=array());
			
			$this->assertTrue(
				$Resp == true,
				'Say hello'
			);
			
		}
		catch (Exception $e)
		{
			return $this->fail('Say hello. Error: ' . $e->getMessage());
		}
		
		
		////
		// 12. Renew Domain for 2 years
		//
		
		try
		{
			$old_expire_date = $Domain->ExpireDate;
			$this->Registry->RenewDomain($Domain, $extra=array('period' => 2));
			
			$this->assertTrue(
				date('Ymd', $Domain->ExpireDate) == date('Ymd', strtotime('+2 year', $old_expire_date)),
				'Domain renewal'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Domain renewal. Error: ' . $e->getMessage());
		}
		
		
		////
		// 13. Open Second Session using the OT&E2 account logon
		//
		
		// It will be done automatical in next command
		
		
		////
		// 14. Perform INFO command on the newly created domain from step 1 using the AUTH
		// INFO code populated in step 9 to get INFO results
		//
		
		try
		{
			$RDomain = $this->Registry2->NewDomainInstance();
			$RDomain->Name = $Domain->Name;
			$RDomain->AuthCode = $Domain->AuthCode;
			
			$RDomain = $this->Registry2->GetRemoteDomain($RDomain);
			
			$flags = $Domain->GetFlagList();
			$rflags = $RDomain->GetFlagList();
			sort($flags);
			sort($rflags);
			
			$this->assertTrue(
				$RDomain->Name == $Domain->Name &&
				date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
				date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
				$rflags == $flags,
			
				'Get remote domain from another account with authorization'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Get remote domain from another account with authorization. Error: ' . $e->getMessage());
		}
		
		
		////
		// 15. Initiate Transfer domain command using your OT&E2 account
		//
		
		try
		{
			$ok = $this->Registry2->TransferRequest($Domain, array('pw' => $Domain->AuthCode));
			
			$this->assertTrue($ok, 'Initiate transfer OT&E2');
		}
		catch (Exception $e)
		{
			return $this->fail('Initiate tranfer OT&E2. Error: ' . $e->getMessage());
		}
		
		
		////
		// 16. Perform a Transfer Query command using your OT&E2 account
		//
		
		try
		{
			// Not implemented in cross-registry API
			
			$VerisignModule2 = $this->Registry2->GetModule();
			
			$params = array(
				'name' => $Domain->GetHostName(),
				'pw' => $Domain->AuthCode,
				'subproduct' => 'dot' . strtoupper($Domain->Extension)
			);
			
			$Resp = $VerisignModule2->Request('domain-trans-query', $params);
			
			$this->assertTrue(
				$Resp->Succeed,
				'Get transfer status'
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Get transfer status. Error: ' . $e->getMessage());
		}
		

		////
		// 17. Approve the Transfer using your OT&E1 account
		//
		
		try
		{
			$ok = $this->Registry->TransferApprove($Domain);
			
			$this->assertTrue($ok, 'Approve transfer');
		}
		catch (Exception $e)
		{
			return $this->fail('Approve transfer. Error: ' . $e->getMessage());
		}
				
		
		////
		// 18. Perform a Poll Command to check for messages in poll queue, ACK first poll message
		//
		
		// not works
		
		try
		{
			$VerisignModule2 = $this->Registry->GetModule();
			
			$max_iter = 10;
			$i = 0;
			while ($i < $max_iter && ($Mess = $VerisignModule2->ReadMessage()) === false)
			{
				sleep(1);
				$i++;
			}
			
			$this->assertTrue((bool)$Mess, 'Poll messages');
			
		}
		catch (Exception $e)
		{
			return $this->fail('Poll messages. Error: ' . $e->getMessage());
		}

		
		////
		// 19. Initiate the Transfer again using your OT&E1 account 
		//
		
		try
		{
			$ok = $this->Registry->TransferRequest($Domain, array('pw' => $Domain->AuthCode));
			
			$this->assertTrue($ok, 'Initiate transfer OT&E1');
		}
		catch (Exception $e)
		{
			return $this->fail('Initiate tranfer OT&E1. Error: ' . $e->getMessage());
		}
		
		
		////
		// 20. Perform a Transfer Query command using your OT&E2 account
		//
		
		try
		{
			// Not implemented in cross-registry API
			
			$VerisignModule2 = $this->Registry2->GetModule();
			
			$params = array(
				'name' => $Domain->GetHostName(),
				'pw' => $Domain->AuthCode,
				'subproduct' => 'dot' . strtoupper($Domain->Extension)
			);
			
			$Resp = $VerisignModule2->Request('domain-trans-query', $params);
			
			$this->assertTrue(
				$Resp->Succeed,
				'Get transfer status '
			);
		}
		catch (Exception $e)
		{
			return $this->fail('Get transfer status. Error: ' . $e->getMessage());
		}
		
		
		////
		// 21. Reject the Transfer using your OT&E2 account
		//
		
		try
		{
			$ok = $this->Registry2->TransferReject($Domain);
			
			$this->assertTrue($ok, 'Reject transfer OT&E2');
		}
		catch (Exception $e)
		{
			return $this->fail('Reject transfer OT&E2. Error: ' . $e->getMessage());
		}
		
		
		////
		// 22.
		//
		
		try
		{
			$ok = $VerisignModule2->UpdateDomainConsoliDate($Domain, array('expMonth' => 4, 'expDay' => 15));
			
			$this->assertTrue($ok, 'Sync the domain to the 15th day of the next month');
		}
		catch (Exception $e)
		{
			return $this->fail('Sync the domain to the 15th day of the next month. Error: ' . $e->getMessage());
		}

		
		////
		// 23. Exit Gracefully from both sessions by issuing the LOGOUT command
		//
		
		try
		{
			$VerisignModule->Request('logout', $params=array());
			$VerisignModule2->Request('logout', $params=array());
			
			$this->assertTrue(true, 'Logout from both sessions');
		}
		catch (Exception $e)
		{
			return $this->fail('Logout from both sessions. Error: ' . $e->getMessage());
		}
	}
	
	function _testContactLifeCircle ()
	{
		//$this->Contact = $this->Registry->NewContactInstanceByGroup('generic');
		//$this->Contact->CLID = '10002303';
		//return;
		
		if (in_array('ContactLifeCircle', $this->Skip))
		{
			//$this->Contact = DBContact::GetInstance()->LoadByCLID('4713068');	
			return;
		}
		
		////
		// Create contact
		
		$Contact = $this->Registry->NewContactInstanceByGroup('generic');
		$Contact->SetFieldList($this->ContactFields);			
		
		try
		{
			$this->Registry->CreateContact($Contact);
			$this->assertTrue(
				$Contact->ID != null &&
				$Contact->CLID != null, 
				'Create contact'
			);
		}
		catch (Exception $e)
		{
			$this->fail('Create contact. Error: ' . $e->getMessage());
			return $this->skipIf(true);
		}
		
		$this->Contact = $Contact;
		
		die();
		
		////
		// Get remote contact
		
		try
		{
			//$RContact = $this->Registry->NewContactInstanceByGroup($Contact->GroupName);
			$RContact = $this->Registry->NewContactInstanceByGroup('generic');
			//$RContact->CLID = $Contact->CLID;
			$RContact->CLID = '10002303';
			$RContact = $this->Registry->GetRemoteContact($RContact);
			
			var_dump($RContact); die();
			
			$fields = $Contact->GetFieldList();
			$rfields = $RContact->GetFieldList();

			unset($fields['sp'], $rfields['sp']); // ignore province diffs in test
			sort($fields);
			sort($rfields);
			
			$this->assertEqual($fields, $rfields, 'Get remote contact');
		}
		catch (Exception $e)
		{
			$this->fail('Get remote contact. Error: ' . $e->getMessage());
			return;
		}
		
		////
		// Update contact
		
		try
		{
			$fields = $Contact->GetFieldList();
			$fields['street1'] = 'Square';
			$fields['sp'] = 'Krim';
			$Contact->SetFieldList($fields);
			
			$this->Registry->UpdateContact($Contact);
			
			$RContact = $this->Registry->NewContactInstanceByGroup($Contact->GroupName);
			$RContact->CLID = $Contact->CLID;
			$RContact = $this->Registry->GetRemoteContact($RContact);
			
			$fields = $Contact->GetFieldList();
			$rfields = $RContact->GetFieldList();
			
			$this->assertTrue(
				$fields['street1'] == $rfields['street1'],
				'Update contact'
			);
		}
		catch (Exception $e)
		{
			$this->fail('Update contact. Error: ' . $e->getMessage());
		}
	}

	function _testDomainLifeCircle ()
	{
		if (in_array('DomainLifeCircle', $this->Skip))
		{
			//$this->Domain = DBDomain::GetInstance()->Load(14);
			return;
		}
		
		$Contact = $this->Contact;
		
		////
		// Create domain
		
		try
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'marat' . rand(1000, 9999);
			$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
			$Domain->SetContact($Contact, CONTACT_TYPE::ADMIN);
			$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
			$Domain->SetNameserverList(array(
				new Nameserver('ns1.google.com'),
				new Nameserver('ns2.google.com')
			));
			
			$this->Registry->CreateDomain($Domain, 2);
			$this->assertTrue(
				$Domain->ID != null &&
				date('Ymd', $Domain->ExpireDate) == date('Ymd', strtotime('+2 year')) && 
				$Domain->Status == DOMAIN_STATUS::DELEGATED,
				'Create Domain'
			);
		}
		catch (Exception $e)
		{
			$this->fail('Create domain');
			return $this->skipIf(true, $e->getMessage());
		}
		
		return;
		
		$this->Domain = $Domain;

		////
		// Get remote domain
		
		try
		{
			$RDomain = $this->GetRemoteDomainCopy($Domain);
			
			$rcontacts = $RDomain->GetContactList();
			$contacts = $Domain->GetContactList();

			$this->assertTrue(
				date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
				date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
				$rcontacts[CONTACT_TYPE::ADMIN]->CLID == $contacts[CONTACT_TYPE::ADMIN]->CLID &&
				$rcontacts[CONTACT_TYPE::REGISTRANT]->CLID == $contacts[CONTACT_TYPE::REGISTRANT]->CLID &&
				$rcontacts[CONTACT_TYPE::TECH]->CLID == $contacts[CONTACT_TYPE::TECH]->CLID &&
				$rcontacts[CONTACT_TYPE::BILLING]->CLID == $contacts[CONTACT_TYPE::BILLING]->CLID, 
				'Get remote domain'
			);
			
			$RDomain->Name = 'nonexisted-webta-domain';
			try
			{
				$RDomain = $this->Registry->GetRemoteDomain($RDomain);
				$this->fail('Get non existed domain');
			}
			catch (Exception $e)
			{
				$this->assertTrue($e->getMessage() == 'Object does not exist', 'Get non existed domain');
			}
		}
		catch (Exception $e)
		{
			$this->fail($e->getMessage());
		}
				
				
		
		////
		// Update domain contact
		
		try
		{
			$Admin = $this->Registry->NewContactInstance(CONTACT_TYPE::ADMIN);
			$Admin->SetFieldList($this->ContactFields);
			$this->Registry->CreateContact($Admin);
	
			$this->Registry->UpdateDomainContact($Domain, CONTACT_TYPE::ADMIN, $Domain->GetContact(CONTACT_TYPE::ADMIN), $Admin);
			
			$RDomain = $this->GetRemoteDomainCopy($Domain);
			
			$this->assertTrue(
				$RDomain->GetContact(CONTACT_TYPE::ADMIN)->CLID == $Admin->CLID,
				'Update domain contact'
			);
			
		}
		catch (Exception $e)
		{
			$this->fail('Update domain contact. Error: ' . $e->getMessage());
		}
		
		////
		// Update domain nameservers
		
		try
		{
			$Changelist = $Domain->GetNameserverChangelist();
			$ns = new NameserverHost('ns.' . $Domain->GetHostName(), '70.84.240.138');
			$Changelist->Add($ns);
			$this->Registry->UpdateDomainNameservers($Domain, $Changelist);
			
			$RDomain = $this->GetRemoteDomainCopy($Domain);
			
			$this->assertTrue(
				in_array($ns, $RDomain->GetNameserverList()),
				'Update nameserver list'
			);
		}
		catch (Exception $e)
		{
			$this->fail('Update nameserver list. Error: ' . $e->getMessage());
		}
		
		////
		// Renew domain
		
		try
		{
			$expire_date = $Domain->ExpireDate;
			$this->Registry->RenewDomain($Domain, array('period' => 1));
			
			$RDomain = $this->GetRemoteDomainCopy($Domain);
			$this->assertTrue(
				date('Ymd', strtotime("+1 year", $expire_date)) == date('Ymd', $Domain->ExpireDate) &&
				date('Ymd', $Domain->ExpireDate) == date('Ymd', $RDomain->ExpireDate),
				
				'Renew domain'
			);
		}
		catch (Exception $e)
		{
			$this->fail('Renew domain. Error: ' . $e->getMessage());
		}
		
		////
		// Delete domain 
		
		try
		{
			$this->Registry->DeleteDomain($Domain);
			
			try
			{
				$RDomain = $this->GetRemoteDomainCopy($Domain);
				$this->fail('Delete domain');
			}
			catch (Exception $e)
			{
				$this->assertTrue($e->getMessage() == 'Object not exists', 'Delete domain');
			}
		}
		catch (Exception $e)
		{
			$this->fail('Delete contact. Error: ' . $e->getMessage());
		}
	}
	
	
	function _testNameserverLifeCircle ()
	{
		if (in_array('NameserverLifeCircle', $this->Skip))
			return;
		
		$Domain = $this->Domain;
		if (!$Domain)
		{
			$this->fail('$this->Domain is undefined');
			return;
		}

		$ns = new NameserverHost('ns'.rand(100, 999).'.' . $Domain->GetHostName(), '72.232.62.140');
		try
		{
			$ok = $this->Registry->NameserverCanBeCreated($ns);
			$this->assertTrue($ok, 'Nameserver can be created');
		}
		catch (Exception $e)
		{
			$this->fail('Nameserver can be created. Error: ' . $e->getMessage());
		}
		
		
		try
		{
			$this->Registry->CreateNameserverHost($ns);
			$this->assertTrue(true, 'Create DNS host');
		}
		catch (Exception $e)
		{
			$this->fail('Create DNS host. Error: ' . $e->getMessage());
			return;
		}

		
		try
		{
			$ns->IPAddr = '70.84.240.138';
			$this->Registry->UpdateNameserverHost($ns);
			$this->assertTrue(true, 'Update DNS host');
		}	
		catch (Exception $e)
		{
			$this->fail('Update DNS host. Error: ' . $e->getMessage());
		}
		
		try
		{
			$this->Registry->DeleteNameserverHost($ns);
			$this->assertTrue(true, 'Delete DNS host');
		}
		catch (Exception $e)
		{
			$this->fail('Delete DNS host. Error: ' . $e->getMessage());			
		}
		
	}
	
	/**
	 * @param Domain $domain
	 * @return Domain
	 */
	private function GetRemoteDomainCopy ($domain)
	{
		$rdomain = $this->Registry->NewDomainInstance();
		$rdomain->Name = $domain->Name;
		return $this->Registry->GetRemoteDomain($rdomain);
	}
}
?>