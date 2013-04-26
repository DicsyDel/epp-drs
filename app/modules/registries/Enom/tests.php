<?php

	require_once ('class.RegistryModule.php');
	require_once ('class.Transport.php');

	class EnomRegistryTests extends UnitTestCase 
	{
		/**
		 * @var Registry
		 */
		private $Registry;
		
		/**
		 * @var EnomRegistryModule
		 */
		private $Module;
		
		private $contact_fields, $tech_contact_fields;
		
		function setUp ()
		{
			$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByName("Enom");
			return;
			
			$Config = EnomRegistryModule::GetConfigurationForm();
			$config_fields = $Config->ListFields();
			$config_fields['Login']->Value = 'webta';
			$config_fields['Password']->Value = 'UpAkior5';
			$config_fields['ServerHost']->Value = 'http://resellertest.enom.com/interface.asp'; 
			
			$Module = new EnomRegistryModule();
			$Module->InitializeModule('mobi', $Config);
			
			$this->Registry = new Registry($Module);
			$this->Module = $Module;
			
			$this->contact_fields = array(
				'FirstName' => 'marat',
				'LastName' => 'komarov',
				'OrganizationName' => 'webta',
				'JobTitle' => 'Mr',
				'Address1' => 'Testing str. 80',
				'City' => 'Sebastopol',
				'StateProvinceChoice' => 'P',
				'StateProvince' => 'Crimea',
				'PostalCode' => '99009',
				'Country' => 'UA',
				'EmailAddress' => 'marat@webta.net',
				'Phone'	=> '+33-131-2312',
				'Fax'	=> '+33-123-1231'
			);
			
			$this->tech_contact_fields = array(
				'FirstName' => 'nikolas123',
				'LastName' => 'toursky',
				'OrganizationName' => 'webta',
				'JobTitle' => 'Mr',
				'Address1' => 'Testing str. 80',
				'City' => 'Sebastopol',
				'StateProvinceChoice' => 'P',
				'StateProvince' => 'Crimea',
				'PostalCode' => '99009',
				'Country' => 'UA',
				'EmailAddress' => 'marat@webta.net',
				'Phone'	=> '+33-131-2312',
				'Fax'	=> '+33-123-1231'
			);
		}
		
		
		function _testBusy ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'webtta';

			$RContact = $this->Registry->NewContactInstanceByGroup('generic');
			
			
			/*
			$NS = new NameserverHost('ns100.svetloe-pivo.asia', '127.0.0.1');
			$Resp = $this->Module->CreateNameserverHost($NS);
			var_dump($Resp);
			*/
			
			/*
			$params = array(
				'SLD' => 'mobile-reviews',
				'TLD' => 'us'
			);
			$Resp = $this->Module->Request('GetRegLock', $params);
			var_dump($Resp->Data);
			return;
			*/
			
			/*
			$RDomain = $this->Registry->NewDomainInstance();
			$RDomain->Name = 'mobile-reviews';
			$Resp = $this->Module->GetRemoteDomain($RDomain);
			var_dump($Resp);
			*/
		}
		
		function testBusy()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = "podocare";
			var_dump($this->Registry->GetRemoteDomain($Domain));
		}
		
		function _testModule ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'webta' . rand(1000, 9999);
			
			////
			// 1. Check domain
			$Resp = $this->Module->DomainCanBeRegistered($Domain); 
			$this->AssertTrue(
				$Resp->Succeed() && $Resp->Result, 
				'Domain available for registration'
			);
			
			////
			// 2. Create contact
			$Contact = $this->Registry->NewContactInstanceByGroup('generic');
			$Contact->SetFieldList($this->contact_fields);
			$Resp = $this->Module->CreateContact($Contact);
			$Contact->CLID = $Resp->CLID;
			$this->AssertTrue(
				$Resp->Succeed() && $Resp->CLID,
				'Create contact'
			);
			
			////
			// 3. Contact info
			$RContact = $this->Registry->NewContactInstanceByGroup('generic');
			$RContact->CLID = $Contact->CLID;
			$RContact = $this->Registry->GetRemoteContact($RContact);
			$rfields = $RContact->GetFieldList();
			$fields = $Contact->GetFieldList();
			$eq = true;
			foreach (array_keys($this->contact_fields) as $k)
			{
				$eq = $eq && $rfields[$k] == $fields[$k];
			}			
			$this->AssertTrue(
				$Resp->Succeed() && 
				$rfields['FirstName'] == $fields['FirstName'] &&
				$rfields['LastName'] == $fields['LastName'], 
				'Get remote contact'
			);
			
			////
			// 4. Create domain
			$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
			$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
			$Domain->SetContact($Contact, CONTACT_TYPE::ADMIN);
			$Domain->SetNameserverList(array(
				new Nameserver('ns.hostdad.com'),
				new Nameserver('ns2.hostdad.com')
			));
			$period = 3;
			
			$Resp = $this->Module->CreateDomain($Domain, $period);
			$this->AssertTrue(
				$Resp->CreateDate &&
				date('Ymd', strtotime("+{$period} year")) == date('Ymd', $Resp->ExpireDate),
				'Create domain'
			);
			
			////
			// 5. Create 2 child nameservers for domain
			$NS1 = new NameserverHost('ns1.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
			$NS2 = new NameserverHost('ns2.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
			
			$Resp = $this->Module->CreateNameserverHost($NS1);
			$this->AssertTrue(
				$Resp->Succeed() && $Resp->Result,
				'Create subordinate nameserver 1'
			);
			
			$Resp = $this->Module->CreateNameserverHost($NS2);
			$this->AssertTrue(
				$Resp->Succeed() && $Resp->Result,
				'Create subordinate nameserver 2'
			);
			
			////
			// 6. Assign nameservers to domain
			$Changes = $Domain->GetNameserverChangelist();
			$Changes->Add($NS1);
			$Changes->Add($NS2);
			
			$Resp = $this->Module->UpdateDomainNameservers($Domain, $Changes);
			$this->AssertTrue(
				$Resp->Succeed() && $Resp->Result,
				'Update domain nameservers'
			);
			
			////
			// 7. Create tech contact and assign it to domain
			$Tech = $this->Registry->NewContactInstanceByGroup('generic');
			$Tech->SetFieldList($this->tech_contact_fields);
			$Resp = $this->Module->CreateContact($Tech);
			$Tech->CLID = $Resp->CLID;
			$Resp = $this->Module->UpdateDomainContact($Domain, CONTACT_TYPE::TECH, null, $Tech);
			
			////
			// 8. Lock domain
			$Resp = $this->Module->LockDomain($Domain);
			$this->AssertTrue(
				$Resp->Succeed() && $Resp->Result,
				'Lock domain'
			);
			
			////
			// 9. Perform an INFO command on the domain to verify update
			$RDomain = $this->Registry->NewDomainInstance();
			$RDomain->Name = $Domain->Name;
			$Resp = $this->Module->GetRemoteDomain($RDomain);
			$this->AssertTrue(
				$Resp->TechContact == $Tech->CLID &&
				count($Resp->GetNameserverList()) == 4,
				'Perform an INFO command on the domain to verify update'
			);
			
			$Tech2 = $this->Registry->NewContactInstanceByGroup('generic');
			$Tech2->CLID = $Resp->TechContact;
			$this->Registry->GetRemoteContact($Tech2);
			
			////
			// 10. Update one of the name server�s IP Address
			$NS1->IPAddr = gethostbyname('ns.hostdad.com');
			$Resp = $this->Module->UpdateNameserverHost($NS1);
			$this->AssertTrue(
				$Resp->Succeed() && $Resp->Result,
				'Update one of the name server�s IP Address'
			);
			
			////
			// 11. Renew domain for 2 years
			$old_expire_date = $Domain->ExpireDate;
			$period = 2;
			$Resp = $this->Module->RenewDomain($Domain, array('period' => $period));
			$this->AssertTrue(
				date('Ymd', $Resp->ExpireDate) == date('Ymd', strtotime("+{$period} year", $old_expire_date)),
				'Renew domain'
			);
			
			////
			// 12. Delete nameservers
			$list = $Domain->GetNameserverList();
			$list[] = $NS1;
			$list[] = $NS2;
			$Changes = new Changelist($list, array());
			//var_dump($Changes->GetList());
			//var_dump($Changes->GetRemoved());
			
			$Resp = $this->Module->UpdateDomainNameservers($Domain, $Changes);
			$this->AssertTrue(
				$Resp->Succeed() && $Resp->Result,
				'Remove nameservers from domain'
			);
			
			////
			// 13. Delete domain
			
			$Resp = $this->Module->DeleteDomain($Domain);
			$this->AssertTrue(
				$Resp->Succeed() && $Resp->Result,
				'Delete domain'
			);
			
			////
			// 14. Delete contact
			$Resp = $this->Module->DeleteContact($Contact);
			$Resp = $this->Module->DeleteContact($Tech);
		}
	}

?>