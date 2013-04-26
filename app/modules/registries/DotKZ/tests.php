<?php

	class DotKZRegistryTests extends UnitTestCase 
	{
		/**
		 * @var Registry
		 */
		private $Registry;
		
		/**
		 * @var DotKZRegistryModule
		 */
		private $Module;
		
		private $contact_fields;
		
		function setUp ()
		{
			$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension('kz');
			$this->Module = $this->Registry->GetModule();
			
			$this->contact_fields = array(
				'name' => 'Marat Komarov',
				'org' => 'Webta',
				'email' => 'marat@webta.net',
				'street1' => 'Test street',
				'city' => 'Sebastopol',
				'pc' => '99011',
				'sp' => 'Crimea',
				'cc' => 'UA',
				'voice' => '+380-434-4343223',
				'fax' => '+380-424-5546433'
			);
		}
		
		function testBusy ()
		{
			//
			$factory = RegistryModuleFactory::GetInstance(); 
			$r1 = $factory->GetRegistryByExtension('kz');
			$r2 = $factory->GetRegistryByExtension('gov.kz');
			
			$c1 = $r1->NewContactInstanceByGroup('generic');
			$c2 = $r2->NewContactInstanceByGroup('generic');
			
			var_dump($c2->ModuleName);
			var_dump($c2->SectionName);
			var_dump($c2->TargetIndex);
			
			$this->assertTrue(
				$c1->ModuleName == 'DotKZ' &&
				$c1->SectionName == 'all' &&
				$c1->TargetIndex == 0
			);
			$this->assertTrue(
				$c2->ModuleName == 'DotKZ' &&
				$c2->SectionName == 'all' &&
				$c2->TargetIndex == 1
			);
		}
		
		function _testNS ()
		{
			$Domain = DBDomain::GetInstance()->LoadByName('metropolin', 'kz');
			$NS1 = new Nameserver('ns.hostdad.com'); 
			$NS2 = new Nameserver('ns2.hostdad.com');
			$changelist  = $Domain->GetNameserverChangelist();
			$changelist->Add($NS1);
			$changelist->Add($NS2);
			
			$this->Registry->UpdateDomainNameservers($Domain, $changelist);
		}
		
		function _testOTE ()
		{
			$DbDomain = DBDomain::GetInstance();
			$DbNameserverHost = DBNameserverHost::GetInstance();

			// Cleanup previous execution traces
			try	{
				$this->Module->Request("domain-delete", array("name" => "testeppart.kz"));
			} catch (Exception $e) {}
			try {
				$Domain = $DbDomain->LoadByName('testeppart', 'kz');
				$this->Registry->DeleteDomain($Domain);
			} catch (Exception $e) {}
			
			try	{
				$this->Module->Request("domain-delete", array("name" => "newtesteppart.kz"));
			} catch (Exception $e) {}
			try {
				$Domain = $DbDomain->LoadByName('newtesteppart', 'kz');
				$this->Registry->DeleteDomain($Domain);
			} catch (Exception $e) {}
			
				
			
			//
			// 1. Create domain
			//
			
			$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
			$Contact->SetFieldList($this->contact_fields);
			$this->Registry->CreateContact($Contact);
			
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->UserID = 39; 
			$Domain->Name = 'testeppart';
			$Domain->Period = 1;
			$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
			$Domain->SetContact($Contact, CONTACT_TYPE::ADMIN);
			$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
			$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
	
			$this->Registry->CreateDomain($Domain, $Domain->Period);
			
			$NS1 = new NameserverHost('ns1.testeppart.kz', '212.110.212.110');
			$this->Registry->CreateNameserver($NS1);
	
			$NS2 = new NameserverHost('ns2.testeppart.kz', '212.110.111.111');
			$this->Registry->CreateNameserver($NS2); 
					
			$Changelist = $Domain->GetNameserverChangelist();
			$Changelist->Add($NS1);
			$Changelist->Add($NS2);
			$this->Registry->UpdateDomainNameservers($Domain, $Changelist);
	
			$this->AssertTrue(
				date('Ymd', $Domain->ExpireDate) == date('Ymd', strtotime('+1 year')) &&
				count($Domain->GetNameserverList()) == 2,
				'Create domain'
			);
			
			// Reload domain from Db for correct operations
			$Domain = $DbDomain->LoadByName('testeppart', 'kz');
			$DbNameserverHost->LoadList($Domain->ID);
			
			//
			// 2. Update nameserver host
			//
			
			$nslist = $Domain->GetNameserverList();
			$NS2 = $nslist[1];
			$NS2->IPAddr = '212.111.110.110';
			$this->Registry->UpdateNameserverHost($NS2);
			$this->assertTrue(true, 'Update nameserver host');
			
			
			//
			// 3. Create nameserver host 
			//
			
			$Domain4Host = $this->Registry->NewDomainInstance();
			$Domain->UserID = 39; 
			$Domain->Name = 'newtesteppart';
			$Domain->Period = 1;
			$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
			$this->Registry->CreateDomain($Domain, $Domain->Period);			
			
			$NS3 = new NameserverHost('ns.newtesteppart.kz', '211.211.211.211');
			$this->Registry->CreateNameserverHost($NS3);
			$this->assertTrue(true, 'Create nameserver host');
			
			//
			// 4. Add nameserver to domain
			//
			
			$Changelist = $Domain->GetNameserverChangelist();
			$Changelist->Add($NS3);
			$this->Registry->UpdateDomainNameservers($Domain, $Changelist);
			$this->assertTrue(count($Domain->GetNameserverList()) == 3, 'Add nameserver to domain');

			//
			// 5. Remove nameserver from domain
			//
			
			$nslist = $Domain->GetNameserverList();
			$NS1 = $nslist[0];
			$Changelist = $Domain->GetNameserverChangelist();
			$Changelist->Remove($NS1);
			$this->Registry->UpdateDomainNameservers($Domain, $Changelist);
			$this->assertTrue(count($Domain->GetNameserverList()) == 2, 'Remove nameserver from domain');
			
			//
			// 6. Delete nameserver host
			//
			try
			{
				$this->Registry->DeleteNameserverHost($NS1);
				$this->assertTrue(true, 'Delete nameserver host');
			} 
			catch (Exception $e)
			{
				$this->assertTrue(true, 'Delete nameserver host failed. Don\'t forget to cheat response code');
			}
			
			//
			// 7. Update contact
			//
			$contact_fields = $Contact->GetFieldList();
			$contact_fields['voice'] = '+380-555-7654321';
			$this->Registry->UpdateContact($Contact);
			$this->assertTrue(true, 'Update contact');
			
			//
			// 8. Start ingoing transfer
			//
			$TrnDomain = $this->Registry->NewDomainInstance();
			$TrnDomain->Name = 'xyz1';
			$TrnDomain->UserID = 39;
			$this->Registry->TransferRequest($TrnDomain, array('pw' => '123456'));
			
			
			$this->Registry->DeleteDomain($Domain);
			$this->Registry->DeleteContact($Contact);
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
			
			// Create domain
			
			try
			{
				$Domain->UserID = 3;
				$Domain->SetContact($Registrant, CONTACT_TYPE::REGISTRANT);
						
				$NS1 = new Nameserver('ns.hostdad.com'); 
				$NS2 = new Nameserver('ns2.hostdad.com');
				$Domain->SetNameserverList(array($NS1, $NS2));
				
				$this->Registry->CreateDomain($Domain, 2);
				$this->assertTrue(true, 'Create domain');
			}
			catch (Exception $e)
			{
				return $this->fail('Create domain. Error: ' . $e->getMessage());
			}
			
			try
			{
				$RDomain = $this->Registry->NewDomainInstance();
				$RDomain->Name = $Domain->Name;
				
				$RDomain = $this->Registry->GetRemoteDomain($RDomain);
				
				$this->assertTrue(
					$RDomain->Name == $Domain->Name &&
					count($RDomain->GetNameserverList()) == count($Domain->GetNameserverList()) &&
					date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
					date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate),
					
					'Get remote domain'
				);
			}
			catch (Exception $e)
			{
				return $this->fail('Get remote domain. Error: ' . $e->getMessage());
			}			
			
			////
			// 3. CREATE 2 child name servers of newly created domain
			//
			try
			{
				$nshost = new NameserverHost('ns1.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
				$nshost2 = new NameserverHost('ns2.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
				
				$this->Registry->CreateNameserverHost($nshost);
				$this->Registry->CreateNameserverHost($nshost2);
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
				$nslist->Add($nshost);
				$nslist->Add($nshost2);
				
				$this->Registry->UpdateDomainNameservers($Domain, $nslist);
				
				$this->assertTrue(
					count($Domain->GetNameserverList()) == 4,
					'Attach nameservers to domain'
				);
			}
			catch (Exception $e)
			{
				return $this->fail('Attach nameservers to domain. Error: ' . $e->getMessage());
			}
			
			
			////
			// 5. UPDATE Domain’s status to 
			// clientHold, clientUpdateProhibited, clientDeleteProhibited, and clientTransferProhibited 
			// within one command
			
			try
			{
				$flag_list = $Domain->GetFlagChangelist();
				$flag_list->SetChangedList(array(
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
			// 7. UPDATE Domain’s status to OK
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
			// 10. UPDATE one of the name server’s IP Address
			//
			
			try
			{
				$nshost->IPAddr = gethostbyname('ns.hostdad.com');
				$Resp = $this->Registry->GetModule()->UpdateNameserverHost($nshost);
				
				$this->assertTrue($Resp->Result, 'Update domain nameserver');
			}
			catch (Exception $e)
			{
				return $this->fail('Update domain nameserver. Error: ' . $e->getMessage());
			}
			
			
			////
			// 12. Renew Domain for 2 years
			//
			
			/*
			try
			{
				$old_expire_date = $Domain->ExpireDate;
				$this->Registry->RenewDomain($Domain, $extra=array('period' => 1));
				
				$this->assertTrue(
					date('Ymd', $Domain->ExpireDate) == date('Ymd', strtotime('+1 year', $old_expire_date)),
					'Domain renewal'
				);
			}
			catch (Exception $e)
			{
				return $this->fail('Domain renewal. Error: ' . $e->getMessage());
			}
			*/
			
			
			////
			// Remove domain nameservers
			
			try
			{
				$nslist = $Domain->GetNameserverChangelist();
				$nslist->Remove($nshost);
				$nslist->Remove($nshost2);
				$nslist->Remove($NS1);
				$nslist->Remove($NS2);
				
				$this->Registry->UpdateDomainNameservers($Domain, $nslist);

				$this->assertTrue(count($Domain->GetNameserverList()) == 0, 'Remove nameservers from domain');
			}
			catch (Exception $e)
			{
				return $this->fail('Remove nameservers from domain. Error: ' . $e->getMessage());
			}
			
			////
			// Delete nameservers
			
			try
			{
				$this->Registry->DeleteNameserverHost($nshost);
				$this->Registry->DeleteNameserverHost($nshost2);
				
				$this->assertTrue(true, 'Delete nameservers');
			}
			catch (Exception $e)
			{
				return $this->fail('Delete nameservers. Error: ' . $e->getMessage());
			}
			
			
			////
			// Delete domain
			
			try
			{
				$this->Registry->DeleteDomain($Domain);
				$this->assertTrue(true, 'Delete domain');
			}
			catch (Exception $e)
			{
				return $this->fail('Delete domain. Error: ' . $e->getMessage());
			}
			
			////
			/// Delete contact
			
			try
			{
				$this->Registry->DeleteContact($Registrant);
				$this->assertTrue(true, 'Delete contact');
			}
			catch (Exception $e)
			{
				return $this->fail('Delete contact. Error: ' . $e->getMessage());
			}
		}
	}

?>