<?php
/*
require_once (dirname(__FILE__) . '/class.AbstractRegistryModule.php');
require_once (dirname(__FILE__) . "/class.Domain.php");
require_once (dirname(__FILE__) . "/class.DBDomain.php");
require_once (dirname(__FILE__) . '/class.Contact.php');
require_once (dirname(__FILE__) . '/class.DBContact.php');
require_once (dirname(__FILE__) . '/class.Nameserver.php');
require_once (dirname(__FILE__) . '/class.PendingOperation.php');
require_once (dirname(__FILE__) . '/class.RegistryAccessible.php');
require_once (dirname(__FILE__) . '/class.Registry.php');
*/


//require_once (dirname(__FILE__) . '/class.Phone.php');
//require_once (dirname(__FILE__) . '/Verisign/class.RegistryModule.php');
//require_once (dirname(__FILE__) . '/Verisign/class.Transport.php');


class RegistryTests extends UnitTestCase 
{
	/**
	 * @var Registry
	 */
	private $Registry, $Registry2, $SRSPlusRegistry;
	
	private $ContactFields = array();
	
	/**
	 * @var DBContact
	 */
	private $DBContact;
	
	/**
	 * @var DBDomain
	 */
	private $DBDomain;
	
	function setUp ()
	{
		// OT&E1
		/*
		$DataForm = new DataForm();
		$DataForm->AppendField(new DataFormField('ServerHost', 'text', null, null, null, null, 'web3.hostdad.com'));
		$DataForm->AppendField(new DataFormField('Login', 'text', null, null, null, null, 'bindrop1-admin'));
		$DataForm->AppendField(new DataFormField('Password', 'text', null, null, null, null, 'fR3#w$9aT*'));
		$DataForm->AppendField(new DataFormField('ServerPort', 'text', null, null, null, null, '700'));
		$DataForm->AppendField(new DataFormField('SSLCertPath', 'text', null, null, null, null, '/home/marat/webdev/epp-drs/branches/v1000/app/modules/registries/Verisign/ssl/certchain.pem'));
		$DataForm->AppendField(new DataFormField('SSLCertPass', 'text', null, null, null, null, ''));
		
		$Module = new VerisignRegistryModule();
		$Module->InitializeModule('net', $DataForm);
		$this->Registry = new Registry($Module);
		
		
		$this->ContactFields = array(
			'firstname' => 'Marat',
			'lastname' => 'Komarov',
			'kind' => 'individual',
			'org' => 'Webta',
			'email' => 'marat@webta.net',
			'street1' => 'Test street',
			'street2' => 'Test street 2',
			'city' => 'Sebastopol',
			'pc' => '99011',
			'sp' => 'Crimea',
			'cc' => 'UA',
			'voice' => '123-434-4343223',
		);

		$this->DBContact = DBContact::GetInstance();
		$this->DBDomain = DBDomain::GetInstance();
		*/
	}
	
	function testDataFormValidation ()
	{
		$xmlstr = <<<XML
<fields>
	<field iseditable="1" description="Phone" type="phone" registry_format="+[2].[4-16]" name="Phone" required="1" />
	<field iseditable="1" type="select" name="asia_identform" description="Form of Identity" required="0">
		<values>
			<value name="Certificate of Incorp" value="certificate"/>
			<value name="Charter" value="legislation"/>
			<value name="Societies Registry" value="societyRegistry"/>
			<value name="Political Party Registry" value="politicalPartyRegistry"/>
			<value name="Passport/Citizenship ID" value="passport"/>
			<value name="Other" value="other"/>
		</values>
	</field>
	<!--  required if asia_identform=other -->
	<field iseditable="1" type="text" name="asia_otheridentform" description="Other Identification Form" required="0" note="Required when form of identity is 'Other'"/>
</fields>		
XML;
		$manifest = simplexml_load_string($xmlstr);
		
		$dform = new DataForm();
		$dform->AppendFromXML($manifest);
		$dform->AddXMLValidator($manifest);
		$dform->AddValidator(create_function(
			'$name, $value, $data', 
			'if (!$data["asia_identform"] && !$value) return "identform or otheridentform must be set";'
		), "asia_otheridentform");
		$err = $dform->Validate(array());
		var_dump($err);
		return;
		
		$manifest = simplexml_load_string('<fields><field iseditable="0" description="Full name" type="text" name="name" required="1" minlength="3" maxlength="255" /></fields>');
		
		$err = $dform->ValidateOverManifest($manifest, array("name" => "ma"));
		var_dump($err);
		
		$dform->Bind(array("name" => "vv"));
		$err = $dform->ValidateOverManifest($manifest, array("name" => "ma"));
		var_dump($err);
	}
	
	function _testJWhois ()
	{
		$Whois = JWhois::GetInstance();
		print "<pre>";
		print $Whois->Whois("aws11.net");
		print "</pre>";
	}
	
	function _testPhone ()
	{
		$Phone = Phone::GetInstance();
		$phone = $Phone->PhoneToE164('+41-335-555555');
		$this->assertTrue('+41.335555555');
	}
	
	function _testIDN ()
	{
		$hostname = file_get_contents(dirname(__FILE__) . '/idn-domain-name.txt');
		
		$this->assertTrue($this->Registry->IsIDNHostName($hostname), 'IDN hostname');
		
		$hostname = 'gui.ru';
		$this->assertFalse($this->Registry->IsIDNHostName($hostname), 'not IDN hostname');
	}
	
	
	function _testErrorList ()
	{
		$ErrList = new ErrorList();
		$ErrList->AddMessage('Invalid parameter value: 3');
		$ErrList->AddMessage('Access denied for user piska');
		throw $ErrList;
	}
	
	function _testRRPResponse ()
	{
		$response = new stdClass;
$response->Data = "
HTTP/1.1 200 OK
Date: Mon, 24 Mar 2008 16:22:01 GMT
Server: Apache/2.2.3
Transfer-Encoding: chunked
Content-Type: text/plain; charset=UTF-8

[RESPONSE]
code = 200
description = Command completed successfully []
property[registration expiration date][0] = 2009-03-24 16:22:02.0
property[renewal date][0] = 2009-05-06 16:22:02.0
property[status][0] = ACTIVE

queuetime=0
runtime=0.781

EOF


0
";

		preg_match("/property\[registration expiration date\]\[0\][\s]*=[\s]*(.*?)\n/si", $response->Data, $matches);
		$time = strtotime($matches[1]);

		$resp->CreateDate = $time-(date("z", $time)*86400);
		$resp->ExpireDate = $time;
				
		var_dump(date('Y-m-d H:i:s', $resp->CreateDate));
		var_dump(date('Y-m-d H:i:s', $resp->ExpireDate));
		die();
		
		
	}
	
	
	/*
	function testContactPersistence ()
	{
		$Contact = $this->SRSPlusRegistry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Contact->UserID = '3';
		$Contact->SetFieldList($this->ContactFields);
		$this->DBContact->Save($Contact);
		
		$LoadedContact = $this->DBContact->Load($Contact->ID);
		
		var_export($LoadedContact);
		
		$this->assertTrue($Contact == $LoadedContact, 'Contact persistence');
		
		$this->DBContact->Delete($Contact);
	}
	*/
	
	/*
	function testContactFields ()
	{
		$fields = $this->ContactFields; 
		$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		
		$fields['voice'] = '4332'; // invalid voice
		$Contact->SetFieldList($fields, false);
		$this->DBContact->Save($Contact);
		$this->assertFalse($Contact->StrictFields, 'Fields set non strict');		
		
		$Contact = $this->DBContact->LoadByCLID($Contact->CLID);
		$this->assertFalse($Contact->StrictFields, 'Fields loaded non strict');
		
		$fields = $Contact->SetFieldList($this->ContactFields);
		$this->DBContact->Save($Contact);
		$this->assertTrue($Contact->StrictFields, 'Fields strict');

		$Contact = $this->DBContact->LoadByCLID($Contact->CLID);
		$this->assertTrue($Contact->StrictFields, 'Fields loaded strict');
		
	}
	*/

	function _testPhoneField ()
	{
		$e164_phone = '+380.507243484';
		$phone = '+380-50-7243484';

		$Phone = Phone::GetInstance();		
		
		$this->assertTrue(
			$Phone->PhoneToE164($phone) == $e164_phone,
			'phone to e164'
		);
		
		$this->assertTrue(
			$Phone->IsPhone($phone),
			'phone is phone.. la lay lalala'
		);
		
		$this->assertTrue(
			$Phone->IsE164($e164_phone),
			'phone is e164'
		);
		
		
		$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
		
		$field_list = $this->ContactFields;
		$field_list['voice'] 	= $phone;
		$field_list['fax'] 		= '';
		
		
		$Contact->SetFieldList($field_list);
		
		try
		{
			
		}
		catch (ErrorList $e)
		{
			var_dump($e->GetAllMessages());
		}	
		
		$this->DBContact->Save($Contact);
		
		
		/*
		$this->assertTrue(
			$Contact->GetField('voice') == $phone_e164 &&
			$Contact->GetField('voice_display') == $phone,
			'Set phone'
		);
		*/
	}
	
	
	function _testGenerateCLID ()
	{
		return;
		
		$DBContact = DBContact::GetInstance();
		$clid = $DBContact->GenerateCLID('H%d8');
		$this->assertTrue(preg_match('/^H\d{8}$/', $clid) == true, 'Generate CLID');
	}
	
	
	
	/*
	function testChangelist ()
	{
		$list = array('mountain', 'city', 'sheep');
		$changedList = array('lake', 'city');

		$changes = new ChangeList($list, $changedList);
		
		$this->assertTrue(
			$changes->GetAdded() == array('lake') &&
			$changes->GetRemoved() == array('mountain', 'sheep') &&
			$changes->GetList() == $changedList,
			
			'Changelist with origin and changes'
		);
	}
	*/
	
	/*
	function testDomainExtra ()
	{
		$Domain = DBDomain::GetInstance()->Load(39, $this->Registry->GetManifest());
		$this->assertTrue($Domain->comment == 'fgdhfhdf', '������� ����������');
		DBDomain::GetInstance()->Save($Domain);
		die();
	}
	*/
	
	/*
	function testDBDelete ()
	{
		$DBDomain = DBDomain::GetInstance();
		$DBContact = DBContact::GetInstance();
		$id = 70;
		
		if ($DBDomain->Find($id))
		{
			$Domain = $DBDomain->Load($id, $this->Registry->GetManifest());
			$DBDomain->Delete($Domain);
			
			$this->assertFalse(
				$DBDomain->Find($id),
				'Delete domain from DB'
			);
		}
	}
	*/
	
	/*
	function testPersistence ()
	{
		$dbdomain = DBDomain::GetInstance();
		$domain = $dbdomain->Load(71, $this->Registry->GetManifest());
		var_dump($domain->GetContactList());
		
		var_dump($domain->GetContact(CONTACT_TYPE::BILLING));
	}
	*/
	
	/*
	function testParse ()
	{
		$xml = simplexml_load_file(dirname(__FILE__).'/EPPLU/response.xml');
		//var_dump($xml->xpath('//@xmlns:contact'));
		
		$creData = $xml->response->resData->children('urn:ietf:params:xml:ns:contact-1.0');
		$creData = $creData[0];
		
		$clid = (string)$creData->id[0];
		
		var_dump($clid);
		die();
	}
	*/
	
	function _testGetRemoteContact ()
	{
		return;
		
		$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Contact->CLID = 'H26523637';
		$Contact = $this->Registry->GetRemoteContact($Contact);
		
		$discloses = $Contact->GetDiscloseList();
		$fields = $Contact->GetFieldList();
		
		$this->assertTrue(
			$Contact->CLID == 'H26523637' &&
			
			// fields
			$fields['name'] == 'marat komarov' &&
			$fields['isorg'] == '0' &&
			$fields['cc'] == 'UA' &&
			$fields['city'] == 'sebastopol' &&	
		
			
			// discloses
			$discloses['name'] == true &&
			$discloses['attr'] == false,
			
			'Get remote contact'
		);
		
		die();
	}
	
	function _testContact ()
	{
		return;
		
		$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
	}
	
	function _testCreateDomain ()
	{
		return;
		
		// Registrant contact
		$Registrant = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Registrant->SetFieldList(array(
       		'name' 		=> 'marat komarov',
       		'isorg' 	=> '0',
       		'cc' 		=> 'UA',
       		'city' 		=> 'sebastopol',
			'pc' 		=> '3212',
			'street1' 	=> 'bbfdgfd fds',
			'street2' 	=> 'dsf fd d'
		));
		$Registrant->SetDiscloseList(array('name' => true, 'addr' => false));
		
		$this->Registry->CreateContact($Registrant);
		
		/*
		
							<field description="Name" type="text" name="name" required="1" minlength="3" maxlength="255" />
					<field description="Type" type="select" name="isorg" required="1" minlength="3" maxlength="255">
						<values name="Person" key="0" />
						<values name="Organization" key="1" />
					</field>
					<field description="Country" type="select" name="cc" required="1" minlength="2" maxlength="2" pattern="/^[A-Za-z]{2}$/">
						<database table="countries" key="code" name="name" />
					</field>
					<field description="City" type="text" name="city" required="1" minlength="2" maxlength="255" />
					<field description="Postal code" type="text" name="pc" required="1" minlength="2" maxlength="16" />
					<field description="Address 1" type="text" name="street1" required="1" minlength="3" maxlength="255" />
					<field description="Address 2" type="text" name="street2" required="0" minlength="3" maxlength="255" />
					<disclose>
						<option name="name" description="Name" />
						<option name="addr" description="Address" />
					</disclose>
		*/
		
		
		// Tech contact
		$Tech = $this->Registry->NewContactInstance(CONTACT_TYPE::TECH);
		$Tech->SetFieldList($Registrant->GetFieldList());
		$Tech->SetDiscloseList($Registrant->GetDiscloseList());
		$this->Registry->CreateContact($Tech);

		// Admin contact
		$Admin = $this->Registry->NewContactInstance(CONTACT_TYPE::ADMIN);
		$Admin->SetFieldList($Registrant->GetFieldList());
		$Admin->SetDiscloseList($Registrant->GetDiscloseList());
		$this->Registry->CreateContact($Admin);
		
		// Billing contact
		$Billing = $this->Registry->NewContactInstance(CONTACT_TYPE::BILLING);
		$Billing->SetFieldList($Registrant->GetFieldList());
		$Billing->SetDiscloseList($Registrant->GetDiscloseList());
		$this->Registry->CreateContact($Billing);
		
		
		// New domain
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat' . rand(0, 9999);
		
		// Set contact list
		$Domain->SetContact($Registrant);
		$Domain->SetContact($Admin);
		$Domain->SetContact($Billing);
		$Domain->SetContact($Tech);

		// Set nameservers
		$Domain->SetNameserverList(array(
			new Nameserver('ns1.google.com'),
			new Nameserver('ns2.google.com')
		));
		
		$Domain->comment = 'bla bla';
		
		// Create it
		$this->Registry->CreateDomain($Domain, 2);
		
		$this->assertTrue(
			$Domain->ID != null &&
			$Domain->Period == 2 &&
			$Domain->Status == DOMAIN_STATUS::REGISTRATION_PENDING,
			'Create domain'
		);
	}

	
	/*
	function testTransfer ()
	{
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat2345';
		$Domain->AuthCode = '123456';
		
		$this->Registry->TransferRequest($Domain);
		
		$this->assertTrue(
			$Domain->Status = DOMAIN_STATUS::AWAITING_TRANSFER_AUTHORIZATION &&
			date('Ymd', $Domain->TransferDate) == date('Ymd'),
			$Domain->ID != null,
			'Transfer request'
		);
	}
	*/
	

	/*
	function testTransferApprove ()
	{
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat2345';
		$Domain->AuthCode = '123456';
		
		try
		{
			$ok = $this->Registry2->TransferApprove($Domain);
			$this->assertTrue($ok == true, 'Transfer approved');
		}
		catch (Exception $e)
		{
			var_dump($e->getMessage());
		}
	}
	*/

	
	/*
	function testTransferReject ()
	{
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->Name = 'marat1234';
		$Domain->AuthCode = '123456';
		
		try
		{
			$ok = $this->Registry2->TransferReject($Domain);
			$this->assertTrue($ok == true, 'Transfer rejected');
		}
		catch (Exception $e)
		{
			var_dump($e->getMessage());
		}
	}
	*/
	
	/*
	function testDispatchPending ()
	{
		$this->Registry->AttachObserver(new TestListener());
		$this->Registry->DispatchPendingOperations();
	}
	*/
}

class TestListener extends RegistryObserverAdapter
{
	public function OnDomainCreated (Domain $domain)
	{
		var_dump('domain created: ' . $domain->GetHostName());
	}
	
	public function OnDomainTransferApproved (Domain $domain)
	{
		var_dump('transfer approved: ' . $domain->GetHostName());
	}
	
	public function OnDomainTransferDeclined (Domain $domain)
	{
		var_dump('transfer declined: ' . $domain->GetHostName());
	}
	
	public function OnDomainTransferFailed (Domain $domain)
	{
		var_dump('transfer failed: ' . $domain->GetHostName());
	}
	
}

?>