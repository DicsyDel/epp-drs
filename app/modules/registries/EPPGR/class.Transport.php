<?php

	class EPPGRTransport extends GenericEPPTransport
	{
		private $SessionCookie;

		public function Connect ()
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
			$request_xml = $this->ParseTemplate($command, $data);

			// Add log entry
			Log::Log(sprintf("Sending request:\n%s", $request_xml), E_USER_NOTICE);
			if ($this->DumpTraffic && $command != 'login')
			{
				print ">> Sending request:\n";
				print "{$request_xml}\n";				
			}			
					
			// Init CURL object
			$ch = curl_init();

			curl_setopt ($ch, CURLOPT_URL, $this->ConnectionConfig->GetFieldByName("ServerHost")->Value);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $request_xml);
			if ($this->SessionCookie)
			{
				curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Cookie: {$this->SessionCookie}"));
				$this->IsConnected = true;
			}
	
				
		    // SSL stuff
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);
			if ($this->ConnectionConfig->GetFieldByName("UseSSLCert")->Value == 1)
			{
                curl_setopt ($ch, CURLOPT_CAINFO, $this->ConnectionConfig->GetFieldByName("SSLCertPath")->Value);
                curl_setopt ($ch, CURLOPT_SSLCERTPASSWD, $this->ConnectionConfig->GetFieldByName("SSLpwd")->Value);
			}
			// GR after last update(2009-07-29) uses certificate that doesn't match hostname. 
			// Disable validation to get it works 
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
   			
			// Misc
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_POST, 1);
			@curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt ($ch, CURLOPT_HEADER, 1);
			curl_setopt ($ch, CURLOPT_VERBOSE, 0);
	
			// Execute request
			$res = curl_exec($ch);

			
			// Log response
			Log::Log(sprintf("Server response:\n%s", $res), E_USER_NOTICE);
			if ($this->DumpTraffic && $command != 'login')
			{
				print "<< Server respond:\n";
				print "{$res}\n";
			}
						
			// Get cookie and save
			preg_match("/Set-Cookie\:(.*?)\n/i", $res, $m);
			if ($m[1])
				$this->SessionCookie = $m[1];
	
			$error = curl_error($ch);
		
			
			curl_close($ch);
			
			if ($command == "logout")
				return;
			
			if ($error)
				throw new Exception($error);

			// Process response
			$resp = explode("\n<?", $res);
			$response = "<?{$resp[1]}";
			
			//if ($command == 'domain-info')
			//{
			/*	$response = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"><response><result code="2303"><msg>Object does not exist</msg></result><trID><svTRID>20080514-122419340-603-gr</svTRID></trID></response></epp>'; */
			//}
			
			//
			// TODO: ResultObject
			//
			
			//
			// Check for errors
			//
			
			$xml = new SimpleXMLElement($response);
			if (!$xml)
				throw new Exception(_("Registry return malformed XML"));
				
			$result_attributes = $xml->response->result->attributes();
			$response_code = (string)$result_attributes["code"];
			
			if ($response_code == RFC3730_RESULT_CODE::ERR_CMD_FAILED || 
				$response_code == RFC3730_RESULT_CODE::ERR_CMD_FAILED_END_SESSION)
				throw new Exception(_("Registry error"));
		
			
			if ($response_code == RFC3730_RESULT_CODE::ERR_CMD_FAILED_END_SESSION ||
				$response_code == RFC3730_RESULT_CODE::ERR_AUTH_END_SESSION ||
				$response_code == RFC3730_RESULT_CODE::OK_END_SESSION ||
				$response_code == RFC3730_RESULT_CODE::ERR_SESSION_LIMIT_EXCEEDED) 
				$this->IsConnected = false;
			
			if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_NOT_EXISTS)
				throw new ObjectNotExistsException();
			if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_STATUS_PROHIBITS_OP)
				throw new ProhibitedTransformException();
			if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_EXISTS)
				throw new ObjectExistsException();
				
			$ok_codes = array(	RFC3730_RESULT_CODE::OK, 
								RFC3730_RESULT_CODE::OK_ACK_DEQUEUE, 
								RFC3730_RESULT_CODE::OK_END_SESSION,
								RFC3730_RESULT_CODE::OK_NO_MESSAGES,
								RFC3730_RESULT_CODE::OK_PENDING
							  );
						  
			$is_success = in_array($response_code, $ok_codes);
				
			if (!$is_success)
			{
				if ($xml->response->extension)
				{
					 $childs = $xml->response->extension->children("urn:ics-forth:params:xml:ns:extcommon-1.0");
					 
					 if (count($childs) == 0)
					 	 $childs = $xml->response->extension->children("urn:ics-forth:params:xml:ns:extdomain-1.0");
				 	 if (count($childs) == 0)
			 			 $childs = $xml->response->extension->children("urn:ics-forth:params:xml:ns:extcontact-1.0");
				 	 if (count($childs) == 0)
	 					 $childs = $xml->response->extension->children("urn:ics-forth:params:xml:ns:exthost-1.0");
				}
				
				if ($childs[0])
					$errmsg = (string)$childs[0]->comment;
				else
				{
					if (!$xml->response->result[1])
						$errmsg = (string)$xml->response->result->msg;
					else
					{
						foreach ($xml->response->result as $k=>$v)
							$errmsg .= (string)$v->msg;
					}
				}
			}
			
			return new TransportResponse($response_code, $xml, $is_success, $errmsg);
		}
		
		public function Disconnect ()
		{
			if ($this->IsConnected)
			{
				$request_xml = $this->ParseTemplate("logout", array());
						
				// Init CURL object
				$ch = curl_init();
	
				curl_setopt ($ch, CURLOPT_URL, $this->ConnectionConfig->GetFieldByName("ServerHost")->Value);
				curl_setopt ($ch, CURLOPT_POSTFIELDS, $request_xml);
				if ($this->SessionCookie)
				{
					curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Cookie: {$this->SessionCookie}"));
					$this->IsConnected = true;
				}
		
					
			    // SSL stuff
				curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);
				if ($this->ConnectionConfig->GetFieldByName("UseSSLCert")->Value == 1)
				{
	                curl_setopt ($ch, CURLOPT_CAINFO, $this->ConnectionConfig->GetFieldByName("SSLCertPath")->Value);
	                curl_setopt ($ch, CURLOPT_SSLCERTPASSWD, $this->ConnectionConfig->GetFieldByName("SSLpwd")->Value);
	                curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 1);
	                curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
				}
				else 
				{    
	                curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 2);
	                curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);		
				}
	   			
				// Misc
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt ($ch, CURLOPT_POST, 1);
				curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt ($ch, CURLOPT_HEADER, 1);
				curl_setopt ($ch, CURLOPT_VERBOSE, 0);
		
				// Execute request
				$res = curl_exec($ch);
				unset($res);		
				curl_close($ch);
			}
		}
		
		/*
		protected function ParseTemplate($filename, $tags = array())
		{
			// try to get contents from xml file
			$out = file_get_contents(CONFIG::$PATH . "/modules/registries/EPPGR/xml/{$filename}.xml");
			
			if (!$out)
				throw new Exception(sprintf(_("Command template '%s.xml' not found"), $filename));
			if (is_array($tags))
			{
				foreach ($tags as $k=>$v)
					$tagsk[] = "{".$k."}";
				
				$retval = str_replace($tagsk, array_values($tags), $out);
			}
	
			// common
			$cltrid = "{$this->ConnectionConfig->GetFieldByName("Login")->Value}-".time();
			$retval = str_replace(array("{lang}"), "en", $retval);
			$retval = str_replace("{clTRID}", $cltrid, $retval);
			
			$DOMDocument = new DOMDocument();
			if (!$DOMDocument->loadXML($retval))
				throw new Exception(_("Request contains malformed XML"));			
				
			return $retval."\r\n\r\n";
		}
		*/
	}
?>