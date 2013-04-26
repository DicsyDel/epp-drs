<?php

class DotKZRegistryModule extends GenericEPPRegistryModule implements IRegistryModuleServerPollable 
{
	/**
	 * This method must return current Registry CLID
	 *
	 * @return string
	 */
	public function GetRegistrarID()
	{
		return $this->Config->GetFieldByName("RegistrarCLID")->Value;
	}	
	
	public static function GetConfigurationForm()
	{
		$ConfigurationForm = new DataForm();
		$ConfigurationForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
		$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Passsword", 1));
		$ConfigurationForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
		$ConfigurationForm->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));
		$ConfigurationForm->AppendField( new DataFormField("ClientPrefix", FORM_FIELD_TYPE::TEXT , "Client prefix", 1));
		$ConfigurationForm->AppendField( new DataFormField("RegistrarCLID", FORM_FIELD_TYPE::TEXT , "Registrar clID", 1));
		
		return $ConfigurationForm;
	}	
	
	public function CreateNameserver (Nameserver $ns)
	{
		return new CreateNameserverResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
	}
	
    public function DomainCanBeTransferred(Domain $domain)
    {
    	$Ret = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	
    	$Reg = $this->DomainCanBeRegistered($domain);
    	if (!$Reg->Result)
    	{
    		try
    		{
    			$Remote = $this->GetRemoteDomain($domain);
    			$Ret->Result = $Remote->CLID != $this->GetRegistrarID();
    		}
    		catch (ObjectNotExistsException $e)
    		{
    			$Ret->Result = false;
    		}
    	}
    	else
    	{
    		$Ret->Result = false;
    	}
    	
    	return $Ret;
    }
   
	
	/**
     * Update domain flags (options such as clientUpdateProhibited, clientDeleteProhibited)
     *
     * @param Domain $domain
     * @param IChangelist $changes flags changes
     * @version v1000
     */
    public function UpdateDomainFlags(Domain $domain, IChangelist $changes)
    {
	    $params = array (
			"name"   => $this->MakeNameIDNCompatible($domain->GetHostName())
		);
    	
		list($added, $removed) = $this->FixTransferLonelyFlag($domain, $changes);
		
    	if ($added)
    	{
    		$params['add'] = '<domain:add>' . $this->GetFlagsXML($added) . '</domain:add>';
    	}
    	else
    	{
    		$params['add'] = '';
    	}
    	
    	if ($removed)
    	{
    		$params['rem'] = '<domain:rem>' . $this->GetFlagsXML($removed) . '</domain:rem>';
    	}
    	else
    	{
    		$params['rem'] = '';
    	}
							
		$response = $this->Request("domain-update-flags", $params);
		
		if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			$status = REGISTRY_RESPONSE_STATUS::PENDING;
		elseif ($response->Code == RFC3730_RESULT_CODE::OK)
			$status = REGISTRY_RESPONSE_STATUS::SUCCESS;
		else
			$status = REGISTRY_RESPONSE_STATUS::FAILED;
		 
		$ret = new UpdateDomainFlagsResponse($status, $response->ErrMsg, $response->Code);
		$ret->Result = $status != REGISTRY_RESPONSE_STATUS::FAILED;
		return $ret;
    }

    private function FixTransferLonelyFlag (Domain $Domain, Changelist $Changes)
    {
		$added = $Changes->GetAdded();
    	$removed = $Changes->GetRemoved();
		if (count($added) + count($removed) < 2 && in_array('clientTransferProhibited', array_merge($added, $removed)))
		{
			// Add flag that is presented in domain and not will be removed at this time
			$candidates = array_diff($Domain->GetFlagList(), $removed);
			if ($candidates)
			{
				$added[] = array_shift($candidates); 
			}
			else
			{
				// Remove flag that not presented in domain and not will be added at this time
				$candidates = array_diff($this->epp_flags, $Domain->GetFlagList(), $added);
				if ($candidates)
				{
					$removed[] = array_shift($candidates);
				}
			}
		}
    	return array($added, $removed);
    }
	
    protected $epp_flags = array(
    	'clientHold',
    	'clientUpdateProhibited',
    	'clientDeleteProhibited',
    	'clientTransferProhibited',
    	'clientRenewProhibited'
    );

	public function GetTestConfigurationForm ()
	{
		return self::GetConfigurationForm();
	}
	
	public function RunTest ($DF)
	{
		$Runner = new OteTestRunner();
		$Runner->Run(new DotKZRegistryModule_OteTestSuite(), $DF);
	}	
} 

class DotKZRegistryModule_OteTestSuite extends OteTestSuite 
{
	/**
	 * @var Registry
	 */
	private $Registry;
	
	/**
	 * @var DotKZRegistryModule
	 */
	private $Module;
	
	/**
	 * @var string
	 */
	private $clID;
	
	private $contact_fields;
	
	public function GetName ()
	{
		return 'DotKZ';
	}
	
	public function SetUp (DataForm $TestDF)
	{
		$this->Module = new DotKZRegistryModule(new RegistryManifest(MODULES_PATH . "/registries/DotKZ/module.xml"));
		$this->Module->InitializeModule('kz', $TestDF);
		
		$this->Registry = new Registry($this->Module);
		
		$this->clID = strtolower($TestDF->GetFieldByName('Login')->Value);
		
		$this->contact_fields = array(
			'name' => 'Marat Komarov',
			'org' => 'Webta',
			'email' => 'marat@webta.net',
			'street1' => 'Test street',
			'city' => 'Sebastopol',
			'pc' => '99011',
			'sp' => 'Crimea',
			'cc' => 'UA',
			'voice' => '+380-434-4343223',
			'fax' => '+380-424-5546433'
		);
	}
	
	public function Run ()
	{
		$DbDomain = DBDomain::GetInstance();
		$DbNameserverHost = DBNameserverHost::GetInstance();

		// Cleanup previous execution traces
		try	{
			$this->Module->Request("domain-delete", array("name" => "testeppart.kz"));
		} catch (Exception $e) {}
		try {
			$Domain = $DbDomain->LoadByName('testeppart', 'kz');
			$this->Registry->DeleteDomain($Domain);
		} catch (Exception $e) {}
		
		try	{
			$this->Module->Request("domain-delete", array("name" => "newtesteppart.kz"));
		} catch (Exception $e) {}
		try {
			$Domain = $DbDomain->LoadByName('newtesteppart', 'kz');
			$this->Registry->DeleteDomain($Domain);
		} catch (Exception $e) {}
		
			
		
		//
		// 1. Create domain
		//
		
		$Contact = $this->Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
		$Contact->SetFieldList($this->contact_fields);
		$this->Registry->CreateContact($Contact);
		
		$Domain = $this->Registry->NewDomainInstance();
		$Domain->UserID = 39; 
		$Domain->Name = 'testeppart';
		$Domain->Period = 1;
		$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
		$Domain->SetContact($Contact, CONTACT_TYPE::ADMIN);
		$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
		$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);

		$this->Registry->CreateDomain($Domain, $Domain->Period);
		
		$NS1 = new NameserverHost('ns1.testeppart.kz', '212.110.212.110');
		$this->Registry->CreateNameserver($NS1);

		$NS2 = new NameserverHost('ns2.testeppart.kz', '212.110.111.111');
		$this->Registry->CreateNameserver($NS2); 
				
		$Changelist = $Domain->GetNameserverChangelist();
		$Changelist->Add($NS1);
		$Changelist->Add($NS2);
		$this->Registry->UpdateDomainNameservers($Domain, $Changelist);

		$this->AssertTrue(
			date('Ymd', $Domain->ExpireDate) == date('Ymd', strtotime('+1 year')) &&
			count($Domain->GetNameserverList()) == 2,
			'Create domain'
		);
		
		// Reload domain from Db for correct operations
		$Domain = $DbDomain->LoadByName('testeppart', 'kz');
		$DbNameserverHost->LoadList($Domain->ID);
		
		//
		// 2. Update nameserver host
		//
		
		$nslist = $Domain->GetNameserverList();
		$NS2 = $nslist[1];
		$NS2->IPAddr = '212.111.110.110';
		$this->Registry->UpdateNameserverHost($NS2);
		$this->assertTrue(true, 'Update nameserver host');
		
		
		//
		// 3. Create nameserver host 
		//
		
		$Domain4Host = $this->Registry->NewDomainInstance();
		$Domain4Host->UserID = 39; 
		$Domain4Host->Name = 'newtesteppart';
		$Domain4Host->Period = 1;
		$Domain4Host->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
		$this->Registry->CreateDomain($Domain4Host, $Domain4Host->Period);			
		
		$NS3 = new NameserverHost('ns.newtesteppart.kz', '211.211.211.211');
		$this->Registry->CreateNameserverHost($NS3);
		$this->assertTrue(true, 'Create nameserver host');
		
		//
		// 4. Add nameserver to domain
		//
		
		$Changelist = $Domain->GetNameserverChangelist();
		$Changelist->Add($NS3);
		$this->Registry->UpdateDomainNameservers($Domain, $Changelist);
		$this->assertTrue(count($Domain->GetNameserverList()) == 3, 'Add nameserver to domain');

		//
		// 5. Remove nameserver from domain
		//
		
		$nslist = $Domain->GetNameserverList();
		$NS1 = $nslist[0];
		$Changelist = $Domain->GetNameserverChangelist();
		$Changelist->Remove($NS1);
		$this->Registry->UpdateDomainNameservers($Domain, $Changelist);
		$this->assertTrue(count($Domain->GetNameserverList()) == 2, 'Remove nameserver from domain');
		
		//
		// 6. Delete nameserver host
		//
		try
		{
			$this->Registry->DeleteNameserverHost($NS1);
			$this->assertTrue(true, 'Delete nameserver host');
		} 
		catch (Exception $e)
		{
			$this->assertTrue(true, 'Delete nameserver host failed. Don\'t forget to cheat response code');
		}
		
		//
		// 7. Update contact
		//
		$contact_fields = $Contact->GetFieldList();
		$contact_fields['voice'] = '+380-555-7654321';
		$this->Registry->UpdateContact($Contact);
		$this->assertTrue(true, 'Update contact');
		
		//
		// 8. Start ingoing transfer
		//
		$TrnDomain = $this->Registry->NewDomainInstance();
		$TrnDomain->Name = 'xyz1';
		$TrnDomain->UserID = 39;
		$this->Registry->TransferRequest($TrnDomain, array('pw' => '123456'));
		
		
		$this->Registry->DeleteDomain($Domain);
		$this->Registry->DeleteContact($Contact);
				
	}	
}
?>