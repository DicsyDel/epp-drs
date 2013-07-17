<?php

class EPPNLRegistryModule extends GenericEPPRegistryModule implements IRegistryModuleServerPollable
{
	private $ExtSidn = "http://rxsd.domain-registry.nl/sidn-ext-epp-1.0";
	
	public static function GetConfigurationForm()
	{
		$ConfigurationForm = new DataForm();
		$ConfigurationForm->SetInlineHelp("You must set <b>viaPoll</b> = <b>Yes</b> under <i>Registrar &gt; Update registrar details</i> in your SIDN registrar panel");
		$ConfigurationForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
		$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Passsword", 1));
		$ConfigurationForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
		$ConfigurationForm->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
		$ConfigurationForm->AppendField( new DataFormField("RegistrarCLID", FORM_FIELD_TYPE::TEXT , "Registrar EPP clID", 1));		
		
		return $ConfigurationForm;
	}

	public function GetRegistrarID()
	{
		return $this->Config->GetFieldByName("RegistrarCLID")->Value;
	}	

	
	function BeforeRequest ($command, &$data, $method)
	{
		switch ($command)
		{
			case "contact-create":
			case "contact-update":
				$data['nl-ext-legal-form-regno'] = $data['nl-ext-legal-form-regno'] ? 
					'<sidn-ext-epp:legalFormRegNo>'.$data['nl-ext-legal-form-regno'].'</sidn-ext-epp:legalFormRegNo>' : '';
				break;
		}
	}
	
    public function DomainCanBeTransferred(Domain $domain)
    {
    	$Ret = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	
    	$TransResp = $this->Request("domain-info", array(
    		"name" => $this->MakeNameIDNCompatible($domain->GetHostName()),
    		"authinfo" => ""
    	));
    	$Ret->Result = $TransResp->SidnErrCode == "C0038b";
    	
    	return $Ret;
    }	
	
    function CreateDomain(Domain $domain, $period, $extra = array())
    {
    	$Ret = parent::CreateDomain($domain, $period, $extra);
    	if ($Ret->Succeed())
    	{
    		// Request SIDN for generated auth token
    		$Grd = $this->GetRemoteDomain($domain);
    		$Ret->AuthCode = $Grd->AuthCode;
    	}
    	
    	return $Ret;
    }
    
	function GetRemoteDomain (Domain $domain)
	{
		$Grd = parent::GetRemoteDomain($domain);
		if ($Grd->Succeed())
		{
			$info = $Grd->RawResponse->response->resData->children($this->XmlNamespaces['domain']);
			$info = $info[0];
			
			$up_date = strtotime("{$info->upDate}");
			if (!$up_date) {
				$up_date = $Grd->CreateDate;
			}
			$cr_date_gd = getdate($Grd->CreateDate);
			
			// Manipulations with create date 'm-d' and current date 'Y' 
			$ex_date = mktime(0, 0, 0, $cr_date_gd["mon"], $cr_date_gd["mday"], date("Y"));
			if ($ex_date < $up_date)
				$ex_date = strtotime("+1 year", $ex_date);
			
			$Grd->ExpireDate = $ex_date;
		}
		
		return $Grd;
	}
	
	function GetRemoteContact (Contact $contact)
	{
		$Resp = parent::GetRemoteContact($contact);
		if ($Resp->Succeed())
		{
			$Ext = $Resp->RawResponse->response->extension->children($this->ExtSidn);
			$contact = $Ext[0]->infData->contact;
			$Resp->{"nl-ext-legal-form"} = "{$contact->legalForm}";
			$Resp->{"nl-ext-legal-form-regno"} = "{$contact->legalFormRegNo}";
		}
		
		return $Resp;
	}
	
	/*
	function DeleteDomain(Domain $domain, $executeDate=null)
	{
		$Ret = parent::DeleteDomain($domain, $executeDate);
		
		// In SIDN delete is pending, but EPP server returns 1000  
		if ($Ret->Succeed() || $Ret->Pending())
		{
			$Ret->Status = REGISTRY_RESPONSE_STATUS::PENDING;
		}
		
		return $Ret;
	}
	*/
	
	function OnDomainTransferApproved (Domain $domain)
	{
		// Transfer includes 1 year of registration starting 
		// at the day the transfer was successfully transferred.
		$domain->ExpireDate = strtotime("+1 year");
	}
	
	function OnDomainTransferRequested (Domain $domain)
	{
		$ops = $this->RegistryAccessible->GetPendingOperationList(Registry::OBJ_DOMAIN, $domain->ID);
		foreach ($ops as $op) {
			if ($op->Type == Registry::OP_TRANSFER) {
				$resp = new PollTransferResponse();
				$resp->HostName = $domain->GetHostName();
				$resp->TransferStatus = TRANSFER_STATUS::APPROVED;
				$this->RegistryAccessible->DispatchPollTransfer($resp);
				
				$this->RegistryAccessible->RemovePendingOperation($op->ID);				
				break;
			}
		}
	}

	function ReadMessage ()
	{
		$Resp = $this->Request('poll-request', array());
		
		/*
		$Resp = new stdClass();
		$Resp->Code = RFC3730_RESULT_CODE::OK_ACK_DEQUEUE;
		$Resp->Data = new SimpleXmlElement('<?xml version="1.0" encoding="utf-8"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:sidn-ext-epp="http://rxsd.domain-registry.nl/sidn-ext-epp-1.0"
	xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
	<response>
		<result code="1301">
			<msg>The message has been picked up. Please confirm receipt to remove
				the message from the queue.</msg>
		</result>
		<msgQ count="1" id="100206">
			<qDate>2010-02-11T09:18:46.000Z</qDate>
			<msg>1014 Verhuizen domeinnaam for-transfer-03.nl in behandeling
				genomen</msg>
		</msgQ>
		<resData>
			<sidn-ext-epp:pollData>
				<sidn-ext-epp:command>domain:transfer-start</sidn-ext-epp:command>
				<sidn-ext-epp:data>
					<result code="1000">
						<msg>Transfer of the domain name has begun.</msg>
					</result>
					<resData>
						<domain:trnData>
							<domain:name>for-transfer-03.nl</domain:name>
							<domain:trStatus>pending</domain:trStatus>
							<domain:reID>IWORK</domain:reID>
							<domain:reDate>2010-02-11T09:18:46.000Z</domain:reDate>
							<domain:acID>X1140</domain:acID>
							<domain:acDate>2010-02-11T09:48:46.000Z</domain:acDate>
						</domain:trnData>
					</resData>
					<trID>
						<svTRID>28096172</svTRID>
					</trID>
				</sidn-ext-epp:data>
			</sidn-ext-epp:pollData>
		</resData>
		<trID>
			<clTRID>900398-1265880242</clTRID>
			<svTRID>C54BF9AD-907C-4C5C-F791-AE0CB997716E</svTRID>
		</trID>
	</response>
</epp>');
		$Resp->Data->registerXPathNamespace("domain", $this->XmlNamespaces["domain"]);
		$Resp->Data->registerXPathNamespace("sidn-ext-epp", $this->ExtSidn);
		*/
		
		if ($Resp->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE)
		{
			$msgID = (string)$Resp->Data->response->msgQ->attributes()->id;
			$pollData = $Resp->Data->response->resData->children($this->ExtSidn);
			
			if (count($pollData))
			{
				$pollData = $pollData[0];
				switch ("{$pollData->command}")
				{
					case "domain:transfer-start":
						// XXX: По хорошему не работает
						// $resData->children() возвращает пустой элемент						
						$resData = $pollData->data->children("urn:ietf:params:xml:ns:epp-1.0")->resData;
						$resData = @new SimpleXMLElement($resData->asXML());
						
						// Detect outgoing transfer notification
						if ("{$resData->trnData->reID}" != $this->GetRegistrarID())
						{
							$Ret = new PollOutgoingTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
							$Ret->HostName = "{$resData->trnData->name}";
							$Ret->TransferStatus = OUTGOING_TRANSFER_STATUS::REQUESTED;
						}
						break;
					
					
					case "domain:transfer":
						// XXX: см выше
						$resData = $pollData->data->children("urn:ietf:params:xml:ns:epp-1.0")->resData;
						$resData = @new SimpleXMLElement($resData->asXML());
						
						$is_outgoing = "{$resData->trnData->reID}" != $this->GetRegistrarID();
						
						if ($is_outgoing)
							$Ret = new PollOutgoingTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
						else
							$Ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);	
						
						
						$Ret->HostName = "{$resData->trnData->name}";
						
						// Transfer status
						$trStatus = "{$resData->trnData->trStatus}";
						if ($is_outgoing) 
						{
							$Ret->TransferStatus = OUTGOING_TRANSFER_STATUS::AWAY;
						}
						else
						{
							switch ($trStatus)
							{
								case self::TRANSFER_CLIENT_APPROVED:
								case self::TRANSFER_SERVER_APPROVED:
									$Ret->TransferStatus = TRANSFER_STATUS::APPROVED;
									break;
			
								case self::TRANSFER_CLIENT_CANCELLED:
								case self::TRANSFER_SERVER_CANCELLED:
								case self::TRANSFER_CLIENT_REJECTED:
									$Ret->TransferStatus = TRANSFER_STATUS::DECLINED;
									break;
									
								case self::TRANSFER_PENDING:
									$Ret->TransferStatus = TRANSFER_STATUS::PENDING;
									break;
									
								default:
									$Ret->TransferStatus = TRANSFER_STATUS::FAILED;
							}
						}
						break;
						
					case "domain:delete":
						$Ret = new PollDeleteDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
						
						// Match result
						$msg = "{$Resp->Data->response->msgQ->msg}";
						$Ret->Result = !(bool)preg_match("/transaction for ([^\s]+\.nl) rejected/", $msg);
						
						// Match hostname
						preg_match("/([^\s]+\.nl)\s/", $msg, $matches);
						$Ret->HostName = $matches[1];
						
						// Match fail reason
						if (!$Ret->Result) {
							// XXX: см выше
							$result = $pollData->data->children("urn:ietf:params:xml:ns:epp-1.0");
							$result = @new SimpleXMLElement($result->asXML());
							$Ret->FailReason = "{$result->msg}";
						}
						break;
				}
			}
			
			if (!$Ret)
			{
				$Ret = new PendingOperationResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);			
			}
			$Ret->MsgID = $msgID;
			$Ret->RawResponse = $Resp->Data;
			
			return $Ret;
		}
	}
	
	
}