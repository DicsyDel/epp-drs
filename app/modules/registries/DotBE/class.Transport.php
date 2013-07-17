<?php
	class DotBETransport extends GenericEPPTransport
	{		
		protected $ExtPrefix = "dnsbe";		
		protected $ExtNamespace = "http://www.dns.be/xml/epp/dnsbe-1.0";
		protected $ExtSchemaLocation = "http://www.dns.be/xml/epp/dnsbe-1.0 dnsbe-1.0.xsd";
		
		
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
			$Response = parent::Request($command, $data);
			if (!$Response->Succeed)
			{
				$ext = $Response->Data->response->extension->children($this->ExtNamespace);
				if ($ext[0])
				{
					$extMsg = (string)$ext[0]->result->msg;
					if ($Response->ErrMsg)
					{
						$Response->ErrMsg .=  ". {$extMsg}";
					}
					else
					{
						$Response->ErrMsg = $extMsg;
					}
				}
			}
			
			return $Response;
		}
	}
?>