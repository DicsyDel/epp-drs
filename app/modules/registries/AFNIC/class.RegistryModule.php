<?
	class AFNICRegistryModule extends GenericEPPRegistryModule implements IRegistryModuleServerPollable 
	{
		protected $AfnicNamespace = "http://www.afnic.fr/xml/epp/frnic-1.0";
		
		public static function GetConfigurationForm()
		{
			$Config = new DataForm();
			$Config->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
			$Config->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
			$Config->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$Config->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
			$Config->AppendField( new DataFormField("SSLCertPath", FORM_FIELD_TYPE::TEXT , "Path to SSL certificate", 1));
			$Config->AppendField( new DataFormField("SSLCertPass", FORM_FIELD_TYPE::TEXT , "SSL private key password", 1));
			
			return $Config;
		}


		protected function BeforeRequest($command, &$data, $method)
		{
			switch ($command)
			{
				case "contact-create":
					if ($data["type"] == "P")
					{
						$cr = "<frnic:individualInfos>";
						$cr .= "<frnic:birthDate>".date("Y-m-d", strtotime($data["frnic-birthDate"]))."</frnic:birthDate>";
						if ($data["frnic-birthCity"])
							$cr .= "<frnic:birthCity>{$data["frnic-birthCity"]}</frnic:birthCity>";
						if ($data["frnic-birthPc"])
							$cr .= "<frnic:birthPc>{$data["frnic-birthPc"]}</frnic:birthPc>";
						$cr .= "<frnic:Cc>{$data["frnic-Cc"]}</frnic:birthCc>";
						$cr .= "</frnic:individualInfos>";
						$cr .= "<frnic:firstName>{$data["frnic-firstName"]}</frnic:firstName>";
					}
					elseif ($data["type"] == "O")
					{
						$cr = "<frnic:legalEntityInfos>";
						$cr .= "<frnic:status type=\"{$data["frnic-status"]}\">";
						if ($data["frnic-status"] == "other")
							$cr .= $data["frnic-statusOther"];
						$cr .= "</frnic:status>";
						if ($data["frnic-siren"]) 
							$cr .= "<frnic:siren>{$data["frnic-siren"]}</frnic:siren>";
						if ($data["frnic-trademark"])
							$cr .= "<frnic:trademark>{$data["frnic-trademark"]}</frnic:trademark>";
						$cr .= "</frnic:legalEntityInfos>";
					}
					else
					{
						throw new Exception("Undefined value '{$data["contact"]}' for contact type");
					}
					$data["frnic-create"] = $cr;
					break;
					
				case "domain-trans-request":
					$extra = func_get_arg(4);
					$xml = '';
					foreach (array("admin", "tech") as $k)
						$xml .= '<frnic:contact type="'.$k.'">'.$extra[$k].'</frnic:contact>';
					$data["frnic-transfer"] = $xml;
					break;
			}
		}
		
		public function ChangeDomainOwner(Domain $domain, $period=null, $extra=array())
		{
			$contacts = $domain->GetContactList();
			
			$params = array(
				"name" => $this->MakeNameIDNCompatible($domain->GetHostName()),
				"registrant_id"		=> $contacts['registrant']->CLID
			);
			unset($contacts[CONTACT_TYPE::REGISTRANT]);
			$params['contacts'] = '';
			foreach ($contacts as $contact_type => $contact)
				$params['contacts'] .= '<frnic:contact type="'.$contact_type.'">'.$contact->CLID.'</frnic:contact>';
			
			$Resp = $this->Request("domain-trade", $params);
			
			$Ret = new ChangeDomainOwnerResponse(
				$this->GetResponseStatusFromEPPResponse($Resp), $Resp->ErrMsg, $Resp->Code);
			return $Ret;
		}		
		
		public function CreateDomain(Domain $domain, $period, $extra = array())
		{
			$contacts = $domain->GetContactList();
			
			$params = array(
				"name"				=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"registrant_id"		=> $contacts['registrant']->CLID,
				"period"			=> $period,
				"pw"				=> $domain->AuthCode ? $domain->AuthCode : rand(100000000, 999999999)
			);
			
			unset($contacts[CONTACT_TYPE::REGISTRANT]);
			$params['contacts'] = '';
			foreach ($contacts as $contact_type => $contact)
				$params['contacts'] .= '<domain:contact type="'.$contact_type.'">'.$contact->CLID.'</domain:contact>';

			$this->BeforeRequest('domain-create', $params, __METHOD__, $domain, $period, $extra);
			$response = $this->Request("domain-create", $params);
		
			$resp = new CreateDomainResponse(
				$this->GetResponseStatusFromEPPResponse($response), 
				$response->ErrMsg, $response->Code
			);
			
			if ($response->Succeed)
			{
				// Fill response
				$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
				$info = $info[0];
	
				$resp->CreateDate = $this->StrToTime((string)$info->crDate[0]); 
				$resp->ExpireDate = $this->StrToTime((string)$info->exDate[0]); 
				$resp->AuthCode = (string)$params["pw"];
				
				if ($nameservers = $domain->GetNameserverList()) 
				{
					// XXX AD and PIZDETS
					
					// registration process in AFNIC contains 2 steps:
					// 1. create domain without nameservers; epp code = 1000
					// 2. update created domain with nameservers; epp code = 1001
					
					$Changes = new Changelist(array(), $nameservers);
					$UpdateResponse = $this->UpdateDomainNameservers($domain, $Changes);
					if ($UpdateResponse->Pending())
					{
						// Need this to lock domain update 
						// before we recive operation result via poll message.
						$domain->AddPendingOperation(Registry::OP_UPDATE);
					}
				}
			}
			
			return $resp;
		}

		public function GetRemoteContact(Contact $contact)
		{
			$Response = parent::GetRemoteContact($contact);
			if ($Response->Succeed())
			{
				$frnicExt = $Response->RawResponse->response->extension->children($this->AfnicNamespace);
				$frnicExt = $frnicExt[0];
				if ($frnicExt)
				{
					$frnicContact = $frnicExt->resData->infData->contact;
					if ($info = $frnicContact->individualInfos[0])
					{
						$Response->{"frnic-firstName"} = trim((string)$frnicContact->firstName);
						$Response->{"frnic-birthDate"} = trim((string)$info->birthDate);
						$Response->{"frnic-birthCity"} = trim((string)$info->birthCity);
						$Response->{"frnic-birthPc"} = trim((string)$info->birthPc);
						$Response->{"frnic-birthCc"} = trim((string)$info->birthCc);
					}
					elseif ($info = $frnicContact->legalEntityInfos[0])
					{
						$Response->{"frnic-status"} = (string)$info->status->attributes()->type;
						if ($Response->{"frnic-status"} == "other")
							$Response->{"frnic-statusOther"} = trim((string)$info->status);
						$Response->{"frnic-siren"} = trim((string)$info->siren);
						$Response->{"frnic-trademark"} = trim((string)$info->trademark);
					}
				}
			}
			
			return $Response;
		}

		
		public function RenewDomain (Domain $domain, $extra=array())
		{
			$params = array(
				"name" => $this->MakeNameIDNCompatible($domain->GetHostName())
			);
			$Response = $this->Request("domain-restore", $params);
			$Ret = new RenewDomainResponse($this->GetResponseStatusFromEPPResponse($Response), 
				$Response->ErrMsg, $Response->Code);
			if ($Response->Succeed)
			{
				$Ret->ExpireDate = $domain->ExpireDate + ($domain->Period*86400*365);				
			}
			return $Ret; 
		}
		
		public function ReadMessage ()
		{
			$SuperRet = parent::ReadMessage();
			if (get_class($SuperRet) == "PendingOperationResponse")
			{
				$RawResponse = $SuperRet->RawResponse;
				$data = $RawResponse->response->resData->children($this->XmlNamespaces['domain']);
				$data = $data[0];
				if ($data && $data->getName() == "panData")
				{
					$panData = $data;
					$hostName = (string)$panData->name;
					$result = (int)$panData->name->attributes()->paResult == 1;
					
					if ($resZC = $RawResponse->response->msgQ->resZC[0])
					{
						$Ret = new PollUpdateDomainResponse($SuperRet->Status, $SuperRet->Code, $SuperRet->ErrMsg);
						$Ret->HostName = $hostName;
						$Ret->Result = $result;
						$Ret->FailReason = (string)$resZC;
						return $Ret;
					}
					elseif (strpos($RawResponse->response->msgQ->msg, "Trade completed"))
					{
						$Ret = new ChangeDomainOwnerResponse($SuperRet->Status, $SuperRet->Code, $SuperRet->ErrMsg);
						$Ret->HostName = $hostName;
						$Ret->Result = $result;
						return $Ret;
					}
				}
			}
			return $SuperRet;
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
	}
?>
