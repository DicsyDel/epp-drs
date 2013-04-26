<?

		
	class DotNORegistryModule extends GenericEPPRegistryModule implements IRegistryModuleServerPollable 
	{
		public function GetTestConfigurationForm ()
		{
			$CF = new DataForm();
			$CF->AppendField(new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$CF->AppendField(new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
			$CF->AppendField(new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1, null, null, null, 'Your OT&E1 account'));
			$CF->AppendField(new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
			$CF->AppendField(new DataFormField("Login2", FORM_FIELD_TYPE::TEXT, "Login 2", 1, null, null, null, 'Your OT&E2 account'));
			$CF->AppendField(new DataFormField("Password2", FORM_FIELD_TYPE::TEXT, "Password 2", 1));
			$CF->AppendField(new DataFormField("RealEmail", FORM_FIELD_TYPE::TEXT, "Real email address", 1));
			$CF->AppendField(new DataFormField(
				"Domain1", FORM_FIELD_TYPE::TEXT, "Test domain 1", 1, array(), null, 
				'epp-drs-test-' . rand(1000, 9999) . '.no'
			));
			$CF->AppendField(new DataFormField(
				"Domain2", FORM_FIELD_TYPE::TEXT, "Test domain 2", 1, array(), null, 
				'epp-drs-test-' . rand(1000, 9999) . '.no'
			));
			$CF->AppendField(new DataFormField("NS1", FORM_FIELD_TYPE::TEXT, "Nameserver 1", 1));
			$CF->AppendField(new DataFormField("NS2", FORM_FIELD_TYPE::TEXT, "Nameserver 2", 1));
	
		
			return $CF;
		}

		public function RunTest ($DF)
		{
			$Runner = new OteTestRunner();
			$Runner->Run(new DotNORegistryModule_OteTestSuite(), $DF);
		}		
		
		
		protected function BeforeRequest($command, &$data, $method /* args */)
		{
			switch ($command)
			{
				case "contact-create":
					$Contact = func_get_arg(3);
					$data["no-ext-type"] = $Contact->GroupName == "registrant" ? "organization" 
							: ($Contact->GroupName == "role" ? "role" : "person");
					$data['no-ext-role-contact'] = $data['no-ext-role-contact'] ?
						"<no-ext-contact:roleContact>{$data['no-ext-role-contact']}</no-ext-contact:roleContact>" : '';
					
				case "contact-update":
					$data["no-ext-organization"] = $data["no-ext-organization"] ? 
						"<no-ext-contact:organization>{$data["no-ext-organization"]}</no-ext-contact:organization>"	: 
						"";

					$data["no-ext-identity"] = $data["no-ext-identity"] ?
						"<no-ext-contact:identity type=\"{$data["no-ext-identity-type"]}\">"
						. "{$data["no-ext-identity"]}"
						. "</no-ext-contact:identity>" : 
						"";
					
					if ($command == "contact-update")
					{
						$Contact = func_get_arg(3);
						$ContactBefore = DBContact::GetInstance()->GetInitialState($Contact);
						$fields = $Contact->GetFieldList();
						$fields_before = $ContactBefore->GetFieldList();
						
						// If email was changed, add command parameters
						$data["no-ext-add"] = array();
						$data["no-ext-rem"] = array();
						if ($fields["email"] != $fields_before["email"])
						{
							$data["no-ext-add"][] = "<no-ext-contact:email>{$fields["email"]}</no-ext-contact:email>";
							$data["no-ext-rem"][] = "<no-ext-contact:email>{$fields_before["email"]}</no-ext-contact:email>";
						}
						//if ($fields["no-ext-organization"] != $fields_before["no-ext-organization"])
						//{

						//	$data["no-ext-add"][] = "<no-ext-contact:organization>{$fields["no-ext-organization"]}</no-ext-contact:organization>";
							// Sly walkaround.
							// NORID требует наличия связи между админ контактом и регистрантом.
							// admin 1 <-> n registrant
							// Если удалять старую связь, то не будет возможности изменить регистранта у домена.   
							
							//$data["no-ext-rem"][] = "<no-ext-contact:organization>{$fields_before["no-ext-organization"]}</no-ext-contact:organization>";
						// }
						
						$data["no-ext-add"] = $data["no-ext-add"] ? "<no-ext-contact:add>".join("", $data["no-ext-add"])."</no-ext-contact:add>" : "";
						$data["no-ext-rem"] = $data["no-ext-rem"] ? "<no-ext-contact:rem>".join("", $data["no-ext-rem"])."</no-ext-contact:rem>" : "";  
					}
				
					break;
					
				case "contact-update-disclose":
					$Contact = func_get_arg(3);
					$discloses = $Contact->GetDiscloseList();
					$data["no-ext-disclose"] = "<no-ext-contact:chg>"
							. "<no-ext-contact:disclose flag=\"{$discloses["voice"]}\">"
								. "<no-ext-contact:mobilePhone />"
							. "</no-ext-contact:disclose>"
						. "</no-ext-contact:chg>";
						
				case "host-update":
					if (!key_exists('extension', $data)) {
						$data['extension'] = '';
					}
					break;
			}
		}
		
		private function AddRegistrantAssociation ($clid, $org_clid) 
		{
			// Add admin -> registrant association
			$this->Request("contact-add-org", array(
				"id" => $clid,
				"org" => $org_clid
			));
		}
		
		public function DomainCanBeTransferred(Domain $domain)
	    {
	    	$resp = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	
	    	try
	    	{
	    		$Grd = $this->GetRemoteDomain($domain);
	    		$resp->Result = $Grd->CLID != $this->GetRegistrarID();
	    	}
	    	catch(ObjectNotExistsException $e)
	    	{
	    		$resp->Result = true;
	    	}
	    	
	    	return $resp;
	    }		
		
		public function UpdateDomainContact(Domain $domain, $contactType, Contact $oldContact, Contact $newContact)
		{
			if ($contactType == CONTACT_TYPE::REGISTRANT || $contactType == CONTACT_TYPE::ADMIN)
			{
				// Add admin -> registrant association
				if ($contactType == CONTACT_TYPE::REGISTRANT)
				{
					$this->AddRegistrantAssociation($domain->GetContact(CONTACT_TYPE::ADMIN)->CLID, $newContact->CLID);
				}
				else
				{
					$this->AddRegistrantAssociation($newContact->CLID, $domain->GetContact(CONTACT_TYPE::REGISTRANT)->CLID);
				} 
			}
			
			return parent::UpdateDomainContact($domain, $contactType, $oldContact, $newContact);
		}
		
		public function CreateDomain(Domain $domain, $period, $extra = array())
		{
			$contacts = $domain->GetContactList();
			$nameservers = $domain->GetNameserverList();

			$params = array(
				"name"				=> $this->MakeNameIDNCompatible($domain->GetHostName()),
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
			
			$this->AddRegistrantAssociation($contacts[CONTACT_TYPE::ADMIN]->CLID, $params["registrant_id"]);
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

				// NORID returns only crDate in domain:create response
				// To get exDate execute domain:info 				
				$Grd = $this->GetRemoteDomain($domain);
				$resp->ExpireDate = $Grd->ExpireDate; 
				
				$resp->AuthCode = (string)$params["pw"];
			}
			
			return $resp;
		}

		public function RenewDomain(Domain $domain, $extra=array())
		{
			$params = array(
				"name" 		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"exDate" 	=> date("Y-m-d", $domain->ExpireDate),
				"period" 	=> $extra["period"]
			);
			
			$this->BeforeRequest('domain-renew', $params, __METHOD__, $domain, $extra);
			$response = $this->Request("domain-renew", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
			$resp = new RenewDomainResponse($status, $response->ErrMsg, $response->Code);
			
			if ($response->Succeed)
			{
				$Grd = $this->GetRemoteDomain($domain);
				$resp->ExpireDate = $Grd->ExpireDate;
			}
			
			return $resp;
		}		
		
		public function GetRemoteContact(Contact $contact)
		{
			$Response = parent::GetRemoteContact($contact);
			if ($Response->Succeed())
			{
				$EppResponse = $Response->RawResponse;
				$InfData = $EppResponse->response->extension->children("http://www.norid.no/xsd/no-ext-contact-1.0");
				$InfData = $InfData[0];
				if ($InfData)
				{
					if ($InfData->type[0] == "organization")
					{
						$Response->{"no-ext-identity-type"} = (string)$InfData->identity->attributes()->type;
						$Response->{"no-ext-identity"} = (string)$InfData->identity;
					}
				} 
			}
			return $Response;
		}
		
		public function ReadMessage ()
		{
			$Resp = $this->Request('poll-request', array());

			if ($Resp->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE)
			{
				$msgID = (string)$Resp->Data->response->msgQ->attributes()->id;
				$resData = $Resp->Data->response->resData;
				if ($trnData = $resData->children($this->XmlNamespaces['domain']))
				{
					// Domain transfer message
					$trnData = $trnData[0];
					$trStatus = (string)$trnData->trStatus;
					$reID = (string)$trnData->reID;

					// Test for outgoing transfer message
					if ($reID != $this->Config->GetFieldByName('Login')->Value)
					{
						// Message relates to OUTGOING transfer. skip it.
						$this->Request('poll-ack', array('msgID' => $msgID));
						return $this->ReadMessage();						
					}
					
					switch ($trStatus)
					{
						case self::TRANSFER_CLIENT_APPROVED:
						case self::TRANSFER_SERVER_APPROVED:
							$transfer_status = TRANSFER_STATUS::APPROVED;
							break;
	
						case self::TRANSFER_CLIENT_CANCELLED:
						case self::TRANSFER_SERVER_CANCELLED:
						case self::TRANSFER_CLIENT_REJECTED:
							$transfer_status = TRANSFER_STATUS::DECLINED;
							break;
							
						case self::TRANSFER_PENDING:
							$transfer_status = TRANSFER_STATUS::PENDING;
							break;
							
						default:
							$transfer_status = TRANSFER_STATUS::FAILED;
					}
					
					$Ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$Ret->MsgID = $msgID;
					$Ret->HostName = (string)$trnData->name;
					$Ret->TransferStatus = $transfer_status;
					$Ret->RawResponse = $Resp->Data;
					return $Ret;
				}
				else
				{
					$Ret = new PendingOperationResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$Ret->MsgID = $msgID;
					$Ret->RawResponse = $Resp->Data;
					return $Ret;
				}
			}
			
			return false;
		}

		public function OnDomainTransferApproved (Domain $domain) 
		{
			$registrant = $domain->GetContact(CONTACT_TYPE::REGISTRANT);
			$admin = $domain->GetContact(CONTACT_TYPE::ADMIN);
			if ($registrant && $admin)
			{
				$this->AddRegistrantAssociation($admin->CLID, $registrant->CLID);	
			}
		}
	}
	
	
	
	class DotNORegistryModule_OteTestSuite extends OteTestSuite 
	{
		public function GetName ()
		{
			return "DotNO";
		}
		
		/**
		 * @var DotNORegistryModule
		 */
		private $module, $module2;
		
		/**
		 * @var Registry
		 */
		private $registry, $registry2;
		
		/**
		 * @var DataForm
		 */
		private $testConf;
		
		private $org1, $org2, $contact1, $contact2, $role1, $ns1, $ns2;
		
		private $domain1, $domain2;
		
		private $suffix;
		
		public function SetUp (DataForm $testConf)
		{
			$this->testConf = $testConf;
	        $this->module = new DotNORegistryModule(new RegistryManifest(MODULES_PATH . "/registries/DotNO/module.xml"));
	        $this->module2 = new DotNORegistryModule(new RegistryManifest(MODULES_PATH . "/registries/DotNO/module.xml"));
	        $this->module->InitializeModule("no", $testConf);
	        $this->registry = new Registry($this->module);
	        
	        $testConf2 = clone $testConf;
			$testConf2->GetFieldByName('Login')->Value = $testConf2->GetFieldByName('Login2')->Value;
			$testConf2->GetFieldByName('Password')->Value = $testConf2->GetFieldByName('Password2')->Value;
	        $this->module2->InitializeModule("no", $testConf2);
	        $this->registry2 = new Registry($this->module2);
	        
	        $this->suffix = rand(1000, 9999);
		}
		
		public function Run () {
			$this->createOrganization();
			$this->createContact();
			$this->createRoleObject();
			$this->createHost();
			$this->createDomain();
			
			$this->updateOrganization();
			$this->updateContact();
			$this->updateRoleObject();
			$this->updateHost();
			$this->updateDomain1();
			$this->updateDomain2();
			
			$this->changeHolder();
			$this->changeRegistrarStep1();
			$this->changeRegistrarStep2();
			$this->changeRegistrarStep3();
			
			$this->renewDomain();
			
			$this->withdrawDomain();
			$this->deleteDomain();
			$this->deleteContact();
			$this->deleteHost();
		}
		
		function createOrganization () {
			$title = 'Register two organizations';
			try {
				$dataTemplate = array(
					'name' => 'KS AS TANANGER',
					'street1' => 'Rådhusgata 1-3',
					'street2' => 'Akersgaten 42 (H-blokk)',
					'pc' => 'NO-8005',
					'city' => 'Bodø',
					'sp' => 'Nordland',
					'cc' => 'NO',
					'email' => 'norwaypolice@yahoo.com',
					'voice' => '+22.123456',
					'fax' => '+22.123457',
					'no-ext-identity-type' => 'organizationNumber',
					'no-ext-identity' => '932168243'
				);
				
				$org1 = $this->registry->NewContactInstanceByGroup('registrant');
				$org1->SetFieldList($dataTemplate);
				$Resp = $this->module->CreateContact($org1);
				preg_match('/\[organizationNumber:(\d+)\] already exists as \[([^\]]+)\]/', $Resp->ErrMsg, $matches);
				if ($matches && $matches[2]) {
					$this->Pass($title);						
					$org1->CLID = $matches[2];
				} else {
					$this->assertTrue($Resp->Code == 1000, $title);
					$org1->CLID = $Resp->CLID;
				}
				$this->org1 = $org1;					
				
				$org2 = $this->registry->NewContactInstanceByGroup('registrant');
				$dataTemplate['name'] = '1 TANANGER SJØSPEIDERGRUPPE';
				$dataTemplate['no-ext-identity'] = '984004737';
				$org2->SetFieldList($dataTemplate);
				$Resp = $this->module->CreateContact($org2);
				preg_match('/\[organizationNumber:(\d+)\] already exists as \[([^\]]+)\]/', $Resp->ErrMsg, $matches);
				if ($matches && $matches[2]) {
					$org2->CLID = $matches[2];
				} else {
					$org2->CLID = $Resp->CLID;
				}
				$this->org2 = $org2;				
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function createContact () {
			$title = 'Register two contacts';
			try {
				$dataTemplate = array(
					'name' => 'Harald Frøland',
					'street1' => 'Rådhusgata 1-3',
					'street2' => 'Akersgaten 42 (H-blokk)',
					'pc' => 'NO-8005',
					'city' => 'Bodø',
					'sp' => 'Nordland',
					'cc' => 'NO',
					'email' => $this->testConf->GetFieldByName('RealEmail')->Value,
					'voice' => '+22.123456',
					'fax' => '+22.123457'				
				);
				
				$contact1 = $this->registry->NewContactInstanceByGroup('generic');
				$contact1->SetFieldList($dataTemplate);
				$resp = $this->module->CreateContact($contact1);
				$this->assertTrue($resp->Code == 1000, $title);
				$contact1->CLID = $resp->CLID;
				$this->contact1 = $contact1;
				
				$contact2 = $this->registry->NewContactInstanceByGroup('generic');
				$dataTemplate['name'] = 'Martin Horntveth';
				$contact2->SetFieldList($dataTemplate);		
				$resp = $this->module->CreateContact($contact2);
				$contact2->CLID = $resp->CLID;
				$this->contact2 = $contact2;
			} catch (ErrorList $e2) {
				throw new Exception($title . " " . join("; ", $e2->GetAllMessages()));
				//$this->fail($title . " " . $e2->GetAllMessages());				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function createRoleObject () {
			$title = 'Register one role object';
			try {
				$role = $this->registry->NewContactInstanceByGroup('generic');
				$role->SetFieldList(array(
					'name' => 'Harald Frøland',
					'street1' => 'Rådhusgata 1-3',
					'street2' => 'Akersgaten 42 (H-blokk)',
					'pc' => 'NO-8005',
					'city' => 'Bodø',
					'sp' => 'Nordland',
					'cc' => 'NO',
					'email' => 'info@norid.no',
					'voice' => '+22.123456',
					'fax' => '+22.123457',
					'no-ext-role-contact' => $this->contact1->CLID				
				));
				$role->GroupName = 'role';
				
				$Resp = $this->module->CreateContact($role);
				$this->assertTrue($Resp->Code == 1000, $title);
				
				$role->CLID = $Resp->CLID;
				$this->role1 = $role;
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function createHost () {
			$title = "Register two hosts.";
			try {
				$ns1_host = $this->testConf->GetFieldByName('NS1')->Value;
				$ns1 = new NameserverHost($ns1_host, gethostbyname($ns1_host));
				$ns2_host = $this->testConf->GetFieldByName('NS2')->Value;
				$ns2 = new NameserverHost($ns2_host, gethostbyname($ns2_host));
				$resp = $this->module->CreateNameserverHost($ns1);
				$this->assertTrue($resp->Code == 1000, $title);
				$this->ns1 = $ns1;
				
				$resp = $this->module->CreateNameserverHost($ns2);
				$this->ns2 = $ns2;
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function createDomain () {
			$title = 'Register one domain.';
			try {
				$domain = $this->registry->NewDomainInstance();
				$domain->Name = explode('.', $this->testConf->GetFieldByName('Domain1')->Value, 2);
				$domain->Name = $domain->Name[0];
				$domain->SetContact($this->org1, CONTACT_TYPE::REGISTRANT);
				$domain->SetContact($this->contact1, CONTACT_TYPE::TECH);
				$domain->SetContact($this->contact1, CONTACT_TYPE::ADMIN);
				$domain->SetNameserverList(array($this->ns1, $this->ns2));
				$domain->AuthCode = 's6adyPaT';
				
				$resp = $this->module->CreateDomain($domain, 1);
				$this->assertTrue(in_array($resp->Code, array(1000, 1001)), $title);
				
				$domain->ExpireDate = $resp->ExpireDate;
				$this->domain1 = $domain;
				

				$domain = $this->registry->NewDomainInstance();
				$domain->Name = explode('.', $this->testConf->GetFieldByName('Domain2')->Value, 2);
				$domain->Name = $domain->Name[0];
				$domain->SetContact($this->org1, CONTACT_TYPE::REGISTRANT);
				$domain->SetContact($this->contact1, CONTACT_TYPE::TECH);
				$domain->SetContact($this->contact1, CONTACT_TYPE::ADMIN);
				$domain->SetNameserverList(array($this->ns1, $this->ns2));
				$domain->AuthCode = 's6adyPaT';
				
				$resp = $this->module->CreateDomain($domain, 1);
				$this->assertTrue(in_array($resp->Code, array(1000, 1001)), $title);
				
				$domain->ExpireDate = $resp->ExpireDate;
				$this->domain2 = $domain;
				
				
				
			} catch (RegistryException $e) {
				$this->fail($title);
			} 
		}
		
		function updateOrganization () {
			$title = 'Update an organization with a new postal address';
			try {
				// Need to save contact to prevent 
				// [Exception] Contact was not loaded through DBContact, or was deleted at class.DBContact.php line 55
				// DotNO module during UpdateContact checks DBContact initial state
				$dbContact = DBContact::GetInstance();
				$dbContact->Save($this->org1);
				
				$data = $this->org1->GetFieldList();
				unset($data['no-ext-identity-type'], $data['no-ext-identity']);
				$data['street1'] = 'Boks 1072 Blindern';
				$this->org1->SetFieldList($data);
				$resp = $this->module->UpdateContact($this->org1);
				
				$this->assertTrue($resp->Code == 1000, $title);
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
			
			$dbContact->Delete($this->org1);
		}
		
		function updateContact () {
			$title = 'Update a contact person with a new telephone number.';
			try {
				// Need to save contact to prevent 
				// [Exception] Contact was not loaded through DBContact, or was deleted at class.DBContact.php line 55
				// DotNO module during UpdateContact checks DBContact initial state
				$dbContact = DBContact::GetInstance();
				$dbContact->Save($this->contact1);
				
				$data = $this->contact1->GetFieldList();
				$data['voice'] = '+22.567432';
				$this->contact1->SetFieldList($data);

				$resp = $this->module->UpdateContact($this->contact1);
				
				$this->assertTrue($resp->Code == 1000, $title);
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
			
			$dbContact->Delete($this->contact1);
		}
		
		function updateRoleObject () {
			$title = 'Update the contact role with an additional contact person.';
			try {
				// Need to save contact to prevent 
				// [Exception] Contact was not loaded through DBContact, or was deleted at class.DBContact.php line 55
				// DotNO module during UpdateContact checks DBContact initial state
				$dbContact = DBContact::GetInstance();
				$dbContact->Save($this->role1);				
				
				$data = $this->role1->GetFieldList();
				$data['id'] = $this->role1->CLID;
				$data['no-ext-add'] = "<no-ext-contact:add>"
						. "<no-ext-contact:roleContact>{$this->contact2->CLID}</no-ext-contact:roleContact>"
						. "</no-ext-contact:add>";
				$data['no-ext-rem'] = '';
				
				$trResp = $this->module->Request('contact-update', $data);
				$this->assertTrue($trResp->Succeed, $title);
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
			
			$dbContact->Delete($this->role1);
		}
		
		function updateHost () {
			$title = 'Update one of the hosts with a new contact.';
			try {
				$data = array(
					'hostname' => $this->ns1->HostName,
					'clid' => $this->contact2->CLID
				);
				$trResp = $this->module->Request('test-host-update', $data);
				$this->assertTrue($trResp->Succeed, $title);
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function updateDomain1() {
			$title = 'Update the domain with a new technical contact and a host';
			try {
				$ns3 = new NameserverHost("ns3.epp-drs-test-{$this->suffix}.no", '8.8.8.11');
				$resp = $this->module->CreateNameserverHost($ns3);				
				
				$params = array(
					"name" => "{$this->domain1->GetHostName()}",
					"add" => '<domain:add>'
						. '<domain:ns>'
							. '<domain:hostObj>' . "ns3.epp-drs-test-{$this->suffix}.no" . '</domain:hostObj>'
						. '</domain:ns>'
					    . '<domain:contact type="tech">' . $this->contact2->CLID . '</domain:contact>'
						. '</domain:add>',
					"rem" => '<domain:rem>' 
						. '<domain:contact type="tech">' . $this->contact1->CLID . '</domain:contact>'
						. '</domain:rem>',
					"change" => ""
				);
				
				$trResp = $this->module->Request('domain-update', $params);				
				$this->assertTrue($trResp->Succeed, $title);
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function updateDomain2() {
			$title = 'Remove the auth-info from the domain';
			try {
				$params = array(
					"name" => "{$this->domain1->GetHostName()}",
					"add" => '',
					"rem" => '',
					"change" => "<domain:chg><domain:authInfo><domain:pw></domain:pw></domain:authInfo></domain:chg>"
				);
				
				$trResp = $this->module->Request('domain-update', $params);				
				$this->assertTrue($trResp->Succeed, $title);
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function changeHolder() {
			$title = 'Update the domain with a new holder.';
			try {
				$Resp = $this->module->UpdateDomainContact($this->domain1, CONTACT_TYPE::REGISTRANT, $this->org1, $this->org2);
				$this->assertTrue(in_array($Resp->Code, array(1000, 1001)), $title);
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function changeRegistrarStep1() {
			$title = "Transfer the domain you registered in task no. 5";
			
			try {
				$pw = '16ad12Pav';
				$params = array(
					"name" => "{$this->domain1->GetHostName()}",
					"add" => '',
					"rem" => '',
					"change" => "<domain:chg><domain:authInfo><domain:pw>$pw</domain:pw></domain:authInfo></domain:chg>"
				);
				$trResp = $this->module->Request('domain-update', $params);
				$this->domain1->AuthCode = $pw;
				// NORID test server support 1 session per client
				$this->module->GetTransport()->Disconnect();
				
				$resp = $this->module2->TransferRequest($this->domain1, array("pw" => $this->domain1->AuthCode));
				$this->assertTrue(in_array($resp->Code, array(1000, 1001)), $title);
				
				/*
				$rdomain = $this->registry2->NewDomainInstance();
				$rdomain->Name = $this->domain1->Name;
				do {
					sleep(5);
					$grd = $this->module2->GetRemoteDomain($rdomain);
				} while ($grd->CLID != $this->testConf->GetFieldByName('Login2')->Value);
				
				// Set authCode
				$this->module2->UpdateDomainAuthCode($this->domain1, $this->domain1->AuthCode);
				*/
				
				$this->module2->GetTransport()->Disconnect();
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function changeRegistrarStep2() {
			/*
			$title = 'Transfer the domain back to you';
			
			try {
				$trResp = $this->module->TransferRequest($this->domain1, array("pw" => $this->domain1->AuthCode));
				$this->assertTrue(in_array($trResp->Code, array(1000, 1001)), $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
			*/
		}
		
		function changeRegistrarStep3() {
			
		}
		
		function renewDomain () {
			$title = 'Renew the domain for 12 months.';
			
			try {
				// Transfer domain back
				//$resp = $this->module->TransferRequest($this->domain1, array("pw" => $this->domain1->AuthCode));
				
				$resp = $this->module->RenewDomain($this->domain1, array('period' => 1));
				$this->assertTrue(in_array($resp->Code, array(1000, 2105)), $title);
				
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function withdrawDomain () {
			$title = 'Withdraw from the domain.';
			
			try {
				$trResp = $this->module->Request('domain-withdraw', array('name' => $this->domain1->GetHostName()));
				$this->assertTrue($trResp->Succeed, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function deleteDomain () {
			$title = 'Delete the domain.';
			
			try {
				$resp = $this->module->DeleteDomain($this->domain2);
				$this->assertTrue($resp->Succeed(), $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function deleteContact () {
			$title = 'Delete one of your contacts.';
			
			try {
				$dataTemplate = array(
					'name' => 'Harald Frøland',
					'street1' => 'Rådhusgata 1-3',
					'street2' => 'Akersgaten 42 (H-blokk)',
					'pc' => 'NO-8005',
					'city' => 'Bodø',
					'sp' => 'Nordland',
					'cc' => 'NO',
					'email' => $this->testConf->GetFieldByName('RealEmail')->Value,
					'voice' => '+22.123456',
					'fax' => '+22.123457'				
				);
				
				$contact = $this->registry->NewContactInstanceByGroup('generic');
				$contact->SetFieldList($dataTemplate);
				$resp = $this->module->CreateContact($contact);
				$contact->CLID = $resp->CLID;
				
				$resp = $this->module->DeleteContact($contact);
				$this->assertTrue($resp->Succeed(), $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function deleteHost () {
			$title = 'Delete one of your hosts.';
			
			try {
				$ns = new NameserverHost("ns4.epp-drs-test-{$this->suffix}.no", '8.8.8.12');
				$resp = $this->module->CreateNameserverHost($ns);				
				
				$resp = $this->module->DeleteNameserverHost($ns);
				$this->assertTrue($resp->Succeed(), $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
	}	
?>
