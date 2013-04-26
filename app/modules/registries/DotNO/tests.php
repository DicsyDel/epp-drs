<?php

require_once (dirname(__FILE__) . '/class.Transport.php');
require_once (dirname(__FILE__) . '/class.RegistryModule.php');

class DotNORegistryTests extends UnitTestCase 
{
	/**
	 * @var Registry
	 */
	private $Registry, $Registry2;
	
	private $ContactFields, $RccContactFields;
	
	private $Contact;
	
	private $Domain;
	
	function setUp ()
	{
		$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension("no");
	}
	
	function _testBusy ()
	{
		$Contact = DBContact::GetInstance()->LoadByCLID('VRSGN3');
		
		//$Module = $this->Registry->GetModule();
		//$Domain = $this->Registry->NewDomainInstance();
		//$Domain->Name = "webta";
		//$Module->CreateDomain($Domain, 2);
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = "webta-".rand(1000, 9999);
		$Domain->UserID = 1;
		$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
		$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
		$Domain->SetContact($Contact, CONTACT_TYPE::TECH);

		$this->Registry->CreateDomain($Domain, 2);

		//$ns1 = new NameserverHost('ns1.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
		//$ns2 = new NameserverHost('ns2.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));

		/*
		$this->Registry->CreateNameserverHost($ns1);
		$this->Registry->CreateNameserverHost($ns2);
		
		$Changes = $Domain->GetNameserverChangelist();
		$Changes->Add($ns1);
		$Changes->Add($ns2);
		$this->Registry->UpdateDomainNameservers($Domain, $Changes);
		*/
	}
	
	function testPoll ()
	{
		$Module = $this->Registry->GetModule();
		$Resp = $Module->ReadMessage();
		print $Resp->RawResponse->asXML();		
		$Module->AcknowledgeMessage($Resp);
	}
}
?>