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
	
	/**
     * @name       CPanel
     * @category   LibWebta
     * @package    NET_API
     * @subpackage WHM
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class CPanel
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
		public $Domain;
		public $User;	
		public $Password;	
		public $Theme;
		public $UseSSL;
		public $ConnectTimeout;
		public $ExecTimeout;
		public $ExecRetries;
		public $ExecRetry;
		
		public $HTMLResult;
		
		function __construct($host, $user, $pass, $theme="x", $domain = null)
		{
			$this->Host = $host;
			$this->User = $user;
			$this->Password = $pass;
			$this->Theme = $theme;
			$this->Domain = (!$domain) ? $host : $domain;	
			
			// Default values
			$this->ConnectTimeout =  self::CONNECT_TIMEOUT;
			$this->ExecTimeout =  self::EXEC_TIMEOUT;			
			$this->ExecRetries = self::EXEC_RETRIES;
		}

		/**
		 * Create a random username
		 */
		public function Randomize($length=6)
		{
			$str = rand(str_repeat(0, $length), str_repeat(9, $length));
			$str = substr(md5($str), 0, $length);
			return $str;
		}
		
		public function Fetch($file, $notheme=false)
		{
			$this->HTMLResult = false;
			try
			{
				$this->ExecRetry++;
				$ch = curl_init();
					
				if (!$notheme)
					curl_setopt($ch, CURLOPT_URL, "https://{$this->Host}:2083/frontend/".$this->Theme."/{$file}");
				else
					curl_setopt($ch, CURLOPT_URL, "https://{$this->Host}:2083/{$file}");
						
				curl_setopt($ch, CURLOPT_USERPWD, $this->User.":".$this->Password);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->ConnectTimeout);
				curl_setopt($ch, CURLOPT_TIMEOUT, $this->ExecTimeout);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
					
				$res = curl_exec($ch);

				$e = curl_error($ch);
				
				// Try to Fetch again or raise warning.
				if ($e)
				{
					if ($this->ExecRetry >= $this->ExecRetries)
						Core::RaiseWarning($e);
					else
					{
						@curl_close($ch);
						$this->Fetch($file, $notheme);
					}
				}
				
				@curl_close($ch);
				
			}
			catch(Exception $e)
			{
				Core::RaiseWarning("Failed to fetch CPanel page. ".$e->__toString());
				return false;
			}
			
			if (!$res)
			{
				Core::RaiseWarning("Failed to fetch CPanel page. Make sure that theme name is correct.");
				return false;
			}
			
			// Return
			$this->HTMLResult = $res;
			
			// Reset retries counter
			$this->ExecRetry = 0;
			return true;
			
		}
		
		public function SQLAddDB($db)
		{
			return $this->Fetch("sql/adddb.html?db=$db");
		}
		
		
		public function SQLAddUser($user, $pass)
		{
			return $this->Fetch("sql/adduser.html?user=$user&pass=$pass");
		}
		
		
		/**
		* Create FTP account
		* @access public
		* @param string $username FTP Username
		* @param string $newquota Quota in megabytes
		* @return void
		*/
		public function AddFTPAccount($username, $password, $quota="unlimited", $homedir="")
		{
			$this->Fetch("ftp/doaddftp.html?login=".$username."&password=".$password."&quota=".$quota."&homedir=".$homedir."&submit=Create");
			
			if ($this->HTMLResult && stristr($this->HTMLResult, "was added"))
				return true;
			else 
				return false;	
		}
		
		
		/**
		* Change FTP user quota
		* @access public
		* @param string $username FTP Username
		* @param string $newquota Quota in megabytes
		* @return void
		*/
		public function FTPChangeQuota($username, $newquota)
		{
			$this->Fetch("ftp/doeditquota.html?acct=".$username."&quota=".$newquota);
		}
		
		
		/**
		* Change FTP password
		* @access public
		* @param string $username FTP Username
		* @param string $password Password
		* @return void
		*/
		public function FTPChangePassword($username, $password)
		{
			$this->Fetch("ftp/dopasswdftp.html?acct=".$username."&password=".$password);
		}
		
		
		/**
		* List subdomains
		* @access public
		* @return array Single array of domain names
		*/
		public function ListSubdomains()
		{
			$this->Fetch("subdomain/index.html");
			preg_match_all("/\_.*?\>([a-zA-Z0-9\-\_\.]+)\<\/option\>/msi", $this->HTMLResult, $retval);
			
			$retval = array_unique($retval[1]);
			
			// Kick off original domain
			$retval = array_diff($retval, array($this->Domain));
			
			return($retval);
		}
		
		
		/**
		* Get email accounts list
		* @access public
		* @return array Single array of emails
		*/
		public function ListMailAccounts()
		{
			$this->Fetch("mail/pops.html");
			preg_match_all("/name=\"user\"\s+value=\"(.*?@*.?)\"/msi", $this->HTMLResult, $m);
			$retval = array_unique((array)$m[1]);
			
			foreach ($retval as $r)
			{
				$rr[] = str_replace(array("\r", "\n"), "", $r);
			}
			
			return($rr);
		}
		
		
		/**
		* Get email forwarders list
		* @access public
		* @return array Array of from => to
		*/
		public function ListMailForwarders()
		{
			$this->Fetch("mail/fwds.html");
			preg_match_all("/dodelfwd\.html\?email=(.*?)=(.*?)\"/msi", $this->HTMLResult, $m);
			
			$froms = $m[1];
			$tos = $m[2];
			
			$i = 0;
			foreach((array)$froms as $from)
			{
				$retval[urldecode($from)] = str_replace(array("'","\""), "", urldecode($tos[$i]));
				$i++;
			}
			
			
			return($retval);
		}
		
		
		/**
		* Get default email account
		* @access public
		* @return arrayArray as domain => email
		*/
		public function GetDefaultEmailAcct()
		{
			$this->Fetch("mail/def.html");
			preg_match_all("/\<legend\>\<b\>(.*?)\<\/b\>\<\/legend\>/msi", $this->HTMLResult, $m);
			preg_match_all("/\<table\s+align=\"center\"\s+width=\"100%\"\s+cellspacing=\"0\"\s+cellpadding=\"0\"\s+border=\"0\"\>[\s\r\n]+\<tr\>[\s\r\n]+\<td\>(.*?)\<\/td/msi", $this->HTMLResult, $m1);

			$doms = $m[1];
			$em = $m1[1];
			
			$i = 0;
			foreach($doms as $dom)
			{
				$retval[$dom] = str_replace(array("\r", "\n"), "", $em[$i]);
				$i++;
			}
			
			return($retval);
		}

		
		
		/**
		* Create subdomain
		* @access public
		* @param string $rootdomain Parent domain
		* @param string $domain Subdomain name
		* @return void
		*/
		public function AddSubdomain($rootdomain, $domain)
		{
			$this->Fetch("subdomain/doadddomain.html?domain=".$domain."&rootdomain=".$rootdomain);
		
			if ($this->HTMLResult && stristr($this->HTMLResult, "has been added"))
				return true;
			else 
				return false;
				
			
		}
		
		
		public function DelSubdomain($domain)
		{
			$this->Fetch("subdomain/dodeldomain.html?domain=".$domain);
		}
		
		public function AddAddon($domain, $foldername = "", $pass = "")
		{
			Core::RaiseError("Fixme: Add \$crypto instance here.");
			if (!$foldername) $foldername = preg_replace("/[\-\_]/i", "", $domain);
			if (!$pass) $pass = $crypto->Sault(13);

			$this->Fetch("addon/doadddomain.html?domain=".$domain."&user=".$foldername."&pass=".$pass);
			return array("user" => $foldername, "pass" => $pass);
		}
		
		public function DelAddon($addon, $subdomain)
		{
			$this->Fetch("addon/dodeldomain.html?domain=".$addon.",".$subdomain);
		}
		
		
		/**
		* List addon domains
		* @access public
		* @return array Associative array of domain => subdomain
		*/
		public function ListAddonDomains()
		{
			$this->Fetch("addon/index.html");
			
			preg_match_all("/\<option.*?value\=(\"|\')([a-zA-Z0-9\-\_\.]+)\,([a-zA-Z0-9\-\_\.]+)\\1.*?\<\/option\>/msi", 
			substr($this->HTMLResult, strpos($this->HTMLResult, "Remove Addon Domain")), $m);
			
			$doms = $m[2];
			$subs = $m[3];
			
			$i = 0;
			foreach($doms as $dom)
			{
				$retval[$dom] = $subs[$i];
				$i++;
			}
			
			
			return($retval);
		}
		
		
		/**
		* List parked domains
		* @access public
		* @return array Single array of domain names
		*/
		public function ListParkedDomains()
		{
			$this->Fetch("park/index.html");
			
			preg_match_all("/select\s+name=\"domain\"\>.*?value=\"(.*?)\"\>.*?\<\/select/msi", 
			substr($this->HTMLResult, strpos($this->HTMLResult, "Remove Addon Domain")), $m);
			
			$retval = array_unique($m[1]);
			
			return($retval);
		}
		
		
		/**
		* Assign DB user to database with specific privileges
		* @access public
		* @param string $user New username
		* @param string $db Database name
		* @param string $privs Privileges table
		* @return void
		*/
		public function SQLAddUserToDB($user, $db, $privs = array(0))
		{
			$privsreal = array
			(
				0	=> "ALL=ALL",
				1	=> "ALTER=ALTER",
				2	=> "CREATETEMPORARYTABLES=TEMPORARY",
				3	=> "CREATE=CREATE",
				4	=> "DELETE=DELETE",
				5	=> "DROP=DROP",
				6	=> "SELECT=SELECT",
				7	=> "INSERT=INSERT",
				8	=> "UPDATE=UPDATE",
				9	=> "REFERENCES=REFERENCES",
				10	=> "INDEX=INDEX",
				11	=> "LOCKTABLES=LOCK"
			);
			
			foreach($privs as $pv)
			{
				$privsstr .= "&".$privsreal[$pv];
			}
			return $this->Fetch("/sql/addusertodb.html?user=$user&db=$db".$privsstr);
		}
		
		
		public function DelUserFromDB($user, $db)
		{
			$this->Fetch("sql/deluserfromdb.html?db={$db}&user={$user}");
		}
		
		public function DelUser($user)
		{
			$this->Fetch("sql/deluser.html?user={$user}");
		}
		
		public function DelDB($db)
		{
			$this->Fetch("sql/deldb.html?db={$db}");
		}
		
		public function ListDatabases()
		{
			$this->Fetch("sql/index.html");
			
			// Users
			preg_match_all("/\>(".$this->User."_([\w\d\_]+)?)\<\/option/msi", 
			substr($this->HTMLResult, strpos($this->HTMLResult, "name=user"), strpos($this->HTMLResult, "name=db") - strpos($this->HTMLResult, "name=user")), $users);
			unset($users[0]);
			
			// Databases
			preg_match_all("/\>(".$this->User."_([\w\d\_]+)?)\<\/option/msi", 
			substr($this->HTMLResult, strpos($this->HTMLResult, "name=db")), $databases);
			unset($databases[0]);

			$pos_dbs = strpos($this->HTMLResult, "Databases:");
			$pos_select = strpos($this->HTMLResult, "<select");
			$this->htmlresult = substr($this->HTMLResult, $pos_dbs, $pos_select - $pos_dbs);
			
			// The rest
			$matches = preg_split("/deldb.html/msi", $this->HTMLResult);
			unset($matches[0]);
			if ($matches)
				foreach($matches as $k=>$match)
				{
					preg_match_all("/(".$this->User."_[\w\d\_]+)\s+\(privileges\:(.*?)\)/msi", $match, $retval[$k]);
					unset($retval[$k][0]);
				}

			return array("dbs" => $databases, "users" => $users, "assigns" => $retval);
		}
		
		public function SQLAddRandomDB()
		{
			$this->SQLAddHost(false);
			$db = $this->Randomize(6);
			$this->SQLAddDB($db);
			
			$user = $this->Randomize(6);
			$pass = $this->Randomize(6);
			$this->SQLAddUser($user, $pass);
			
			$this->SQLAddUserToDB($this->User."_".$user, $this->User."_".$db, array('0'));
			
			$retval = array
			(
				"host"	=> $this->Host,
				"db"	=> $this->User."_".$db,
				"user"	=> $this->User."_".$user,
				"pass"	=> $pass
			);
			
			return($retval);
		}
		
		public function SQLAddHost($host)
		{
			if (!$host)
				$host = gethostbyaddr (gethostbyname ($_SERVER['SERVER_NAME']));
			$this->Fetch("/sql/addhost.html?host=$host");
		}
		
		
		public function CronAddJob($min, $hour, $day, $month, $weekday, $command, $checkok = false)
		{
			
			$command = urlencode($command);
			
			// Fetch current jobs
			$this->Fetch("/cron/advcron.html");
			preg_match_all("/-minute(.*?)value=\"(.*?)\"/msi", $this->HTMLResult, $mimutes);
			preg_match_all("/-hour(.*?)value=\"(.*?)\"/msi", $this->HTMLResult, $hours);
			preg_match_all("/-day(.*?)value=\"(.*?)\"/msi", $this->HTMLResult, $days);
			preg_match_all("/-month(.*?)value=\"(.*?)\"/msi", $this->HTMLResult, $months);
			preg_match_all("/-weekday(.*?)value=\"(.*?)\"/msi", $this->HTMLResult, $weekdays);
			preg_match_all("/-command(.*?)value=\"(.*?)\"/msi", $this->HTMLResult, $commands);
			
			if (count($commands[2]) > 1)
			{
				for ($i = 0; $i<=count($commands[2])-2; $i++)
				{
					$n = $i+1;
					$p .= "&$n-minute=".$mimutes[2][$i]."&$n-hour=".$hours[2][$i]."&$n-day=".$days[2][$i]."&$n-month=".$months[2][$i]."&$n-weekday=".$weekdays[2][$i]."&$n-command=".urlencode($commands[2][$i]);
				}
			}
			
			$res = $this->Fetch("/cron/editcron.html?0-minute=$min&0-hour=$hour&0-day=$day&0-month=$month&0-weekday=$weekday&0-command=$command$p&entcount=$n");
			
			$this->Fetch("/cron/advcron.html");
			
			if ($checkok)
				$retval = ereg(htmlspecialchars(urldecode($command)), $this->HTMLResult);
			
			return ($checkok ? $retval : true);
			
		}
		
		public function FilesSetPerms($path, $perms)
		{
			
			// Convert octal to bin chmod representation
			list($u, $g, $w) = preg_split('//', $perms, -1, PREG_SPLIT_NO_EMPTY);
			list($ur, $uw, $ux) = preg_split('//', decbin(octdec($u)), -1, PREG_SPLIT_NO_EMPTY);
			list($gr, $gw, $gx) = preg_split('//', decbin(octdec($g)), -1, PREG_SPLIT_NO_EMPTY);
			list($wr, $ww, $wx) = preg_split('//', decbin(octdec($w)), -1, PREG_SPLIT_NO_EMPTY);
			
			
			// Yeah i know, what a crap eh?
			if ($ur)
				$str .= "&ur=4";
			if ($gr)
				$str .= "&gr=4";
			if ($wr)
				$str .= "&wr=4";
				
			if ($uw)
				$str .= "&uw=2";				
			if ($gw)
				$str .= "&gw=2";				
			if ($ww)
				$str .= "&ww=2";				
				
			if ($ux)
				$str .= "&ux=1";				
			if ($gx)
				$str .= "&gx=1";				
			if ($wx)
				$str .= "&wx=1";				
			
			// Split directory and filename
			$dir = dirname($path);
			$file = basename($path);
			
			$this->Fetch("/files/changeperm.html?dir=/home/{$this->User}/public_html/$dir&file=$file$str&u=$u&g=$g&w=$w");
		}
	
		
		public function TestLogin()
		{
			$this->Fetch("");
			$retval = ereg("Directory", $this->HTMLResult);
			return $retval;
		}

	}
?>