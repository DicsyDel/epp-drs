<?php

	class EPPLUTransport extends GenericEPPTransport
	{
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
			$request = $this->ParseTemplate($command, $data);
			
			// Add log entry
			Log::Log(sprintf("Sending request:\n%s", $request), E_USER_NOTICE);
			if ($this->DumpTraffic && $command != 'login')
			{
				print ">> Sending request:\n";
				print "{$request}\n";				
			}
					
			// Generate EPP header
			$eppheader = pack("N", strlen($request)+4);
			
			// Send request
			fwrite($this->Socket, $eppheader.$request);
			
			if ($command == "logout")
				return;
			
			// Read EPP Header
			$buff = fread($this->Socket, 4);
			
			// Get response length from EPP header
			$l = unpack("N", $buff);
			$unread_bytes = $l[1]-4;
			
			// Read response
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
			
						
			// Log response
			Log::Log(sprintf("Server response:\n%s", $full_response), E_USER_NOTICE);
			if ($this->DumpTraffic && $command != "login")
			{
				print "<< Server respond:\n";
				print "{$full_response}\n";
			}			
			
			//
			// Check for errors
			//			
			$xml = new SimpleXMLElement($full_response);
			
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
			
			// Check for intrenal server error
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
			if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_STATUS_PROHIBITS_OP)
				throw new ProhibitedTransformException();
			if ($response_code == RFC3730_RESULT_CODE::ERR_OBJECT_EXISTS)
				throw new ObjectExistsException();
								
			// Set ok codes
			$ok_codes = array(	RFC3730_RESULT_CODE::OK, 
								RFC3730_RESULT_CODE::OK_ACK_DEQUEUE, 
								RFC3730_RESULT_CODE::OK_END_SESSION,
								RFC3730_RESULT_CODE::OK_NO_MESSAGES,
								RFC3730_RESULT_CODE::OK_PENDING
							  );
						  
			$is_success = in_array($response_code, $ok_codes);
			$errmsg = ($result->extValue && $result->extValue->reason) ? (string)$result->extValue->reason : (string)$result->msg;
			if ($result->value->string)
			{
				$errmsg .= ". {$result->value->string}";
			} 
						
			// Return Transport Response
			return new TransportResponse($response_code, $xml, $is_success, $errmsg);
		}
		
		function ParseTemplate2($filename, $tags = array())
		{
			return $this->ParseTemplate($filename, $tags);
		}
	}
?>