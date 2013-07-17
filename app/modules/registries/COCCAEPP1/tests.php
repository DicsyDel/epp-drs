<?php

	class COCCAEPP1RegistryTests extends UnitTestCase 
	{
		/**
		 * @var Registry
		 */
		private $Registry;
		
		private $contact_fields;
		
		function setUp ()
		{
			$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension('in.na');
			
			$this->contact_fields = array(
				'name' => 'Marat Komarov',
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
		
		
		function testEPP ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'webta' . rand(1000, 9999);
			
			// check domain
			try
			{
				$ok = $this->Registry->DomainCanBeRegistered($Domain)->Result;
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
				$ns3 = new NameserverHost('ns3.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
				
				$this->Registry->CreateNameserverHost($ns1);
				$this->Registry->CreateNameserverHost($ns2);
				$this->Registry->CreateNameserverHost($ns3);
				
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
				$nslist->Add($ns3);
				
				
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
			// 5. UPDATE Domain�s status to 
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
			// 10. UPDATE one of the name server�s IP Address
			//
			
			try
			{
				$ns1->IPAddr = gethostbyname('ns.hostdad.com');
				$Resp = $this->Registry->GetModule()->UpdateNameserverHost($ns1);
				
				$this->assertTrue($Resp->Result, 'Update domain nameserver');
			}
			catch (Exception $e)
			{
				return $this->fail('Update domain nameserver. Error: ' . $e->getMessage());
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
			// Remove domain nameservers
			
			try
			{
				$nslist = $Domain->GetNameserverChangelist();
				$nslist->Remove($ns1);
				$nslist->Remove($ns2);
				$nslist->Remove($ns3);
				
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
				$this->Registry->DeleteNameserverHost($ns1);
				$this->Registry->DeleteNameserverHost($ns2);
				
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