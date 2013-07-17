<?php

class OnlineNICRegistryTests extends UnitTestCase 
{
	/**
	 * @var Registry
	 */
	private $Registry;
	
	private $contact_data;
	
	function setUp ()
	{
		$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension('eu');
		$this->contact_data = array(
			"name"		=> "Marat Komarov",
			"org" 		=> "Webta",
			"street1" 	=> "Testing str. 80",
			"street2"	=> "Drive 54",
			"city"		=> "Testing",
			"pc"		=> "3129112",
			"cc"		=> "GB",
			"sp"		=> "test",
			"voice"		=> "+333-432-1234567",
			"fax"		=> "+333-432-1234567",
			"email"		=> "marat@webta.net",
			"lang"		=> "fr"
		);		
	}
	
	function testBusy ()
	{
		/*
<domain:type>902</domain:type>
<domain:name>marcosaiu.eu</domain:name>
<domain:period>1</domain:period>
<domain:ns1>ns1.srgdns.net</domain:ns1>
<domain:ns2>ns2.srgdns.net</domain:ns2>
<domain:registrant>c9153961</domain:registrant>
<domain:contact type="admin">c9153960</domain:contact>
<domain:contact type="tech">c9153960</domain:contact>
<domain:contact type="billing">c9153960</domain:contact>
<domain:authInfo type="pw">25893130</domain:authInfo>
		*/


		/*
		$Module = $this->Registry->GetModule();
		
		$Registrant = $this->Registry->NewContactInstanceByGroup('registrant');
		$Registrant->CLID = 'c9153961';
		
		$Contact = $this->Registry->NewContactInstanceByGroup('generic');
		$Contact->CLID = 'c9153960';
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marcosaiu';
		$Domain->SetContact($Registrant, CONTACT_TYPE::REGISTRANT);
		$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
		$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
		$Domain->SetContact($Contact, CONTACT_TYPE::ADMIN);
		$Domain->SetNameserverList(array(
			new Nameserver('ns1.srgdns.net'),
			new Nameserver('ns2.srgdns.net')
		));
		$Domain->AuthCode = '25893130';
		$Cdr = $Module->CreateDomain($Domain, 1);
		var_dump($Cdr);
		*/
	}

	function _testOnlineNIC ()
	{
		$Registrant = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Registrant->SetFieldList($this->contact_data);
		$Registrant->AuthCode = rand(100000, 999999);
		$this->Registry->CreateContact($Registrant);
		
		$this->assertTrue($Registrant->CLID != null, 'Create registrant contact');
		
		$Contact = $this->Registry->NewContactInstanceByGroup('generic');
		$Contact->SetFieldList(array_merge($this->contact_data, array(
			'cc' => 'US',
			'AppPurpose' => 'P1',
			'NexusCategory' => 'C12'	
		))); 
		$Contact->AuthCode = rand(100000, 999999);		
		$this->Registry->CreateContact($Contact);
		$this->assertTrue($Contact->CLID != null, 'Create generic contact');
		

		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'webta' . rand(1000, 9999);

		$this->assertTrue($this->Registry->DomainCanBeRegistered($Domain), 'Domain available for registration');
			
		$Domain->SetNameserverList(array(
			new Nameserver('ns1.onlinenic.com'),
			new Nameserver('ns2.onlinenic.com')
		));
		$Domain->SetContact($Registrant, CONTACT_TYPE::REGISTRANT);
		$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
		$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
		$Domain->SetContact($Contact, CONTACT_TYPE::ADMIN);
		$Domain->AuthCode = rand(10000000, 99999999);
		
		$this->Registry->CreateDomain($Domain, 2);
		$this->assertTrue(
			$Domain->ID != null &&
			$Domain->Status == DOMAIN_STATUS::DELEGATED,
			'Create domain'			
		);
		

		$contact_data = $Contact->GetFieldList();
		$contact_data['name'] = 'Nerrible man';
		$Contact->SetFieldList($contact_data);
		$Contact->ExtraData['type'] = CONTACT_TYPE::TECH;
		$Contact->ExtraData['domainname'] = $Domain->Name;
		$this->Registry->UpdateContact($Contact);
		$this->assertTrue(
			true,
			'Update contact'
		);

		$old_expire_date = $Domain->ExpireDate;
		$this->Registry->RenewDomain($Domain, array('period' => 1));
		$this->assertTrue(
			$Domain->ExpireDate !== $old_expire_date,
			'Renew domain'
		);
		
		$nslist = $Domain->GetNameserverList();
		$changelist = $Domain->GetNameserverChangelist();
		$changelist->SetChangedList(array(
			new Nameserver('ns1.google.com'),
			new Nameserver('ns2.google.com')
		));
		$this->Registry->UpdateDomainNameservers($Domain, $changelist);
		$this->assertTrue(
			$Domain->GetNameserverList() == $changelist->GetList(),
			'Update domain nameservers'
		);
		
		$Domain2 = $this->Registry->NewDomainInstance();
		$Domain2->Name = $Domain->Name;
		
		$Domain2 = $this->Registry->GetRemoteDomain($Domain2);
		$this->assertTrue(
			$Domain2->ExpireDate == $Domain->ExpireDate,
			'Get remote domain'
		);
		
		$nshost = new NameserverHost("ns1.{$Domain->GetHostName()}", '70.84.45.21');
		$this->Registry->CreateNameserverHost($nshost);
		
		$nshost->IPAddr = '70.84.45.23';
		$this->Registry->GetModule()->UpdateNameserverHost($nshost);
		
		$ok = $this->Registry->DeleteNameserverHost($nshost);
		$this->assertTrue($ok, 'Delete nameserver host');
		
		
		$ok = $this->Registry->DeleteDomain($Domain);
		$this->assertTrue($ok, 'Delete domain');
	}
}

?>