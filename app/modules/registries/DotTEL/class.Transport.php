<?php
	class DotTELTransport extends GenericEPPTransport
	{
		public function Connect ()
		{
			if (is_resource($this->Socket))
				@fclose($this->Socket);
			
			$host = $this->ConnectionConfig->GetFieldByName("ServerHost")->Value;
			$port = $this->ConnectionConfig->GetFieldByName("ServerPort")->Value;
			
			$cert_path = $this->ConnectionConfig->GetFieldByName("SSLCertPath")->Value; 
			$passphrase = $this->ConnectionConfig->GetFieldByName("SSLCertPass")->Value; // ''
									
			$context = stream_context_create(
				array(	'ssl' => array(	'local_cert'=> $cert_path, 'passphrase' => $passphrase ))
			);
			
			$this->Socket = @stream_socket_client("sslv3://{$host}:{$port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

			// If error
			if (!$this->Socket)
				throw new Exception(sprintf(_("Cannot connect to registry server: %s"), $errstr));
			
			// Read greeting message
			$this->ReadGreeting();
			$this->IsConnected = true;
			
			return true;
		}
		
		public function Request ($command, $data = array())
		{
			$Trn = parent::Request($command, $data);
			if ($Trn->Data->response->result->value)
			{
				foreach ($Trn->Data->response->result->value as $XmlValue)
				{
					$text = (string)$XmlValue->text;
					if (! (strpos($text, 'SRS') === 0 || strpos($text, '--') === 0))
					{
						$Trn->ErrMsg .= ". $text";
					}
				}
			}
			
			return $Trn;
		}
	}
?>