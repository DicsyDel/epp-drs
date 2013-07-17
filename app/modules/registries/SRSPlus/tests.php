<?php

class SRSPlusRegistryTests extends UnitTestCase 
{
	/**
	 * @var Registry
	 */
	private $Registry;
	
	function setUp ()
	{
		$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension('com');
	}
	
	function testModule ()
	{
		$Contact = $this->Registry->NewContactInstanceByGroup('generic');
		$Contact->SetFieldList(array(
			"firstname"		=> "Marat",
			"lastname"		=> "Komarov",
			"org" 			=> "Webta",
			"street1" 		=> "Testing str. 80",
			"street2"		=> "Drive 54",
			"city"			=> "Testing",
			"pc"			=> "90210",
			"cc"			=> "US",
			"sp"			=> "TX",
			"voice"			=> "+33-312-34567",
			"fax"			=> "+33-312-34567",
			"email"			=> "marat@webta.net"
		));
		$this->Registry->CreateContact($Contact);
		
		$Contact2 = $this->Registry->NewContactInstanceByGroup('generic');
		$Contact2->SetFieldList(array(
			"firstname"		=> "Marat",
			"lastname"		=> "Komarov",
			"org" 			=> "Webta",
			"street1" 		=> "Testing str. 80",
			"street2"		=> "Drive 54",
			"city"			=> "Testing",
			"pc"			=> "90210",
			"cc"			=> "US",
			"sp"			=> "TX",
			"voice"			=> "+333.1234567",
			"fax"			=> "+333.1234567",
			"email"			=> "marat@webta.net"
		));
		$this->Registry->CreateContact($Contact2);
		
		
		$Domain = $this->Registry->NewDomainInstance();

		$Domain->Name = 'webta' . rand(100, 999);
		$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
		$Domain->SetContact($Contact2, CONTACT_TYPE::TECH);
		$period = 1; 
		
		$this->Registry->CreateDomain($Domain, $period);
	}
}
?>