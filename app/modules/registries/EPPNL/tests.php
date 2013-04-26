<?php
class EPPNLRegistryModuleTest extends UnitTestCase 
{
	private $Registry;
	
	function __construct()
	{
		parent::__construct();
		$this->Registry =  RegistryModuleFactory::GetInstance()->GetRegistryByName('EPPNL');
	}
	
	function _testGrd ()
	{
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = "parelsenpiraten";
		$this->Registry->GetRemoteDomain($Domain);
		var_dump(date("Y-m-d", $Domain->ExpireDate));
	}
	
	function testPoll ()
	{
		$Msg = $this->Registry->GetModule()->ReadMessage();
		var_dump($Msg);
		print "<pre>";
		print ($Msg->RawResponse->asXML());
		print "</pre>";
		$this->Registry->GetModule()->AcknowledgeMessage($Msg);
		//$Msg = $this->Registry->GetModule()->ReadMessage();
		//print ($Msg->RawResponse->asXML());
	}
	
	function _testPoll2 ()
	{
		$Msg = $this->Registry->GetModule()->ReadMessage();
	}
	
}