<?php

/**
 * 
 * @author marat
 *
 * @method SimpleXMLElement listDomains (EppDrs_Api_Client_ListDomainsParams $params) 
 * @method SimpleXMLElement listDomains (array $params)  
 * @method SimpleXMLElement checkDomain (EppDrs_Api_Client_CheckDomainParams $params)
 * @method SimpleXMLElement checkDomain (array $params)
 * @method SimpleXMLElement getTldInfo (EppDrs_Api_Client_GetTldInfoParams $params)
 * @method SimpleXMLElement getTldInfo (array $params) 
 * @method SimpleXMLElement createDomain (EppDrs_Api_Client_CreateDomainParams $params)
 * @method SimpleXMLElement createDomain (array $params)  
 * @method SimpleXMLElement updateDomainContact (EppDrs_Api_Client_UpdateDomainContactParams $params)
 * @method SimpleXMLElement updateDomainContact (array $params)
 * @method SimpleXMLElement updateDomainNameservers (EppDrs_Api_Client_UpdateDomainNameserversParams $params)
 * @method SimpleXMLElement updateDomainNameservers (array $params)
 * @method SimpleXMLElement updateDomainLock (EppDrs_Api_Client_UpdateDomainLockParams $params)
 * @method SimpleXMLElement updateDomainLock (array $params)
 * @method SimpleXMLElement transferDomain (EppDrs_Api_Client_TransferDomainParams $params)
 * @method SimpleXMLElement transferDomain (array $params)
 * @method SimpleXMLElement importDomains (EppDrs_Api_Client_ImportDomainsParams $params) 
 * @method SimpleXMLElement importDomains (array $params)
 * @method SimpleXMLElement listContacts (EppDrs_Api_Client_ListContactsParams $params)
 * @method SimpleXMLElement listContacts (array $params)
 * @method SimpleXMLElement getBalanceInfo (EppDrs_Api_Client_GetBalanceInfoParams $params)
 * @method SimpleXMLElement getBalanceInfo (array $params)
 * @method SimpleXMLElement renewDomain (EppDrs_Api_Client_RenewDomainParams $params)
 * @method SimpleXMLElement renewDomain (array $params)
 * 
 */
class EppDrs_Api_Client_Service
{
	private $url, $keyId, $key;
	
	private $curl;
	
	private $initialized = faLse;
	
	function __construct ($config)
	{
		foreach ($config as $k => $v)
			$this->{$k} = $v;
	}
	
	private function getCurl()
	{
		if (!$this->curl)
		{
			$this->curl = curl_init();
			
			curl_setopt_array($this->curl, array(
				CURLOPT_URL => $this->url,
				CURLOPT_POST => 1,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_HEADER => 0,
				// For https
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_SSL_VERIFYHOST => 0
			));
		}
		
		return $this->curl;
	}
	
	private function init ()
	{
		if (!$this->initialized)
		{
			if (!$this->keyId || !$this->key)
				throw new Exception("EPP-DRS key-pair is null. "
					. "Check that 'key' and 'keyId' are not missed in configuration passed to __construct");
					
			$this->initialized = true;
		}
	}
	
	function __call ($method, $args)
	{
		$this->init();
		
		$request = array
		(
			"action" => $method,
			"keyId" => $this->keyId
		);

		if ($args[0])
		{
			if (is_array($args[0]))
			{
				$request = array_merge($request, $args[0]);
			}
			elseif ($args[0] instanceof EppDrs_Api_Client_MethodParams)
			{
				$request = array_merge($request, $args[0]->toArray());
			}
		}
			
			
		$request["signature"] = $this->sign($this->getCanonicalString($request), $this->key);
		
		$curl = $this->getCurl();
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($request));
		
		$response_body = curl_exec($curl);
		if ("200" == ($status = curl_getinfo($curl, CURLINFO_HTTP_CODE)))
		{
			return new SimpleXMLElement($response_body);
		}
		else
		{
			try
			{
				$xml = new SimpleXMLElement($response_body);
			}
			catch (Exception $ignore) {}
			
			if ($xml)
				throw new Exception($xml->message);
			else
				throw new Exception($response_body);
		} 
	}
	
	private function getCanonicalString ($request)
	{
		$string2sign = "";
		ksort($request);
		foreach ($request as $k => $v)
			$string2sign .= "{$k}{$v}";

		return $string2sign; 
	}
	
	private function sign ($string2sign, $key)
	{
		return base64_encode(hash_hmac("sha1", $string2sign, $key, true));
	}
}

class EppDrs_Api_Client_MethodParams
{
	function __call ($method, $args)
	{
		$underscored = preg_replace_callback("/[A-Z]/", array($this, "_underscore"), $method);
		$accessor = substr($underscored, 1, 3);
		$property = substr($underscored, 5);
		
		if (property_exists($this, $property))
		{
			if ($accessor == "get")
				return $this->{$property};
			elseif ($accessor == "set")
				$this->{$property} = $args[0];
		}
	}
	
	function _underscore ($matches)
	{
		return "_".strtolower($matches[0]);
	}
	
	function formatProperies ($properties)
	{
		$ret = array();
		
		foreach ($properties as $k => $v)
		{
			if ($v !== null)
				$ret[$k] = $v;
		}
				
		$keys = array_keys($ret);
		foreach ($keys as &$k)
		{
			$k = join("", array_map("ucfirst", explode("_", $k)));
			$k = strtolower($k{0}).substr($k, 1);
		} 
		
		return array_combine($keys, array_values($ret));
	}
	
	function toArray () {
		return $this->formatProperies(get_object_vars($this)); 
	}
}

/**
 * 
 * @author marat
 * @method void setQuery (string $query)
 * @method string getQuery ()
 * @method string getCreateDate ()
 * @method array getCreateDateRange () 
 * @method string getExpireDate ()
 * @method array getExpireDateRange ()
 * @method void setLimit (int $limit) 
 * @method int getStart ()
 */
class EppDrs_Api_Client_ListDomainsParams extends EppDrs_Api_Client_MethodParams
{
	protected $query; 
	protected $create_date, $create_date_range; 
	protected $expire_date, $expire_date_range;
	protected $contact;
	protected $user_id;
	protected $start, $limit;
	
	function setCreateDate ($date) 
	{ 
		$this->create_date_range = null; 
		$this->create_date = $this->parseDate($date);
	}
	
	function setCreateDateRange ($start_date, $end_date)
	{
		$this->create_date = null;
		$this->create_date_range = array
		(
			$this->parseDate($start_date), $this->parseDate($end_date)
		);
	}
	
	function setExpireDate ($date) 
	{ 
		$this->expire_date_range = null; 
		$this->expire_date = $this->parseDate($date); 
	}
	
	function setExpireDateRange ($start_date, $end_date)
	{
		$this->expire_date = null;
		$this->expire_date_range = array
		(
			$this->parseDate($start_date), $this->parseDate($end_date)
		);
	}
	
	function setNoLimits ()
	{
		$this->start = null;
		$this->limit = -1;
	}
	
	private function parseDate ($date)
	{
		return date("Y-m-d", is_numeric($date) ? $date : strtotime($date));
	}
	
	//function toArray () { return $this->formatProperies(get_object_vars($this)); }	
}

/**
 * 
 * @author marat
 * @method void setName (string $name)
 * @method string getName ()
 */
class EppDrs_Api_Client_CheckDomainParams extends EppDrs_Api_Client_MethodParams
{
	protected $name;
}

/**
 * 
 * @author marat
 * @method void setName (string $name)
 * @method string getName ()
 * @method void setMode (string $name)
 * @method string getMode ()
 */
class EppDrs_Api_Client_GetDomainInfoParams extends EppDrs_Api_Client_MethodParams
{
	const MODE_LOCAL = "local";
	const MODE_REGISTRY = "registry";
	
	protected $name;
	protected $mode = self::MODE_LOCAL;

}

/**
 * 
 * @author marat
 * @method void setNames (array $name)
 * @method array getNames ()
 * @method void setDefaultTld (string $default_tld)
 * @method string getDefaultTld ()
 * @method void setUserId (int $user_id)
 * @method int getUserId () 
 */
class EppDrs_Api_Client_ImportDomainsParams extends EppDrs_Api_Client_MethodParams
{
	protected $names;
	protected $default_tld;
	protected $user_id;
}

/**
 * 
 * @author marat
 * @method void setQuery (string $query)
 * @method string getQuery ()
 * @method void setUserId (int $user_id)
 * @method int getUserId ()
 * @method void setStart (int $start)
 * @method int getStart ()
 * @method void setLimit (int $limit)
 * @method int getLimit ()
 */
class EppDrs_Api_Client_ListContactsParams extends EppDrs_Api_Client_MethodParams
{
	protected $query;
	protected $user_id;
	protected $start, $limit; 
	
	function setNoLimits ()
	{
		$this->start = null;
		$this->limit = -1;
	}
}

/**
 * 
 * @author marat
 * @method void setUserId (int $user_id)
 * @method int getUserId ()
 */
class EppDrs_Api_Client_GetBalanceInfoParams extends EppDrs_Api_Client_MethodParams
{
	protected $user_id;
}

/**
 * 
 * @author marat
 * @method void setTld (string $tld)
 * @method string getTld ()
 */
class EppDrs_Api_Client_GetTldInfoParams extends EppDrs_Api_Client_MethodParams
{
	protected $tld;
}

/**
 * 
 * @author marat
 * @method void setName (string $name)
 * @method string getName ()
 * @method void setPeriod (int $period)
 * @method int getPeriod ()
 * @method void setRegistrant (string $clid)
 * @method string getRegistrant ()
 * @method void setAdmin (string $clid)
 * @method string getAdmin ()
 * @method void setBilling (string $clid)
 * @method string getBilling ()
 * @method void setTech (string $clid)
 * @method string getTech ()
 * @method void setExtraFields (array $ns)
 * @method array getExtraFields ()
 * @method void setUserId (int $user_id)
 * @method int getUserId ()
 * @method void setNoBilling (bool $no_billing)
 * @method bool getNoBilling ()
 */
class EppDrs_Api_Client_CreateDomainParams extends EppDrs_Api_Client_MethodParams
{
	protected $name;
	protected $period; 
	protected $registrant, $admin, $billing, $tech;
	protected $ns;
	protected $extra_fields;
	protected $user_id;
	protected $no_billing;

	function SetNS (array $ns) { $this->ns = $ns; }
	function GetNS () { return $this->ns; }
}

/**
 * 
 * @author marat
 * @method void setName (string $name)
 * @method string getName ()
 * @method void setContactType (string $contact_type)
 * @method string getContactType ()
 * @method void setClid (string $clid)
 * @method string getClid ()
 * @method void setNoBilling (bool $no_billing)
 * @method bool getNoBilling ()
 */
class EppDrs_Api_Client_UpdateDomainContactParams extends EppDrs_Api_Client_MethodParams
{
	protected $name;
	protected $contact_type;
	protected $clid;
	protected $no_billing; // For trade	
}

/**
 * 
 * @author marat
 * @method void setName (string $name)
 * @method string getName ()
 */
class EppDrs_Api_Client_UpdateDomainNameserversParams extends EppDrs_Api_Client_MethodParams
{
	protected $name;
	protected $ns;
	
	function SetNS (array $ns) { $this->ns = $ns; }
	function GetNS () { return $this->ns; }	
}

/**
 * 
 * @author marat
 * @method void setName (string $name)
 * @method string getName ()
 * @method void setLocked (boolean $locked)
 * @method boolean getLocked ()
 */
class EppDrs_Api_Client_UpdateDomainLockParams extends EppDrs_Api_Client_MethodParams
{
	protected $name;
	protected $locked;
}

/**
 * 
 * @author marat
 * @method void setName (string $name)
 * @method string getName ()
 * @method void setAuthCode (string $auth_code)
 * @method string getAuthCode ()
 * @method void setRegistrant (string $clid)
 * @method string getRegistrant ()
 * @method void setAdmin (string $clid)
 * @method string getAdmin ()
 * @method void setBilling (string $clid)
 * @method string getBilling ()
 * @method void setTech (string $clid)
 * @method string getTech ()
 * @method void setExtraFields (array $extra_fields)
 * @method array getExtraFields ()
 * @method void setUserId (int $user_id)
 * @method int getUserId ()
 * @method void setNoBilling (bool $no_billing)
 * @method bool getNoBilling () 
 */
class EppDrs_Api_Client_TransferDomainParams extends EppDrs_Api_Client_MethodParams
{
	protected $name;
	protected $auth_code;
	protected $registrant, $admin, $billing, $tech;
	protected $extra_fields;
	protected $user_id;
	protected $no_billing;	
}


/**
 * 
 * @author marat
 * @method void setName (string $name)
 * @method string getName ()
 * @method void setUserId (int $user_id)
 * @method int getUserId ()
 * @method void setNoBilling (bool $no_billing)
 * @method bool getNoBilling ()
 * @method void setPeriod(int $period)
 * @method int getPeriod() 
 */
class EppDrs_Api_Client_RenewDomainParams extends EppDrs_Api_Client_MethodParams
{
	protected $name;
	protected $period;
	protected $user_id;
	protected $no_billing;
}
?>