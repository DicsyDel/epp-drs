<?php

class EPPZATransport extends GenericEPPTransport
{
	public function Connect ()
	{
		if (is_resource($this->Socket))
			@fclose($this->Socket);
			
		$host = $this->ConnectionConfig->GetFieldByName("ServerHost")->Value;
			
		$cert_path = $this->ConnectionConfig->GetFieldByName("SSLCertPath")->Value;
		$passphrase = $this->ConnectionConfig->GetFieldByName("SSLpwd")->Value;
			
		$context = stream_context_create(
				array(  'ssl' => array( 'local_cert' => $cert_path,
							'passphrase' => $passphrase,
							'verify_peer' => false,
							'allow_self_signed' => false,
							'cafile' => '/opt/epp-drs/modules/registries/EPPZA/ssl/cert.pem',
							'verify_depth' => 5,
							'CN_match' => 'epp.coza.net.za' ))
		);
			
		$this->Socket = @stream_socket_client("tls://{$host}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
			
		// If error
		if (!$this->Socket)
			throw new Exception(sprintf(_("Cannot connect to registry server: %s"), $errstr));
			
		// Read greeting message
		$this->ReadGreeting();
		$this->IsConnected = true;
			
		return true;
	}

	/**
	 * Overriden to pre-process ZACR response xmls before parsing.
	 * 
	 */
	public function Request ($command, $data = array())
	{
		
		// Prepare request string
		$request = $this->ParseTemplate($command, $data);

		// Add log entry
		Log::Log(sprintf("Sending request:\n%s", $request), E_USER_NOTICE);
		if ($this->DumpTraffic && $command != "login")
		{
			print ">> Sending request:\n";
			print "{$request}\n";
		}
		
		
		// Generate EPP header
		$head = pack("N", strlen($request) + 4);

		// Send request
		$this->fwrite($this->Socket, $head . $request);

		// Read EPP Header
		$head = $this->fread($this->Socket, 4);

		// Get response length from EPP header
		$len = unpack("N", $head);

		// Read response
		$full_response = $this->fread($this->Socket, $len[1] - 4);
		
		
		/*
			$chunk_length = 512;
		$read_len = 0;
		$full_response = '';
		do {
		$buffer_length = ($unread_bytes > $chunk_length) ? $chunk_length : $unread_bytes;

		if ($buffer_length == 0)
			break;

		$str = fread($this->Socket, $buffer_length);
		$full_response .= $str;
		$unread_bytes -= $chunk_length;

		} while ($unread_bytes > 0);
		*/


		// Log response
		Log::Log(sprintf("Server response:\n%s", $full_response), E_USER_NOTICE);
		if ($this->DumpTraffic && $command != "login")
		{
			print "<< Server respond:\n";
			print "{$full_response}\n";
		}
			
		//Replace epp: tags for avoiding xml parse error.
		$full_response =  $this->RepairZacrXml($full_response);
		
		
		
		//
		// Check for errors
		//
		$xml = new SimpleXMLElement($full_response);
		$this->LastResponse = $xml;
			
		if (!$xml)
			throw new Exception(_("Registry returned malformed XML"));

		// Register standart object namespaces
		foreach ($this->ObjURIs as $prefix => $ns)
			$xml->registerXPathNamespace($prefix, $ns);

		// Register extension namespaces
		foreach ($this->ExtURIs as $prefix => $ns)
			$xml->registerXPathNamespace($prefix, $ns);

		// If we send hello command we don't need to parse response
		if ($command == "hello")
			return true;

		// get response code
		$result = (is_array($xml->response->result) && $xml->response->result[0] && $xml->response->result[0]->msg) ? $xml->response->result[0] : $xml->response->result;

		$result_attributes = $result->attributes();
		$response_code = (string)$result_attributes["code"];

		// Check for internal server error
		if ($response_code == RFC3730_RESULT_CODE::ERR_CMD_FAILED ||
				$response_code == RFC3730_RESULT_CODE::ERR_CMD_FAILED_END_SESSION)
			throw new Exception(_("Registry error"));

		// Check for session end
		if ($response_code == RFC3730_RESULT_CODE::ERR_CMD_FAILED_END_SESSION ||
				$response_code == RFC3730_RESULT_CODE::ERR_AUTH_END_SESSION ||
				$response_code == RFC3730_RESULT_CODE::OK_END_SESSION ||
				$response_code == RFC3730_RESULT_CODE::ERR_SESSION_LIMIT_EXCEEDED)
			$this->IsConnected = false;
			
		if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_NOT_EXISTS)
			throw new ObjectNotExistsException();
		if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_STATUS_PROHIBITS_OP && $command != "contact-delete")
			throw new ProhibitedTransformException();
		if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_EXISTS)
			throw new ObjectExistsException();

		// Set ok codes
		$ok_codes = array(
				RFC3730_RESULT_CODE::OK,
				RFC3730_RESULT_CODE::OK_ACK_DEQUEUE,
				RFC3730_RESULT_CODE::OK_END_SESSION,
				RFC3730_RESULT_CODE::OK_NO_MESSAGES,
				RFC3730_RESULT_CODE::OK_PENDING
		);

		$is_success = in_array($response_code, $ok_codes);
		$errmsg = (string)$result->msg;

		// Return Transport Response
		return new TransportResponse($response_code, $xml, $is_success, $errmsg);
	}

	/**
	 * Overriden to pre-process ZACR response xmls before parsing.
	 *
	 */
	function ReadGreeting()
	{
		$buf = $this->fread($this->Socket, 4);
		$l = unpack("N", $buf);
			
		// read greeting message
		$greeting = $this->fread($this->Socket, $l[1]-4);
			
		$greeting = $this->RepairZacrXml($greeting);
			
			
		$xml = new SimpleXMLElement($greeting);
			
		if ($xml->greeting->svcMenu->svcExtension)
		{

			foreach ($xml->greeting->svcMenu->svcExtension->extURI as $item)
			{
				$item = (string)$item;
					
				$parsed = parse_url($item);
				if ($parsed["scheme"] == "http")
				{
					preg_match_all("/[A-Za-z-]+/", basename($parsed["path"]), $matches);
					$prefix = trim($matches[0][0], "-");
				}
				else
				{
					$chunks = explode(":", $item);
					preg_match("/[A-Za-z]+/", $chunks[count($chunks)-1], $matches);
					$prefix = $matches[0];
				}

				$this->ObjURIs[$prefix] = $item;
				$this->ExtURIs[$prefix] = $item;
			}
		}

		foreach ($xml->greeting->svcMenu->objURI as $item)
		{
			$item = (string)$item;

			$parsed = parse_url($item);
			if ($parsed["scheme"] == "http")
			{
				preg_match_all("/[A-Za-z-]+/", basename($parsed["path"]), $matches);
				$prefix = trim($matches[0][0], "-");
			}
			else
			{
				$chunks = explode(":", $item);
				preg_match("/[A-Za-z]+/", $chunks[count($chunks)-1], $matches);
				$prefix = $matches[0];
			}
				
			$this->ObjURIs[$prefix] = $item;
		}

		Log::Log(sprintf("Greeting from registry:\n %s", $greeting), E_USER_NOTICE);
			
	}
	
	/**
	 * Overriden as ZACR is too quick for us to use seconds as a unique ID.
	 *
	 */
	function ParseTemplate($filename, $tags = array())
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
		else
			$retval = $out;
		
		// common
		
		$retval = str_replace("{lang}", "en", $retval);
		if (!isset($tags['clTRID']))
		{
			$cltrid = "{$this->ConnectionConfig->GetFieldByName("Login")->Value}-".microtime('get_as_float');
			$retval = str_replace("{clTRID}", $cltrid, $retval);
		}
		
		$DOMDocument = new DOMDocument();
		if (!$DOMDocument->loadXML($retval))
		{
			Log::Log('Request contains malformed XML: ' . $retval, E_ERROR);
			throw new Exception(_("Request contains malformed XML"));
		}
		
		return $retval;
	}
	
	/**
	 * This function removes epp: from all tags to avoid parse error. 
	 * @param String $xml_string The xml response coming from ZACR
	 * @return String repaired xml
	 * 
	 * @author Alwin Tom
	 */
	private function RepairZacrXml($xml_string) {
		$out_string = preg_replace('/<epp:([a-zA-Z0-9]+\w+.*)>/', '<$1>', $xml_string);
		$out_string = preg_replace('/<\/epp:([a-zA-Z0-9]+\w+.*)>/', '</$1>', $out_string);
		
		return $out_string;
	}
}
?>
