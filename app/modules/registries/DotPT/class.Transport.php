<?php

class DotPTTransport extends GenericEPPTransport 
{
	public function Connect ()
	{
		if (is_resource($this->Socket))
			@fclose($this->Socket);
		
		$host = $this->ConnectionConfig->GetFieldByName("ServerHost")->Value;
		$port = $this->ConnectionConfig->GetFieldByName("ServerPort")->Value;
		
		// Open socket to registry server
		$this->Socket = fsockopen("sslv3://{$host}", $port, $errno, $errmsg, 20);
		socket_set_timeout($this->Socket, 10);	
		
		// If error
		if (!$this->Socket)
			throw new Exception(sprintf(_("Cannot connect to registry server. Code: %s. Reason: %s"), $errno, $errmsg));
		
		// Read greeting message
		$this->ReadGreeting();
		$this->IsConnected = true;
		
		return true;
	}
	
	function Request ($command, $data = array())
	{
		$Resp = parent::Request($command, $data);
		if ($Resp->Code != 1000)
		{
			$reason = "{$this->LastResponse->response->result->value->reason}";
			if ($reason)
			{
				$Resp->ErrMsg .= ". $reason";
			}
		}
		return $Resp;
	}
}