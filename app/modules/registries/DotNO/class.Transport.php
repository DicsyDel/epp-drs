<?php

	class DotNOTransport extends GenericEPPTransport
	{		
		public function Connect ()
		{
			if (is_resource($this->Socket))
				@fclose($this->Socket);
			
			$host = $this->ConnectionConfig->GetFieldByName("ServerHost")->Value;
			$port = $this->ConnectionConfig->GetFieldByName("ServerPort")->Value;
			
			// Open socket to registry server
			$this->Socket = fsockopen("ssl://{$host}", $port, $errno, $errmsg, 20);
			socket_set_timeout($this->Socket, 10);	
			
			// If error
			if (!$this->Socket)
				throw new Exception(sprintf(_("Cannot connect to registry server. Code: %s. Reason: %s"), $errno, $errmsg));
			
			// Read greeting message
			$this->ReadGreeting();
			$this->IsConnected = true;
			
			return true;
		}
		
		public function Request ($command, $data = array())
		{
			$resp = parent::Request($command, $data);
			if (!$resp->Succeed)
			{
				// Parse errmsg
				$ext = $resp->Data->response->extension;
				if ($ext && $ext->getName() != "")
				{
					$conditions = $ext->children("http://www.norid.no/xsd/no-ext-result-1.0");
					$conditions = $conditions[0];
					$resp->ErrMsg .= ". {$conditions->condition[0]->msg}";
					$resp->ErrMsg .= ". {$conditions->condition[0]->details}";
				}
			}
			return $resp;
		}
	}
?>