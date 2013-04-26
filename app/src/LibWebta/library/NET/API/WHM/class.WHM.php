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
     * @package    NET_API
     * @subpackage WHM
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */    
	
	define ("CONNECT_TIMEOUT", 5);
	define ("TIMEOUT_ERR_HANDLER", "TimeoutErrorHandler");
	define ("CONNECT_ERR_HANDLER", "ConnectErrorHandler");
	
	/**
	 * Timeout handler
	 *
	 * @param string $host WHM host
	 */
	function TimeoutErrorHandler($host)
	{
		Core::RaiseError("Timeout while talking to WHM on {$host}");
	}
	
	/**
	 * Connection error handler
	 *
	 * @param string $host WHM host
	 * @param string $reason Reason
	 */
	function ConnectErrorHandler($host, $reason)
	{
		Core::RaiseError("Cannot connect to WHM on {$host}. {$reason}");
	}
	
	/**
     * @name       WHM
     * @category   LibWebta
     * @package    NET_API
     * @subpackage WHM
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Yuri Omelchuk <jurgen@webta.net>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class WHM extends Core
	{
		
		/**
		 * Default connection timeout in seconds
		 */
		const CONNECT_TIMEOUT = 150;
		
		/**
		 * Default Fetch method execution timeout in seconds
		 */
		const EXEC_TIMEOUT = 150;
		
		/**
		 * Default number of fech retries
		 */
		const EXEC_RETRIES = 5;
		
		public $Host;
		public $User;
		public $AccessHash;
		public $UseSSL;
		public $Theme;
		public $SharedIP;
		public $ConnectTimeout;
		public $LastResponseError;
		
		public $ExecTimeout;
		public $ExecRetries;
		public $ExecRetry;
		
		
		function __construct($args)
		{
			parent::__construct();
			$this->Host = $args["hostname"];
			$this->User = $args["login"];
			$this->AccessHash = $args["rkey"];
			$this->UseSSL = $args["usessl"];
			$this->Theme = $args["theme"];
			$this->SharedIP = $args["sharedip"];
			
			// Default values
			$this->ConnectTimeout =  self::CONNECT_TIMEOUT;
			$this->ExecTimeout =  self::EXEC_TIMEOUT;			
			$this->ExecRetries = self::EXEC_RETRIES;
		}
		
		
		public function GenerateUsername($domain)
		{
			$retval = str_replace(array("-", ".", "_"), "", $domain);
			$retval = substr($retval,0,5). mt_rand(111,999);
			if (!preg_match("/^[A-Za-z]+[A-Za-z0-9]*$/", $retval))
			{
					$retval = "a".substr($retval, 0, 7);
			}
			return $retval;
		}
		
		
		public function ListFreeIPs()
		{	
			$result = $this->Request("/scripts/ipusage");
			
			preg_match_all("/\d+\.\d+\.\d+\.\d+/", $result, $m);
			
			return $m[0];
		}
		
		
		public function SuspendAccount($domain, $user) 
		{
			$result = $this->Request("/scripts2/suspendacct?domain=$user&user=$domain&suspend-domain=Suspend&reason=");
			return $result;
		} 
		
		
		public function UnSuspendAccount($domain, $user) 
		{
			$result = $this->Request("/scripts2/suspendacct?domain=$user&user=$domain&unsuspend-domain=UnSuspend&reason=");
			return $result;
		}
		
		public function KillAccount ($domain, $user) 
		{
			$result = $this->Request("/scripts/killacct?domain=$user&user=$domain&submit-domain=Terminate");
			return $result;
		}
		
		public function ChangePassword($pass, $user, $domain)
		{
			$result = $this->Request("/scripts/passwd?password=$pass&domain=$user&user=$domain");
			return $result;
		}
		
		public function CreateAccount ($domain, $user, $pass, $args) 
		{
			$re.="domain=".$domain;
			$re.="&username=".$user;
			$re.="&password=".$pass;
			$re.="&quota=".$args["quota"];
			$re.="&cpmod=".$args["cpmod"];
			#if ($ip==1) { $re.="&ip=".$ip; }	
			$re.="&cgi=y";
			$re.="&frontpage=n";
			$re.="&maxftp=".((trim($args["maxftp"])=="-1")?"unlimited":"".$args["maxftp"]."");
			$re.="&maxsql=".((trim($args["maxsql"])=="-1")?"unlimited":"".$args["maxsql"]."");
			$re.="&maxpop=".((trim($args["maxpop"])=="-1")?"unlimited":"".$args["maxpop"]."");
			$re.="&maxlst=".((trim($args["maxlst"])=="-1")?"unlimited":"".$args["maxlst"]."");
			$re.="&maxsub=".((trim($args["maxsub"])=="-1")?"unlimited":"".$args["maxsub"]."");
			$re.="&bwlimit=".$args["bwlimit"];
			$re.="&hasshell=".($args["hasshell"]) ? "y" : "n";
			$re.="&maxpark=".$args["maxpark"];
			$re.="&maxaddon=".$args["maxaddon"];
			$re.="&contactemail=".$args["contactemail"];
			#if ($ip == 1) { $re.="&customip=".$default_ip; }
			#else if ($ip==0) { $re.="&customip=--Auto+Assign--"; }
			$re.="&customip=--Auto+Assign--";
			$re.="&nohtml=1";
		
			#$msel=$ip.",";
			$msel="1,";
			$msel.="y,";
			$msel.=$args["quota"].",";
			$msel.="n,";
			$msel.=$args["cpmod"].",";
			$msel.=((trim($args["maxftp"]) == "-1") ? "unlimited" : "".$args["maxftp"]."").",";
			$msel.=((trim($args["maxsql"]) == "-1") ? "unlimited" : "".$args["maxsql"]."").",";
			$msel.=((trim($args["maxpop"]) == "-1") ? "unlimited" : "".$args["maxpop"]."").",";
			$msel.=((trim($args["maxlst"]) == "-1") ? "unlimited" : "".$args["maxlst"]."").",";
			$msel.=((trim($args["maxsub"]) == "-1") ? "unlimited" : "".$args["maxsub"]."").",";
			$msel.=$args["bwlimit"].",";
			$msel.=$args["hasshell"].",";
			$msel.="undefined"; //package name
		
			$re.="&msel=".urlencode($msel);

			$req = "/scripts/wwwacct?$re";
			$result = $this->Request($req);
			
			if (!ereg("wwwacct creation finished", $result))
			{
				$this->RaiseError("Account creation failed.");
			}
			
			return $result;
		}
		
		
		public function CreateAccountFromPackage ($acctdomain, $acctuser, $acctpass, $acctplan) 
		{
			$result = $this->Request("/scripts/wwwacct?remote=1&nohtml=1&username=${acctuser}&password=${acctpass}&domain=${acctdomain}&plan=${acctplan}");
			if (!ereg("wwwacct creation finished", $result))
			{
				$this->RaiseError("Account creation failed.");
			}
			
			$retval = (eregi("Account Creation Complete!!!", $result) && eregi("wwwacct creation finished", $result));
			
			if (!$retval)
			{
				preg_match_all("/<table[^>]*>[^<]*<tr>[^<]*<td>([^<]*)<\/td>/msi", $result, $matches);
				$this->LastResponseError = $matches[1][0];
				
				if (!$this->LastResponseError)
					$this->LastResponseError = "Cannot login to WHM.";
				
				return false;
			}
			else
			{
				return true;
			}
		}
		
		
		public function CheckError($result)
		{
			$errors = array
			(
				"Account Creation Complete" => 0,
				"/etc/wwwacct.conf not found"	=> 1,
				"Missing NS Config Line in /etc/wwwacct.conf"	=> 2,
				"Missing NS2 Config Line in /etc/wwwacct.conf"	=> 3,
				"Missing HOMEDIR Config Line in /etc/wwwacct.conf" => 4,
				"Missing HOMEMATCH Config Line in /etc/wwwacct.conf" => 5,
				"Missing DEFMOD Config Line in /etc/wwwacct.conf" => 6,
				"Missing HOST Config Line in /etc/wwwacct.conf" => 7,
				"Missing LOGSTYLE Config Line in /etc/wwwacct.conf" => 8,
				"Missing BINDVER Config Line in /etc/wwwacct.conf" => 9,
				"Missing FTPTYPE Config Line in /etc/wwwacct.conf" => 10,
				"Missing SCRIPTALIAS Config Line in /etc/wwwacct.conf" => 11,
				"httpd.conf is corrupt." => 12,
				"proftpd.conf" => 13,
				"Unable to determine main ip" => 14,
				"Ouch, sorry that username is taken" => 15,
				"Sorry that username is too long." => 16,
				"Sorry usernames cannot begin with a number." => 17,
				"Sorry usernames cannot begin with a dash." => 18,
				"Requested IP address" => 19,
				"Unable to find an ip address" =>20,
				"You cannot setup a domain that is the same as the servers hostname" => 21,
				"Sorry, thats an invalid domain" => 22,
				"Sorry, thats an invalid username" =>23,
				"No domain name given" => 24,
				"Sorry, that domain is already setup" => 25,
				"Ok Guess NOT" => 26,
				"Sorry thats not a valid domain" => 27,
				"is not a valid domain name" => 28,
				"is not a valid domain name" => 29,
				"Sorry you are at your limit for creating" => 30,
				"Sorry, you must choose a plan" => 31, 
				"Sorry, you cannot create an account with an unlimited bandwidth limit." =>33
			);
			
			$result = str_replace(array("\n", "\r"), "", $result);
			
			foreach ($errors as $ek=>$ev)
			{
				if (ereg($ek, $result))
				{
					$this->RaiseError($ek);
				}
			}
			return $ev;
			
		}
		
		
		public function EditBW ($user, $bw)
		{
			$result = $this->Request("/scripts2/dolimitbw?user=$user&bwlimit=$bw");				
			return $result;
		}
		
		
		public function EditQuota ($user, $quota) 
		{
			$result = $this->Request("/scripts/editquota?user=$user&quota=$quota");
			return $result;
		}
		
		
		public function EditAccount ($domain, $user, $args) 
		{
			// static part
			$static = "PLAN=undefined&OWNER=root&LANG=english&FEATURELIST=default&seeshell=1";
			$req  = "user=$user";
			$req .= "&DNS=$domain";
			$req .= "&RS=".$this->Theme;
			$req .= "&IP=".$this->SharedIP;
			$req .= "&STARTDATE=".time();
			
			// build params from array
			$reqd = $this->buildURL($args);
			// send request
			$result = $this->Request("/scripts/saveedituser?$req&$reqd&$static");
			return $result;
		}
		
		public function ListAccounts () 
		{
			$result = $this->Request("/scripts2/listaccts?nohtml=1&viewall=1");
			$page = split("\n",$result);
			foreach ($page as $line) 
			{
				list($acct,$contents) = split("=", $line);
				if ($acct != "") 
				{
					$allc = split(",", $contents);
					$accts[$acct] = $allc;
				}
			}
			return($accts);
		}
		
		
		//
		// Maps array for ListAccountsCSV function
		//
		public function mapCSV($a)
		{
			return explode(",", $a);
		}
		
		
		public function ListAccountsCSV () 
		{
			
			// Moronish Cpanel shows only the last viewed page of accounts
			// So we have to call "All" page first
			$result = $this->Request("/scripts2/listaccts?nohtml=1&viewall=1");
			$result = $this->Request("/scripts/fetchcsv?viewall=1");
			$resa = split("\n", $result);
			
			// Pop empty entry
			array_pop($resa);
			
			$resa = array_map(array($this, "mapCSV"), $resa);
			
			return($resa);
		}
		
		
		public function ListPackages () 
		{
			$result = $this->Request("/scripts/remote_listpkg");
			$page = split("\n",$result);
			
			$pkgs = array();
			
			foreach ($page as $line) 
			{
				if ($line != 1)
				{
					list($pkg,$contents) = split("=", $line);
					if ($pkg != "") 
					{
						$allc = split(",", $contents);
						$pkgs[$pkg] = $allc;
					}
				}
			}
			return($pkgs);
		}
		
		public function GetCpanelVersion () 
		{
			$result = $this->Request("/scripts2/showversion");
			return $result;
		}
		
		public function DeleteDNSZone ($domain) 
		{
			$result = $this->Request("/scripts/killdns?domain=$domain&domainselect=$domain");
			return $result;
		}
		
		
		public function GetAccountEmail($user)
		{
			$result = $this->Request("/scripts2/changeemail?user=$user");
			preg_match("/name=\"email\"\s+value=\"(.*?)\"/msi", $result, $m);
			$retval = $m[1];
			return $retval;
		}
		
		
		public function Request ($request) 
		{
			$cleanaccesshash = preg_replace("'(\r|\n)'","",$this->AccessHash);
			$authstr = $this->User . ":" . $cleanaccesshash;
			
			if (function_exists("curl_init")) 
			{
				try
				{
					$ch = curl_init();
					if ($this->UseSSL) 
					{
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
						curl_setopt($ch, CURLOPT_URL, "https://{$this->Host}:2087{$request}");
					} 
					else 
						curl_setopt($ch, CURLOPT_URL, "http://{$this->Host}:2086{$request}");

					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
					
					// Timeouts
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->ConnectTimeout);
					curl_setopt($ch, CURLOPT_TIMEOUT, $this->ExecTimeout);
					
					$curlheaders[0] = "Authorization: WHM $authstr";
					curl_setopt($ch, CURLOPT_HTTPHEADER,$curlheaders);
					$data=curl_exec($ch);
					
					$e = curl_error($ch);
					
					// Try to Fetch again or raise warning.
					if ($e)
					{
						if ($this->ExecRetry >= $this->ExecRetries)
							Core::RaiseWarning($e);
						else
						{
							@curl_close($ch);
							$this->Request($request);
						}
					}
					
					@curl_close($ch);
				}
				catch(Exception $e)
				{
					Core::RaiseWarning("Failed to fetch WHM page. ".$e->__toString());
				}
			} 
			elseif(function_exists("socket_create")) 
			{
				if ($this->UseSSL) 
					$this->FatalError("SSL Support requires cURL");
				
				try
				{
					$service_port = 2086;
					$address = gethostbyname($this->Host);
					$socket = socket_create (AF_INET, SOCK_STREAM, 0);
					if ($socket < 0) 
					{
						$this->FatalError("socket_create() failed");
					}
					
					@socket_set_timeout($socket, CONNECT_TIMEOUT);
					$result = socket_connect ($socket, $address, $service_port);
					if ($result < 0) 
					{
						$this-FatalError("socket_connect() failed");
					}
					$in = "GET $request HTTP/1.0\n";
					socket_write($socket,$in,strlen($in));
					$in = "Connection: close\n";
					socket_write($socket,$in,strlen($in));
					$in = "Authorization: WHM $authstr\n\n\n";
					socket_write($socket,$in,strlen($in));
					$inheader = 1;
					while(($buf = socket_read($socket, 512)) != false) 
					{
						if (!$inheader) 
							$data .= $buf;
							
						if(preg_match("'\r\n\r\n$'s", $buf)) 
							$inheader = 0;
						
						if(preg_match("'\n\n$'s", $buf)) 
							$inheader = 0;
					}
				
				}
				catch(Exception $e)
				{
					Core::RaiseWarning("Failed to fetch WHM page. ".$e->__toString());
				}
			} 
			else 
			{
				Core::RaiseWarning("Cannot find neither sockets nor cURL");
				return;
			}
				
			// Failed to auth
			if (stristr($data, "input type=\"password\""))
			{
				$this->RaiseWarning("WHM authentication failed");
				return false;
			}
			else
				return $data;	
				
		}
		
		
		public function buildURL($inp)
		{
			foreach ($inp as $k=>$v)
			{
				$sep = ($i) ? "&" : "";
				$retval .= "$sep$k=$v";
				$i = true;
			}
			return $retval;
		}
		
	
	} 
	

?>