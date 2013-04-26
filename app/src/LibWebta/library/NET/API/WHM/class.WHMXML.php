<?
    
	/**
     * @name       WHMXML
     * @package    WHMXML
     * @subpackage XMLAPI
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class WHMXML extends Core
	{
		
		/**
		 * Default connection timeout in seconds
		 */
		const CONNECT_TIMEOUT = 150;
		
		/**
		 * Maximum amount of seconds for a single request
		 */
		const EXEC_TIMEOUT = 150;
		
		/**
		 * Default retries count
		 */
		const EXEC_RETRIES = 1;
		
		const URLBASE = "/xml-api/";
		
		public $Host;
		public $Login;
		public $AccessKey;
		public $UseHTTPS;
		public $Errors;
		public $ExecTimeout;
		public $ConnectTimeout;
		public $ExecRetries;
		protected $ExecRetry;
		protected $URLBase;
		protected $ErrorPatterns = 
		array (
			"404 Not Found" => "% not found. Most likely you are connecting to an older version of WHM",
			"WWW-Authenticate" => "Authentication failed"
		);
		
		
		function __construct($host)
		{
			$this->Host = $host;
			
			// Default values
			$this->ConnectTimeout =  self::CONNECT_TIMEOUT;
			$this->ExecTimeout =  self::EXEC_TIMEOUT;			
			$this->ExecRetries = self::EXEC_RETRIES;
			$this->UseHTTPS = true;
			$this->URLBase = self::URLBASE;
			$this->ExecRetry = 0;
			
		}
		
		
		
		public function Authenticate($login, $access_key)
		{
			$this->Login = $login;
			$this->AccessKey = preg_replace("'(\r|\n)'", "", $access_key);
		}
	
		
		public function Request($url, $args = null) 
		{
			$this->Error = null;
			$args = @http_build_query($args);
			$authstr = "{$this->Login}:{$this->AccessKey}";
			
			if (function_exists("curl_init")) 
			{
				try
				{
					$curl = curl_init();
					
					if ($this->UseHTTPS) 
					{
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 
						curl_setopt($curl, CURLOPT_URL, "https://{$this->Host}:2087{$this->URLBase}{$url}?{$args}");
					} 
					else
						curl_setopt($curl, CURLOPT_URL, "http://{$this->Host}:2086{$this->URLBase}{$url}?{$args}");
					
					curl_setopt($curl, CURLOPT_HEADER, 0);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl, CURLOPT_FAILONERROR, 1);
					
					// Timeouts
					curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->ConnectTimeout);
					curl_setopt($curl, CURLOPT_TIMEOUT, $this->ExecTimeout);
					
					// Authorization header
					$headers[0] = "Authorization: WHM $authstr";
					curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
					
					do 
					{
						$this->ExecRetry++;
						$result = curl_exec($curl);
						$curl_error = curl_error($curl);
						$curl_errno = curl_errno($curl);
						
						$message = "WHM request on {$url} failed with error {$curl_errno}/{$curl_error} and maximum amount of attempts ({$this->ExecRetries}) reached.";
						if ($this->ExecRetry > $this->ExecRetries)
						{
							switch ($curl_errno)
							{
								case CURLE_HTTP_RETURNED_ERROR;
								$message .= "Most likely you are connecting to an older version of WHM. {$curl_error}";
									break;
							}
							throw new Exception($message);
						}
					}
					while (!$result);
					@curl_close($ch);
					
					//
					// Parse response and handle common errors
					//
					
					foreach ($this->ErrorPatterns as $pk=>$pv)
					{
						if (preg_match("/{$pk}/ms", $result, $matches))
							throw new Exception(sprintf($pv, $url));
					}
					
				}
				catch(Exception $ex)
				{
					throw new Exception("WHM request on {$url} failed. ". $ex->getMessage());
				}
			} 
			
			return($result);
				
		}
		
		private function CutXML ($roottag, $result)
		{
			preg_match("/<{$roottag}>.*?<\/{$roottag}>/msi", $result, $m);
			return($m[0]);
		}
		
			
		/**
		 * Convert PHP bolean to CPanel's notation (y/n)
		 * @param bool $value
		 * @return Cstring CPanel-ready value
		 */
		private function Bool2YN ($value)
		{
			return($value ? "y" : "n");
		}
		
		/**
		 * Convert PHP bolean to CPanel's notation (1/0)
		 * @param bool $value
		 * @return Cstring CPanel-ready value
		 */
		private function Bool2Digit($value)
		{
			return($value ? "1" : "0");
		}
		
		/**
		 * Convert PHP bolean to CPanel's notation (y/n)
		 * @param bool $value
		 * @return Cstring CPanel-ready value
		 */
		private function Empty2Unlimited($value)
		{
			return($value ? $value : "0");
		}

		/**
		 * List all available XML API functions.
         *
         * @return array Array of available methods or False on failure
         */
		public function Applist()
		{
			$result = $this->Request("applist");
			try
			{
				$result = new SimpleXMLElement($result);
				$result = (array)$result;
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($result["app"]);
		}
		
		
		/**
		 *Create account.
         *
         * @param string $username User name of the account. Ex: user
         * @param string $domain Domain name. Ex: domain.tld 
         * @param string $plan Package to use for account creation. Ex: reseller_gold  
         * @param int $quota Disk space quota in MB. (0-999999, 0 is unlimited) 
         * @param string $password Password to access cPanel. Ex: p@ss!w0rd$123 
         * @param bool $ip Whether or not the domain has a dedicated IP address.
         * @param bool $cgi Whether or not the domain has cgi access.
         * @param bool $frontpage Whether or not the domain has FrontPage extensions installed. 
         * @param bool $hasshell Whether or not the domain has shell / ssh access.
         * @param string $contactemail Contact email address for the account. Ex: user@otherdomain.tld 
         * @param string $cpmod cPanel theme name. Ex: x3 
         * @param string $maxftp Maximum number of FTP accounts the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxsql Maximum number of SQL databases the user can create. (0-999999 | unlimited, nullis unlimited) 
         * @param string $maxpop Maximum number of email accounts the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxlst Maximum number of mailing lists the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxsub Maximum number of subdomains the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxpark Maximum number of parked domains the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxaddon Maximum number of addon domains the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $bwlimit Bandiwdth limit in MB. (0-999999, null is unlimited)
         * @param string $customip Specific IP address for the site.
         * @param bool $useregns Use the registered nameservers for the domain instead of the ones configured on the server. (1 = Yes, 0 = No) 
         * @param bool hasuseregns
         * @param bool $reseller Give reseller privileges to the account.
         * @return Array of domain options or False on failure
         */
		public function CreateAccount(
			$username, 
			$domain, 
			$plan, 
			$quota, 
			$password, 
			$ip, 
			$cgi, 
			$frontpage, 
			$hasshell,
			$contactemail,
			$cpmod,
			$maxftp,
			$maxsql,
			$maxpop,
			$maxlst,
			$maxsub,
			$maxpark,
			$maxaddon,
			$bwlimit,
			$customip,
			$useregns,
			$hasuseregns,
			$reseller
		)
		{
			$args = array(
			"username" => $username, 
			"domain" => $domain, 
			"plan" => $plan, 
			"quota" => $quota, 
			"password" => $password, 
			"ip" => $this->Bool2YN($ip), 
			"cgi" => $this->Bool2YN($cgi), 
			"frontpage" => $this->Bool2YN($frontpage), 
			"hasshell" => $this->Bool2YN($hasshell),
			"contactemail" => $contactemail,
			"cpmod" => $cpmod,
			"maxftp" => $this->Empty2Unlimited($maxftp),
			"maxsql" => $this->Empty2Unlimited($maxsql),
			"maxpop" => $this->Empty2Unlimited($maxpop),
			"maxlst" => $this->Empty2Unlimited($maxlst),
			"maxsub" => $this->Empty2Unlimited($maxsub),
			"maxpark" => $this->Empty2Unlimited($maxpark),
			"maxaddon" => $this->Empty2Unlimited($maxaddon),
			"bwlimit" => $bwlimit,
			"customip" => $customip,
			"useregns" => $this->Bool2YN($useregns),
			"hasuseregns" => $this->Bool2YN($hasuseregns),
			"reseller" => $this->Bool2YN($reseller));
			
			$result = $this->Request("createacct", $args);
			
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return((array)$result->result->options);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($result["app"]);
			
		}
		
		/**
		 * Lists all accounts on the server or allows you to search for a specific account or set of accounts.
         *
         * @param string $search Search string (Perl Regular Expression)
         * @param string $searchtype One of domain | owner | user | ip | package
         * @return array Array of available methods or False on failure
         */
		public function ListAccounts($search = "", $searchtype = "domain")
		{
			$args = array("search" => $search, "searchtype" => $searchtype);
			
			$result = $this->Request("listaccts", $args);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->status == 0)
				{
					$this->Error = $result->statusmsg;
					return(false);
				}
				else
				{
					$result = (array)$result;
					foreach((array)$result["acct"] as $v)
					{
						$retval[] = (array)$v;	
					}
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 *  Display pertient account information for a specific account.
         *
         * @param string $user CPanel Username
         * @return array Array of account parameters
         */
		public function GetAccountSummary($user)
		{
			$args = array("user" => $user);
			
			$result = $this->Request("accountsummary", $args);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->status == 0)
				{
					$this->Error = $result->statusmsg;
					return(false);
				}
				else
				{
					return((array)$result->acct);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 *  Suspend account
         *
         * @param string $user CPanel Username
         * @return bool Operation status
         */
		public function SuspendAccount($user, $reason = "No reason")
		{
			$args = array("user" => $user, "reason" => $reason);
			
			$result = $this->Request("suspendacct", $args);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 *  displays pertient account information for a specific account.
         *
         * @param string $user CPanel Username
         * @return bool Operation status
         */
		public function UnsuspendAccount($user)
		{
			$args = array("user" => $user);
			
			$result = $this->Request("unsuspendacct", $args);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		
		/**
		 *  displays pertient account information for a specific account.
         *
         * @param string $user CPanel Username
         * @param bool $keepdns Keep DNS entries for the domain 
         * @return bool Operation status
         */
		public function TerminateAccount($user, $keepdns = false)
		{
			$args = array("user" => $user, "keepdns" => $this->Bool2YN($keepdns));
			
			$result = $this->Request("removeacct", $args);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 *  displays pertient account information for a specific account.
         *
         * @param string $user CPanel User name of the account to change the package for
         * @param string $package Name of the package that the account should use. 
         * @return bool Operation status
         */
		public function UpgradeAccount($user, $package)
		{
			$args = array("user" => $user, "pkg" => $package);
			
			$result = $this->Request("changepackage", $args);
			$result = $this->CutXML("changepackage", $result);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		
		/**
		 * Add a new hosting package.
         *
         * @param string $name User name of the account. Ex: user
         * @param string $featurelist  Domain name. Ex: domain.tld 
         * @param int $quota Disk space quota in MB. (0-999999, 0 is unlimited) 
         * @param string $password Password to access cPanel. Ex: p@ss!w0rd$123 
         * @param bool $ip Whether or not the domain has a dedicated IP address.
         * @param bool $cgi Whether or not the domain has cgi access.
         * @param bool $frontpage Whether or not the domain has FrontPage extensions installed. 
         * @param string $cpmod cPanel theme name. Ex: x3 
         * @param string $maxftp Maximum number of FTP accounts the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxsql Maximum number of SQL databases the user can create. (0-999999 | unlimited, nullis unlimited) 
         * @param string $maxpop Maximum number of email accounts the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxlists Maximum number of mailing lists the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxsub Maximum number of subdomains the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxpark Maximum number of parked domains the user can create. (0-999999 | unlimited, null is unlimited) 
         * @param string $maxaddon Maximum number of addon domains the user can create. (0-999999 | unlimited, null is unlimited)
         * @param bool $hasshell Whether or not the domain has shell / ssh access. 
         * @param string $bwlimit Bandiwdth limit in MB. (0-999999, null is unlimited)
         * @param string $customip Specific IP address for the site.
         * @param bool $useregns Use the registered nameservers for the domain instead of the ones configured on the server. (1 = Yes, 0 = No) 
         * @param bool hasuseregns
         * @param bool $reseller Give reseller privileges to the account.
         * @return Array of domain options or False on failure
         */
		public function AddPackage(
			$name, 
			$featurelist, 
			$quota, 
			$ip, 
			$cgi, 
			$frontpage, 
			$cpmod,
			$maxftp,
			$maxsql,
			$maxpop,
			$maxlists,
			$maxsub,
			$maxpark,
			$maxaddon,
			$bwlimit
		)
		{
			$args = array(
			"name" => $name, 
			"featurelist" => $featurelist, 
			"quota" => $quota, 
			"ip" => $this->Bool2Digit($ip), 
			"cgi" => $this->Bool2Digit($cgi), 
			"frontpage" => $this->Bool2Digit($frontpage), 
			"cpmod" => $cpmod,
			"maxftp" => $maxftp,
			"maxsql" => $maxsql,
			"maxpop" => $this->Empty2Unlimited($maxpop),
			"maxlists" => $this->Empty2Unlimited($maxlst),
			"maxsub" => $this->Empty2Unlimited($maxsub),
			"maxpark" => $this->Empty2Unlimited($maxpark),
			"maxaddon" => $this->Empty2Unlimited($maxaddon),
			"hasshell" => $this->Bool2Digit($maxaddon),
			"bwlimit" => $bwlimit
			);
			
			$result = $this->Request("addpkg", $args);
			$result = $this->CutXML("addpkg", $result);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		
		/**
		 *  Delete a specific hosting package.
         *
         * @param string $package Name of the package
         * @return bool Operation status
         */
		public function RemovePackage($package)
		{
			$args = array("pkg" => $package);
			
			$result = $this->Request("killpkg", $args);
			$result = $this->CutXML("killpkg", $result);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 *  Give reseller status to an account
         *
         * @param string $user Name of the user to give reseller status to
         * @param bool $makeowner Whether or not to make the reseller own their own account (they will be able to modify their account if they own it).
         * @return bool Operation status
         */
		public function AddResellerPrivileges($user, $makeowner)
		{
			$args = array("user" => $user, "makeowner" => $makeowner);
			
			$result = $this->Request("setupreseller", $args);
			$result = $this->CutXML("setupreseller", $result);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 *  Remove reseller status from account
         *
         * @param string $user Name of the user to remove reseller status
         * @return bool Operation status
         */
		public function RemoveResellerPrivileges($user)
		{
			$args = array("user" => $user);
			
			$result = $this->Request("unsetupreseller", $args);
			$result = $this->CutXML("unsetupreseller", $result);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		
		/**
		 * Add a new hosting package.
         *
         * @param string $name Name of the reseller to see account information for. 
         * @return Array of ACLs
         */
		public function ListResellerACLs(
			$name
		)
		{
			$args = array(
			"reseller" => $name
			);
			
			$result = $this->Request("resellerstats", $args);
			$result = $this->CutXML("resellerstats", $result);
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					$result = (array)$result->result;
					foreach((array)$result["accts"] as $v)
					{
						$retval[] = (array)$v;	
					}
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 * List the saved reseller ACL lists on the server
         *
         * @return Array of domain options or False on failure
         */
		public function ListSavedACLs()
		{
			$args = array(
			);
			
			$result = $this->Request("listacls", $args);
			$result = $this->CutXML("listacls", $result); 
			try
			{
				$result = new SimpleXMLElement($result);

				$result = (array)$result;
				foreach((array)$result["acls"] as $v)
				{
					$retval[] = (array)$v;	
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 * List the usernames of all resellers on the server
         *
         * @return Array of domain options or False on failure
         */
		public function ListResellers()
		{
			$args = array(
			);
			
			$result = $this->Request("listresellers", $args);
			$result = $this->CutXML("listresellers", $result); 
			try
			{
				$result = new SimpleXMLElement($result);

				$result = (array)$result;
				foreach((array)$result["reseller"] as $v)
				{
					$retval[] = (array)$v;	
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 * Terminate reseller
         * @param string $name Name of the reseller to terminate
         * @param bool $terminate_account Whether or not to terminate the reseller's main account (1 = Yes, 0 = No) 
         * @return Array of domain options or False on failure
         */
		public function TerminateReseller($name, $terminate_account = false)
		{
			$args = array(
				"reseller" => $name,
				"terminatereseller" => $this->Bool2YN($terminate_account),
				"verify" => "I understand this will irrevocably remove all the accounts owned by the reseller {$name}"
				
			);
			
			$result = $this->Request("terminatereseller", $args); 
			$result = $this->CutXML("terminatereseller", $result); 
			try
			{
				$result = new SimpleXMLElement($result);
				if ($result->result->status == 0)
				{
					$this->Error = $result->result->statusmsg;
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 * List the server's hostname.
         * @return string Hostname
         */
		public function GetHostName()
		{
			$args = array(
			);
			
			$result = $this->Request("gethostname", $args); 
			$result = $this->CutXML("gethostname", $result); 
			try
			{
				$result = new SimpleXMLElement($result);
				$result = (array)$result;
				return($result["hostname"]);
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
		
		/**
		 * Lists the verison of cPanel and WHM installed on the server.
         * @return string version
         */
		public function GetAPIVersion()
		{
			$args = array(
			);
			
			$result = $this->Request("version", $args); 
			$result = $this->CutXML("version", $result); 
			return ($result);	
		}
		
		/**
		 * Terminate reseller
         * @param string $service Service to restart. One of bind | interchange | ftp | httpd | imap | cppop | exim | mysql | postgres | ssh | tomcat
         * @return bool Status
         */
		public function RestartService($service)
		{
			$args = array(
				"service" => $service
				
			);
			
			$result = $this->Request("restartservice", $args); 
			$result = $this->CutXML("restartservice", $result); 
			try
			{
				$result = new SimpleXMLElement($result); 
				$result = (array)$result->restart;
				if ($result["result"] == 0)
				{
					$this->Error = $result["statusmsg"];
					return(false);
				}
				else
				{
					return(true);
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
		}
		
		
		/**
		 *  Generates a SSL certificate
         *
         * @return array Array of available methods or False on failure
         */
		public function GenerateSSLCert($email, $host, $country, $state, $city, $company, $dept, $send_to_email, $pass)
		{
			$args = array(
			"xemail" => $email,
			"host" => $host,
			"country" => $country,
			"state" => $state,
			"city" => $city,
			"co" => $company,
			"cod" => $dept,
			"email" => $send_to_email,
			"pass" => $pass,
			);
			
			$result = $this->Request("generatessl", $args); 
			$result = $this->CutXML("generatessl", $result); 
			try
			{
				$result = new SimpleXMLElement($result); 
				$result = (array)$result->results;
				
				if ($result["status"] == 0)
				{
					$this->Error = $result["statusmsg"];
					return(false);
				}
				else
				{
					$retval["csr"] = $result["csr"];
					$retval["crt"] = $result["crt"];
					$retval["key"] = $result["key"];
				}
			}
			catch(Exception $ex)
			{
				$this->Error = $ex->getMessage();
				return false;
			}	
			return ($retval);
		}
	
	} 
	

?>