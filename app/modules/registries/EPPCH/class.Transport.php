<?php

	class EPPCHTransport extends GenericEPPTransport
	{		
		public function Connect ()
		{
			if (is_resource($this->Socket))
				@fclose($this->Socket);
			
			$host = $this->ConnectionConfig->GetFieldByName("ServerHost")->Value;
			$port = $this->ConnectionConfig->GetFieldByName("ServerPort")->Value;
			
			// Open socket to registry server
			$this->Socket = fsockopen("tls://{$host}", $port, $errno, $errmsg, 20);			
			
			// If error
			if (!$this->Socket)
				throw new Exception(sprintf(_("Cannot connect to registry server. Code: %s. Reason: %s"), $errno, $errmsg));
			
			// Read greeting message
			$this->ReadGreeting();
			$this->IsConnected = true;
			
			return true;
		}
		
		public function Login ()
		{
			if (count($this->ExtURIs) > 0)
			{
				$svcExtension = "<svcExtension>";
				foreach($this->ExtURIs as $URI)
					$svcExtension .= "<extURI>{$URI}</extURI>";
				$svcExtension .= "</svcExtension>";
			}
			else
				$svcExtension = "";
			
			if ($this->ConnectionConfig->GetFieldByName("NewPassword"))
			{
				// Login with password change
				$response = $this->Request("login-ex", array(
					"clid"	=> $this->ConnectionConfig->GetFieldByName("Login")->Value,
					"pw" 	=> $this->ConnectionConfig->GetFieldByName("Password")->Value,
					'newPw' => $this->ConnectionConfig->GetFieldByName("NewPassword")->Value,
					"lang"	=> "en",
					"svcExtension" => $svcExtension
	 			));
			}
			else
			{
				// Usual login
				$response = $this->Request("login", array(
					"clid"	=>	$this->ConnectionConfig->GetFieldByName("Login")->Value,
					"pw" 	=>	$this->ConnectionConfig->GetFieldByName("Password")->Value,
					"lang"	=> "en",
					"svcExtension" => $svcExtension
	 			));
			}

			return $response->Succeed;
		}
	}
?>