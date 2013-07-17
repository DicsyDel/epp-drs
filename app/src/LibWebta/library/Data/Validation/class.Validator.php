<?
	/**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    Data
     * @subpackage Validation
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	// A simple checkdnsrr for Windows. Needed for Validator::IsEmail() 
	if(!function_exists('checkdnsrr'))
	{
	    function checkdnsrr($hostName, $recType = '') 
	    { 
	     if(!empty($hostName)) { 
	       if( $recType == '' ) $recType = "MX"; 
	       exec("nslookup -type=$recType $hostName", $result); 
	       // check each line to find the one that starts with the host 
	       // name. If it exists then the function succeeded. 
	       foreach ($result as $line) { 
	         if(eregi("^$hostName",$line)) { 
	           return true; 
	         } 
	       } 
	       // otherwise there was no mail handler for the domain 
	       return false; 
	     } 
	     return false;
	    }
	}

	/**
     * @name Validator
     * @version 1.0
     * @category   LibWebta
     * @package    Data
     * @subpackage Validation
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class Validator extends Core
	{
		const ERROR_EMPTY = "%s cannot be empty";
		const ERROR_NUMERIC = "%s must be a number";
		const ERROR_NUMERIC_RANGE = "%s must be a number between %s and %s";
		const ERROR_ALPHA = "%s must contain english letters only";
		const ERROR_EQUAL = "%s must be equal";
		const ERROR_PATTERN = "%s is invalid";
		const ERROR_IP = "%s must be valid IP addres";
		const ERROR_URL = "%s must be valid URL";
		const ERROR_DOMAIN = "%s must be valid domain name";
		const ERROR_EMAIL = "%s must be valid E-mail address";
		const ERROR_ALPHANUM = "%s must contain english letters or numbers only";
		const ERROR_XSS = "%s contains disallowed characters";
		const ERROR_LENGTH = "%s must be longer than %s characters";
		const ERROR_DATE = "%s must be valid date";
		const ERROR_E164PHONE = "%s must be in XXX-XXX-XXXX format. Minimum length of last field is 4, maximum: 12";
		
		/**
		 * Errors
		 *
		 * @var array
		 */
		public $Errors;
		
		/**
		 * Validator constructor
		 *
		 */
		function __construct()
		{
			$this->Errors = array();
		}
		
		/**
		 * Scan strings for debug code
		 *
		 * @param string $string
		 * @return bool
		 */
		public static function ScanForDebugCode($string)
		{
			return preg_match("/((apd_[A-Za-z]*\()|(xdebug_[A-Za-z]*\()|(eval\()|(var_dump\()|(print_r\()|(classkit_[a-zA-Z_]*\()|(create_function)|(call_user_func)|(Reflection[A-Za-z]+\())/si", $string, $matches);
		}
		
		/**
		 * Return true if $var is empty
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 */
		public function IsNotEmpty($var, $name = null, $error = null)
		{
			$retval = !empty($var);
			
			if (!$retval)
				$this->AddError(self::ERROR_EMPTY, $var, $name, $error);
				
			return $retval;
		}
		
		/**
		 * Return true if $var is numeric
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 */
		public function IsNumeric($var, $name = null, $error = null)
		{
			$retval = is_numeric($var);
			
			if (!$retval)
				$this->AddError(self::ERROR_NUMERIC, $var, $name, $error);
				
			return $retval;
		}
		
		/**
		 * Return true if $var is numeric
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 */
		public function IsInRange($var, $min = null, $max = null, $strict, $name = null, $error = null)
		{
			$retval = $this->IsNumeric($var);
			if (!is_null($min))
				$retval &= $strict ? ($var > $min) : ($var >= $min);
			if (!is_null($max))
				$retval &= $strict ? ($var < $man) : ($var <= $max);
			
			if (!$retval)
			{
				$this->AddError(self::ERROR_NUMERIC_RANGE, $var, $name, $error);
			}
				
			return $retval;
		}
		
		/**
		 * Return true if $var is alpha
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 */
		public function IsAlpha($var, $name = null, $error = null)
		{
			$retval = preg_match("/^[[:alpha:]]+$/", $var);
			
			if (!$retval)
				$this->AddError(self::ERROR_ALPHA, $var, $name, $error);
				
			return $retval;
		}
		
		public function IsE164Phone($var, $name = null, $error = null)
		{
			$retval = preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4,12}$/", $var);
			
			if (!$retval)
				$this->AddError(self::ERROR_E164PHONE, $var, $name, $error);
				
			return $retval;
		}
		
		/**
		 * Return true if $var1 equal $var2
		 *
		 * @param mixed $var1
		 * @param mixed $var2
		 * @param sting $name
		 * @param string $error
		 * @return bool
		 */
		public function AreEqual($var1, $var2, $name = null, $error = null)
		{
			$retval = ($var1 === $var2);
			
			if (!$retval)
				$this->AddError(self::ERROR_EQUAL, "{$var1} and {$var2}", $name, $error);
				
			return $retval;
		}
		
		/**
		 * Return true if $var matches pattern $pattern
		 *
		 * @param mixed $var
		 * @param string $pattern
		 * @param string $name
		 * @param string $error
		 * @return bool
		 */
		public function MatchesPattern($var, $pattern, $name = null, $error = null)
		{
			$retval = preg_match($pattern, $var);
			
			if (!$retval)
				$this->AddError(self::ERROR_PATTERN, $var, $name, $error);
				
			return $retval;
		}
		
		
		/**
		 * Return true is $var is valid Date
		 * (allowed date formats: Y-m-d, Y/m/d, Y m d or Y.m.d)
		 * 
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return array
		 */
		public function IsDate($var, $name = null, $error = null)
		{
			$retval = preg_match("/^(19|20)\d\d[\.\s\/\-](0[1-9]|1[012])[\.\s\/\-](0[1-9]|[12][0-9]|3[01])$/", $var);
			
			if (!$retval)
				$this->AddError(self::ERROR_DATE, $var, $name, $error);
				
			return $retval;
		}
		
		/**
		 * Return true if $var is valid IP address
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return array
		 */
		public function IsIPAddress($var, $name = null, $error = null)
		{
			$retval = (ip2long($var) === false) ? false : true;			
			
			if (!$retval)
				$this->AddError(self::ERROR_IP, $var, $name, $error);
				
			return $retval;
		}
		
		
		/**
		 * Checks either a $var is a local network IP address or broadcast IP address.
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return array
		 */
		public function IsExternalIPAddress($var, $name = null, $error = null)
		{
			$internal_classA = (bool)preg_match('/^10(\.[0-9]{1,3}){3}$/', $var);
			$internal_classB = (bool)preg_match('/^172\.(1[6-9]|2[0-9]|31){1}(\.[0-9]{1,3}){2}$/', $var);
			$internal_classC = (bool)preg_match('/^192.168(\.[0-9]{1,3}){2}$/', $var);
			
			$bcast = (bool)preg_match('/^([0-9]{1,3}\.){3}(255|0)$/', $var);
			
			$retval = !($internal_classA || $internal_classB || $internal_classC || $bcast);
			
			if (!$retval)
				$this->AddError(self::ERROR_IP, $var, $name, $error);
				
			return $retval;
		}
		
		
		/**
		 * Returns true if $var is valid URL
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 */
		public function IsURL($var, $name = null, $error = null)
		{
			$retval = preg_match("/^(http:\/\/)?(www\.)?([A-Za-z0-9]+[A-Za-z0-9-]*[A-Za-z0-9]+[\.]){2,}$/", $var.".");
		
			if (!$retval)
				$this->AddError(self::ERROR_URL, $var, $name, $error);
				
			return $retval;
		}
		
		
		/**
		 * Returns true if $var is valid domain name
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 */
		public function IsDomain($var, $name = null, $error = null, $allowed_utf8_chars = "", $disallowed_utf8_chars = "")
		{	
			// Remove trailing dot if its there. FQDN may contain dot at the end!
			$var = rtrim($var, ".");
			
			$retval = (bool)preg_match('/^([a-zA-Z0-9'.$allowed_utf8_chars.']+[a-zA-Z0-9-'.$allowed_utf8_chars.']*\.[a-zA-Z0-9'.$allowed_utf8_chars.']*?)+$/usi', $var);
							
			if ($disallowed_utf8_chars != '')
				$retval &= !(bool)preg_match("/[{$disallowed_utf8_chars}]+/siu", $var);
			
			
			if (!$retval)
				$this->AddError(self::ERROR_DOMAIN, $var, $name, $error);
			
			return $retval;
		}
		

		/**
		 * Return true if $var is valid e-mail. RFC-compliant.
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 * @link http://www.linuxjournal.com/article/9585
		 */
		function IsEmail($var, $name = null, $error = null, $check_dns = false)
		{
			$email = $var;
		   $isValid = true;
		   $atIndex = strrpos($email, "@");
		   if (is_bool($atIndex) && !$atIndex)
		   {
		      $isValid = false;
		   }
		   else
		   {
		      $domain = substr($email, $atIndex+1);
		      $local = substr($email, 0, $atIndex);
		      $localLen = strlen($local);
		      $domainLen = strlen($domain);
		      if ($localLen < 1 || $localLen > 64)
		      {
		         // local part length exceeded
		         $isValid = false;
		      }
		      else if ($domainLen < 1 || $domainLen > 255)
		      {
		         // domain part length exceeded
		         $isValid = false;
		      }
		      else if ($local[0] == '.' || $local[$localLen-1] == '.')
		      {
		         // local part starts or ends with '.'
		         $isValid = false;
		      }
		      else if (preg_match('/\\.\\./', $local))
		      {
		         // local part has two consecutive dots
		         $isValid = false;
		      }
		      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
		      {
		         // character not valid in domain part
		         $isValid = false;
		      }
		      else if (preg_match('/\\.\\./', $domain))
		      {
		         // domain part has two consecutive dots
		         $isValid = false;
		      }
		      else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
		                 str_replace("\\\\","",$local)))
		      {
		         // character not valid in local part unless 
		         // local part is quoted
		         if (!preg_match('/^"(\\\\"|[^"])+"$/',
		             str_replace("\\\\","",$local)))
		         {
		            $isValid = false;
		         }
		      }
		      if ($check_dns)
		      {
			      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
			      {
			         // domain not found in DNS
			         $isValid = false;
			      }
		      }
		   }
		   return $isValid;
		}

		
		/**
		 * Return true if $var is valid e-mail. RFC-compliant. Also checks in DNS.
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 * @link http://www.linuxjournal.com/article/9585
		 */
		function IsEmailPlusDNS($var, $name = null, $error = null)
		{
			Validator::IsEmail($var, $name, $error, true);
		}
		
		/**
		 * Return true if $var is valid e-mail
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 */
		public function IsEmailRestrictive($var, $name = null, $error = null)
		{
			$retval = preg_match("/^[a-zA-Z0-9]+([a-zA-Z0-9_\.-]*[a-zA-Z0-9])*?@([a-zA-Z0-9]+[a-zA-Z0-9-]*[a-zA-Z0-9]+\.)+[a-zA-Z]{2,5}$/i", $var);
			
			if (!$retval)
				$this->AddError(self::ERROR_EMAIL, $var, $name, $error);
				
			return $retval;
		}
		
		/**
		 * Return true if $var is alphanumeric
		 *
		 * @param mixed $var
		 * @param string $name
		 * @param string $error
		 * @return bool
		 */
		public function IsAlphaNumeric($var, $name = null, $error = null)
		{
			$retval = preg_match("/^[A-Za-z0-9]+$/si", $var);
		
			if (!$retval)
				$this->AddError(self::ERROR_ALPHANUM, $var, $name, $error);
				
			return $retval;	
		}
		
		public function IsXSSClean()
		{
			//TODO: I don't know what i can write here!	
		}
		
		/**
		 * Add error to errors stack
		 *
		 * @param string $error_template
		 * @param string $var
		 * @param string $name
		 * @param string $error_str
		 */
		public function AddError($error_template, $var, $name = null, $error_str = null)
		{
			if ($error_str)
				array_push($this->Errors, $error_str);
			elseif ($name)
				array_push($this->Errors, sprintf($error_template, $name));
			else 
				array_push($this->Errors, sprintf($error_template, "'{$var}'"));
		}
		
		/**
		 * Validate all data in array
		 *
		 * @param array $array
		 * @return bool or array of errors
		 */
		public function ValidateAll($array)
		{
			$this->Errors = array();
					
			foreach($array as $var=>$method)
			{
				if (method_exists($this, $method))
				{
					eval("\$this->{$method}('{$var}');");
				}
				else
					Core::RaiseWarning("{$method} is not valid method of Validator class.");
			}
			
			if ($this->HasErrors())
				return $this->Errors;
			else 
				return true;
		}
		
		/**
		 * Return true if we have errors
		 *
		 * @return bool
		 */
		public function HasErrors()
		{
			return (count($this->Errors) > 0);
		}
		
		/**
		 * Return last error message
		 *
		 * @return bool
		 */
		public function GetLastError()
		{
			return $this->Errors[count($this->Errors) -1];
		}
	}		
	
?>