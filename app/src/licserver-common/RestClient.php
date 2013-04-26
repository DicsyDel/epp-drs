<?php

class RestClient
{
	private $url;
	
	/**
	 * @var XmlDataBinding
	 */
	private $data_binding;
	
	/**
	 * @var string
	 */
	private $interface;
	
	/**
	 * @var HttpRequest
	 */
	private $http_request;
	
	private $curl;
	
	private $result_class_map = array();
	
	function __construct ($url)
	{
		$this->url = $url;
		$this->data_binding = new XmlDataBinding();
	}
	
	function __call ($method, $args)
	{
		if (!method_exists($this->interface, $method))
			throw new Exception(sprintf("Service interface has not method '%s'", $method));
		
		$ref_method = new ReflectionMethod($this->interface, $method);
		$method_params = $ref_method->getParameters();
		
		$message = '<?xml version="1.0"?>';
		$message .= "<$method>";
		foreach ($args as $i => $arg)
		{
			if ($i < count($method_params))
			{
				$ref_method_param = $method_params[$i];
				$message .= 
					"<".$ref_method_param->getName().">"
					. $this->data_binding->Marshall($arg)
					. "</".$ref_method_param->getName().">\n";
			}
			else
			{
				break;
			}
		}
		$message .= "</$method>";
		
		$result = $this->ReadResponse($this->DoExchange($message));
		if (key_exists($method, $this->result_class_map))
			return call_user_func(array($this->result_class_map[$method], "FromString"), $result);
		else
			return $result;
	}
	
	private function ReadResponse ($message)
	{
		$xml = @new SimpleXMLElement($message);
		//
		
		return $this->data_binding->Unmarshall($message);
	}
	
	private function DoExchange ($message)
	{
		if (class_exists("HttpRequest") && !$this->curl)
		{
			if (!$this->http_request)
				$this->http_request = new HttpRequest();
			
			$http_request = $this->http_request;
			$http_request->setUrl($this->url);
			$http_request->setMethod(HTTP_METH_POST);
			$http_request->setRawPostData($message);
			
			$http_message = $http_request->send();
			if ($http_message->getResponseCode() == 200)
			{
				return $http_message->getBody();
			}
			else
			{
				throw new Exception(sprintf("Service failed with code = %d, message: %s", 
					$http_message->getResponseCode(), $http_message->getBody()));
			}
		}
		else
		{
			if (!$this->curl)
				$this->curl = curl_init();
			
			curl_setopt_array($this->curl, array(
				CURLOPT_URL => $this->url,
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $message,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_HEADER => 0,
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_SSL_VERIFYHOST => 0
			));
			
			$http_body = curl_exec($this->curl);
			if ("200" == ($status = curl_getinfo($this->curl, CURLINFO_HTTP_CODE)))
			{
				return $http_body;
			}
			else
			{
				if ($err = curl_error($this->curl))
					Log::Log("CURL error: {$err}", E_USER_NOTICE);
				throw new Exception(sprintf("Service failed with code = %d, message: %s", 
						$status, $http_body));
			}
		}
	}
	
	function SetInterface ($interface) 
	{
		$this->interface = is_object($interface) ? get_class($interface) : (string)$interface;
	}
	function GetInterface () { return $this->interface; } 

	function SetHttpRequest (HttpRequest $http_request) { $this->http_request = $http_request; }
	function GetHttpRequest () { return $this->http_request; }
	
	function SetCurl ($ch) { $this->curl = $ch; }
	function GetCurl () { return $this->curl; }
	
	function SetResultClassMap ($map) { $this->result_class_map = $map; }
	function GetResultClassMap () { return $this->result_class_map; }
	
} 

?>