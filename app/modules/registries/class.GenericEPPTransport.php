<?php

	/**
	 * RFC-compliant TCP transport for EPP. 
	 * @name GenericEPPTransport
	 * @category   EPP-DRS
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @sdk-doconly
	 * @author Marat Komarov <http://webta.net/company.html> 
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	abstract class GenericEPPTransport implements IRegistryTransport
	{
		protected $ConnectionConfig;
		protected $IsConnected;
		protected $ExtURIs = array();
		protected $ObjURIs = array();
		protected $Socket;
		protected $ModulePath;
		protected $DumpTraffic = false;
		
		public $LastResponse;
		
		/**
		 * Transport constructor
		 *
		 * @param DataForm $ConnectionConfig
		 */
		public function __construct(DataForm $ConnectionConfig, $module_path)
		{
			if ($ConnectionConfig instanceof DataForm)
				$this->ConnectionConfig = $ConnectionConfig;
			else
				throw new Exception(_("ConnectionConfig must be an instance of DataForm"));
				
			$this->ModulePath = $module_path;
		}
		
		protected function fread($fp, $len) {
			$ret = '';
			$read = 0;

			while ($read < $len && ($buf = fread($fp, $len - $read))) {
				$read += strlen($buf);
				$ret .= $buf;
			}

			return $ret;			
		}

		protected function fwrite($fp, $buf) {
			$total = 0;
			$len = strlen($buf);

			while ($total < $len && ($written = fwrite($fp, $buf))) {
				$total += $written;
				$buf = substr($buf, $written);
			}

			return $total;
		}

		public function SetDumpTraffic ($bool) 
		{
			$this->DumpTraffic = $bool;
		}
		
		public function Connect ()
		{
			if (is_resource($this->Socket))
				@fclose($this->Socket);
			
			$host = $this->ConnectionConfig->GetFieldByName("ServerHost")->Value;
			$port = $this->ConnectionConfig->GetFieldByName("ServerPort")->Value;
			
			// Open socket to registry server
			$this->Socket = fsockopen("tcp://{$host}", $port, $errno, $errmsg, 20);			
						
			// If error
			if (!$this->Socket)
				throw new Exception(sprintf(_("Cannot connect to registry server: %s"), $errmsg));
			
			// Read greeting message
			$this->ReadGreeting();
			$this->IsConnected = true;
			
			return true;
		}
	
		public function IsConnected()
		{
			return $this->IsConnected;
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
			
			$response = $this->Request("login", array(
				"clid"	=>	$this->EscapeXML($this->ConnectionConfig->GetFieldByName("Login")->Value),
				"pw" 	=>	$this->EscapeXML($this->ConnectionConfig->GetFieldByName("Password")->Value),
				"lang"	=> "en",
				"svcExtension" => $svcExtension
 			));
 			if (!$response->Succeed) {
 				throw new Exception($response->ErrMsg);
 			}
 			
 			return $response->Succeed;
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
		
		public function Disconnect ()
		{
			if ($this->IsConnected)
			{
				//$this->Request("logout");
				// We cannot use $this->Request here because:
				// Program terminated with signal 11, Segmentation fault.
				
				
				$request = $this->ParseTemplate("logout");				
				$eppheader = pack("N", strlen($request)+4);
			
				// Send request
				$this->fwrite($this->Socket, $eppheader.$request);
				fclose($this->Socket);
				
			}
			
			$this->IsConnected = false;
				
			return true;
		}
		
		protected function ReadGreeting()
		{
			// Read greeting epp header
			$buf = $this->fread($this->Socket, 4);			
			$l = unpack("N", $buf);
			
			// read greeting message
			$greeting = $this->fread($this->Socket, $l[1]-4);
			
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
			
			//var_dump($this->ObjURIs);
			//var_dump($this->ExtURIs);
			//die();
			
			Log::Log(sprintf("Greeting from registry:\n %s", $greeting), E_USER_NOTICE);
		}
		
		protected function ParseTemplate($filename, $tags = array())
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
				$cltrid = "{$this->ConnectionConfig->GetFieldByName("Login")->Value}-".time();
				$retval = str_replace("{clTRID}", $cltrid, $retval);
			}
			
			//if ($tags["name"] && $tags["pw"])
			//{
			//	var_dump($retval);// die();
			//}
			
			$DOMDocument = new DOMDocument();
			if (!$DOMDocument->loadXML($retval))
			{
				Log::Log('Request contains malformed XML: ' . $retval, E_ERROR);
				throw new Exception(_("Request contains malformed XML"));
			}			
				
			return $retval;
		}
		
		protected function EscapeXML ($str)
		{
			return str_replace(array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $str);
		}		
		
		public function __destruct()
		{
			$this->Disconnect();	
		}
	}
?>
