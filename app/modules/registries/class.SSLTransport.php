<?php
	class SSLTransport extends GenericEPPTransport
	{
		public function Connect ()
		{
			if (is_resource($this->Socket))
				@fclose($this->Socket);
			
			$host = $this->ConnectionConfig->GetFieldByName("ServerHost")->Value;
			$port = $this->ConnectionConfig->GetFieldByName("ServerPort")->Value;

			if ($this->ConnectionConfig->GetFieldByName("SSLCertPath"))
			{
				$cert_path = $this->ConnectionConfig->GetFieldByName("SSLCertPath")->Value; 
				$passphrase = $this->ConnectionConfig->GetFieldByName("SSLCertPass")->Value;
									
				$context = stream_context_create(
					array('ssl' => array('local_cert'=> $cert_path, 'passphrase' => $passphrase))
				);
			
				$this->Socket = stream_socket_client("sslv3://{$host}:{$port}", $errno, $errstr, 120, STREAM_CLIENT_CONNECT, $context);
			}
			else
			{
				$this->Socket = fsockopen("ssl://{$host}", $port, $errno, $errmsg, 20);
			}

			// If error
			if (!$this->Socket)
				throw new Exception(sprintf(_("Cannot connect to registry server: %s"), $errstr));
			
			// Read greeting message
			$this->ReadGreeting();
			$this->IsConnected = true;
			
			return true;
		}
	}
?>