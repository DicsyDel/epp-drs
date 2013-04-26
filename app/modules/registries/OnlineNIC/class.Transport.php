<?php

class OnlineNICTransport extends GenericEPPTransport 
{
	private static $CommandParamsMap = array(
		'login' => array(
			'login',
			array()	 
		),
		'domain-lock' => array(
			'upddomain',
			array('type', 'name')
		),
		'domain-create' => array(
			'crtdomain',
			array('type', 'name', 'period', 'ns1', 'ns2', 'registrant', 'admin', 'tech', 'billing', 'pw')
		),
		'domain-info' => array(
			'getdomaininfo',
			array()
		),
		'domain-update-ns' => array(
			'upddomain',
			array('type', 'name', 'ns1', 'ns2', 'pw')
		),
		'domain-check' => array(
			'chkdomain',
			array('type', 'name')
		),
		'domain-delete' => array(
			'deldomain',
			array('type', 'name')
		),
		'domain-renew' => array(
			'renewdomain',
			array('type', 'name', 'period')
		),
		'domain-trans-request' => array(
			'transferdomain',
			array('type', 'name')
		),
		'create-contact' => array(
			'crtcontact', 
			array("name", "org", "email")
		),
		'contact-update' => array(
			'updcontact',
			array('type', 'domainname', 'contacttype', 'name', 'org', 'email')
		),
		'host-create' => array(
			'crthost',
			array('type', 'host', 'ip1')
		),
		'host-update' => array(
			'updhost',
			array('type', 'host', 'ip_new', 'ip_old')
		),
		'host-delete' => array(
			'delhost',
			array('type', 'host')
		)
	);
	
	private function GenerateChecksum ($command, $params, $cltrid)
	{
		$args = array(
			$this->ConnectionConfig->GetFieldByName('Login')->Value,		// Login
			md5($this->ConnectionConfig->GetFieldByName('Password')->Value),// Password
			$cltrid															// clTRID
		);
		
		if (array_key_exists($command, self::$CommandParamsMap))
		{
			list($cmd, $arg_names) = self::$CommandParamsMap[$command];
			$args[] = $cmd; 

			if ($arg_names)
			{
				foreach ($arg_names as $n)
					$args[] = $params[$n];
			}
		}
		
		return md5(join('', $args));
	}
	
	private function ReadData ()
	{
		// Set read timeout
		$timeout = 15;		
		@stream_set_timeout($this->Socket, $timeout);
		
		// Set blocking
		@stream_set_blocking($this->Socket, false);
		
		$full_response = '';

		$start_time = time();
	    while(!stristr($full_response, "</epp>") && !$meta["timed_out"])
		{
			$time = time();
			if ($time - $start_time > $timeout)
			{
				throw new Exception(_('Timeout while waiting for registry response.'));
			}
		    $full_response .= @fgetc($this->Socket);
		}
		
		return $full_response;
	} 
	
	protected function ReadGreeting()
	{
		// read greeting message
		$greeting = $this->ReadData();
		
		if (!$greeting)
			throw new Exception(_("Unable to read a greeting message from server. Try again later."));
		
		Log::Log(sprintf("Greeting from registry:\n %s", $greeting), E_USER_NOTICE);
		
   		$this->ObjURIs['contact'] = 'urn:iana:xml:ns:contact-1.0';
   		$this->ObjURIs['domain'] = 'urn:iana:xml:ns:domain-1.0';
   		$this->ObjURIs['host'] = 'urn:iana:xml:ns:host-1.0';
   		$this->ObjURIs['svcsub'] = 'urn:iana:xml:ns:svcsub-1.0';
	}	
	
	public function Disconnect ()
	{
		if ($this->IsConnected)
		{
			//$this->Request("logout");
			// We cannot use $this->Request here because:
			// Program terminated with signal 11, Segmentation fault.

			$request = $this->ParseTemplate("logout");				
		
			// Send request
			fwrite($this->Socket, $request);
			fclose($this->Socket);
			
		}
		
		$this->IsConnected = false;
			
		return true;
	}
	
	public function Request ($command, $params=array())
	{
		$request_xml = $this->ParseTemplate($command, $params);
		
		Log::Log(sprintf("Sending request:\n%s", $request_xml), E_USER_NOTICE);
		if ($this->DumpTraffic && $command != 'login')
		{
			print ">> Sending request:\n";
			print "{$request_xml}\n";				
		}	
		
		fwrite($this->Socket, $request_xml);

		$full_response = $this->ReadData();
		
		Log::Log(sprintf("Response:\n%s", $full_response), E_USER_NOTICE);
		if ($this->DumpTraffic && $command != 'login')
		{
			print "<< Server respond:\n";
			print "{$full_response}\n";			
		}
		
		if (trim($full_response) == "")
			throw new Exception(_("Timeout while waiting for registry response."));
		
		// Ctrl-C, Ctrl-V from GenericEPP transport
		$xml = @simplexml_load_string($full_response); 
		
		if (!$xml)
			throw new Exception(_("Timeout while waiting for registry response."));
		
		// Register standart object namespaces
		foreach ($this->ObjURIs as $prefix => $ns)
			$xml->registerXPathNamespace($prefix, $ns);
			
		// Register extension namespaces
		foreach ($this->ExtURIs as $prefix => $ns)
			$xml->registerXPathNamespace($prefix, $ns);
						
		// If we send hello command we don't need to parse response
		if ($command == "hello")
			return true;
			
		if (!($xml instanceof SimpleXMLElement) || !$xml->response || !$xml->response->result)
			throw new Exception(_("Registry returned malformed XML"));
			
		// get response code
		$result_attributes = $xml->response->result->attributes();
		$response_code = (string)$result_attributes["code"];

		$errmsg = (string)$xml->response->result->msg;
		$errmsg_value = (string)$xml->response->result->value;
		if ($errmsg_value)
			$errmsg .= ". {$errmsg_value}";
		
		// Check for intrenal server error
		if ($response_code == RFC3730_RESULT_CODE::ERR_CMD_FAILED || 
			$response_code == RFC3730_RESULT_CODE::ERR_CMD_FAILED_END_SESSION)
			throw new Exception(_("Registry error: $errmsg"));
	
		// Check for session end
		if ($response_code == RFC3730_RESULT_CODE::ERR_CMD_FAILED_END_SESSION ||
			$response_code == RFC3730_RESULT_CODE::ERR_AUTH_END_SESSION ||
			$response_code == RFC3730_RESULT_CODE::OK_END_SESSION ||
			$response_code == RFC3730_RESULT_CODE::ERR_SESSION_LIMIT_EXCEEDED) 
			$this->IsConnected = false;
		
		if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_NOT_EXISTS)
			throw new ObjectNotExistsException($errmsg);
		if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_STATUS_PROHIBITS_OP)
			throw new ProhibitedTransformException($errmsg);
		if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_EXISTS)
			throw new ObjectExistsException($errmsg);
			
			
		// Set ok codes
		$ok_codes = array(
			RFC3730_RESULT_CODE::OK, 
			RFC3730_RESULT_CODE::OK_ACK_DEQUEUE, 
			RFC3730_RESULT_CODE::OK_END_SESSION,
			RFC3730_RESULT_CODE::OK_NO_MESSAGES,
			RFC3730_RESULT_CODE::OK_PENDING
		);
					  
		$is_success = in_array($response_code, $ok_codes);
					
		// Return Transport Response
		return new TransportResponse($response_code, $xml, $is_success, $errmsg);
	}
	
	public function ParseTemplate ($filename, $tags = array())
	{
		// try to get contents from xml file
		$out = file_get_contents("{$this->ModulePath}/xml/{$filename}.xml");
		
		if (!$out)
			throw new Exception(sprintf(_("Command template '%s.xml' not found"), $filename));
		if (is_array($tags))
		{
			foreach ($tags as $k=>$v)
				$tagsk[] = "{".$k."}";
			
			$retval = str_replace($tagsk, array_values($tags), $out);
		}

		// common
		$cltrid = "{$this->ConnectionConfig->GetFieldByName("Login")->Value}-".str_replace(' ', '-', microtime());
		$retval = str_replace(array("{lang}"), "en", $retval);
		$retval = str_replace("{clTRID}", $cltrid, $retval);
		
		$checksum = $this->GenerateChecksum($filename, $tags, $cltrid);
		$retval = str_replace("{chksum}", $checksum, $retval);
		
		//$DOMDocument = new DOMDocument();
		//if (!$DOMDocument->loadXML($retval))
		//	throw new Exception(_("Request contains malformed XML"));			
			
		return $retval;
	}
} 

?>