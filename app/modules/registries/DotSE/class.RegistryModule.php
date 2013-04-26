<?php
		
	class DotSERegistryModule extends GenericEPPRegistryModule implements IRegistryModuleClientPollable 
	{
		
		public function GetTestConfigurationForm ()
		{
			$CF = new DataForm();
			$CF->AppendField(new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$CF->AppendField(new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
			$CF->AppendField(new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1, null, null, null, 'Your OT&E1 account'));
			$CF->AppendField(new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
			$CF->AppendField(new DataFormField("DomainPrefix", FORM_FIELD_TYPE::TEXT, "Domain prefix", 1));
			$CF->AppendField(new DataFormField("ContactCLID", FORM_FIELD_TYPE::TEXT, "Contact CLID", 1));
			$CF->AppendField(new DataFormField("AuthInfo", FORM_FIELD_TYPE::TEXT, "AuthInfo", 1));
		
			return $CF;
		}
		
		public static function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->AppendField( new DataFormField("CLID", FORM_FIELD_TYPE::TEXT , "Registrar clID", 1));			
			$ConfigurationForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
			$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Passsword", 1));
			$ConfigurationForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$ConfigurationForm->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));
			$ConfigurationForm->AppendField( new DataFormField("SSLCertPath", FORM_FIELD_TYPE::TEXT , "Path to SSL certificate", 1));
			$ConfigurationForm->AppendField( new DataFormField("SSLCertPwd", FORM_FIELD_TYPE::TEXT , "SSL certificate password", 1));
			
			
			return $ConfigurationForm;
		}

		public function GetRegistrarID()
		{
			return $this->Config->GetFieldByName("CLID")->Value;
		}		
	
		public function RunTest ($DF)
		{
			$Runner = new OteTestRunner();
			$Runner->Run(new DotSERegistryModule_OteTestSuite(), $DF);
		}
		
		public function GetRemoteContact(Contact $contact)
		{
			$Resp = parent::GetRemoteContact($contact);
			if ($Resp->Succeed())
			{
				$infData = $Resp->RawResponse->response->extension->children("urn:se:iis:xml:epp:iis-1.1");
				$infData = $infData[0];
				if ($infData)
				{
					$Resp->{"vatno"} = trim((string)$infData->vatno);
					$Resp->{"orgno"} = trim((string)$infData->orgno);
				}
			}
			return $Resp;
		}
		
		static function ContactValidator ($fields)
		{
			$err = array();
			
			if ($fields["cc"] == "SE")
			{
				if ($fields["orgno"] && !preg_match("/^\[SE\]\d{6}-\d{4}$/", $fields["orgno"]))
				{
					$err[] = sprintf(_("'%s' must be valid Sweden personal/organization number"), _("Personal/Org number"));
				}
				
				/*
				if ($fields["org"] && !preg_match("/^\[SE\]\d{6}-\d{4}$/", $fields["orgno"]))
				{
					$err[] = sprintf(_("'%s' is not valid"), _("Personal/Org number"));
				}
				*/
				if ($fields["pc"] && strlen($fields["pc"]) != 5)
				{
					$err[] = sprintf(_("'%s' should be 5 digits"), _("Postal code"));
				}
			}
			else
			{
				if ($fields["orgno"] && !preg_match("/^\[\w{2}\]/", $fields["orgno"]))
					$err[] = sprintf(_("'%s' is not valid"), _("Personal/Org number"));
			}
			
			return $err;
		}
		
		protected function BeforeRequest ($command, &$data, $method /** args */)
		{
			switch ($command)
			{
				case "domain-create":
					$data["pw"] = $this->EscapeXML($this->GeneratePassword());
					break;
					
				case "domain-trans-request":
					$extra = func_get_arg(4);
					
					//$data['ns'] .= "<domain:hostObj>".$this->MakeNameIDNCompatible($extra["ns1"]->HostName)."</domain:hostObj>";
					//$data['ns'] .= "<domain:hostObj>".$this->MakeNameIDNCompatible($extra["ns2"]->HostName)."</domain:hostObj>";					
					
					break;
			}
		}		
		
		public function DomainCanBeTransferred(Domain $domain)
	    {
	    	$Ret = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	
	    	$CanRegisteredResponse = $this->DomainCanBeRegistered($domain);
	    	if ($CanRegisteredResponse->Result == false)
	    	{ 	
		    	try
		    	{
		    		$params = array(
						"name"	=> $this->MakeNameIDNCompatible($domain->GetHostName()),			
						'authinfo' => ''			
		    		);
		    		$Resp = $this->Request('domain-info', $params);
		    		if ($Resp->Succeed)
		    		{
		    			$infData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
		    			$infData = $infData[0];
		    			
		    			$Ret->Result = $this->GetRegistrarID() != "{$infData->clID}";
		    			
 		    			// Check for non existed registrant node .-)
		    			//$Ret->Result = $infData->registrant->getName() == ''; 
		    		}
		    		else if ($Resp->Code == RFC3730_RESULT_CODE::ERR_AUTHORIZE_ERROR)
		    		{
		    			$Ret->Result = true;
		    		}
		    		else
		    		{
		    			$Ret->Result = false;
		    		}
		    	}
		    	catch(ObjectNotExistsException $e)
		    	{
		    		$Ret->Result = true;
		    	}
	    	}
	    	else
	    		$Ret->Result = false;
	    	
	    	return $Ret;
	    }

		public function PollCreateDomain (Domain $domain) {}
		

		public function PollDeleteDomain (Domain $domain) {}
		

		public function PollChangeDomainOwner (Domain $domain) {}
	

		public function PollTransfer (Domain $domain) 
		{
			$params = array(
				'name' => $this->MakeNameIDNCompatible($domain->GetHostName()),
				'pw' => $this->EscapeXML($domain->AuthCode)
			);
			$Resp = $this->Request('domain-info', $params);
			
			if ($Resp->Succeed)
			{
    			$infData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
    			$infData = $infData[0];
    			
    			// transfer complete when trDate node exists.
	   			$ok = $infData->trDate->getName() != '';
    			$trStatus = $ok ? TRANSFER_STATUS::APPROVED : TRANSFER_STATUS::PENDING;
				$respStatus = $trStatus != TRANSFER_STATUS::PENDING ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::PENDING;
				$Ret = new PollTransferResponse($respStatus, $Resp->ErrMsg, $Resp->Code); 
				$Ret->HostName = $domain->GetHostName();
				$Ret->TransferStatus = $trStatus;
				
				return $Ret;
			}
			else
			{
				return new PollTransferResponse(REGISTRY_RESPONSE_STATUS::FAILED, $Resp->ErrMsg, $Resp->Code);
			}			
		}
		
		public function OnDomainTransferApproved (Domain $Domain)
		{
			// Change domain password
			$pw = $this->GeneratePassword();
			$resp = $this->UpdateDomainAuthCode($Domain, $pw);
			if ($resp->Succeed())
			{
				$Domain->AuthCode = $pw;				
			}
		}
		
		public function PollUpdateDomain (Domain $domain) {}
		

		public function PollDeleteContact (Contact $contact) {}
		
		public function PollDeleteNamserverHost (NamserverHost $nshost) {}		
	}
	
	class DotSERegistryModule_OteTestSuite extends OteTestSuite 
	{
		public function GetName ()
		{
			return "DotSE";
		}
		
		/**
		 * @var DotTELRegistryModule
		 */
		private $Module;
		
		/**
		 * @var Registry
		 */
		private $Registry;
		
		private $Prefix;

		private $ContactCLID;
		
		private $AuthInfo;
		
		public function SetUp (DataForm $TestConf)
		{
			$test_conf = $TestConf->ListFields();
			$ModuleConf = DotSERegistryModule::GetConfigurationForm();
			$module_conf = $ModuleConf->ListFields();
			$module_conf['ServerHost']->Value 	= $test_conf['ServerHost']->Value;
			$module_conf['ServerPort']->Value 	= $test_conf['ServerPort']->Value;
			$module_conf['Login']->Value 		= $test_conf['Login']->Value;
			$module_conf['Password']->Value 	= $test_conf['Password']->Value;
		
	        $this->Module = new DotSERegistryModule(new RegistryManifest(MODULES_PATH . "/registries/DotSE/module.xml"));
	        $this->Module->InitializeModule("se", $ModuleConf);
	        $this->Registry = new Registry($this->Module);
	        
	        $this->Prefix = $test_conf["DomainPrefix"]->Value;
	        $this->ContactCLID = $test_conf["ContactCLID"]->Value;
	        $this->AuthInfo = $test_conf["AuthInfo"]->Value;
		}
		
		public function Run ()
		{
			// 1. Create domain
			$this->createDomain();
			// 2. Update contact
			$this->updateContact();
			// 3. Update host
			$this->updateHost();
			// 4. Add host to domain
			$this->addDomainHost();
			// 5. Remove host from domain
			$this->removeDomainHost();
			// 6. Change domain owner
			$this->changeDomainOwner();
			// 7. Renew domain
			$this->renewDomain();
			// 8. Delete domain 
			$this->deleteDomain();
			// 9. Cancel delete
			$this->undeleteDomain();
			// 10. Request transfer
			$this->requestTransfer();
			// 11. Remove DS records from domain
			$this->removeDomainDS();
			// 12. Update domain authInfo
			$this->updateDomainAuthInfo();
			// 13. clearMessageQueue
			$this->clearMessageQueue();
		}
		
		function createDomain () 
		{
			$title = "create domain";	
			try {
				$domainname = "{$this->Prefix}-01.se";
				$params = array(
					"name" => $domainname,
					"period" => 1,
					'contacts' => '',
					'registrant_id' => $this->ContactCLID,
					'ns' => '',
					'pw' => $this->Module->GeneratePassword()
				);
				$Resp = $this->Module->Request("domain-create", $params);
				
				$Resp = $this->Module->Request("host-create", array(
					"name" => "ns1.{$domainname}",
					"addr" => ""
				));
				
				$Resp = $this->Module->Request("host-create", array(
					"name" => "ns2.{$domainname}",
					"addr" => ""
				));
				
				$Resp = $this->Module->Request("domain-update", array(
					"name" => $domainname,
					"add" => '<domain:add><domain:ns>'
						. '<domain:hostObj>ns1.'.$domainname.'</domain:hostObj>'
						. '<domain:hostObj>ns2.'.$domainname.'</domain:hostObj>'
						. '</domain:ns></domain:add>',
					"remove" => "",
					"change" => ""
				));
				$this->assertTrue($Resp->Code == 1000, $title);

			} catch (RegistryException $e) {
				$this->Fail($title);
			}
		}

		function updateContact ()
		{
		    $title = "update contact";
        	
        	try {
        		// Get contact info
        		$Contact = $this->Registry->NewContactInstanceByGroup("generic");
        		$Contact->CLID = $this->ContactCLID;

        		$Resp = $this->Module->GetRemoteContact($Contact);
        		if (!$Resp->Succeed()) throw new RegistryException($Resp->ErrMsg);
        		$props = get_object_vars($Resp);
        		foreach ($props as $name => $value) {
					$contact_data[$name] = $value;        		
        		}
        		

				// Update data
				$contact_data["pc"] = "18752";
				$contact_data["voice"] = "+46.799999999";
				$contact_data["id"] = $this->ContactCLID;
				$contact_data["disclose"] = "";
				
	        	$Resp = $this->Module->Request('contact-update', $contact_data);
	        	$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, $title);
        	} catch (RegistryException $e) {
        		$this->fail($title);
        	}
		}
		
		function updateHost ()
		{
			$title = "update host";
			
			try {
				$params = array(
					"name" => "a.{$this->Prefix}-02.se",
					"ipv4" => "217.108.99.249",
					"ipv6" => "2001:698:a:e:208:2ff:fe15:b2e8"
				);
				$Resp = $this->Module->Request('test-host-update', $params);
				$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
			
		}
		
		function addDomainHost ()
		{
			$title = "Add host to domain";
			
			try {
				$params = array(
					"name" => "{$this->Prefix}-03.se",
					"add" => '<domain:add><domain:ns><domain:hostObj>primary.se</domain:hostObj></domain:ns></domain:add>',
					"remove" => "",
					"change" => ""
				);
				$Resp = $this->Module->Request('domain-update', $params);
				$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function removeDomainHost ()
		{
			$title = "Remove host from domain";
			
			try {
				$params = array(
					"name" => "{$this->Prefix}-04.se",
					"add" => "",				
					"remove" => '<domain:rem><domain:ns><domain:hostObj>testhost.'.$this->Prefix.'-04.se</domain:hostObj></domain:ns></domain:rem>',
					"change" => ""
				);
				$Resp = $this->Module->Request('domain-update', $params);
				$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function changeDomainOwner ()
		{
			$title = "Change domain owner";
			
			try {
				$Contact = $this->Registry->NewContactInstanceByGroup("registrant");
				$Contact->SetFieldList(array(
	        		'name' 		=> 'Kim Gordon',
	        		'org' 		=> 'Sonic',
	        		'cc' 		=> 'NO',
	        		'sp' 		=> 'Crimea',
	        		'city' 		=> 'TRONDHEIM',
	        		'pc' 		=> '7491',
	        		'sp' 		=> 'Crimea',
	        		'street1' 	=> 'absdcdc',
					'street2' 	=> '',
	        		'voice' 	=> '+33.12345678',
	        		'fax' 		=> '+33.12345678',
	        		'email' 	=> 'kim@gordon'.rand(10, 99).'.net',
					'vatno'		=> 'UA'.rand(1000, 9999),
					'orgno'		=> '[UA]'.rand(1000, 9999)						
				));
				$this->Module->CreateContact($Contact);
				
				$Resp = $this->Module->Request('domain-update-contact', array(
					'name' => "{$this->Prefix}-05.se",
					'add' =>  '',
					'rem' =>  '',
					'change' => "<domain:chg><domain:registrant>{$Contact->CLID}</domain:registrant></domain:chg>",
				));
				$Resp = $this->Module->Request('domain-info', array(
					'name' => "{$this->Prefix}-05.se"
				));
				$this->assertTrue($Resp->Code == 1000, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}

		function renewDomain ()
		{
			$title = "Renew domain";
			
			try {
				// Get current expiration date
				$Domain = $this->Registry->NewDomainInstance();
				$Domain->Name = "{$this->Prefix}-06";
				$Resp = $this->Module->GetRemoteDomain($Domain);
				$curExpDate = date("Y-m-d", $Resp->ExpireDate);
			
				// Renew domain
				$Resp = $this->Module->Request("domain-renew", array(
					'name' => "{$this->Prefix}-06.se",
					'exDate' => $curExpDate,
					'period' => 1
				));
				$this->assertTrue($Resp->Code == 1000, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function deleteDomain ()
		{
			$title = "Set client delete for domain";
			
			try {
				$Resp = $this->Module->Request("test-domain-delete", array(
					'name' => "{$this->Prefix}-07.se",
					'clientDelete' => '1'
				));
				$this->assertTrue($Resp->Code == 1000, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function undeleteDomain ()
		{
			$title = "Cancel client delete for domain";
			
			try {
				$Resp = $this->Module->Request("test-domain-delete", array(
					'name' => "{$this->Prefix}-08.se",
					'clientDelete' => '0'
				));
				$this->assertTrue($Resp->Code == 1000, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function requestTransfer ()
		{
			$title = "Request transfer of domain";
			
			try {
				$Resp = $this->Module->Request("domain-trans-request", array(
					'name' => "{$this->Prefix}-09.se",
					'pw' =>  $this->AuthInfo
				));
				$this->assertTrue(in_array($Resp->Code, array(1000, 1001)), $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}		
		}
		
		
		function removeDomainDS ()
		{
			$title = "Remove domain DS records";
		
			try {
				$Resp = $this->Module->Request("test-domain-remove-ds", array(
					'name' => "{$this->Prefix}-09.se"
				));
				$this->assertTrue($Resp->Code == 1000, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function updateDomainAuthInfo ()
		{
			$title = "Update domain authInfo";
		
			try {
				$params = array(
					"name" => "{$this->Prefix}-09.se",
					"add" => "",				
					"remove" => "",
					"change" => "<domain:chg><domain:authInfo><domain:pw>{$this->AuthInfo}</domain:pw></domain:authInfo></domain:chg>"
				);
				$Resp = $this->Module->Request('domain-update', $params);
				
				$Resp = $this->Module->Request('domain-info', array(
					'name' => "{$this->Prefix}-09.se"
				));				
				
				$this->assertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
		
		function clearMessageQueue ()
		{
			$title = "Empty message queue";
		
			try {
				while ($Resp = $this->Module->ReadMessage()) {
					$this->Module->AcknowledgeMessage($Resp);
				}
				$this->assertTrue(true, $title);
			} catch (RegistryException $e) {
				$this->fail($title);
			}
		}
	}
?>