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
     * @package NET_API
     * @subpackage Apache
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */      

    /**
     * @name RemoteApacheTool
     * @category   LibWebta
     * @package NET_API
     * @subpackage Apache
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class RemoteApacheTool extends ApacheTool
	{
	    /**
	     * Config
	     *
	     * @var string
	     * @access protected
	     */
		protected $conf;
		
		/**
		 * SSH2 instance
		 *
		 * @var SSH2
		 */
		protected $SSH2;
		
		/**
		 * RemoteApacheTool constructor
		 *
		 * @param string $host SSH Host
		 * @param string $port SSH port
		 * @param string $username SSH login
		 * @param string $password SSH password
		 */
		function __construct ($host, $port, $authinfo)
		{
			parent::__construct();
			
			$this->Authinfo = $authinfo;
			
			// SSH2
			$this->SSH2 = new SSH2();
						
			if ($this->Authinfo["type"] == "password")
				$this->SSH2->AddPassword($this->Authinfo["login"], $this->Authinfo["password"]);
			elseif ($this->Authinfo["type"] == "pubkey")
				$this->SSH2->AddPubkey($this->Authinfo["login"], $this->Authinfo["pubkey_path"], $this->Authinfo["privkey_path"], $this->Authinfo["key_pass"]);
				
			if (!$this->SSH2->Connect($host, $port, $username, $password))
                Core::RaiseWarning("RemoteApacheTool: SSH2 connection failed");
			
			/*
			$this->conf = $this->SSH2->Exec("/bin/cat {$this->ConfPath}");
			if (!$this->conf || !strlen($this->conf)>0)
			{
				$this->RaiseError("Cannot open HTTPD conf!");
				return false;
			}
			*/
		}
		
		/**
		 * Is SSH2 connected
		 *
		 * @return bool
		 */
		public function IsConnected()
		{
		    return $this->SSH2->IsConnected();
		}
		
		/**
		* Change virtual host details by its hostname
		* @access public
		* @param string $host Old hostname
		* @param string $oldip Old ip to bind to
		* @param string $ip New ip to bind to
		* @param string $username New System username
		* @param string $email New webmaster email
		* @return bool Success or failure
		*/
		public function EditVHost($host, $oldip, $ip, $username, $email)
		{
			/*
			$oldipq = preg_quote($oldip);
			$host = preg_quote($host);
			$pat = "/(<IfDefine SSL>{$this->rn}){0,1}<VirtualHost\s+(_default_|{$oldip}|\*])(:\d+){0,}>.*?ServerName\s+(www.){0,1}{$host}.*?<\/VirtualHost>(<\/IfDefine>{$this->rn}){0,}/msi";
			preg_match($pat, $this->conf, $m); 
			
			$retval = true;
			// If vhost exists, replace values
			if (count($m) > 0)
			{
				/*
				Replace IP
				We need to know an old IP because of <VirtualHost> syntax.
				User can edit it manually and set it like <VirtualHost ip1 ip2>
				So, we replace the one defined by cp only.
				*/
				/*
				$vhost = str_replace($oldip, $ip, $m[0]);
				
				// Replace username
				$vhost = preg_replace("/User\s+(.*?){$this->rn}/i", "User $username\n", $vhost);
				$vhost = preg_replace("/Group\s+(.*?){$this->rn}/i", "Group $username\n", $vhost);
				
				// Replace email
				$vhost = preg_replace("/ServerAdmin\s+(.*?){$this->rn}/i", "ServerAdmin $email\n", $vhost);
				
				//Save back
				$this->conf = str_replace($m[0], "$vhost", $this->conf);
				$retval = $this->SaveConf();
			}
			return $retval;
			*/
			return false;
		}
		
		/**
		* Delete VHost entry
		* @access public
		* @param string $ip Ip to bind to
		* @param string $host Domain name
		* @param bool $ssl Either delete SSL VHost or regular one
		* @return bool Success or failure
		*/
		public function DeleteVHost($ip, $host, $ssl=false)
		{
			// Prepare
			/*
			$ip = preg_quote($ip);
			$host = preg_quote($host);
			
			$retval = false;
			$sslhook = $ssl ? "SSLCertificateFile.*?" : "";
			$this->conf = preg_replace("/{$this->rn}(<IfDefine SSL>{$this->rn}){0,1}<VirtualHost\s+(_default_|{$ip}|\*])(:\d+){0,}>.*?ServerName\s+(www.){0,1}{$host}.*?$sslhook<\/VirtualHost>{$this->rn}(<\/IfDefine>{$this->rn}){0,}/msi", "", $this->conf);
			//Save back
			$retval = $this->SaveConf();
			*/
			$retval = $this->SSH2->Exec("rm -f ".CF_HTTPD_VHOSTSPATH."/{$host}.vhost");
			$retval &= $this->RestartApache();
			return $retval;
		}
		
		/**
		* List VHosts
		* @access public
		* @param string $ip Filter VHosts by IP
		* @return array Associative array []=>"ip", "host", "ssl"
		*/
		public function ListVHosts($ip = NULL)
		{
			/*
			// Preg chunks
			$host = "([a-zA-Z0-9\-_\.]+)";
			if (!$ip)
				$ip = "([0-9]{1,3}\.){3}[0-9]{1,3}";
				
			preg_match_all("/(<IfDefine\s+SSL>{$this->rn}){0,1}<VirtualHost\s+(_default_|{$ip}|\*])(:\d+){0,}>.*?ServerName\s+(www.){0,1}{$host}{$this->rn}.*?<\/VirtualHost>{$this->rn}(<\/IfDefine>{$this->rn}){0,1}/msi", $this->conf, $m);

			// Add vhosts to result
			$i = 0;
			foreach ($m[2] as $r)
			{
				$ssl = preg_match("/SSLCertificateFile/mi", $m[0][$i]);
				$retval[] = array($m[6][$i], $r, $ssl);
				$i++;
			}
			return $retval;
			*/
			return false;
		}
		
		/**
		* Check VHost entry for existence
		* @access public
		* @param string $ip Ip to bind to
		* @param string $host Domain name
		* @return bool Success or failure
		*/

		public function VHostExists($ip, $host)
		{
			/*
			// Prepare
			$ip = preg_quote($ip);
			$host = preg_quote($host);
			
			$retval = false;
			preg_match("/(<IfDefine\s+SSL>{$this->rn}){0,1}<VirtualHost\s+(_default_|{$ip}|\*])(:\d+){0,}>.*?ServerName\s+(www.){0,1}{$host}.*?<\/VirtualHost>{$this->rn}(<\/IfDefine>{$this->rn}){0,1}/msi", $this->conf, $m);
			$retval = (count($m) > 0);

			return $retval;
			*/
			return false;
		}
		
		/**
		* Create new virtual host entry
		* @access public
		* @param string $ip Ip to bind to
		* @param string $host Domain name
		* @param string $username System username - used for paths
		* @param string $email Webmaster email
		* @param bool $ssl Either virtual host is HTTPS one
		* @return bool Success or failure
		*/
		public function AddVHost($ip, $host, $dir, $email, $ssl=false)
		{
			
			// Default template
			$tpl = $ssl ? $this->VHostTemplateSSL : $this->VHostTemplate;
			// Template is empty
			if (!$tpl)
				$this->RaiseError("Cannot find suitable VHost template");
			
			// Parse template
			$tpl = str_replace('{$ip}', $ip, $tpl);
			$tpl = str_replace('{$host}', $host, $tpl);
			$tpl = str_replace('{$dir}', $dir, $tpl);
			$tpl = str_replace('{$user}', CF_HTTPD_USER, $tpl);
			$tpl = str_replace('{$email}', $email, $tpl);
			$vhost = stripslashes($tpl);
			$vhost = str_replace("\r\n", "\n", $vhost);
			
			// Add vhost to the end of httpd.conf
			
			$tempfn = tempnam("","");
			$temp = fopen($tempfn, "w+");
			fwrite($temp, $vhost);
			fclose($temp);
		
			$this->SSH2->Exec("/bin/mv ".CF_HTTPD_VHOSTSPATH."/{$host}.vhost ".CF_HTTPD_VHOSTSPATH."/{$host}.vhost".time());
			$retval = $this->SSH2->SendFile(CF_HTTPD_VHOSTSPATH."/{$host}.vhost", $tempfn);
			@unlink($tempfn);
			
			$this->CreateUserDirs($dir);
			
			$retval &= $this->RestartApache();	
			
			if ($retval)
				return true;
			else
				return false;
		}
		
		/**
		 * Create user dirs
		 *
		 * @param string $homedir
		 * @access private
		 */
		private function CreateUserDirs($homedir)
		{
			$chown = CF_HTTPD_USER.":".CF_HTTPD_USER;
			
			$this->SSH2->Exec("/bin/mkdir {$homedir}");
			$this->SSH2->Exec("/bin/mkdir {$homedir}/public_html");
			$this->SSH2->Exec("/bin/mkdir {$homedir}/public_html/cgi-bin");
			$this->SSH2->Exec("/bin/mkdir {$homedir}/logs");
			$this->SSH2->Exec("/bin/mkdir {$homedir}/logs/ssl");
			$this->SSH2->Exec("/bin/mkdir {$homedir}/ssl");
			$this->SSH2->Exec("/bin/chown -R $chown {$homedir} && chmod -R 0755 {$homedir}");
		}
		
		/**
		 * Save apache config
		 *
		 * @return bool
		 */
		private function SaveConf()
		{
			$this->ConfCleanup();
			//
			$tempfn = tempnam("","");
			$temp = fopen($tempfn, "w+");
			fwrite($temp, $this->conf);
			fclose($temp);
		
			$this->SSH2->Exec("/bin/mv {$this->ConfPath} {$this->ConfPath}.".time());
			$retval = $this->SSH2->SendFile("{$this->ConfPath}", $tempfn);
			
			@unlink($tempfn);
			
			$retval &= $this->RestartApache();
			
			return($retval);
		}
		
		/**
		 * Reastart apache
		 *
		 * @return bool
		 */
		public function RestartApache()
		{
			$retval = $this->SSH2->Exec(CF_HTTPD_APACHECTL." restart 2>&1");
			
			$retval = !(bool)strstr($retval, "error") && !(bool)strstr($retval, "Usage");
			
			if (!$retval)
                Core::RaiseWarning("Cannot restart apache: {$retval}");
			
			return $retval;
		}
	}
?>