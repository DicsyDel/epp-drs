<?php
	require_once(dirname(__FILE__).'/class.Transport.php');
	require_once(dirname(__FILE__).'/class.RegistryModule.php');
	
	class DotEUMockupTransportBug358 extends DotEUTransport
	{
		function __construct () 
		{
			$this->IsConnected = true;
		}
		
		function Login ()
		{
			return true;
		}
		
		function Request ($command, $data = array())
		{
			var_dump($command);
			
			if ($command == "domain-info" && $data["name"] == "interdotcom.eu")
			{
				$xml = simplexml_load_string('<epp xmlns="http://www.eurid.eu/xml/epp/epp-1.0">
						<response>
						<result code="2201">
						<msg>Authorization error</msg>
						</result>
						<trID>
						<clTRID>a000984-1249546684</clTRID>
						<svTRID>eurid-0</svTRID>
						</trID>
						</response>
						</epp>');
				$code = 2201;
				$msg = "Authorization error";
			}
			else if ($command == "domain-info" && $data["name"] == "3plus.eu")
			{
				$xml = simplexml_load_string('<epp xmlns="http://www.eurid.eu/xml/epp/epp-1.0" xmlns:domain="http://www.eurid.eu/xml/epp/domain-1.0" xmlns:eurid="http://www.eurid.eu/xml/epp/eurid-1.0">
						<response>
						<result code="1000">
						<msg>Command completed successfully</msg>
						</result>
						<resData>
						<domain:infData>
						<domain:name>3plus.eu</domain:name>
						<domain:roid>6062344-EURID</domain:roid>
						<domain:status s="ok"/>
						<domain:registrant>c7874043</domain:registrant>
						<domain:contact type="billing">c38256</domain:contact>
						<domain:contact type="onsite">c10811080</domain:contact>
						<domain:ns>
						<domain:hostAttr>
						<domain:hostName>ns487.hostgator.com</domain:hostName>
						</domain:hostAttr>
						<domain:hostAttr>
						<domain:hostName>ns488.hostgator.com</domain:hostName>
						</domain:hostAttr>
						</domain:ns>
						<domain:clID>a000984</domain:clID>
						<domain:crID>a000984</domain:crID>
						<domain:crDate>2007-08-23T16:03:42.000Z</domain:crDate>
						<domain:upID>a000984</domain:upID>
						<domain:upDate>2009-08-06T08:02:24.000Z</domain:upDate>
						<domain:exDate>2010-08-31T21:59:59.999Z</domain:exDate>
						<domain:trDate>2009-08-06T08:02:24.000Z</domain:trDate>
						</domain:infData>
						</resData>
						<extension>
						<eurid:ext>
						<eurid:infData>
						<eurid:domain>
						<eurid:onhold>false</eurid:onhold>
						<eurid:quarantined>false</eurid:quarantined>
						</eurid:domain>
						</eurid:infData>
						</eurid:ext>
						</extension>
						<trID>
						<clTRID>a000984-1249546206</clTRID>
						<svTRID>eurid-0</svTRID>
						</trID>
						</response>
						</epp>');
				
				/*
				$xml = simplexml_load_string('<epp xmlns="http://www.eurid.eu/xml/epp/epp-1.0" xmlns:domain="http://www.eurid.eu/xml/epp/domain-1.0" xmlns:eurid="http://www.eurid.eu/xml/epp/eurid-1.0">
						<response>
						<result code="1000">
						<msg>Command completed successfully</msg>
						</result>
						<resData>
						<domain:infData>
						<domain:name>3plus.eu</domain:name>
						<domain:roid>0-EURID</domain:roid>
						<domain:clID>#non-disclosed#</domain:clID>
						</domain:infData>
						</resData>
						<extension>
						<eurid:ext>
						<eurid:infData>
						<eurid:domain>
						<eurid:onhold>false</eurid:onhold>
						<eurid:quarantined>false</eurid:quarantined>
						<eurid:pendingTransaction>
						<eurid:transfer>
						<eurid:domain>
						<eurid:registrant>c7874043</eurid:registrant>
						<eurid:trDate>2009-08-05T22:00:00.000Z</eurid:trDate>
						<eurid:billing>c38256</eurid:billing>
						<eurid:onsite>c10811080</eurid:onsite>
						<eurid:ns>
						<domain:hostAttr>
						<domain:hostName>ns487.hostgator.com</domain:hostName>
						</domain:hostAttr>
						<domain:hostAttr>
						<domain:hostName>ns488.hostgator.com</domain:hostName>
						</domain:hostAttr>
						</eurid:ns>
						</eurid:domain>
						<eurid:initiationDate>2009-08-06T03:18:23.000Z</eurid:initiationDate>
						<eurid:status>ApprovedAndScheduled</eurid:status>
						<eurid:replyOwner>Approved</eurid:replyOwner>
						</eurid:transfer>
						</eurid:pendingTransaction>
						</eurid:domain>
						</eurid:infData>
						</eurid:ext>
						</extension>
						<trID>
						<clTRID>a000984-1249545735</clTRID>
						<svTRID>eurid-0</svTRID>
						</trID>
						</response>
						</epp>');
				*/
				$code = 1000;
				$msg = "Command completed successfully";
			}
			
			return new TransportResponse($code, $xml, $code == 1000, $msg);
		}
	}
	
	class DotEURegistryTests extends UnitTestCase 
	{
		private $Module;
		
		private $Registry;
		
		function setUp () 
		{
			$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension("eu");
			//$eu_transport = $this->Registry->GetModule()->GetTransport();
			//$mock_transport = new DotEUMockupTransportBug358();
			
			$this->Registry->GetModule()->SetTransport(new DotEUMockupTransportBug358());
		}
		
		function testBusy ()
		{
			$module = $this->Registry->GetModule();
			$domain = $this->Registry->NewDomainInstance();
			$domain->Name = "3plus";
			
			var_dump($module->PollTransfer($domain));
			
			die();
			//$domain = DBDomain::GetInstance()->LoadByName("for-transfer-1", "eu");
			//$module = $this->Registry->GetModule();
			//$ret = $module->PollTransfer($domain);
			//var_dump($ret);
		}
		
		function _testPoll ()
		{
			$Module = $this->Registry->GetModule();
			$Resp = $Module->ReadMessage();
			//$Module->AcknowledgeMessage($Resp);
			var_dump($Resp);
		}		
		
		function _clearMessageQueue ()
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
