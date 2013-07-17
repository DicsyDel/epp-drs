<?php

class ZendLicense 
{
	const PRODUCT_NAME = "Product-Name";
	const REGISTERED_TO = "Registered-To";
	const EXPIRES = "Expires";
	const HARDWARE_LOCKED = "Hardware-Locked";
	const HOST_ID = "Host-ID";
	const PRODUCED_BY = "Produced-By";
	const VERIFICATION_CODE = "Verification-Code";
	
	const X_LICENSE_ID = "X-License-ID";
	
	private static $eol = "\n";
	private static $eq = " = ";
	
	public $product_name;
	public $registered_to;
	public $expires;
	public $hardware_locked;
	public $produced_by;
	public $verification_code;

	public $host_id = array();	
	public $user_directives = array();
	
	public function __toString () 
	{
		$buf = array();
		$eq = self::$eq;
		$eol = self::$eol;
		
		// Product-Name
		$buf[] = self::PRODUCT_NAME . $eq . $this->product_name . $eol;
		// Registered-To
		$buf[] = self::REGISTERED_TO . $eq . $this->registered_to . $eol;
		// Expires
		if ($this->expires != null) 
			$buf[] = self::EXPIRES . $eq . date("d-M-Y", $this->expires) . $eol;
		else
			$buf[] = self::EXPIRES . $eq . "Never" . $eol;
		// Hardware-Locked
		$buf[] = self::HARDWARE_LOCKED . $eq . ($this->IsHardwareLocked() ? "Yes" : "No") . $eol;
		// Host-ID
		if ($this->host_id)
		{
			foreach ($this->host_id as $hid)
				$buf[] = self::HOST_ID . $eq . $hid .$eol;
		} 
		else if (!$this->hardware_locked && !$this->verification_code)
		{
			// Exists only in non signed license 
			$buf[] = self::HOST_ID . $eq . "Not-Locked" . $eol;  
		}
		
		// User directives
		if ($this->user_directives)
		{
			foreach ($this->user_directives as $key => $value)
				$buf[] = $key . $eq . $value . $eol;
		}
		// Produced-By
		$buf[] = self::PRODUCED_BY . $eq . $this->produced_by . $eol;
		// Verification-Code
		if ($this->verification_code != null)
			$buf[] = self::VERIFICATION_CODE . $eq . $this->verification_code . $eol;
		
		return join("", $buf);
	}
	
	/**
	 * Parse license containing string
	 * @param license
	 * @return
	 * @throws Exception on parse error
	 */
	public static function FromString ($license_string) 
	{
		$license = new ZendLicense();
		
		$pair_re = "/([^=]+)=(.*)\n?\r?/";
		preg_match_all($pair_re, $license_string, $m);
		
		if (count($m) > 2)
		{
			$keys = array_map("trim", $m[1]);
			$values = array_map("trim", $m[2]);
			foreach ($keys as $i => $key)
			{
				if (!$key)
					throw new Exception("Cannot parse line '{$m[0]}'");
					
				$value = $values[$i];
				
				if (self::PRODUCT_NAME == $key)
					$license->product_name = $value;
				elseif (self::REGISTERED_TO == $key)
					$license->registered_to = $value;
				elseif (self::EXPIRES == $key)
				{
					if ("Never" != $value)
						$license->expires = strtotime($value);
				}
				elseif (self::HARDWARE_LOCKED == $key)
					$license->hardware_locked = $value == "Yes";
				elseif (self::HOST_ID == $key)
				{
					if ("Not-Locked" != $value)
						$license->host_id[] = $value;
				}
				elseif (self::PRODUCED_BY == $key)
					$license->produced_by = $value;
				elseif (self::VERIFICATION_CODE == $key)
					$license->verification_code = $value;
				else
					$license->user_directives[$key] = $value;
			}
		}

		// Check required properties
		if (!$license->product_name)
		{
			throw new Exception(sprintf("%s property is required", self::PRODUCT_NAME));
		}
		else if (!$license->produced_by)
		{
			throw new Exception(sprintf("%s property is required", self::PRODUCED_BY));
		}
		else if (!$license->registered_to)
		{
			throw new Exception(sprintf("%s property is required", self::REGISTERED_TO));
		}
		
		return $license;
	}
	
	function Hash ()
	{
		$license_string = $this->__toString();
		$canonical_string = "";		
		
		$pair_re = "/([^=]+)=(.*)\n?\r?/";
		preg_match_all($pair_re, $license_string, $m);
		
		if (count($m) > 2)
		{
			$keys = array_map("trim", $m[1]);
			$values = array_map("trim", $m[2]);
			foreach ($keys as $i => $key)
			{
				if ($key)
					$canonical_string .= $key.$values[$i];
			}
			
			return hash("sha256", $canonical_string);			
		}
		
		return "";
	}
	
	function IsNeverExpires () 
	{
		return $this->expires === null;
	}

	function IsHardwareLocked()	
	{
		return (bool) $this->host_id || $this->hardware_locked;
	}
}
?>