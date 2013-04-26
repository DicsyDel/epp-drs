<?php
	require_once(dirname(__FILE__).'/class.Transport.php');
	require_once(dirname(__FILE__).'/class.RegistryModule.php');
	
	class DotSERegistryTests extends UnitTestCase 
	{
		private $Module;
		
		private $Registry;
		
		/*
		function setUp ()
		{
			$ModuleConf = DotSERegistryModule::GetConfigurationForm();
			$module_conf = $ModuleConf->ListFields();
			$module_conf['Login']->Value = 'odweab';
			$module_conf['Password']->Value = 'Xwv0d9k{wfgK';
			$module_conf['ServerHost']->Value = '192.176.123.153';
			$module_conf['ServerPort']->Value = '80';
			
			$this->Module = new DotSERegistryModule(new RegistryManifest(dirname(__FILE__) . '/module.xml'));
        	$this->Module->InitializeModule("se", $ModuleConf);
        	$this->Registry = new Registry($this->Module);
        	$this->Prefix = "odweab-d2684f6dd481c32df4723f3640441700";
        	$this->ContactCLID = "ODWEAB0909-0002"; 
        	$this->AuthInfo = "SONson--3110";
		}
		*/
		
		function setUp () 
		{
			$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension("se");
		}
		
		function testImpendingExpiration ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'about-to-expire';
			$Domain->ID = 283;
			$Domain->UserID = 42;
			$Domain->ExpireDate = strtotime("2009-05-20 00:00:00");
			$Domain->CreateDate = strtotime("2008-05-20 00:00:00");
			
			$this->Registry->HandleImpendingExpiration($Domain);
			
			print "save domain";
			DBDomain::GetInstance()->Save($Domain);
			print "after save";
			
		}
		
		function _testPoll ()
		{
			$Module = $this->Registry->GetModule();
			$Resp = $Module->ReadMessage();
			//$Module->AcknowledgeMessage($Resp);
			var_dump($Resp);
		}		
		
		function _testOte () {
			/*
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
			*/
			$this->changeDomainOwner();
			/*
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
			*/
			
			// 13. clearMessageQueue
			//$this->clearMessageQueue();
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
				$this->assertTrue($Resp->Code == 1000);

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
				$Contact = $this->Registry->NewContactInstanceByGroup("generic");
				$Contact->SetFieldList(array(
	        		'name' 		=> 'marat komarov',
	        		'org' 		=> 'MK1O',
	        		'cc' 		=> 'NO',
	        		'sp' 		=> 'crimea',
	        		'city' 		=> 'TRONDHEIM',
	        		'pc' 		=> '7491',
	        		'sp' 		=> 'Crimea',
	        		'street1' 	=> 'bbfdgfd fds',
					'street2' 	=> 'dsf fd d',
	        		'voice' 	=> '+33.12345678',
	        		'fax' 		=> '+33.12345678',
	        		'email' 	=> 'igor@webta.net',
					'vatno'		=> 'UA3333',
					'orgno'		=> '[UA]2343'						
				));
				$this->Module->CreateContact($Contact);

				
				$Resp = $this->Module->Request('domain-update-contact', array(
					'name' => "{$this->Prefix}-05.se",
					'add' =>  '',
					'rem' =>  '',
					//'change' => "<domain:chg><domain:registrant>{$this->ContactCLID}</domain:registrant></domain:chg>",
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
					"change" => "<domain:authInfo><domain:pw>{$this->AuthInfo}</domain:pw></domain:authInfo>"
				);
				$Resp = $this->Module->Request('domain-update', $params);
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
				
		function _testPwGen ()
		{
			$pw = $this->Module->GeneratePassword();
			var_dump($pw);
		}
		
		/*
		function testInfo ()
		{
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = 'for-transfer-4';
			$Resp = $this->Module->GetRemoteDomain($Domain);
			var_dump($Resp);
		}
		*/
	}
?>