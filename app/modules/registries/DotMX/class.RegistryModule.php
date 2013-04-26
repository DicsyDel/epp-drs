<?php

	class DotMXRegistryModule extends GenericEPPRegistryModule implements IRegistryModuleServerPollable
	{
		public static function GetConfigurationForm()
		{
			$CF = new DataForm();
			$CF->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
			$CF->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
			$CF->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$CF->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
			$CF->AppendField( new DataFormField("SSLCertPath", FORM_FIELD_TYPE::TEXT , "Path to SSL certificate", 1));
			$CF->AppendField( new DataFormField("SSLCertPass", FORM_FIELD_TYPE::TEXT , "SSL private key password", 1));
			
			return $CF;
		}

		
		public function ReadMessage ()
		{
			$Resp = $this->Request('poll-request', array());
	
			if ($Resp->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE)
			{
				$msgID = (string)$Resp->Data->response->msgQ->attributes()->id;
				$resData = $Resp->Data->response->resData;
				if ($resData)
				{
					$domData = $resData->children($this->XmlNamespaces['domain']);
					$domData = $domData[0];
					if ($domData->getName() == "panData")
					{
						$transfer_status = (string)$domData->name->attributes("paResult") == "1" ?  
							TRANSFER_STATUS::APPROVED : TRANSFER_STATUS::DECLINED;
					}
					else if ($domData->getName() == "trnData")
					{
						$trStatus = (string)$domData->trStatus;
						switch ($trStatus)
						{
							case self::TRANSFER_CLIENT_APPROVED:
							case self::TRANSFER_SERVER_APPROVED:
								$transfer_status = TRANSFER_STATUS::APPROVED;
								break;
		
							case self::TRANSFER_CLIENT_CANCELLED:
							case self::TRANSFER_SERVER_CANCELLED:
							case self::TRANSFER_CLIENT_REJECTED:
								$transfer_status = TRANSFER_STATUS::DECLINED;
								break;
								
							case self::TRANSFER_PENDING:
							default:								
								$transfer_status = TRANSFER_STATUS::PENDING;
								break;
						}
					}
					else
					{
						$transfer_status = TRANSFER_STATUS::PENDING;
					}

					
					// Domain transfer message
					$Ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$Ret->MsgID = $msgID;
					$Ret->HostName = (string)$domData->name;
					$Ret->TransferStatus = $transfer_status;
					$Ret->RawResponse = $Resp->Data;
					return $Ret;
				}
				else
				{
					$Ret = new PendingOperationResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$Ret->MsgID = $msgID;
					$Ret->RawResponse = $Resp->Data;
					return $Ret;
				}
			}
			
			return false;
		}		
	}
?>