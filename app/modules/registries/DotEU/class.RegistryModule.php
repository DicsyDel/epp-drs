<?php

class DotEURegistryModule extends GenericEPPRegistryModule
{
	/*
	protected $XmlNamespaces = array
	(
		'domain' 	=> 'http://www.eurid.eu/xml/epp/domain-1.0',
		'contact' 	=> 'http://www.eurid.eu/xml/epp/contact-1.0'
	);
	*/
	
	protected $ExtPrefix = "eurid";		
	protected $ExtNamespace = "http://www.eurid.eu/xml/epp/eurid-1.0";
	protected $ExtSchemaLocation = "http://www.eurid.eu/xml/epp/eurid-1.0 eurid-1.0.xsd";
	protected $ExtFieldPrefix = "eu-";
	protected $ExtDomain = "http://www.eurid.eu/xml/epp/domain-ext-1.0";
	
	
	public static function GetConfigurationForm()
	{
		$Config = new DataForm();
		$Config->AppendField(new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
		$Config->AppendField(new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
		$Config->AppendField(new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
		$Config->AppendField(new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));
		$Config->AppendField(new DataFormField("BillingContactCLID", FORM_FIELD_TYPE::TEXT , "Billing contact CLID", 1, 
				null, null, null, "Create it in EURid registrar panel"));

		return $Config;
	}


	protected function BeforeRequest($command, &$data, $method)
	{
		switch ($command)
		{
			case "contact-create":
				$Contact = func_get_arg(3);
				$data[$this->ExtFieldPrefix . "type"] = $Contact->GroupName;
				break;
				
			case "domain-create":
				$Domain = func_get_arg(3);
				$data['contacts'] = '<domain:contact type="billing">'
					. $this->Config->GetFieldByName('BillingContactCLID')->Value
					. '</domain:contact>';
				$data['extension'] = "<extension>"
      				. "<domain-ext:create xmlns:domain-ext='http://www.eurid.eu/xml/epp/domain-ext-1.0'>"
        			. "<domain-ext:contact type='onsite'>". $Domain->GetContact(CONTACT_TYPE::TECH)->CLID ."</domain-ext:contact>"
     				. "</domain-ext:create>"
   					. "</extension>";
				break;
				
			case "domain-update-contact":
				$data["add"] = str_replace("tech", "onsite", $data["add"]);
				$data["rem"] = str_replace("tech", "onsite", $data["rem"]);
				if (strpos("onsite", $data["add"]) != -1) {
					$data["extension"] = "<extension>"
	      				. "<domain-ext:update xmlns:domain-ext='http://www.eurid.eu/xml/epp/domain-ext-1.0'>"
	      				. str_replace("domain:", "domain-ext:", $data["add"])
	      				. str_replace("domain:", "domain-ext:", $data["rem"])
	      				. "</domain-ext:update>"
	      				. "</extension>";
					$data["add"] = "";
					$data["rem"] = "";
				}
				break;
			
			case "domain-delete":
				$executeDate = func_get_arg(4);
				$data["ext"] = $executeDate ? "<extension>"
      				. "<domain-ext:delete xmlns:domain-ext='http://www.eurid.eu/xml/epp/domain-ext-1.0'>"
        			. "<domain-ext:schedule>"
          			. "<domain-ext:delDate>".date("Y-m-d\TH:i:s", $executeDate).".0Z</domain-ext:delDate>"
        			. "</domain-ext:schedule>"
      				. "</domain-ext:delete>"
    				. "</extension>" : "";
				break;

			case "domain-trans-request":
				$Domain = func_get_arg(3);
				$contacts = $Domain->GetContactList();				
				$data['contacts'] = 
						"<domain-ext:contact type=\"onsite\">" 
						. $contacts["onsite"]->CLID 
						. "</domain-ext:contact>"
						. "<domain-ext:contact type=\"billing\">" 
						. $this->Config->GetFieldByName('BillingContactCLID')->Value 
						. "</domain-ext:contact>";
				if ($contacts["registrant"]) {
					$data["contacts"] .= "<domain-ext:contact type=\"registrant\">" 
						. $contacts["registrant"]->CLID 
						. "</domain-ext:contact>";
				}

				$data["ns"] = $this->GetNSXML($Domain->GetNameserverList(), "domain-ext");

			default:
		}
	}
	
	public function CreateDomain(Domain $domain, $period, $extra = array())
	{
		$resp = parent::CreateDomain($domain, 1, $extra);
		if ($resp->Succeed())
		{
			// DNSBE is an autorenew registry, 
			// To set expiration date send schedulled delete command
			$expire_date = strtotime("+{$period} year");
			$this->DeleteDomain($domain, $expire_date);
			$resp->ExpireDate = $expire_date;
		}
		return $resp;
	}


	
	public function ChangeDomainOwner(Domain $domain, $period=null, $extra=array())
	{
		$params = array
		(
			"name" => $this->MakeNameIDNCompatible($domain->GetHostName()),
			"c_registrant" => $domain->GetContact(CONTACT_TYPE::REGISTRANT)->CLID,
			"c_onsite" => $domain->GetContact(CONTACT_TYPE::TECH)->CLID,
			"c_billing" => $this->Config->GetFieldByName('BillingContactCLID')->Value,
			"ns" => '<'.$this->ExtPrefix.':ns>' . $this->GetNSXML($domain->GetNameserverList()) . '</'.$this->ExtPrefix.':ns>'
		);
		
		$response = $this->Request("domain-trade-request", $params);
		
		$status = $response->Succeed ? 
			REGISTRY_RESPONSE_STATUS::PENDING : REGISTRY_RESPONSE_STATUS::FAILED;  
		
		return new ChangeDomainOwnerResponse($status, $response->ErrMsg, $response->Code); 			
	}
	
	public function GetRemoteDomain(Domain $domain)
	{
		$resp = parent::GetRemoteDomain($domain);
		if ($resp->Succeed())
		{
			// Billing contact is 1 for the whole .EU module
			$resp->BillingContact = "";

			// Process extension 
			$extData = $resp->RawResponse->response->extension->children($this->ExtDomain);
			$resp->TechContact = "{$extData->infData->contact}";
		}
		
		return $resp;
	}
	
	public function GetRemoteContact(Contact $contact)
	{
		$Response = parent::GetRemoteContact($contact);
		if ($Response->Succeed())
		{
			$eppExt = $Response->RawResponse->response->extension->children($this->ExtNamespace);
			if (count($eppExt) && $eppExt = $eppExt[0])
			{
				$Response->{$this->ExtFieldPrefix."vat"} = trim((string)$eppExt->infData->contact->vat);
				$Response->{$this->ExtFieldPrefix."lang"} = trim((string)$eppExt->infData->contact->lang);
			}
		}
		
		return $Response;
	}		
	
	public function RenewDomain(Domain $domain, $extra=array())
	{
		$new_expire_date = strtotime("+{$extra["period"]} year", $domain->ExpireDate);
		$params = array
		(
			"name" 		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
			"deleteDate" => date("Y-m-d\TH:i:s", $new_expire_date),
			"period" => $extra["period"],
			"curExpDate" => date("Y-m-d", $domain->ExpireDate)
		);
		
		$this->BeforeRequest('domain-renew', $params, __METHOD__, $domain, $extra);
		$response = $this->Request("domain-renew", $params);
		
		$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;		
		$resp = new RenewDomainResponse($status, $response->ErrMsg, $response->Code);
		
		if ($response->Succeed)
		{
			$resp->ExpireDate = $new_expire_date; 
		}
		
		return $resp;
	}

	public function DeleteDomain (Domain $domain, $executeDate=null)
	{
		try
		{
			// See http://webta.net/support/admin/replies_view.php?id=1257
			return parent::DeleteDomain($domain, $executeDate);
		}
		catch (ProhibitedTransformException $e)
		{
			return new DeleteDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
		}
	}
	
	private static $TransferTrade = "trade";
	private static $TrasferQuarantine = "transfer-quarantine";
	private static $Transfer = "transfer";
	
	public function TransferRequest(Domain $domain, $extra=array())
	{
		$params = array
		(
			'name' => $this->MakeNameIDNCompatible($domain->GetHostName()),
			'c_registrant' => $extra['registrant'] ? $extra['registrant'] : "#AUTO#",
			'c_onsite' => $extra['tech'],
			'c_billing' => $this->Config->GetFieldByName('BillingContactCLID')->Value,
			'ns' => '<'.$this->ExtPrefix.':ns>' . $this->GetNSXML(array($extra["ns1"], $extra["ns2"])) . '</'.$this->ExtPrefix.':ns>',
			'pw' => $this->EscapeXML(trim($extra["pw"]))
		);
		
		if ($extra["type"] == self::$TransferTrade && $params['c_registrant'] == "#AUTO#")
			throw new Exception(sprintf(_("%s contact required"), "Registrant"));
		
		$res = $this->Request("domain-trans-request", $params);
		
		$status = ($res->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
		return new TransferRequestResponse($status, $res->ErrMsg, $res->Code);					
	}
	
	public function DomainCanBeTransferred(Domain $domain)
    {
    	$Ret = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    
    	$Resp = $this->DomainCanBeRegistered($domain);
    	if ($Resp->Result == false)
    	{
	    	try
	    	{
	    		$Resp = $this->GetRemoteDomain($domain);
	    		if ($Resp->Code == RFC3730_RESULT_CODE::ERR_AUTHORIZE_ERROR)
	    		{
					$Ret->Result = true;
	    		}
				elseif ($Resp->Succeed())
				{
					$eppExt = $Resp->RawResponse->response->extension->children($this->ExtNamespace);
					$pendingTrans = $eppExt[0]->xpath("//".$this->ExtPrefix.":pendingTransaction");
					$Ret->Result = count($pendingTrans) == 0;
				}
	    	}
	    	catch(ObjectNotExistsException $e)
	    	{
	    		$Ret->Result = true;
	    	}
    	}
    	else
    	{
    		$Ret->Result = false;
    	}
    	
    	return $Ret;
    }		
	
	public function NameserverCanBeCreated(Nameserver $ns)
	{
		throw new NotImplementedException();
	}
	
	public function CreateNameserver(Nameserver $ns)
	{
		throw new NotImplementedException();
	}
	
	public function CreateNameserverHost(NameserverHost $ns)
	{
		throw new NotImplementedException();
	}
	
	public function UpdateNameserverHost(NameserverHost $ns)
	{
		throw new NotImplementedException();
	}
	
	public function DeleteNameserverHost(NameserverHost $ns)
	{
		throw new NotImplementedException();			
	}

	public function PollTransfer (Domain $domain)
	{
		try
		{
			$Resp = $this->GetRemoteDomain($domain);

			$trStatus = null;				
			
			// Detect transfer status. 
			if ($Resp->Succeed())
			{
				$eppExt = $Resp->RawResponse->response->extension->children($this->ExtNamespace);
				if (count($eppExt) && $eppExt = $eppExt[0])
				{
					// Check pending transaction
					$transfer = $eppExt->xpath("//{$this->ExtPrefix}:pendingTransaction/{$this->ExtPrefix}:transfer");
					$trade = $eppExt->xpath("//{$this->ExtPrefix}:pendingTransaction/{$this->ExtPrefix}:trade");
					
					if ($transfer[0] || $trade[0])
					{
						$pendingTrans = $transfer[0] ? $transfer[0] : $trade[0];
						$children = $pendingTrans->children($this->ExtNamespace);
						foreach ($children as $node)
						{
							if ("status" == $node->getName() && "NotYetApproved" == (string)$node)
							{
								$trStatus = TRANSFER_STATUS::PENDING;
							}
						} 
					}
				}
				
				if ($Resp->CLID == $this->GetRegistrarID())
				{
					$trStatus = TRANSFER_STATUS::APPROVED;
				}
			}
			else if ($Resp->Code == RFC3730_RESULT_CODE::ERR_AUTHORIZE_ERROR)
			{
				// Client has no permissions for this object...
				// Because transfer failed or was not initiated, dude!					
				$trStatus = TRANSFER_STATUS::FAILED;
			}
			
			// If transfer status was detected, 
			// construct response and return it to the uplevel code.
			if ($trStatus !== null)
			{
				$Ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS, $Resp->ErrMsg, $Resp->Code);
				$Ret->HostName = $domain->GetHostName();
				$Ret->TransferStatus = $trStatus;
				return $Ret; 
			}
			
		}
		catch (Exception $e)
		{
			Log::Log($e->getMessage(), E_USER_ERROR);
		}
	}
	
	public function PollCreateDomain (Domain $domain) {}
	
	public function PollDeleteDomain (Domain $domain) {}

	public function PollChangeDomainOwner (Domain $domain) 
	{
		$resp = $this->GetRemoteDomain($domain);
		if ($resp->Succeed())
		{
			$eppExt = $resp->RawResponse->response->extension->children($this->ExtNamespace);
			if (count($eppExt) && $eppExt = $eppExt[0])
			{
				$trade = $eppExt->xpath("//{$this->ExtPrefix}:pendingTransaction/{$this->ExtPrefix}:trade");
				if (!count($trade))
				{
					try
					{
						$db = Core::GetDBInstance();
						$DbDomain = DBDomain::GetInstance();
													
						$StoredDomain = $DbDomain->LoadByName($domain->Name, $domain->Extension);							
						$operation_row = $db->GetRow(
							"SELECT * FROM pending_operations WHERE objectid = ? AND operation = ? AND objecttype = ?",
							array($StoredDomain->ID, Registry::OP_TRADE, Registry::OBJ_DOMAIN)
						);
						$After = unserialize($operation_row["object_after"]);
						$registrant_new_clid = $After->GetContact(CONTACT_TYPE::REGISTRANT)->CLID; 

						$ret = new PollChangeDomainOwnerResponse(REGISTRY_RESPONSE_STATUS::SUCCESS, $resp->ErrMsg, $resp->Code);
						$ret->Result = $resp->RegistrantContact == $registrant_new_clid;
						$ret->HostName = $domain->GetHostName();
						return $ret; 
					}
					catch (Exception $e)
					{
						return new PollChangeDomainOwnerResponse(REGISTRY_RESPONSE_STATUS::FAILED, $e->getMessage());
					}
				}
			}
		}
	}		
	
	public function PollUpdateDomain (Domain $domain) {}
	
	public function PollDeleteContact (Contact $contact) {}
	
	public function PollDeleteNamserverHost (NamserverHost $nshost) {}						
}
?>
