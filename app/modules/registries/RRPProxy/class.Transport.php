<?php
 
	class RRPProxyTransport implements IRegistryTransport
	{
		private $ConnectionConfig;
		private $IsConnected;
		private $DumpTraffic;

		/**
		 * Transport constructor
		 *
		 * @param DataForm $ConnectionConfig
		 */
		public function __construct(DataForm $ConnectionConfig)
		{
			if ($ConnectionConfig instanceof DataForm)
				$this->ConnectionConfig = $ConnectionConfig;
			else
				throw new Exception(_("ConnectionConfig must be an instance of DataForm"));
		}
		
		public function SetDumpTraffic ($bool) 
		{
			$this->DumpTraffic = $bool;
		}		
		
		public function Connect ()
		{
			return true;
		}
	
		public function IsConnected()
		{
			return true;
		}
		
		public function Login ()
		{
			return true;
		}
		
		/**
		 * Perform request to registry server
		 *
		 * @param string $command
		 * @param array $data
		 * @return TransportResponse
		 * @throws Exception
		 */
		public function Request ($command, $data = array())
		{
			// Prepare request string
			$request = $this->PrepareCommand($command, $data);
			
			// Add log entry
			Log::Log(sprintf("Sending request:\n%s", $request), E_USER_NOTICE);
			if ($this->DumpTraffic)
			{
				print ">> Sending request:\n";
				print "{$request}\n";
			}

			// Request
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL => $this->ConnectionConfig->GetFieldByName("APIUrl")->Value,
				CURLOPT_POST => 1,
				CURLOPT_HEADER => 1,
				CURLOPT_POSTFIELDS => $request,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYHOST => 0, 
				CURLOPT_SSL_VERIFYPEER => 0			
			));
			$full_response = curl_exec($ch);
			if ($full_response === false) {
				throw Exception(curl_error($ch));
			}
			curl_close($ch);
			
			
			// Log response
			Log::Log(sprintf("Server response:\n%s", $full_response), E_USER_NOTICE);
			if ($this->DumpTraffic)
			{
				print "<< Server respond:\n";
				print "{$full_response}\n";
			}
			
			$chunks = explode("[RESPONSE]", $full_response);
			$res = trim($chunks[1]);
			
			preg_match("/code[\s]*=[\s]*([0-9]+)/si", $res, $matches);
			$response_code = $matches[1];
						
			preg_match("/description[\s]*=[\s]*(.*?)\n/si", $res, $matches);
			$errmsg = $matches[1];
			
			if (!$errmsg)
			{
				preg_match_all("/<title>(.*?)<\/title>/si", $full_response, $matches);
				$errmsg = $matches[1];
				if (!$errmsg)
					$errmsg = "Unknown error. See log for more information.";
			}
				
			if ($response_code == 545)
				throw new ObjectNotExistsException();
			
			$is_success = ((int)$response_code >= 200 && (int)$response_code <= 220);
					
			return new TransportResponse($response_code, $res, $is_success, $errmsg);
		}
		
		public function Disconnect ()
		{
			return true;
		}
		
		private function PrepareCommand($command, $tags = array())
		{
			$tags["command"] = $command;
			$tags["s_login"] = $this->ConnectionConfig->GetFieldByName("Login")->Value;
			$tags["s_pw"] = $this->ConnectionConfig->GetFieldByName("Password")->Value;
			
			$url_info = parse_url($this->ConnectionConfig->GetFieldByName("APIUrl")->Value);			
			if ($url_info['query'])
			{
				parse_str($url_info['query'], $arr);
				$tags = array_merge($tags, $arr);
			}			
			
			return http_build_query($tags);
		}
	}
?>