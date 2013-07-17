<?php
	class EnomTransport implements IRegistryTransport 
	{
		private $DumpTraffic;
		
		public function __construct(DataForm $ConnectionConfig)
		{
			if (! $ConnectionConfig instanceof DataForm)
				throw new Exception(_("ConnectionConfig must be an instance of DataForm"));
				
			foreach ($ConnectionConfig->ListFields() as $field)
				$this->{$field->Name} = $field->Value;
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
			$data["Command"] = $command;
			$data['UID'] = $this->Login;
			$data['PW'] = $this->Password;
			$data['responsetype'] = 'xml';
			
			$request = http_build_query($data);
			Log::Log(sprintf("Sending request: %s", $request), E_USER_NOTICE);
			if ($this->DumpTraffic)
			{
				print ">> Sending request:\n";
				print "{$request}\n";				
			}
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($ch, CURLOPT_URL, $this->ServerHost);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
			
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
				
			// Remove unparsable characters, to prevent "PCDATA invalid Char value" error
			$retval = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $retval);
			// Construct XML object
			$Response = new SimpleXMLElement($retval);
			
			if (!$Response)
				throw new Exception(_("Registry returned malformed XML"));
			
				
			if ($Response->ErrCount > 0)
			{
				$errmsg = (string)$Response->errors->Err1;
			}
			
			if ($Response->RRPCode)
			{
				$response_code = (int)$Response->RRPCode;
				
				// Succes when no error messages and RRP code is successful 
				$is_success = !$errmsg && ((int)$response_code >= 200 && (int)$response_code <= 220);
				
				if (!$is_success && !$errmsg)
				{
					// Set error message
					$errmsg = $Response->RRPText;
				}
			}
			else
			{
				$response_code = 1;
				$is_success = !$errmsg;
			}
				
			return new TransportResponse($response_code, $Response, $is_success, $errmsg);
		}
		
		/**
		 * This method close connection with remote registry.
		 * (Send logout request, close socket or something else implementation specific)
		 * 
		 * @return bool 
		 */
		function Disconnect ()
		{
			
		}
		
		/**
		 * Returns True if transport is connected to remote registry
		 *  
		 * @return bool
		 */
		function IsConnected()
		{
			
		}
	}
?>