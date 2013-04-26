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
     * @package    Security
     * @subpackage OpenSSL
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
	 * @name       SSLManager
	 * @category   LibWebta
     * @package    Security
     * @subpackage OpenSSL
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 *
	 */
	class SSLManager extends Service 
	{
		
		/**
		* Path to OpenSSL binary
		* @var string
		* @access public
		*/
		var $OpenSSL;
		
		
		/**
		* Path to OpenSSL binary
		* @var string
		* @access public
		*/
		var $SSLRoot;
		
		/**
		* Current date in j-n-Y format
		* @var string
		* @access public
		*/
		protected $Date;
		
		
		function __construct()
		{
			parent::__construct();
			$this->Shell = CP::GetShellInstance();
			$this->Validator = CP::GetValidatorInstance();
			$this->OpenSSL = CF_ENV_OPENSSL;
			$this->SSLRoot = CF_ENV_HOMEROOT ."/". CF_ENV_HOMENAME ."/". CF_ENV_SSLROOT;
			
			$this->Date = date("j-n-Y");
		}
		
		
		
		/**
		* Generate new private RSA key
		* @access public
		* @param string $username System username
		* @param string $domain Undotted DNS Zone
		* @param string $keylength Bitlength of the key (default is 1024)
		* @return void 
		*/

		public function GenerateRSAKey($username, $domain, $keylength=1024) 
		{
			// Lock to user 
			$this->SSLRoot = str_replace("{username}", $username, $this->SSLRoot);
			
			// Rotate existing
			@rename("{$this->SSLRoot}/{$domain}.key", "{$this->SSLRoot}/{$domain}.key.{$this->Date}");
			$retval = $this->Shell->ExecuteRaw("{$this->OpenSSL} 
			genrsa 
			$keylength 
			> {$this->SSLRoot}/{$domain}.key");	
			
			return($retval);
		}
		
		
		/**
		* Generate new certificate signing request
		* @access public
		* @param string $username System username
		* @param string $domain Undotted DNS Zone
		* @param string $email Owner email
		* @param string $location Location
		* @param string $country Country, 2 letters abbreviation
		* @param string $state State
		* @param string $company Company
		* @param string $days Number of days (default is 365)
		* @return SystemUser SystemUser instance 
		*/

		public function GenerateSigningRequest($username, $domain, $email, $location, $country, $state, $company, $days=365) 
		{
			
			// Sanity
			$country = $this->NormalizeAbbreviation($country);
			$state = $this->NormalizeAbbreviation($state);
			$company = $this->NormalizeCompany($company);
			$location = $this->NormalizeCompany($location);
			if (!$this->Validator->CheckIsValidEmail($email))
				$this->RaiseWarning("$email does not appear to be a valid email address");

			
			// Lock to user 
			$this->SSLRoot = str_replace("{username}", $username, $this->SSLRoot);
			
			// Rotate existing
			@rename("{$this->SSLRoot}/{$domain}.csr", "{$this->SSLRoot}/{$domain}.csr.{$this->Date}");
			
			$retval = $this->Shell->ExecuteRaw("{$this->OpenSSL}
			req
			-new
			-key {$this->SSLRoot}/{$domain}.key
			-out {$this->SSLRoot}/{$domain}.csr
			-nodes
			-subj /C=$country/ST=$state/L=$location/O=$company/CN=$domain/emailAddress=$email");	
			
			return($retval);
		}
		
		
		/**
		* Generate new certificate signing request
		* @access public
		* @param string $username System username
		* @param string $domain Undotted DNS Zone
		* @param string $email Owner email
		* @param string $location Location
		* @param string $country Country
		* @param string $state State
		* @param string $company Company
		* @param string $days Number of days (default is 365)
		* @return SystemUser SystemUser instance 
		*/

		public function GenerateCert($username, $domain, $email, $location, $country, $state, $company, $days=365) 
		{
			
			// Sanity
			$country = $this->NormalizeAbbreviation($country);
			$state = $this->NormalizeAbbreviation($state);
			$company = $this->NormalizeCompany($company);
			$location = $this->NormalizeCompany($location);
			if (!$this->Validator->CheckIsValidEmail($email))
				$this->RaiseWarning("$email does not appear to be a valid email address");
			
			// Lock to user 
			$this->SSLRoot = str_replace("{username}", $username, $this->SSLRoot);
			
			// Rotate existing
			@rename("{$this->SSLRoot}/{$domain}.crt", "{$this->SSLRoot}/{$domain}.crt.{$this->Date}");
			
			$retval = $this->Shell->ExecuteRaw("{$this->OpenSSL}
			req
			-new
			-x509
			-days $days
			-key {$this->SSLRoot}/{$domain}.key
			-subj /C=$country/ST=$state/L=$location/O=$company/CN=$domain/emailAddress=$email
			> {$this->SSLRoot}/{$domain}.crt
			2>/dev/null");	
			
			return($retval);
			
		}
		
		
		/**
		* Move old certificates stuff with certname.date
		* @access public
		* @param string $username System username
		* @param string $domain Undotted DNS Zone
		* @return void 
		*/

		public function RotateCerts($username, $domain) 
		{
			// Lock to user 
			$this->SSLRoot = str_replace("{username}", $username, $this->SSLRoot);
			
			$retval =  @rename("{$this->SSLRoot}/{$domain}.key", "{$this->SSLRoot}/{$domain}.key.{$this->Date}");
			$retval &= @rename("{$this->SSLRoot}/{$domain}.crt", "{$this->SSLRoot}/{$domain}.crt.{$this->Date}");
			$retval &= @rename("{$this->SSLRoot}/{$domain}.csr", "{$this->SSLRoot}/{$domain}.csr.{$this->Date}");
			
			return($retval);
			
		}
		
		
		/**
		* Delete old cert file
		* @access public
		* @param string $username System username
		* @param string $domain Undotted DNS Zone
		* @param string $filename Extension after domain name. Example: csr, crt
		* @return bool operation status 
		*/

		public function DeleteFile($username, $domain, $filename) 
		{
			// Lock to user 
			$this->SSLRoot = str_replace("{username}", $username, $this->SSLRoot);
			
			$path = "{$this->SSLRoot}/{$domain}.$filename";
			
			$retval =  unlink($path);
			$retval &= !file_exists($path); 
			
			return($retval);
			
		}
		
		
		/**
		* Normalize to 2 letter uppercase abbreviation
		* @access public
		* @param string $country Country
		* @return string Normalized country 
		*/
		protected function NormalizeAbbreviation($country)
		{
			$retval = strtoupper($country);
			if (strlen($country) != 2)
				$this->RaiseWarning("$country is not valid 2-letter abbreviation");
			return($country);
		}
		
		
		/**
		* Normalize company
		* @access public
		* @param string $country Company
		* @return string Normalized company 
		*/
		protected function NormalizeCompany($company)
		{
			$retval = preg_replace("/[^a-z0-9]/i","", $company);
			if ($this->Validator->CheckIsAlphanum($company))
				$this->RaiseWarning("$company s not valid alphanumeric string");
			return($retval);
		}
		
	

	}
?>