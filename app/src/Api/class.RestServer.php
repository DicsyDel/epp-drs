<?php

class EppDrs_Api_RestServer
{
	private $api_version;
	
	private $key, $key_id;
	
	private $user_id;
	
	/**
	 * @var adodb_lite
	 */
	private $Db;
	
	/**
	 * @var EppDrs_Api_Service
	 */
	private $ServiceImpl;
	
	function __construct ($api_version)
	{
		$class_name = "EppDrs_Api_Service{$api_version}";
		$class_file = dirname(__FILE__) . "/class.Service{$api_version}.php";
		if (!class_exists($class_name) && file_exists($class_file))
			require_once($class_file);
		if (!class_exists($class_name))
			throw new Exception("Cannot load service implementation class for API version {$api_version}");
		$this->ServiceImpl = new $class_name;
		
		$this->Db = Core::GetDBInstance();
	}
	
	function Handle ($request)
	{
		$transaction_id = $this->GenerateTransactionID();
		
		try
		{
			if (!$request["keyId"])
				throw new Exception(sprintf("'%s' parameter is required", "keyId"));
			if (!$request["action"])
				throw new Exception(sprintf("'%s' parameter is required", "action"));
				
			// Find API user				
			$key_id = $request["keyId"];
			if (CONFIG::$API_KEY_ID && CONFIG::$API_KEY_ID == $key_id)
			{
				if (!CONFIG::$API_ENABLED)
					throw new Exception("API is turned off. You can enable it in Settings -> API settings");
				$key = CONFIG::$API_KEY;
				$allowed_ips = CONFIG::$API_ALLOWED_IPS;
				
				$this->ServiceImpl->SetAdminAccessMode();
				$this->user_id = -1;
			}
			else
			{
				$Client = Client::LoadByApiKeyID($key_id);
				if (!$Client->GetSettingValue(ClientSettings::API_ENABLED))
					throw new Exception("API is turned off. You can enable it in Settings -> API settings");
				$key = $Client->GetSettingValue(ClientSettings::API_KEY);
				$allowed_ips = $Client->GetSettingValue(ClientSettings::API_ALLOWED_IPS);
				
				$this->ServiceImpl->SetUserAccessMode($Client->ID);
				$this->user_id = $Client->ID;
			}
			
			$this->key_id = $key_id;
			$this->key = $key;
			
			// Check IP access
			if ($allowed_ips && !$this->CheckIPAccess(explode(",", $allowed_ips)))
			{
				throw new Exception(sprintf(_("Access to the API is not allowed from your IP '%s'"), 
					$_SERVER['REMOTE_ADDR']));
			}
			
			
			// Validate request signature
			//$this->ValidateSignature($request);
			
			
			// Call method
			$method_name = ucfirst($request["action"]);
			if (!method_exists($this->ServiceImpl, $method_name))
				throw new Exception(sprintf("Unknown action %s", $request["action"]));
			
			$result = $this->ServiceImpl->{$method_name}($request);
			$result->transactionId = $transaction_id;
			
			// Write response
			$Doc = new DOMDocument('1.0', 'UTF-8');
			$Doc->loadXML("<".strtolower($method_name{0}).substr($method_name, 1)."Response/>");
			$this->ObjectToXML($result, $Doc->documentElement, $Doc);
			
			$response = $Doc->saveXML();
		}
		catch (Exception $e)
		{
			header("HTTP/1.1 500 Internal Server Error");			
			
			$result = new stdClass();
			$result->message = $e->getMessage();
			$result->transactionId = $transaction_id;
			
			$Doc = new DOMDocument('1.0', 'UTF-8');
			$Doc->loadXML("<error/>");
			$this->ObjectToXML($result, $Doc->documentElement, $Doc);
			
			$response = $Doc->saveXML();
			$error_trace = $e->getTraceAsString();
		}

		$this->Log($transaction_id, $request['action'], $_SERVER["REMOTE_ADDR"], 
			$request, $response, $error_trace, $this->user_id);
		
		header("Content-type: text/xml");
		header("Content-length: ".strlen($response));
		print $response;
		die();
	}
	
	private function ValidateSignature($request)
	{
		ksort($request);
		$string_to_sign = "";
    	foreach ($request as $k=>$v)
    	{
    		if (!in_array($k, array("signature", "version")))
    			$string_to_sign.= "{$k}{$v}";
    	}
    	$valid_sign = base64_encode(hash_hmac("sha1", $string_to_sign, $this->key, true));
    	if ($valid_sign != $request['signature'])
    		throw new Exception("Signature doesn't match");
	}
	
	private function ObjectToXML($obj, $DOMElement, $DOMDocument)
	{
		if (is_object($obj) || is_array($obj))
		{
			foreach ($obj as $k=>$v)
			{
				if (is_object($v))
					$this->ObjectToXML($v, $DOMElement->appendChild($DOMDocument->createElement($k)), $DOMDocument);
				elseif (is_array($v))
					foreach ($v as $vv)
					{
						$e = &$DOMElement->appendChild($DOMDocument->createElement($k));
						$this->ObjectToXML($vv, $e, $DOMDocument);
					}
				else
					$DOMElement->appendChild($DOMDocument->createElement($k, $v));
			}
		}
		else
			$DOMElement->appendChild($DOMDocument->createTextNode($obj));
	}
	
	private function CheckIPAccess($allowed_ips)
	{
		$current_ip = $_SERVER['REMOTE_ADDR'];
		
		if ($current_ip == "127.0.0.1") // Allow access from localhost
			return true;
		
		foreach ($allowed_ips as $allowed_ip)
		{
			$allowedhost = trim($allowed_ip);
			if ($allowedhost == '')
				continue;
    	    
    	    if (preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/si", $allowedhost))
    	    {
    	        if (ip2long($allowedhost) == ip2long($current_ip))
    	           return true;
    	    }
    	    elseif (stristr($allowedhost, "*"))
    	    {
    	        $ip_parts = explode(".", trim($allowedhost));
    	        if (
    				($ip_parts[0] == "*" || $ip_parts[0] == $current_ip_parts[0]) &&
    				($ip_parts[1] == "*" || $ip_parts[1] == $current_ip_parts[1]) &&
    				($ip_parts[2] == "*" || $ip_parts[2] == $current_ip_parts[2]) &&
    				($ip_parts[3] == "*" || $ip_parts[3] == $current_ip_parts[3])
    			   )
    			return true;
    	    }
    	    else 
    	    {
    	        $ip = @gethostbyname($allowedhost);
    	        if ($ip != $allowedhost)
    	        {
    	            if (ip2long($ip) == ip2long($current_ip))
    	               return true;
    	        }
    	    }
		}
		
		return false;
	}	
	
	private function Log($transaction_id, $action, $ipaddress, $request, $response, $error_trace, $user_id)
	{
		try
		{
			$this->Db->Execute("INSERT INTO api_log SET
				transaction_id	= ?,
				added_date		= CURRENT_TIMESTAMP,
				action			= ?,
				ipaddress		= ?,
				request			= ?,
				response		= ?,
				error_trace		= ?,
				user_id			= ?
			", array(
				$transaction_id,
				$action,
				$ipaddress,
				http_build_query($request),
				$response,
				"$error_trace",
				(int)$user_id
			));
		}
		catch(Exception $ignore) {
		}
	}

	private function GenerateTransactionID()
	{
		$pr_bits = false;
        if (! $pr_bits) {
            $fp = @fopen ( '/dev/urandom', 'rb' );
            if ($fp !== false) {
                $pr_bits .= @fread ( $fp, 16 );
                @fclose ( $fp );
            } else {
                // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
                $pr_bits = "";
                for($cnt = 0; $cnt < 16; $cnt ++) {
                    $pr_bits .= chr ( mt_rand ( 0, 255 ) );
                }
            }
        }
        $time_low = bin2hex ( substr ( $pr_bits, 0, 4 ) );
        $time_mid = bin2hex ( substr ( $pr_bits, 4, 2 ) );
        $time_hi_and_version = bin2hex ( substr ( $pr_bits, 6, 2 ) );
        $clock_seq_hi_and_reserved = bin2hex ( substr ( $pr_bits, 8, 2 ) );
        $node = bin2hex ( substr ( $pr_bits, 10, 6 ) );
        
        /**
         * Set the four most significant bits (bits 12 through 15) of the
         * time_hi_and_version field to the 4-bit version number from
         * Section 4.1.3.
         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
         */
        $time_hi_and_version = hexdec ( $time_hi_and_version );
        $time_hi_and_version = $time_hi_and_version >> 4;
        $time_hi_and_version = $time_hi_and_version | 0x4000;
        
        /**
         * Set the two most significant bits (bits 6 and 7) of the
         * clock_seq_hi_and_reserved to zero and one, respectively.
         */
        $clock_seq_hi_and_reserved = hexdec ( $clock_seq_hi_and_reserved );
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;
        
        return sprintf ( '%08s-%04s-%04x-%04x-%012s', $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node );
	}	
}
?>