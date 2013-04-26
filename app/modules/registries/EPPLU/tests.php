<?php
/*
require_once (dirname(__FILE__) . '/class.AbstractRegistryModule.php');
require_once (dirname(__FILE__) . "/class.Domain.php");
require_once (dirname(__FILE__) . "/class.DBDomain.php");
require_once (dirname(__FILE__) . '/class.Contact.php');
require_once (dirname(__FILE__) . '/class.DBContact.php');
require_once (dirname(__FILE__) . '/class.Nameserver.php');
require_once (dirname(__FILE__) . '/class.PendingOperation.php');
require_once (dirname(__FILE__) . '/class.RegistryAccessible.php');
require_once (dirname(__FILE__) . '/class.Registry.php');
*/

class EPPLURegistryTests extends UnitTestCase 
{
	/**
	 * @var Registry
	 */
	private $Registry, $Registry2;
	
	
	
	function setUp() 
	{
		// primary registry
		$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByName('EPPLU');
		$this->Registry->AttachObserver(new EmailToRegistrantObserver());
		
		/*
		// secondary registry
		$DataForm = new DataForm();
		$DataForm->AppendField(new DataFormField('ServerHost', 'text', null, null, null, null, 'epp-test.dns.lu'));
		$DataForm->AppendField(new DataFormField('Login', 'text', null, null, null, null, 'alutashop_2'));
		$DataForm->AppendField(new DataFormField('Password', 'text', null, null, null, null, 'uBds9fn3hmLdf'));
		$DataForm->AppendField(new DataFormField('ServerPort', 'text', null, null, null, null, '1703'));
		
		$LURegistry = new EPPLURegistryModule();
		$LURegistry->InitializeModule('lu', $DataForm);
		$this->Registry2 = new Registry($LURegistry);
		*/
	}
	
	function _testSyncExpireDateWhereAutorenew ()
	{
		$module = $this->Registry->GetModule();
		$domain = DBDomain::GetInstance()->LoadByName("chroot", "lu");
		$expire_date = strtotime("2009-09-30");
		
		$this->Registry->SetDomainRemoteExpireDate($domain, $expire_date);
		$this->assertEqual(date("Y-m-d", $domain->ExpireDate), "2009-09-30");
	}
	
	function testBusy ()
	{
		/*
		$s = $this->Registry->PunycodeEncode("zangerlé.lu");
		var_dump($s);
		$s = $this->Registry->PunycodeDecode($s);
		var_dump($s);
		$s = $this->Registry->PunycodeDecode("zangerl-hya");
		var_dump($s);
		die();
		*/		
		
		//$s = $this->Registry->GetModule()->ReadMessage();
		//var_dump($s);

		
		
		$domain = $this->Registry->NewDomainInstance();
		$domain->Name = "tënten";
		$domain->Period = 1;
		$domain->UserID = 42;
		
		$contact = $this->Registry->NewContactInstanceByGroup("holder");
		$contact->CLID = "H93287628";
		$domain->SetContact($contact, CONTACT_TYPE::REGISTRANT);
		
		$contact = $this->Registry->NewContactInstanceByGroup("contact");
		$contact->CLID = "C38889071";
		$domain->SetContact($contact, CONTACT_TYPE::ADMIN);
		$domain->SetContact($contact, CONTACT_TYPE::TECH);
		
		$domain->SetNameserverList(array(
			new Nameserver("ns1.letzebuerg.net"),
			new Nameserver("ns2.letzebuerg.net")
		));
		
		$this->Registry->GetModule()->CreateDomain($domain, $domain->Period);

		
		//$this->Registry->DispatchPendingOperations();
		//$Resp = $this->Registry->GetModule()->ReadMessage();
		//var_dump($Resp);
		
		//$Domain = $this->Registry->NewDomainInstance();
		//$Domain->Name = 'for-transfer-1';
		//$this->Registry->GetModule()->DeleteDomain($Domain, strtotime('+3 day'));
	}
	
	/*
	function testGenerateCLID ()
	{
		$DBContact = DBContact::GetInstance();
		$clid = $DBContact->GenerateCLID('H%d8');
		$this->assertTrue(preg_match('/^H\d{8}$/', $clid) == true, 'Generate CLID');
		
		$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		var_dump($Contact->CLID);
	}
	*/
	
	/*
	function testChangelist ()
	{
		$list = array('mountain', 'city', 'sheep');
		$changedList = array('lake', 'city');

		$changes = new ChangeList($list, $changedList);
		
		$this->assertTrue(
			$changes->GetAdded() == array('lake') &&
			$changes->GetRemoved() == array('mountain', 'sheep') &&
			$changes->GetList() == $changedList,
			
			'Changelist with origin and changes'
		);
	}
	*/
	
	/*
	function testDomainExtra ()
	{
		$Domain = DBDomain::GetInstance()->Load(39, $this->Registry->GetManifest());
		$this->assertTrue($Domain->comment == 'fgdhfhdf', '������� ����������');
		DBDomain::GetInstance()->Save($Domain);
		die();
	}
	*/
	
	/*
	function testDBDelete ()
	{
		$DBDomain = DBDomain::GetInstance();
		$DBContact = DBContact::GetInstance();
		$id = 70;
		
		if ($DBDomain->Find($id))
		{
			$Domain = $DBDomain->Load($id, $this->Registry->GetManifest());
			$DBDomain->Delete($Domain);
			
			$this->assertFalse(
				$DBDomain->Find($id),
				'Delete domain from DB'
			);
		}
	}
	*/
	
	function _testGetRemoteContact ()
	{
		// Skip
		return;
		
		$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Contact->CLID = 'H26523637';
		$Contact = $this->Registry->GetRemoteContact($Contact);
		
		$discloses = $Contact->GetDiscloseList();
		$fields = $Contact->GetFieldList();
		
		$this->assertTrue(
			$Contact->CLID == 'H26523637' &&
			
			// fields
			$fields['name'] == 'marat komarov' &&
			$fields['isorg'] == '0' &&
			$fields['cc'] == 'UA' &&
			$fields['city'] == 'sebastopol' &&	
		
			
			// discloses
			$discloses['name'] == true &&
			$discloses['attr'] == false,
			
			'Get remote contact'
		);
	}
	
	function _testContact ()
	{
		// Skip
		return;
	
		$Admin = $this->Registry->NewContactInstance(CONTACT_TYPE::ADMIN);
		$Admin->SetFieldList(array(
			'name' => 'mosquito k',
			'org' => 'webta',
			'cc' => 'LU',
			'city' => 'sebastopol',
			'pc' => '99011',
			'street1' => 'Baker street',
			'email' => 'marat@local.webta.net'
		));
		$Admin->SetDiscloseList(array(
			'name' => false,
			'org' => false,
			'addr' => false,
			'email' => true
		));
		
		$this->Registry->CreateContact($Admin);
		
		//
		$Tech = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
		$Tech->SetFieldList($Admin->GetFieldList());
		$Tech->SetDiscloseList(array(
			'name' => true,
			'email' => true
		));
		$this->Registry->CreateContact($Tech);
		
		
		$AdminRemote = $this->Registry->NewContactInstance(CONTACT_TYPE::ADMIN);
		$AdminRemote->CLID = $Admin->CLID; 
		$AdminRemote = $this->Registry->GetRemoteContact($Admin);
		$this->assertTrue(
			$AdminRemote->CLID == $Admin->CLID,
			'Create admin contact'
		);
		
		$TechRemote = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
		$TechRemote->CLID = $Tech->CLID; 
		$TechRemote = $this->Registry->GetRemoteContact($Tech);
		$this->assertTrue(
			$TechRemote->CLID == $Tech->CLID,
			'Create tech contact'
		);
	}
	
	function _testGetRemoteDomain ()
	{
		// Skip
		return;
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat9810';
		
		$Domain = $this->Registry->GetRemoteDomain($Domain);
		
		$nameservers = $Domain->GetNameserverList();
		
		$this->assertTrue(
			$Domain->GetContact(CONTACT_TYPE::REGISTRANT)->CLID == 'H26523637' &&
			$Domain->GetContact(CONTACT_TYPE::TECH)->CLID == 'C42940239' &&
			$Domain->GetContact(CONTACT_TYPE::ADMIN)->CLID == 'C47623882' &&
			date('Ymd', $Domain->CreateDate) == '20080228' &&
			$nameservers[0]->HostName == 'ns1.google.com' &&
			$nameservers[1]->HostName == 'ns2.google.com',
		
			'Get remote domain'
		);
	}
	
	function _testCreateDomain ()
	{
		// Skip
		return;
		
		$DBContact = DBContact::GetInstance();
		
		$Registrant = $DBContact->LoadByCLID('H26523637');
		$Admin = $DBContact->LoadByCLID('C47623882');
		$Tech = $DBContact->LoadByCLID('C42940239');
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat' . rand(1000, 9999);
		$Domain->UserID = 8;
		
		$Domain->SetNameserverList(array(
			new Nameserver('ns1.google.com'),
			new Nameserver('ns2.google.com')
		));
		$Domain->SetContact($Registrant);
		$Domain->SetContact($Admin);
		$Domain->SetContact($Tech);
		
		// Create it
		$this->Registry->CreateDomain($Domain, 1);
		
		$this->assertTrue(
			$Domain->ID != null &&
			$Domain->Status == DOMAIN_STATUS::REGISTRATION_PENDING,
			'Create domain'
		);
	}
	
	function _testCanBe ()
	{
		// Skip
		return;
		
		// Domains
		$Domain = DBDomain::GetInstance()->LoadByName('marat9810', 'lu');
		$this->assertTrue($this->Registry->DomainCanBeRegistered($Domain) == false, 'Domain marat9810.lu is mine');
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat' . rand(1000, 9999);
		$this->assertTrue($this->Registry->DomainCanBeRegistered($Domain) == true, 'Domain ' . $Domain->GetHostName() . ' is available');
		
		// Contacts
		$Contact = DBContact::GetInstance()->LoadByCLID('H26523637');
		$this->assertTrue($this->Registry->ContactCanBeCreated($Contact) == false, 'Contact ' . $Contact->CLID . ' is already in use');
		
		$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::ADMIN);
		$this->assertTrue($this->Registry->ContactCanBeCreated($Contact) == true, 'Contact is available');
		
		// Nameservers
		$NS = new NameserverHost('ns.hostdad.com', gethostbyname('ns.hostdad.com'));
		$this->assertTrue($this->Registry->NameserverCanBeCreated($NS) == false, $NS->HostName . ' is already created');
		
		$NS = new NameserverHost('ns3.google.com', gethostbyname('ns3.google.com'));
		$this->assertTrue($this->Registry->NameserverCanBeCreated($NS) == true, $NS->HostName . ' is available');
	}
	
	function _testNameserverLifecircle ()
	{
		// skip
		return;
		
		$NS = new NameserverHost('ns3.google.com', gethostbyname('ns3.google.com'));
		$this->assertTrue($this->Registry->NameserverCanBeCreated($NS) == true, $NS->HostName . ' is available');
		
		$NS = $this->Registry->CreateNameserverHost($NS);
		$this->assertTrue((bool)$NS, 'Nameserver host created');
		
		$NS->IPAddr = gethostbyname('ns1.google.com');
		$NS = $this->Registry->UpdateNameserverHost($NS);
		$this->assertTrue((bool)$NS, 'Namserver host updated');
		
		//$NS = new NameserverHost('ns3.google.com', gethostbyname('ns1.google.com'));
		
		$this->Registry->DeleteNameserverHost($NS);

		
		$this->assertTrue($this->Registry->NameserverCanBeCreated($NS) == true, 'Namserver host deleted');
		
	}

	function _testMemento ()
	{
		// Skip
		return;
		
		// Domain
		$DBDomain = DBDomain::GetInstance();
		
		$domain = $DBDomain->Load(88);
		$domain->Status = DOMAIN_STATUS::DELEGATED;
		
		$oldDomain = $DBDomain->GetInitialState($domain);
		
		$this->assertTrue(
			$oldDomain->Status == DOMAIN_STATUS::REGISTRATION_PENDING,
			'Domain memento'
		);
		
		// Contact
		$DBContact = DBContact::GetInstance();
		
		$contact = $DBContact->LoadByCLID('H26523637');
		$contact->SetDiscloseValue('addr', 1);
		
		$oldContact = $DBContact->GetInitialState($contact);
		
		$oldDisclose = $oldContact->GetDiscloseList();
		$this->assertTrue(
			$oldDisclose['addr'] == 0,
			'Contact memento'
		);
	}
	
	function _testDispatchPending ()
	{
		// Skip
		//return;
		
		$this->Registry->AttachObserver(new TestListener());
		$this->Registry->DispatchPendingOperations();
	}
	
	/*
	function _testInactiveDomain ()
	{
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat9810';
		
		$Domain = $this->Registry->GetRemoteDomain($Domain);
		var_dump($Domain);
	}
	*/
	
	
	function CreateDomainForTransfer()
	{
		// Contacts
		$Registrant = $this->Registry2->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Registrant->SetFieldList(array(
  			'name' => 'marat komarov',
  			'isorg' => '0',
  			'cc' =>'UA',
  			'city' => 'sebastopol',
  			'pc' => '3212',
  			'street1' => 'bbfdgfd fds',
  			'street2' => 'dsf fd d'		
		));
		$this->Registry2->CreateContact($Registrant);
		
		$Admin = $this->Registry2->NewContactInstance(CONTACT_TYPE::ADMIN);
		$Admin->SetFieldList(array(
			'name' => 'mosquito k',
			'org' => 'webta',
			'cc' => 'LU',
			'city' => 'sebastopol',
			'pc' => '99011',
			'street1' => 'Baker street',
			'email' => 'marat@webta.net'
		));
		$Admin->SetDiscloseList(array(
			'name' => false,
			'org' => false,
			'addr' => false,
			'email' => true
		));
		
		$this->Registry2->CreateContact($Admin);
		
		//
		$Tech = $this->Registry2->NewContactInstance(CONTACT_TYPE::TECH);
		$Tech->SetFieldList($Admin->GetFieldList());
		$Tech->SetDiscloseList(array(
			'name' => true,
			'email' => true
		));
		$this->Registry2->CreateContact($Tech);
		
		
		$AdminRemote = $this->Registry2->NewContactInstance(CONTACT_TYPE::ADMIN);
		$AdminRemote->CLID = $Admin->CLID; 
		$AdminRemote = $this->Registry2->GetRemoteContact($Admin);
		$this->assertTrue(
			$AdminRemote->CLID == $Admin->CLID,
			'Create admin contact'
		);
		
		$TechRemote = $this->Registry2->NewContactInstance(CONTACT_TYPE::TECH);
		$TechRemote->CLID = $Tech->CLID; 
		$TechRemote = $this->Registry2->GetRemoteContact($Tech);
		$this->assertTrue(
			$TechRemote->CLID == $Tech->CLID,
			'Create tech contact'
		);		
		
		
		
		//$DBContact = DBContact::GetInstance();
		
		//$Registrant = $DBContact->LoadByCLID('H26523637');
		//$Admin = $DBContact->LoadByCLID('C47623882');
		//$Tech = $DBContact->LoadByCLID('C42940239');
		
		$Domain = $this->Registry2->NewDomainInstance();
		$Domain->Name = 'marat' . rand(1000, 9999);
		$Domain->UserID = 8;
		
		$Domain->SetNameserverList(array(
			new Nameserver('ns1.google.com'),
			new Nameserver('ns2.google.com')
		));
		$Domain->SetContact($Registrant);
		$Domain->SetContact($Admin);
		$Domain->SetContact($Tech);
		
		// Create it
		$this->Registry2->CreateDomain($Domain, 1);
		
		$this->assertTrue(
			$Domain->ID != null &&
			$Domain->Status == DOMAIN_STATUS::REGISTRATION_PENDING,
			'Create domain'
		);		
	}

	function _testChangeDomainOwner ()
	{
		// Skip
		return;
		
		// Contacts
		$Registrant = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Registrant->SetFieldList(array(
  			'name' => 'marat komarov',
  			'isorg' => '0',
  			'cc' =>'UA',
  			'city' => 'sebastopol',
  			'pc' => '3212',
  			'street1' => 'bbfdgfd fds',
  			'street2' => 'dsf fd d'		
		));
		$this->Registry->CreateContact($Registrant);
		
		$Admin = $this->Registry->NewContactInstance(CONTACT_TYPE::ADMIN);
		$Admin->SetFieldList(array(
			'name' => 'mosquito k',
			'org' => 'webta',
			'cc' => 'LU',
			'city' => 'sebastopol',
			'pc' => '99011',
			'street1' => 'Baker street',
			'email' => 'marat@webta.net'
		));
		$Admin->SetDiscloseList(array(
			'name' => false,
			'org' => false,
			'addr' => false,
			'email' => true
		));
		
		$this->Registry->CreateContact($Admin);
		
		//
		$Tech = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
		$Tech->SetFieldList($Admin->GetFieldList());
		$Tech->SetDiscloseList(array(
			'name' => true,
			'email' => true
		));
		$this->Registry->CreateContact($Tech);

		
		$Domain = DBDomain::GetInstance()->LoadByName('marat1996', 'lu');
		$this->Registry->GetRemoteDomain($Domain);
		
		var_dump($Domain);
		
		
		/*
		$Domain->SetContact($Registrant);
		$Domain->SetContact($Admin);
		$Domain->SetContact($Tech);
		*/
		
		//$this->Registry->ChangeDomainOwner($Domain, 1);
	}
	
	function _testTrade ()
	{
		// skip it
		return;
		
		/*
		$DBContact = DBContact::GetInstance();
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat' . rand(1000, 9999);
		$Domain->SetContact($DBContact->LoadByCLID('H67999712'));
		$Domain->SetContact($DBContact->LoadByCLID('C23770836'));
		$Domain->SetContact($DBContact->LoadByCLID('C27479794'));
		$Domain->SetNameserverList(array(
			new Nameserver('ns1.google.com'),
			new Nameserver('ns2.google.com')			
		));
		$this->Registry->CreateDomain($Domain, 1);

		*/
		
		$Domain = DBDomain::GetInstance()->LoadByName('marat8082', 'lu');
		
		$Registrant = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Registrant->SetFieldList(array(
			'name' => 'mosquito',
			'city' => 'sebastopol',
			'isorg' => '0',
			'cc' => 'UA',
			'pc' => '32312',
			'street1' => 'pervomayskaya 13-3'
		));
		$this->Registry->CreateContact($Registrant);
		
		$Domain->SetContact($Registrant);
		$this->Registry->ChangeDomainOwner($Domain, 1);
		
		$this->assertTrue(
			true,
			'change domain owner' 
		);

		return;
		
	}
	

	function _testTransfer ()
	{
		// skip
		return;
		
		/*
		$Domain = $this->Registry2->NewDomainInstance();
		$Domain->Name = 'marat9895';
		$Domain = $this->Registry2->GetRemoteDomain($Domain);
		var_dump($Domain);
		return;
		*/ 
		
		if (0)
			$this->CreateDomainForTransfer();
			

		$this->Registry->AttachObserver(new TestListener());

		
		// Create contacts
		$Registrant = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Registrant->SetFieldList(array(
  			'name' => 'marat komarov',
  			'isorg' => '0',
  			'cc' =>'UA',
  			'city' => 'sebastopol',
  			'pc' => '3212',
  			'street1' => 'bbfdgfd fds',
  			'street2' => 'dsf fd d'		
		));
		$this->Registry->CreateContact($Registrant);
		
		$Admin = $this->Registry->NewContactInstance(CONTACT_TYPE::ADMIN);
		$Admin->SetFieldList(array(
			'name' => 'mosquito k',
			'org' => 'webta',
			'cc' => 'LU',
			'city' => 'sebastopol',
			'pc' => '99011',
			'street1' => 'Baker street',
			'email' => 'marat@local.webta.net'
		));
		$Admin->SetDiscloseList(array(
			'name' => false,
			'org' => false,
			'addr' => false,
			'email' => true
		));
		
		$this->Registry->CreateContact($Admin);
		
		//
		$Tech = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
		$Tech->SetFieldList($Admin->GetFieldList());
		$Tech->SetDiscloseList(array(
			'name' => true,
			'email' => true
		));
		$this->Registry->CreateContact($Tech);
		
		
		
		
		//$DBContact = DBContact::GetInstance(); 
		//$Registrant = $DBContact->LoadByCLID('H67688978');
		//$Admin = $DBContact->LoadByCLID('C72333297');
		//$Tech = $DBContact->LoadByCLID('C50060860');
		
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat1996';
		$Domain->SetContact($Registrant);
		$Domain->SetContact($Admin);
		$Domain->SetContact($Tech);
		
		//$Domain->AuthCode = '123456';
		$extra = array(
			'ns1' => 'ns.hostdad.com',
			'ns2' => 'ns2.hostdad.com'
		);
		
		$this->Registry->TransferRequest($Domain, $extra);
		
		$this->assertTrue(
			$Domain->Status = DOMAIN_STATUS::AWAITING_TRANSFER_AUTHORIZATION &&
			date('Ymd', $Domain->TransferDate) == date('Ymd'),
			$Domain->ID != null,
			'Transfer request'
		);
	}
	

	/*
	function _testTransferApprove ()
	{
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat2345';
		$Domain->AuthCode = '123456';
		
		try
		{
			$ok = $this->Registry2->TransferApprove($Domain);
			$this->assertTrue($ok == true, 'Transfer approved');
		}
		catch (Exception $e)
		{
			var_dump($e->getMessage());
		}
	}
	*/

	
	/*
	function _testTransferReject ()
	{
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat1234';
		$Domain->AuthCode = '123456';
		
		try
		{
			$ok = $this->Registry2->TransferReject($Domain);
			$this->assertTrue($ok == true, 'Transfer rejected');
		}
		catch (Exception $e)
		{
			var_dump($e->getMessage());
		}
	}
	*/
	

}

class TestListener extends RegistryObserverAdapter
{
	public function OnDomainCreated (Domain $domain)
	{
		var_dump('domain created: ' . $domain->GetHostName());
	}
	
	public function OnDomainTransferApproved (Domain $domain)
	{
		var_dump('transfer approved: ' . $domain->GetHostName());
	}
	
	public function OnDomainTransferDeclined (Domain $domain)
	{
		var_dump('transfer declined: ' . $domain->GetHostName());
	}
	
	public function OnDomainTransferFailed (Domain $domain)
	{
		var_dump('transfer failed: ' . $domain->GetHostName());
	}
	
}

?>