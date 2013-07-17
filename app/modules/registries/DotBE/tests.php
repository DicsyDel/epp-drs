<?php
	require_once(dirname(__FILE__).'/class.Transport.php');
	require_once(dirname(__FILE__).'/class.RegistryModule.php');
	
	class DotBERegistryTests extends UnitTestCase 
	{
		private $Module;
		
		private $Registry;
		
		function setUp () 
		{
			$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension("be");
		}
		
		function testBusy ()
		{
			$module = $this->Registry->GetModule();
			$domain = $this->Registry->NewDomainInstance();
			$domain->Name = "trn-05";
			
			$registrant = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
			$registrant->CLID = "c66265";
			$domain->SetContact($registrant, CONTACT_TYPE::REGISTRANT);
			
			$tech = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
			$tech->CLID = "c66266";
			$domain->SetContact($tech, CONTACT_TYPE::TECH);
			
			$domain->SetNameserverList(array(
				new Nameserver("ns.hostdad.com"), 
				new Nameserver("ns2.hostdad.com")
			));
			
			$module->ChangeDomainOwner($domain);
			
			/*
			$domain = DBDomain::GetInstance()->LoadByName("for-transfer-1", "eu");
			$module = $this->Registry->GetModule();
			$ret = $module->PollTransfer($domain);
			var_dump($ret);
			*/
		}
		
		function _testPoll ()
		{
			$Module = $this->Registry->GetModule();
			$Resp = $Module->ReadMessage();
			//$Module->AcknowledgeMessage($Resp);
			var_dump($Resp);
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
