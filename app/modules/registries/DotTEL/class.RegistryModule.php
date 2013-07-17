<?php

	class DotTELRegistryModule extends GenericEPPRegistryModule implements IRegistryModuleClientPollable  
	{
		public static function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->AppendField( new DataFormField("IsSunrise",	FORM_FIELD_TYPE::CHECKBOX, "Sunrise mode", 1));
			$ConfigurationForm->AppendField( new DataFormField("RegistrarID", FORM_FIELD_TYPE::TEXT, "Registrar ID", 1));
			$ConfigurationForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
			$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
			$ConfigurationForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$ConfigurationForm->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
			$ConfigurationForm->AppendField( new DataFormField("SSLCertPath", FORM_FIELD_TYPE::TEXT , "Path to SSL certificate", 1));
			$ConfigurationForm->AppendField( new DataFormField("SSLCertPass", FORM_FIELD_TYPE::TEXT , "SSL private key password", 1));
			
			return $ConfigurationForm;
		}
	
		/**
		 * @return DataForm
		 */
		public static function GetTestConfigurationForm () 
		{
			$CF = new DataForm();
			$CF->AppendField(new DataFormField("IsSunrise",		FORM_FIELD_TYPE::CHECKBOX, "Sunrise OTE", 1));
			$CF->AppendField(new DataFormField("BusinessName",	FORM_FIELD_TYPE::TEXT, "Business name", 1));
			$CF->AppendField(new DataFormField("RegistrarID",	FORM_FIELD_TYPE::TEXT, "Registrar ID", 1));
			$CF->AppendField(new DataFormField("ServerHost", 	FORM_FIELD_TYPE::TEXT, "Server host", 1));
			$CF->AppendField(new DataFormField("ServerPort", 	FORM_FIELD_TYPE::TEXT, "Server port", 1));			
			$CF->AppendField(new DataFormField("Login-1", 		FORM_FIELD_TYPE::TEXT, "Login 1", 1));
			$CF->AppendField(new DataFormField("Password-1", 	FORM_FIELD_TYPE::TEXT, "Password 1", 1));
			$CF->AppendField(new DataFormField("Password-1n", 	FORM_FIELD_TYPE::TEXT, "New Password 1", 1));
			$CF->AppendField(new DataFormField("Login-2", 		FORM_FIELD_TYPE::TEXT, "Login 2", 1));
			$CF->AppendField(new DataFormField("Password-2", 	FORM_FIELD_TYPE::TEXT, "Password 2", 1));
			$CF->AppendField(new DataFormField("SSLCertPath", 	FORM_FIELD_TYPE::TEXT, "Path to SSL certificate", 1));
			$CF->AppendField(new DataFormField("SSLCertPass", 	FORM_FIELD_TYPE::TEXT, "SSL private key password", 1));		
			
			return $CF;
		}
		
		public function RunTest ($DF)
		{
			$Runner = new OteTestRunner();
			$Runner->Run(new DotTELRegistryModule_OteTestSuite(), $DF);
		}
		
		public function GetRegistrarID()
		{
			return $this->Config->GetFieldByName("RegistrarID")->Value;
		}		
		
		private function IsSunrise () 
		{
			return (bool)$this->Config->GetFieldByName("IsSunrise")->Value;
		} 
		
		private function WrapUnspec ($unspec)
		{
			return '<extension><neulevel:extension xmlns="urn:ietf:params:xml:ns:neulevel-1.0" '
							. 'xmlns:neulevel="urn:ietf:params:xml:ns:neulevel-1.0" '
							. 'xsi:schemaLocation="urn:ietf:params:xml:ns:neulevel-1.0 neulevel-1.0.xsd">' 
							. '<unspec>'.$unspec.'</unspec></neulevel:extension></extension>';
		}
		
		protected function BeforeRequest ($command, &$params, $method /* args */) 
		{
			if ($command == "domain-check")
			{
				if ($this->IsSunrise()) 
				{
					$unspec = "ApplicationID=" . $this->GetRegistrarID() . "-" . time(); 
					$params["extension"] = $this->WrapUnspec($unspec);
				}
				else
				{
					$params["extension"] = "";
				}
				
			}
			elseif ($command == 'domain-create')
			{
				$Domain = func_get_arg(3);
				$extra = func_get_arg(5);
				foreach (array_keys($extra) as $k)
				{
					if ($Domain->{$k} !== null)
					{
						$extra[$k] = $Domain->{$k};
					}
				}
				
				$unspec = array();
				foreach ($this->Manifest->GetDomainConfig()->registration->extra_fields->field as $field)
				{
					$k = (string) $field->attributes()->name;
					$v = array_key_exists($k, $extra) ? $extra[$k] : $Domain->{$k};
					$unspec[] = "$k=$v"; 
				}
				$params['unspec'] = join(" ", $unspec);
				
				if ($this->IsSunrise())
				{
					$params["ns"] = "";
				}
			}
			elseif ($command == 'contact-create')
			{
				$params['voice_ext'] = substr($params['voice'], 0, strpos($params['voice'], '.'));
			}
			elseif ($command == "domain-update-contact" 
				|| $command == "domain-update-ns"
				|| $command == "domain-update-flags")
			{
				if ($this->IsSunrise())
				{
					$Domain = func_get_arg(3);
					$unspec = "ApplicationID=".$Domain->ApplicationID;
					$params["extension"] = $this->WrapUnspec($unspec);
				}
				else
				{
					$params["extension"] = "";
				} 
			
			}
		}

		public function NameserverCanBeCreated(Nameserver $ns)
		{
			if ($this->IsSunrise())
				throw new NotImplementedException();
			else
				return parent::NameserverCanBeCreated($ns);
		}
		
		public function CreateNameserver (Nameserver $ns)
		{
			if ($this->IsSunrise())
				throw new NotImplementedException();
			else
				return parent::CreateNameserver($ns);	
		}
		
		/**
		 * This method request registry to create namserver host
		 * 
		 * @param NameserverHost $nshost
		 * @return CreateNameserverHostResponse
		 */
		public function CreateNameserverHost (NameserverHost $ns)
		{
			if ($this->IsSunrise())
				throw new NotImplementedException();		
			else
				return parent::CreateNameserverHost($ns);
		}		
		
		public function CreateDomain(Domain $domain, $period, $extra = array())
		{
			$contacts = $domain->GetContactList();
							
			$nameservers = $domain->GetNameserverList();

			$params = array(
				"name"				=> "{$domain->Name}.{$this->Extension}",
				"registrant_id"		=> $contacts['registrant']->CLID,
				"ns1"				=> $nameservers[0]->HostName,
				"ns2"				=> $nameservers[1]->HostName,
				"period"			=> $period,
				"pw"				=> $domain->AuthCode ? $domain->AuthCode : rand(100000000, 999999999)
			);
			
			unset($contacts[CONTACT_TYPE::REGISTRANT]);
			$params['contacts'] = '';
			foreach ($contacts as $contact_type => $contact)
				$params['contacts'] .= '<domain:contact type="'.$contact_type.'">'.$contact->CLID.'</domain:contact>';

			$params['ns'] = count($nameservers) ? '<domain:ns>' . $this->GetNSXML($nameservers) . '</domain:ns>' : '';
			
			$this->BeforeRequest('domain-create', $params, __METHOD__, $domain, $period, $extra);
			$response = $this->Request("domain-create", $params);
		
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
			
			$resp = new CreateDomainResponse($status, $response->ErrMsg, $response->Code);
			
			if ($response->Succeed)
			{
				$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
				$info = $info[0];
	
				$resp->CreateDate = $this->StrToTime((string)$info->crDate[0]); 
				$resp->ExpireDate = $this->StrToTime((string)$info->exDate[0]); 
				
				if ($response->Data->response->extension)
				{
					$extension = $response->Data->response->extension->children("urn:ietf:params:xml:ns:neulevel-1.0");
					$extension = $extension[0];
					$pairs = explode(" ", (string)$extension->unspec);
					$unspec = array();
					foreach ($pairs as $pair)
					{
						list($key, $value) = explode("=", $pair);
						$unspec[$key] = $value;
					}
					
					// Set unspec extra fields
					foreach ($unspec as $key => $value)
					{
						$domain->SetExtraField($key, $value);
					}
				}
				
				$resp->AuthCode = (string)$params["pw"];
			}
			
			return $resp;
		}		
		
	    public function DomainCanBeTransferred(Domain $domain)
	    {
	    	$resp = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	
	    	try
	    	{
	    		$domainInfo = $this->Request('domain-info', array(
	    			'name' => $domain->GetHostName(),
	    			'authinfo' => ''
	    		));
	    		
	    		$infData = $domainInfo->Data->response->resData->children('urn:ietf:params:xml:ns:domain-1.0');
	    		$infData = $infData[0];
	    		
	    		// No authInfo means that domain belongs to another registrant account.
				$resp->Result = $infData->authInfo->getName() == '';
	    	}
	    	catch(ObjectNotExistsException $e)
	    	{
	    		$resp->Result = true;
	    	}
	    	
	    	return $resp;
	    }		
		
	    
	    
		/**
		 * @param Domain $domain
		 * @return PollTransferResponse 
		 */
		public function PollTransfer (Domain $domain)
		{
			$RDResponse = $this->GetRemoteDomain($domain);
			
			if ($RDResponse->Succeed())
			{
				if ($RDResponse->AuthCode != '')
					$tstatus = TRANSFER_STATUS::APPROVED;
				else if ($RDResponse->RegistryStatus == 'pendingTransfer')
					$tstatus = TRANSFER_STATUS::PENDING;
				else
					$tstatus = TRANSFER_STATUS::DECLINED;
					
				$status = $tstatus != TRANSFER_STATUS::PENDING ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::PENDING;
				
				$resp = new PollTransferResponse($status, $RDResponse->ErrMsg, $RDResponse->Code); 
				$resp->HostName = $domain->GetHostName();
				$resp->TransferStatus = $tstatus;
				if ($tstatus == TRANSFER_STATUS::DECLINED)
				{
					$resp->FailReason = _("Transfer was rejected by the current domain owner.");
				} 				
				
				return $resp;
			}
			else
				return new PollTransferResponse(REGISTRY_RESPONSE_STATUS::FAILED, $RDResponse->ErrMsg, $RDResponse->Code);
		}

			/**
		 * @param Domain $domain
		 * @return DomainCreatedResponse
		 */
		public function PollCreateDomain (Domain $domain)
		{
			try
			{
				$RDResponse = $this->GetRemoteDomain($domain);
				
				if ($RDResponse->Succeed())
				{
					$rs = $RDResponse->RegistryStatus;
					
					$status = $rs == 'ok' || $rs != 'pendingCreate' ? 
						REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::PENDING;
					$resp = new PollCreateDomainResponse($status);
					$resp->HostName = $domain->GetHostName();
					
					if ($rs == 'ok')
					{
						$resp->ExpireDate = $domain->ExpireDate;
						$resp->Result = true;
					}
					else if ($rs != 'pendingCreate')
					{
						$resp->Result = false;
					}
						
					return $resp;
				}
				else
				{
					return new PollCreateDomainResponse(
						REGISTRY_RESPONSE_STATUS::FAILED, 
						$RDResponse->ErrMsg, 
						$RDResponse->Code
					);
				}
			}
			catch (ObjectNotExistsException $e)
			{
				$resp = new PollCreateDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$resp->HostName = $domain->GetHostName();
				$resp->Result = false;
				$resp->FailReason = _("Domain registration declined by registry");
				return $resp;
			}
			
			return;
		}
	
		/**
		 * @param Domain $domain
		 * @return PollDeleteDomainResponse
		 */
		public function PollDeleteDomain (Domain $domain)
		{
			// Not used
			return;
		}
	
		/**
		 * @param Domain $domain
		 * @return PollChangeDomainOwner
		 */
		public function PollChangeDomainOwner (Domain $domain)
		{
			return;
		}
	
		/**
		 * @param Domain $domain
		 * @return DomainUpdatedResponse
		 */
		public function PollUpdateDomain (Domain $domain)
		{
			// Not used
			return;
		}
	
		/**
		 * Called by system when delete contact operation is pending
		 *
		 * @param Contact $contact
		 * @return PollDeleteContactResponse
		 */
		public function PollDeleteContact (Contact $contact)
		{
			// Not used
			return;
		}
	
		/**
		 * Called by system when delete namserver host operation is pending
		 *
		 * @param NamserverHost $nshost
		 * @return PollDeleteNamserverHostResponse
		 */
		public function PollDeleteNamserverHost (NamserverHost $nshost)
		{
			// Not used
			return;
		}		
		
	}
	
	class DotTELRegistryModule_OteTestSuite extends OteTestSuite 
	{
		public function GetName ()
		{
			return "DotTEL";
		}
		
		private $module_conf, $module_conf2, $test_conf;
		
		/**
		 * @var DotTELRegistryModule
		 */
		private $Module, $Module2;
		
		/**
		 * @var Registry
		 */
		private $Registry, $Registry2;
		
		private $is_sunrise;
		
		private $Prefix;
		
		private $cur_exp_date, $create_date, $admin_clid;
		
		public function SetUp (DataForm $TestConf)
		{
			// User input and initialized by EPP-DRS in real test
			// TODO: remove in production
			if (0)
			{
				$TestConf = DotTELRegistryModule::GetTestConfigurationForm();
				$test_conf = $TestConf->ListFields();
				$test_conf['ServerHost']->Value  = '192.168.1.200';
				$test_conf['ServerPort']->Value  = '10236';
				$test_conf['Login-1']->Value 	 = '1000500090';
				$test_conf['Password-1']->Value  = 'qwerty78';
				$test_conf['Login-2']->Value 	 = 'eppadmin2';
				$test_conf['Password-2']->Value  = '123457';
				$test_conf['SSLCertPath']->Value = '/home/marat/cert/tel/certchain.pem';
				$test_conf['SSLCertPass']->Value = '';
			}
			else
			{
				$test_conf = $TestConf->ListFields();
			}
			// 
			$this->test_conf = $test_conf;
			
			
			// Initialize registry
			$ModuleConf = DotTELRegistryModule::GetConfigurationForm();
			$module_conf = $ModuleConf->ListFields();
			$module_conf['RegistrarID']->Value	= $test_conf['RegistrarID']->Value;
			$module_conf['ServerHost']->Value 	= $test_conf['ServerHost']->Value;
			$module_conf['ServerPort']->Value 	= $test_conf['ServerPort']->Value;
			$module_conf['Login']->Value 		= $test_conf['Login-1']->Value;
			$module_conf['Password']->Value 	= $test_conf['Password-1']->Value;
			$module_conf['SSLCertPath']->Value 	= $test_conf['SSLCertPath']->Value;
			$module_conf['SSLCertPass']->Value 	= $test_conf['SSLCertPass']->Value;
			
			$ModuleConf2 = DotTELRegistryModule::GetConfigurationForm();
			$module_conf2 = $ModuleConf2->ListFields();
			$module_conf2['RegistrarID']->Value	= $test_conf['RegistrarID']->Value;
			$module_conf2['ServerHost']->Value 	= $test_conf['ServerHost']->Value;
			$module_conf2['ServerPort']->Value 	= $test_conf['ServerPort']->Value;
			$module_conf2['Login']->Value 		= $test_conf['Login-2']->Value;
			$module_conf2['Password']->Value 	= $test_conf['Password-2']->Value;
			$module_conf2['SSLCertPath']->Value = $test_conf['SSLCertPath']->Value;
			$module_conf2['SSLCertPass']->Value = $test_conf['SSLCertPass']->Value;
			
	        $this->Module = new DotTELRegistryModule(new RegistryManifest(MODULES_PATH . "/registries/DotTEL/module.xml"));
	        $this->Module2 = new DotTELRegistryModule(new RegistryManifest(MODULES_PATH . "/registries/DotTEL/module.xml"));
	        $this->Module->InitializeModule("tel", $ModuleConf);
	        $this->Module2->InitializeModule("tel", $ModuleConf2);
			$this->Registry = new Registry($this->Module);
			$this->Registry2 = new Registry($this->Module2);
			$this->module_conf = $module_conf;
			$this->module_conf2 = $module_conf2;
			
			$this->is_sunrise = $test_conf['IsSunrise']->Value;
			
	        // Read test preferences
			$prefs = parse_ini_file(MODULES_PATH . '/registries/DotTEL/testprefs');
			$prefs['num']++;
	        
			$this->Prefix = substr($test_conf["BusinessName"]->Value, 0, 6) . sprintf('%02d', $prefs['num']);
			
			// Save prefs to the next run
			$prefs_file = '';
			foreach ($prefs as $k => $v)
			{
				$prefs_file .= "{$k}={$v}\n";
			}
			file_put_contents(MODULES_PATH . '/registries/DotTEL/testprefs', $prefs_file);
		}
	
		public function Run ()
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
        	
        	if ($this->is_sunrise)
        	{
        		// 9. create domain
        		$this->createSunriseDomain();
        		// 10. <info> query domain
        		$this->infoQuerySunriseDomain();
        	}
        	else
        	{
	        	// 9. transfer contact request
	        	$this->transferContactRequest();
	        	// 10. query contact transfer status
	        	$this->queryContactTransferStatus();
	        	// 11. cancel contact transfer
	        	$this->cancelContactTransfer();
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
	        	// 17. create in-zone host
	        	$this->createNameserverHost1();
	        	// 18. create in-zone 2 host
	        	$this->createNameserverHost2();
	        	// 19. check host (host known)
	        	$this->checkExistedHost();
	        	// 20. check host (host unknown)
	        	$this->checkUnexistedHost();        	
	        	// 21. <info> query host
	        	$this->infoQueryHost();
	        	/*
	        	// 22. update host (remove IP address)
	        	$this->updateHostRemIP();
	        	// 23. update host
	        	$this->updateHost();
	        	// 24. delete host
	        	$this->deleteHost();
				*/
	        	
	        	// Domain tests
				
	        	// 22. create domain without nameservers and contacts
	        	$this->createDomainWithoutAll();
	        	// 23. create domain with nameservers
	        	$this->createDomainWithNameserverHosts();
	        	// 24. create domain with all required
	        	$this->createDomainWithAllRequired();
	        	// 25. create domain with maximum registration period
	        	$this->createDomainWithMaxPeriod();
	        	// 26. create domain with invalid name
	        	$this->createDomainWithInvalidName();
	        	// 27. check domain (domain not available)
	        	$this->checkUnavailableDomain();
	        	// 28. check domain (domain available)
	        	$this->checkAvailableDomain();
	        	// 29. <info> query domain
	        	$this->infoQueryDomain();
	        	// 30. delete domain
	        	$this->deleteDomain();
	        	// 31. renew domain
	        	$this->renewDomain();
	        	// 32. renew domain to max registration period
	        	$this->renewDomainToMaxPeriod();
	        	// 33. update domain nameservers
	        	$this->updateDomainNameservers();
	        	// 34. update domain contact
	        	$this->updateDomainContact();
	        	// 35. transfer domain request
	        	$this->transferDomainRequest();
	        	// 36. update domain status
	        	$this->updateDomainStatus();
        	}
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
        	$Resp = $this->Module->GetTransport()->Request('logout');
        	$this->Module->GetTransport()->Connect();
        	
        	$newPW = $this->test_conf['Password-1n']->Value;
        	
        	$Resp = $this->Module->GetTransport()->Request('login-ex', array(
        		'clid' 	=> $this->module_conf['Login']->Value,
        		'pw'	=> $this->module_conf['Password']->Value,
        		'newPw'	=> $newPW,
        		'clTRID'=> $this->Prefix . 'ote-testcase03cmd'
        	));
        	$this->assertTrue($Resp->Code == 1000, "change password");
        	
        	$this->module_conf['Password']->Value = $newPW; 
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
        		'pw' => 'mysecret',
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
        		strtolower((string)$trnData[0]->id) == $this->Prefix . 'cont1' &&
        		(string)$trnData[0]->trStatus == 'pending',
        		"query contact transfer status"
        	);
        }
        
        function cancelContactTransfer ()
        {
        	$title = "cancel contact transfer";
        	
			try {
	        	$Resp = $this->Module2->Request('contact-trans-reject', array(
					'id' => $this->Prefix . 'cont1',
					'clTRID' => $this->Prefix . 'ote-testcase11cmd'
				));
	        	$this->assertTrue(in_array($Resp->Code, array(1000, 1001)), $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
        }
        
        function updateContactChgElement ()
        {
        	$title = "update contact (change element)";
        	
        	try {
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
	        		'pw' 		=> 'newsecret',
	        		'clTRID'	=> $this->Prefix . 'ote-testcase12cmd'
	        	);
	        	$Resp = $this->Module->Request('test-contact-update-chg', $contact_data);
	        	
	        	$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, $title);
        	} catch (RegistryException $e) {
        		$this->fail($title);
        	}
        }
        
        function updateContactRemElement ()
        {
        	$title = "update contact (remove element)";
        	
        	try {
	        	$Resp = $this->Module->Request('test-contact-update-rem', array(
	        		'id' => $this->Prefix . 'cont1',
	        		'clTRID'	=> $this->Prefix . 'ote-testcase13cmd'
	        	));
	        	
	        	$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, $title);
        	} catch (RegistryException $e) {
        		$this->fail($title);
        	}
        }
        
        function deleteContact ()
        {
        	$title = "delete contact";
        	
        	try {
	        	$Resp = $this->Module->Request('contact-delete', array(
	        		'id' => $this->Prefix . 'cont1',
	        		'clTRID'	=> $this->Prefix . 'ote-testcase14cmd'
	        	));
	        	
	        	$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, $title);
        	} catch (RegistryException $e) {
        		$this->fail($title);
        	}
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
				'unspec' => 'WhoisType=LEGAL TrademarkName=Hertz TrademarkCountry=GB RegistrationNumber=56482 ApplicantCapacity=Owner',
				'clTRID' => $this->Prefix . 'ote-testcase16cmd'
        	);
        	
        	$Resp = $this->Module->Request('domain-create', $domain_data);
        	$this->assertTrue($Resp->Code == 1001, "create domain");
        }
        
        function createNameserverHost1 ()
        {
			$Resp = $this->Module->Request('host-create', array(
				'name' => "ns1.{$this->Prefix}inzone.tel",
				'addr' => '<host:addr ip="v4">209.1.2.3</host:addr>' 
        			. '<host:addr ip="v4">209.1.2.4</host:addr>',
				'clTRID' => $this->Prefix . 'ote-testcase17cmd'
			));
			
			$this->assertTrue($Resp->Code == 1000, "create in-zone host");
        }
        
        function createNameserverHost2 ()
        {
        	$Resp = $this->Module->Request('host-create', array(
        		'name' => "ns2.{$this->Prefix}inzone.tel",
        		'addr' => '<host:addr ip="v4">209.1.2.5</host:addr>' 
        			. '<host:addr ip="v4">209.1.2.6</host:addr>',
        		'clTRID' => $this->Prefix . 'ote-testcase18cmd'
        	));
			$this->assertTrue($Resp->Code == 1000, "create in-zone host 2");        	
        }
        
        function checkExistedHost ()
        {
        	$Resp = $this->Module->Request('host-check', array(
        		'name' => "ns1.{$this->Prefix}inzone.tel",
        		'clTRID' => $this->Prefix . 'ote-testcase19cmd'
        	));
        	
			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:host-1.0");
			$attr = $chkData ? $chkData[0]->cd[0]->name[0]->attributes() : null;
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				$attr && (string)$attr['avail'] == 0,
				"check host (host known)"
			);
        }
        
        function checkUnexistedHost ()
        {
        	$Resp = $this->Module->Request('host-check', array(
        		'name' => "ns1.{$this->Prefix}abc.tel",
        		'clTRID' => $this->Prefix . 'ote-testcase20cmd'
        	));
        	
			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:host-1.0");
			$attr = $chkData ? $chkData[0]->cd[0]->name[0]->attributes() : null;
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				$attr && (string)$attr['avail'] == 1,
				"check host (host unknown)"
			);
        }
        
        function infoQueryHost ()
        {
        	try {
	        	$Resp = $this->Module->Request('host-info', array(
	        		'hostname' => "ns1.{$this->Prefix}inzone.tel",
	        		'clTRID' => $this->Prefix . 'ote-testcase21cmd'
	        	));
	        	$this->assertTrue($Resp->Code == 1000, "<info> query host");
        	} catch (ObjectNotExistsException $e) {
        		$this->fail("<info> query host. " . $e->getMessage());
        	}
        }
        
        /*
		function updateHostRemIP ()
		{
			$Resp = $this->Module->Request('test-host-update', array(
        		'hostname' => "ns1.{$this->Prefix}inzone.tel",
				'add' => '',
				'rem' => '<host:rem><host:addr ip="v4">209.1.2.4</host:addr></host:rem>',
        		'clTRID' => $this->Prefix . 'ote-testcase22cmd'
        	));
        	
        	$this->assertTrue($Resp->Code == 1000, "update host (remove IP address)");
		}
		
		function updateHost ()
		{
			$Resp = $this->Module->Request('test-host-update', array(
        		'hostname' => "ns1.{$this->Prefix}inzone.tel",
				'add' => '<host:add><host:status s="clientUpdateProhibited"/></host:add>',
				'rem' => '',
        		'clTRID' => $this->Prefix . 'ote-testcase23cmd'
        	));
        	
        	$this->assertTrue($Resp->Code == 1000, "update host");
		}
		
		function deleteHost ()
		{
			$Resp = $this->Module->Request('host-delete', array(
        		'hostname' => "ns1.{$this->Prefix}inzone.tel",
        		'clTRID' => $this->Prefix . 'ote-testcase24cmd'
        	));
        	
        	$this->assertTrue($Resp->Code == 1000, "delete host");
		}
		*/
        
		
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
				'clTRID' => $this->Prefix . 'ote-testcase22cmd'
			));
			
			$this->assertTrue(in_array($Resp->Code, array(/*2003, */2306)), "create domain without nameservers and contacts");
		}
		
		function createDomainWithNameserverHosts ()
		{
			$Resp = $this->Module->Request('domain-create', array(
				'name' =>  $this->Prefix . 'domain1.tel',
        		'period' => 2,
        		'registrant_id' => $this->Prefix . 'cont2',
        		'contacts' => '',
				'ns' => '<domain:ns>'
					. '<domain:hostObj>ns1.'.$this->Prefix.'inzone.tel</domain:hostObj>'
					. '<domain:hostObj>ns2.'.$this->Prefix.'inzone.tel</domain:hostObj>'
					. '</domain:ns>',
				'pw' => 'mysecret',
				'unspec' => 'WhoisType=LEGAL',
				'clTRID' => $this->Prefix . 'ote-testcase23cmd'
			));

			$this->assertTrue(in_array($Resp->Code, array(/*2003, */2306)), "create domain with nameservers");
		}
		
		function createDomainWithAllRequired ()
		{
			try	{
				$title = "create domain with all required";
				$contact_clid = $this->Prefix . 'cont2';
				
				$Resp = $this->Module->Request('domain-create', array(
					'name' =>  $this->Prefix . 'domain1.tel',
	        		'period' => 2,
	        		'registrant_id' => $contact_clid,
	        		'contacts' => '<domain:contact type="admin">'.$contact_clid.'</domain:contact>' 
	        			. '<domain:contact type="tech">'.$contact_clid.'</domain:contact>'
						. '<domain:contact type="billing">'.$contact_clid.'</domain:contact>',
					'ns' => '<domain:ns>'
						. '<domain:hostObj>ns1.'.$this->Prefix.'inzone.tel</domain:hostObj>'
						. '<domain:hostObj>ns2.'.$this->Prefix.'inzone.tel</domain:hostObj>'
						. '</domain:ns>',
					'pw' => 'mysecret',
					'unspec' => 'WhoisType=LEGAL TrademarkName=Hertz TrademarkCountry=GB RegistrationNumber=56482 ApplicantCapacity=Owner',
					'clTRID' => $this->Prefix . 'ote-testcase24cmd'
				));
	
				$this->assertTrue(in_array($Resp->Code, array(1000, 1001)), $title);
			} catch (RegistryException $e) {
				$this->Fail($title);
			}
		}
		
		function createDomainWithMaxPeriod ()
		{
			try {
				$title = "create domain with maximum registration period";
				$contact_clid = $this->Prefix . 'cont2';
				
				$Resp = $this->Module->Request('domain-create', array(
					'name' =>  $this->Prefix . 'domain2.tel',
	        		'period' => 10,
	        		'registrant_id' => $contact_clid,
	        		'contacts' => '<domain:contact type="admin">'.$contact_clid.'</domain:contact>' 
	        			. '<domain:contact type="tech">'.$contact_clid.'</domain:contact>'
						. '<domain:contact type="billing">'.$contact_clid.'</domain:contact>',
					'ns' => '<domain:ns>'
						. '<domain:hostObj>ns1.'.$this->Prefix.'inzone.tel</domain:hostObj>'
						. '<domain:hostObj>ns2.'.$this->Prefix.'inzone.tel</domain:hostObj>'
						. '</domain:ns>',
					'pw' => 'mysecret',
					'unspec' => 'WhoisType=LEGAL TrademarkName=Hertz TrademarkCountry=GB RegistrationNumber=56482 ApplicantCapacity=Owner',
					'clTRID' => $this->Prefix . 'ote-testcase25cmd'
				));
	
				$this->assertTrue($Resp->Code == 1001, $title);

				
				// Create domain3.tld, because tests operate with it but not creates it anywhere.
				/*

	        	$Resp = $this->Module->Request('host-create', array(
	        		'name' => "ns1.{$this->Prefix}inzone.tel",
	        		'addr' => '<host:addr ip="v4">209.1.2.7</host:addr>',
	        		'clTRID' => $this->Prefix . 'ote-testcase28cmd-1'
	        	));	

	        	$Resp = $this->Module->Request('host-create', array(
	        		'name' => "ns3.{$this->Prefix}inzone.tel",
	        		'addr' => '<host:addr ip="v4">209.1.2.10</host:addr>',
	        		'clTRID' => $this->Prefix . 'ote-testcase28cmd-2'
	        	));		
	
	        	$Resp = $this->Module->Request('host-create', array(
	        		'name' => "ns4.{$this->Prefix}inzone.tel",
	        		'addr' => '<host:addr ip="v4">209.1.2.10</host:addr>',
	        		'clTRID' => $this->Prefix . 'ote-testcase28cmd-2'
	        	));		        	
				
				$Resp = $this->Module->Request('domain-create', array(
					'name' =>  $this->Prefix . 'domain3.tel',
	        		'period' => 1,
	        		'registrant_id' => $contact_clid,
	        		'contacts' => '<domain:contact type="admin">'.$contact_clid.'</domain:contact>' 
	        			. '<domain:contact type="tech">'.$contact_clid.'</domain:contact>'
						. '<domain:contact type="billing">'.$contact_clid.'</domain:contact>',
					'ns' => '<domain:ns>'
						. '<domain:hostObj>ns2.'.$this->Prefix.'inzone.tel</domain:hostObj>'						
						. '<domain:hostObj>ns3.'.$this->Prefix.'inzone.tel</domain:hostObj>'
						. '<domain:hostObj>ns4.'.$this->Prefix.'inzone.tel</domain:hostObj>'						
						. '</domain:ns>',
					'pw' => 'mysecret',
					'unspec' => 'WhoisType=LEGAL TrademarkName=Hertz TrademarkCountry=GB RegistrationNumber=56482 ApplicantCapacity=Owner',
					'clTRID' => $this->Prefix . 'ote-testcase28cmd-3'
				));
				*/
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
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
					. '<domain:hostObj>ns1.'.$this->Prefix.'inzone.tel</domain:hostObj>'
					. '<domain:hostObj>ns2.'.$this->Prefix.'inzone.tel</domain:hostObj>'
					. '</domain:ns>',
				'pw' => 'mysecret',
				'unspec' => 'WhoisType=LEGAL TrademarkName=Hertz TrademarkCountry=GB RegistrationNumber=56482 ApplicantCapacity=Owner',
				'clTRID' => $this->Prefix . 'ote-testcase26cmd'
			));

			$this->assertTrue($Resp->Code == 2005, "create domain with invalid name");
		}
		
		function checkUnavailableDomain ()
		{
			$Resp = $this->Module->Request('test-domain-check', array(
				'names' => '<domain:name>'.$this->Prefix.'domain1.tel</domain:name>'
					. '<domain:name>'.$this->Prefix.'domain2.tel</domain:name>',
				'clTRID' => $this->Prefix . 'ote-testcase27cmd'
			));
			
			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				$chkData && 
				$chkData[0]->cd[0]->name[0]->attributes()->avail == '0' &&
				$chkData[0]->cd[1]->name[0]->attributes()->avail == '0', 
				"check domain (domain not available)"
			);
		}
		
		function checkAvailableDomain ()
		{
			$Resp = $this->Module->Request('test-domain-check', array(
				'names' => '<domain:name>'.$this->Prefix.'domain97.tel</domain:name>'
					. '<domain:name>'.$this->Prefix.'domain98.tel</domain:name>'
					. '<domain:name>'.$this->Prefix.'domain99.tel</domain:name>',
				'clTRID' => $this->Prefix . 'ote-testcase28cmd'
			));
			
			$chkData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
			
			$this->assertTrue(
				$Resp->Code == 1000 &&
				$chkData && 
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
					'clTRID' => $this->Prefix . 'ote-testcase29cmd'
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
				'clTRID' => $this->Prefix . 'ote-testcase30cmd'
			));
			
			$this->assertTrue($Resp->Code == 1000, "delete domain");
			
		}
		
		function renewDomain ()
		{
			// First, get the Expiration Date of the Domain from the results of the query <info>
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = $this->Prefix . 'domain1';
			$Domain->Extension = 'tel';						
			try {
				$Resp = $this->Module->GetRemoteDomain($Domain);
				$this->cur_exp_date = $Resp->ExpireDate;
				$this->create_date = $Resp->CreateDate;
				$this->admin_clid = $Resp->AdminContact;
			} catch (ObjectNotExistsException $e) {
				$this->fail("renew domain. " . $e->getMessage());
				return;
			}
			
			$Resp = $this->Module->Request('domain-renew', array(
				'name' => $Domain->GetHostName(),
				'exDate' => date('Y-m-d', $this->cur_exp_date),
				'period' => 6,
				'clTRID' => $this->Prefix . 'ote-testcase31cmd'
			));
			$this->assertTrue($Resp->Code == 1000, "renew domain");
			
			$this->cur_exp_date = strtotime('+6 year', $this->cur_exp_date);
		}
		
		function renewDomainToMaxPeriod ()
		{
			// Max registration period = 10
			$period = 10 - ((int)date('Y', $this->cur_exp_date) - (int)date('Y', $this->create_date)); 
			
			$Resp = $this->Module->Request('domain-renew', array(
				'name' => $this->Prefix . 'domain1.tel',
				'exDate' => date('Y-m-d', $this->cur_exp_date),
				'period' => $period,
				'clTRID' => $this->Prefix . 'ote-testcase32cmd'
			));
			$this->assertTrue($Resp->Code == 1000, "renew domain to max registration period");
		}
	
		function updateDomainNameservers ()
		{
			$title = "update domain nameservers";
			
			try {
				$Resp = $this->Module->Request('domain-update-ns', array(
					'name' => $this->Prefix . 'domain1.tel',
					'add' => '',
					'del' => '<domain:rem><domain:ns>'
						. '<domain:hostObj>ns1.'.$this->Prefix.'inzone.tel</domain:hostObj>'
						. '</domain:ns></domain:rem>',
					'clTRID' => $this->Prefix . 'ote-testcase33cmd'
				));
				
				$this->assertTrue($Resp->Code == 1000, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function updateDomainContact ()
		{
			$title = "update domain contact";
			
			try {
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
					'name' => $this->Prefix . 'domain1.tel',
					'add' =>  '<domain:add><domain:contact type="admin">'.$this->Prefix.'cont3</domain:contact></domain:add>',
					'rem' =>  '<domain:rem><domain:contact type="admin">'.$this->admin_clid.'</domain:contact></domain:rem>',
					'change' => '',
					'clTRID' => $this->Prefix . 'ote-testcase34cmd'
				));
				$this->assertTrue($Resp->Code == 1000, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function transferDomainRequest ()
		{
			$title = "transfer a domain";
			
			try {
				$Resp = $this->Module2->Request('test-domain-trans-request', array(
					'name' => $this->Prefix . 'domain1.tel',
					'period' => 1,
					'pw' => 'mysecret',
					'clTRID' => $this->Prefix . 'ote-testcase35cmd'
				));
				$this->assertTrue($Resp->Code == 1001, $title);
			} catch (ProhibitedTransformException $e) {
				return $this->pass($title);
			} catch (RegistryException $e) {
				$this->fail($title);	
			}
		}
		
		/*
		function queryDomainTransferStatus ()
		{
        	$title = "query domain transfer status";
        	
        	try {
				$Resp = $this->Module2->Request('domain-trans-query', array(
					'name' => $this->Prefix . 'domain1.tel',
					'clTRID' => $this->Prefix . 'ote-testcase37cmd'
	        	));
	
	        	$trnData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
	        	
	        	$this->assertTrue(
	        		$trnData &&
	        		(string)$trnData[0]->trStatus == 'pending',
	        		$title
	        	);
        	} catch (RegistryException $e) {
        		$this->fail($title);
        	}			
		}
		*/
		
		function updateDomainStatus ()
		{
			$title = "update domain status";
			
			try {
				$Resp = $this->Module->Request('domain-update-flags', array(
					'name' => $this->Prefix . 'domain1.tel',
					'add' => '<domain:add><domain:status s="clientTransferProhibited"/></domain:add>',
					'rem' => '',
					'clTRID' => $this->Prefix . 'ote-testcase36cmd'
				));
				$this->assertTrue($Resp->Code == 1000, $title);
			} catch (ProhibitedTransformException $e) {
				return $this->pass($title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		
		function createSunriseDomain ()
		{
        	$contact_clid = $this->Prefix . 'cont1';
        	
        	$domain_data = array(
				'name' =>  $this->Prefix . 'example.tel',
        		'period' => 3,
        		'registrant_id' => $contact_clid,
        		'contacts' => '<domain:contact type="admin">'.$contact_clid.'</domain:contact>' 
        			. '<domain:contact type="tech">'.$contact_clid.'</domain:contact>'
					. '<domain:contact type="billing">'.$contact_clid.'</domain:contact>',
				'ns' => '',
				'pw' => 'mysecret',
				'unspec' => join(' ', array(
					'WhoisType=LEGAL',
					'Publish=Y',
					'TrademarkName=Trademark',
					'TrademarkCountry=US',
					'RegistrationNumber=1000',
					'ApplicantCapacity=Licensee'
				)),
				'clTRID' => $this->Prefix . 'ote-testcase9cmd'
        	);
        	
        	$Resp = $this->Module->Request('domain-create', $domain_data);
        	
        	$neulevel = $Resp->Data->response->extension->children('urn:ietf:params:xml:ns:neulevel-1.0');
        	$unspec_pairs = explode(' ', $neulevel[0]->unspec);
        	$unspec = array();
        	foreach ($unspec_pairs as $unspec_pair)
        	{
        		list($key, $value) = explode('=', $unspec_pair, 2);
        		$unspec[$key] = $value;
        	}
        	$this->sunriceDomainApplicationId = $unspec['ApplicationID'];
        	
        	$this->assertTrue(in_array($Resp->Code, array(1000, 1001)), "create domain");
		}
		
		function infoQuerySunriseDomain ()
		{
			try {
				$Resp = $this->Module->Request('domain-info', array(
					'name' => $this->Prefix . 'example.tel',
					'authinfo' => "<domain:authInfo><domain:pw>mysecret</domain:pw></domain:authInfo>",
					'clTRID' => $this->Prefix . 'ote-testcase10cmd'
				));
				$this->assertTrue($Resp->Code == 1000, "<info> query domain");
			} catch (ObjectNotExistsException $e) {
				$this->fail("<info> query domain. " . $e->getMessage());
			}
		}
	}
?>