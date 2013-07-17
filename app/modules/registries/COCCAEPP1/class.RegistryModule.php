<?

		
	class COCCAEPP1RegistryModule extends GenericEPPRegistryModule implements IRegistryModuleServerPollable 
	{
		public function ReadMessage ()
		{
			$Resp = $this->Request('poll-request', array());

			if ($Resp->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE)
			{
				$msgID = (string)$Resp->Data->response->msgQ->attributes()->id;
				$resData = $Resp->Data->response->resData;
				if ($trnData = $resData->children($this->XmlNamespaces['domain']))
				{
					// Domain transfer message
					$trnData = $trnData[0];
					$trStatus = (string)$trnData->trStatus;
					$reID = (string)$trnData->reID;

					// Test for outgoing transfer message
					if ($reID != $this->Config->GetFieldByName('Login')->Value)
					{
						// Message relates to OUTGOING transfer. skip it.
						$this->Request('poll-ack', array('msgID' => $msgID));
						return $this->ReadMessage();						
					}
					
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
							$transfer_status = TRANSFER_STATUS::PENDING;
							break;
							
						default:
							$transfer_status = TRANSFER_STATUS::FAILED;
					}
					
					$Ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$Ret->MsgID = $msgID;
					$Ret->HostName = (string)$trnData->name;
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
