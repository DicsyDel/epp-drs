<?php

require_once(dirname(__FILE__) . "/Client/class.Service.php");
require_once(dirname(__FILE__) . "/class.Service.php");

class ApiTests extends UnitTestCase 
{
	/**
	 * @var EppDrs_Api_Client_Service
	 */
	private $eppdrs;
	
	private $eppdrs2;
	
	function __construct ()
	{
		parent::__construct();
		
		$this->eppdrs = new EppDrs_Api_Client_Service(array
		(
			"url" => "http://localhost/api/20111014",
			"key" => CONFIG::$API_KEY,
			"keyId" => CONFIG::$API_KEY_ID
		));
		
		$client = Client::Load(42);
		$this->eppdrs2 = new EppDrs_Api_Client_Service(array
		(
			"url" => "http://localhost/api/20111014",
			"key" => $client->GetSettingValue(ClientSettings::API_KEY),
			"keyId" => $client->GetSettingValue(ClientSettings::API_KEY_ID)
		));
	}
	
	function _testCheckDomain ()
	{
		$params = new EppDrs_Api_Client_CheckDomainParams();
		$params->SetName("f00t00morrow.gr");
		$chk_result = $this->eppdrs->CheckDomain($params);
		
		$this->assertNotNull($chk_result);
		$this->assertEqual($chk_result->name, $params->GetName());
		$this->assertNotNull($chk_result->avail);		
	}
	
	function testGetDomainInfoLocal ()
	{
		// params as object
		$params = new EppDrs_Api_Client_GetDomainInfoParams();
		$params->SetName("qout4.no");
		$params->SetMode(EppDrs_Api_Client_GetDomainInfoParams::MODE_LOCAL);
		
		$domain_info = $this->eppdrs->GetDomainInfo($params);
		var_dump($domain_info->asXML());
		
		$this->AssertDomainInfo($domain_info);
		$this->assertEqual($domain_info->localStatus, "Delegated");
	}
	
	function _testGetContactInfo()
	{
		$params = array('clid' => 'C34588046');
		$contactInfo = $this->eppdrs->GetContactInfo($params);
		var_dump($contactInfo);
		$this->eppdrs2->GetContactInfo(array('clid' => 'P-RTV344'));
	}

	function _testUpdateContact()
	{
		$params = array(
			'clid' => 'C34588046', 
			'fields' => array(
				'name' => 'Marat Komarov',
				'street1' => 'Welcome',
				'pc' => '323132',
				'city' => 'Sevastopol',
				'cc' => 'UA',
				'email' => 'marat@webta.net'
			)
		);
		$this->eppdrs->UpdateContact($params);
	}


	function _testGetDomainInfoRegistry ()
	{
		// params as array
		$params = array("name" => "eppdrs-test.ag", "mode" => "registry");
		$domain_info = $this->eppdrs->GetDomainInfo($params);
		print htmlspecialchars($domain_info->asXML());
		$this->AssertDomainInfo($domain_info);
		$this->assertEqual($domain_info->registryStatus, "ok");
	}
	
	function AssertDomainInfo($domain_info)
	{
		$this->assertNotNull($domain_info);
		$this->assertEqual($domain_info->name, "kkolizey.gr");
		$this->assertEqual(count($domain_info->contacts->children()), 2);
		$this->assertEqual($domain_info->contacts->registrant, "141_55215128");
		$this->assertEqual($domain_info->contacts->admin, "141_81338287");
		$this->assertEqual(count($domain_info->ns), 2);
		$this->assertEqual($domain_info->ns[0], "ns.hostdad.com");
		$this->assertEqual($domain_info->ns[1], "ns2.hostdad.com");
		$this->assertEqual($domain_info->locked, "0");
		$this->assertEqual($domain_info->createDate, "2008-09-01 16:09");
		$this->assertEqual($domain_info->expireDate, "2010-08-31 23:59");
	}
	
	function _testListDomains ()
	{
		$params = new EppDrs_Api_Client_ListDomainsParams();
		$params->SetQuery(".gr");
		$params->SetExpireDateRange("2009-07-07", "2010-12-31");
		
		$result = $this->eppdrs->ListDomains($params);
		$this->assertNotNull($result->domainList->getName());
	}

	function _testListContacts ()
	{
		// Admin list
		$params = new EppDrs_Api_Client_ListContactsParams();
		$params->SetQuery("C000");
		$params->SetNoLimits();
		
		$result = $this->eppdrs->ListContacts($params);
		
		$this->assertNotNull($result->contactList->getName());
		
		// Client list
		$params = new EppDrs_Api_Client_ListContactsParams();
		$params->SetQuery("c49");
		$params->SetNoLimits();
		
		$result = $this->eppdrs2->ListContacts($params);
		$this->assertNotNull($result->contactList->getName());
		$this->assertEqual(count($result->contactList->contact), 4);
	}
	
	function _testGetBalanceInfo ()
	{
		$result = $this->eppdrs->GetBalanceInfo(array("userId" => 1));
		$this->assertEqual($result->balance->currency, "EUR");
	}
	
	function _testRenewDomain()
	{
		$params = new EppDrs_Api_Client_RenewDomainParams();
		$params->SetName("webta-test.ag");
		$params->SetPeriod(1);
		//$params->SetUserId(42);
		$result = $this->eppdrs2->RenewDomain($params);
	}
	
	function _testImportDomains ()
	{
		try 
		{
			$db_domain = DBDomain::GetInstance();
			$domain = $db_domain->LoadByName("groovy", "gr");
			$db_domain->Delete($domain);
		}
		catch (Exception $ignore) {}
		
		
		$params = new EppDrs_Api_Client_ImportDomainsParams();
		$params->SetNames(array(
			"b.info", // Exists in database
			"non-existed-domain.gr", 
			"aaa.unknown",
			"groovy"
		));
		$params->SetDefaultTld("gr");
		$params->SetUserId(1);
		
		$result = $this->eppdrs->ImportDomains($params);
		
		$this->assertNotNull($result);
		$this->assertEqual(count($result->importResult), 4);
		// 1
		$this->assertEqual($result->importResult[0]->name, "b.info");
		$this->assertEqual($result->importResult[0]->success, "0");
		// 2
		$this->assertEqual($result->importResult[1]->name, "non-existed-domain.gr");
		$this->assertEqual($result->importResult[1]->success, "0");
		// 3
		$this->assertEqual($result->importResult[2]->name, "aaa.unknown");		
		$this->assertEqual($result->importResult[2]->success, "0");
		// 4
		$this->assertEqual($result->importResult[3]->name, "groovy.gr");
		$this->assertEqual($result->importResult[3]->success, "1");
	}
	
	function _testCreateDomains ()
	{
		// Register domain from admin
		$params = new EppDrs_Api_Client_CreateDomainParams();
		$params->SetUserId(1);
		$params->SetNoBilling(true);		
		
		$params->SetName("imuuu-".rand(100, 999).".no");
		$params->SetPeriod(1);
		$params->SetRegistrant("GL1O");
		$params->SetAdmin("MK10P");
		$params->SetTech("MK10P");
		$params->SetNS(array("ns.google.com", "ns2.google.com"));
		
		$result = $this->eppdrs->CreateDomain($params);
		$this->assertEqual($result->name, $params->GetName());
		$this->assertTrue($result->status == "ok");
		
		// Register domain from client
		$params = new EppDrs_Api_Client_CreateDomainParams();
		$params->SetName("imuuu-".rand(100, 999).".no");
		$params->SetPeriod(1);
		$params->SetRegistrant("GL1O");
		$params->SetAdmin("MK10P");
		$params->SetTech("MK10P");
		$params->SetNS(array("ns.google.com", "ns2.google.com"));
		
		$result = $this->eppdrs2->CreateDomain($params);
		$this->assertEqual($result->name, $params->GetName());
		$this->assertTrue($result->status == "ok");
	}
	
	function _testUpdateDomainContact ()
	{
		$params = new EppDrs_Api_Client_UpdateDomainContactParams();
		$params->SetName("imuuuuy4.no");
		$params->SetContactType(CONTACT_TYPE::ADMIN);
		$params->SetClid("MK10P");
		
		$result = $this->eppdrs->UpdateDomainContact($params);
		
		$this->assertNotNull($result);
	}
	
	function _testTradeDomain ()
	{
		$params = new EppDrs_Api_Client_UpdateDomainContactParams();
		$params->SetName("webta-005.be");
		$params->SetContactType(CONTACT_TYPE::REGISTRANT);
		$params->SetClid("c49027");
		$params->SetNoBilling(true);
		
		$result = $this->eppdrs->UpdateDomainContact($params);
		
		$this->assertNotNull($result);
		
	}
	
	function _testTradeDomain2 ()
	{
		$params = new EppDrs_Api_Client_UpdateDomainContactParams();
		$params->SetName("webta-006.be");
		$params->SetContactType(CONTACT_TYPE::REGISTRANT);
		$params->SetClid("c49027");
		
		$result = $this->eppdrs2->UpdateDomainContact($params);
		
		$this->assertNotNull($result);
	}
	
	function _testUpdateDomainNameservers ()
	{
		$params = new EppDrs_Api_Client_UpdateDomainNameserversParams();
		$params->SetName("imuuuuy4.no");
		$params->SetNS(array("ns2.google.com", "ns2.hostdad.com"));
		
		$result = $this->eppdrs->UpdateDomainNameservers($params);
		
		$this->assertNotNull($result);
		
		$this->eppdrs->UpdateDomainNameservers(array("name" => "imuuuuy4.no"));
	}
	
	function _testUpdateDomainLock ()
	{
		$params = new EppDrs_Api_Client_UpdateDomainLockParams();
		$params->SetName("imuuuuy4.no");
		$params->SetLocked(true);
		
		$result = $this->eppdrs->UpdateDomainLock($params);
		
		$this->assertNotNull($result);
	}
	
	function _testTransferDomain ()
	{
		//$params = new EppDrs_Api_Client_TransferDomainParams();
		//$params->SetUserId(1);
		//$params->SetNoBilling(true);
		
		$params = array(
			"userId" => "42",
			"noBilling" => 1,
			"name" => "luxembourg-a-la-carte.lu",
			"registrant" => "H58225103",
			"admin" => "C89153563",
			"tech" => "62215270",
			"ns" => array("ns1.netsite.lu", "ns2.netsite.lu")
		);
		
		$result = $this->eppdrs->TransferDomain($params);
		print "<pre>";
		print $result->asXML();
		print "</pre>";
		$this->assertNotNull($result);
	}
	
	function _testTransferDomain2 ()
	{
		$params = new EppDrs_Api_Client_TransferDomainParams();
		$params->SetUserId(1);
		$params->SetNoBilling(true);
		
		$params->SetName("api-for-transfer-4.gr");
		$params->SetAuthCode("907908334");
		
		$result = $this->eppdrs2->TransferDomain($params);
		
		$this->assertNotNull($result);
	}	
	
	function _testGetTldInfo ()
	{
		$result = $this->eppdrs->getTldInfo(array("tld" => "lu"));
		$this->assertNotNull($result);
		print $result->asXML();
	}
	
	function _testCreateContact ()
	{
		$params = array(
			"tld" => "ac",
			"type" => "registrant",
			"firstname" => "Marat",
			"lastname" => " Komarov111",
			"org" => "Webta111",
			"street" => "str",
			"pc" => "99022",
			"city" => "Sebastopol",
			"country" => "UA",
			"email" => "marat@local.webta.net",
			"phone" => "+38.111111111"
		);
		$result = $this->eppdrs2->createContact($params);
		$this->assertNotNull($result);
	}
	
	function _testUpdateDomainFlags ()
	{
		$params = array(
			'name' => 'mount-sdb.lu',
			'remove' => array('inactive')
		);
		$result = $this->eppdrs->updateDomainFlags($params);
	}

}
?>