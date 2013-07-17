<?php

Core::Load("Security/GnuPG");

class SRSPlusTransport implements IRegistryTransport 
{
	/**
	 * Registry module config 
	 */
	private
		$Email, 
		$Login,
		$Host,		
		$TestMode,
		$GPGPass,
		$GPGPath,
		$GPGHomeDir,
		$ProtocolVersion,
		$Fields7BitSafe,
		$DumpTraffic = false;
		
		
	/**
	 * Instance of GnuPG bject
	 *
	 * @var GnuPG
	 */
	private $GPG;
		
	
	public function __construct(DataForm $ConnectionConfig)
	{
		if (! $ConnectionConfig instanceof DataForm)
			throw new Exception(_("ConnectionConfig must be an instance of DataForm"));
			
		
		foreach ($ConnectionConfig->ListFields() as $field)
			$this->{$field->Name} = $field->Value;
			
		$this->ProtocolVersion = '1.1';
		$this->Fields7BitSafe = true;
				
		$this->GPG = new GnuPG($this->GPGPath, $this->GPGHomeDir);
	}
	
	public function SetDumpTraffic ($bool) 
	{
		$this->DumpTraffic = $bool;
	}
		
	/**
	 * This method must establish connection with remote registry
	 * 
	 * @return bool True on success
	 * @throws Exception 
	 */
	function Connect ()
	{
		return true;		
	}
	
	/**
	 * This method must login to remote registry
	 *
	 * @return bool True on success
	 * @throws Exception
	 */
	function Login ()
	{
		return true;
	}
	
	/**
	 * This method performs request to remote registry  
	 *
	 * @param string $command Registry command
	 * @param array $data Command dependent data
	 * @return TransportResponse
	 */
	function Request ($command, $data = array())
	{
		$message = $this->CreateMessage($command, $data);
		
		$enc_message = $this->SignMessage($message);

		// Add log entry
		Log::Log(sprintf("Sending request:\n%s", $message), E_USER_NOTICE);
		if ($this->DumpTraffic)
		{
			print ">> Sending request:\n";
			print "{$message}\n";			
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($ch, CURLOPT_URL, "https://$this->Host/cgi-bin/registry.cgi");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "PAYLOAD={$enc_message}");
		
		$retval = @curl_exec($ch);
		
		$e = curl_error($ch);
		curl_close($ch);
		
		// Log response
		Log::Log(sprintf("Server response:\n%s", $retval), E_USER_NOTICE);
		if ($this->DumpTraffic)
		{
			print "<< Server respond:\n";
			print "{$retval}\n";			
		}		
		
		if ($e)
			throw new Exception($e);

		
		// Make response
		
		if (!$this->GPG->VerifySign($retval))
			throw new Exception(_('Response verification failed'));
			
		// Parse message
		$message = explode("\n", $retval);
		$headers = array();
		$body = array();
		$isbody = false;
		foreach ($message as $string)
		{
			if (trim($string) != '')
			{
				if (stristr($string, "END HEADER"))
					$isbody = true;
					
				if (stristr($string, "BEGIN PGP SIGNATURE"))
					break;
					
				if ($string[0] == "-")
					continue;
					
				$pstring = explode(":", trim($string));	
				$key = trim($pstring[0]);
				$value = trim($pstring[1]);
				if ($key != '')
				{
					if ($isbody)
					{
						if ($headers["SAFE CONTENTS"] != '1')
						{
							$temp = pack("H*", $value);
							$body[$key] = $temp[1];
						}
						else
							$body[$key] = $value;
					}
					else
						$headers[$key] = $value;
				}
			}
		}
		
		// Check message
		if ($headers["STATUS"] == "" || $headers["PROTOCOL VERSION"] == "")
			throw new Exception(_('Registry return mailformed response'));
			

		$is_success = $headers["STATUS"] == "SUCCESS";
		$data = $code = $errmsg = null;
			
		if ($is_success)
			$data = $body;
		else
		{
			$errors = array();
			foreach ($body as $k => $v)
			{
				if(strpos($k, "ERROR") !== false)
					$errors[] = $v;
			}
			if ($errors)
				$errmsg = join('; ', $errors);
			else
				$errmsg = 'Registry not informed details';
		}
		
		return new TransportResponse($code, $data, $is_success, $errmsg);
	}
	
	private function CreateMessage ($command, $data = array())
	{
		$message = "";
		
		// Build headers
		$headers = array(	
			"REGISTRAR" 		=> $this->Login,
			"REGISTRAR EMAIL" 	=> $this->Email,
			"PROTOCOL VERSION" 	=> $this->ProtocolVersion,
			"TIME"				=> time(),
			"ACTION"			=> $command,
			"TRANSACTION ID"	=> time(),
			"PLATFORM"			=> "EPPDRS SRSPlus module",
			"SAFE CONTENTS"		=> $this->Fields7BitSafe
		);
		foreach ($headers as $k => $v)
			$message .= $k.": ".$v."\n";
			
		$message .= "-----END HEADER-----\n";
		
		// Build body
		$keys = array_keys($data);
		foreach ((array)$keys as $key)
		{
			if($this->Fields7BitSafe)
				$message .= sprintf("%s: %s", $key, $data[$key])."\n";
			else 
			{
				$val = unpack("H*", $data[$key]);
				$message .= sprintf("%s: %s", $key, $val[1])."\n";
			}
		}
	   
		return $message;
	}
	
	/**
	 * Add GPG sign to message
	 *
	 * @param string $message
	 * @return string
	 */
	private function SignMessage($message)
	{
		$mess = $this->GPG->MakeSign($this->Email, $this->GPGPass, $this->Email, $message);			
		$encoded_mes = unpack("H*", $mess);
		return $encoded_mes[1];
	}
	
	/**
	 * This method close connection with remote registry.
	 * (Send logout request, close socket or something else implementation specific)
	 * 
	 * @return bool 
	 */
	function Disconnect ()
	{
		return true;
	}
	
	/**
	 * Returns True if transport is connected to remote registry
	 *  
	 * @return bool
	 */
	function IsConnected()
	{
		return true;
	}		
}

?>