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
     * @name ApacheTool
     * @category   LibWebta
     * @package NET_API
     * @subpackage Apache
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class ApacheTool extends Core 
	{
		
		
		/**
		* VHost template
		* @var Template
		* @access public
		*/
		public $VHostTemplate = NULL;
		
		
		/**
		* SSL VHost template
		* @var Template
		* @access public
		*/
		public $VHostTemplateSSL = NULL;
		
		/**
		* httpd.conf path
		* @var Template
		* @access public
		*/
		public $ConfPath = NULL;
		
		/**
		* \r\n
		* @var rn
		* @access private
		*/
		private $rn = "\r{0,}\n{0,}";
		
        /**
         * @ignore
         *
         */
		function __construct()
		{
			parent::__construct();
			$this->Smarty = Core::GetSmartyInstance();
			
			// Assign configs
			$this->SetConfPath(CF_ENV_HTTPDCONF);
			$this->SetVHostTemplate(CF_HTTPD_VHOST_TEMPLATE);
			$this->SetVHostTemplate(CF_HTTPD_VHOST_SSL_TEMPLATE, true);
		}


		/**
		* Set virtual host template content
		* @access public
		* @param string $template vhost template
		* @param bool $ssl SSL or non-SSL template
		* @return bool Success or failure
		*/

		public function SetVHostTemplate($template, $ssl=false)
		{
			if ($ssl)
				$this->VHostTemplateSSL = $template;
			else
				$this->VHostTemplate = $template;
		}
		
		
		/**
		* Set httpd.conf path
		* @access public
		* @param string $path path
		* @return bool Success or failure
		*/

		public function SetConfPath($path)
		{
			$this->ConfPath = $path;
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

		public function AddVHost($ip, $host, $username, $email, $ssl=false)
		{
			
			// Default template
			$tpl = $ssl ? $this->VHostTemplateSSL : $this->VHostTemplate;
			if (!$tpl)
			{
				$this->VHostTemplate = $ssl ? "file:/usr/local/cp/etc/httpd.conf.vhost.ssl.tpl" : 
				"file:/usr/local/cp/etc/httpd.conf.vhost.tpl";
			}
			
			// Template is empty
			if (!$tpl)
				$this->RaiseError("Cannot find suitable VHost template");
			
			// Parse template
			$this->Smarty->assign("ip", $ip);
			$this->Smarty->assign("host", $host);
			$this->Smarty->assign("user", $username);
			$this->Smarty->assign("email", $email);
			$vhost = $this->Smarty->fetch($tpl);
			
			
			// Add vhost to the end of httpd.conf
			if (!is_writable($this->ConfPath)) 
			{
				$this->RaiseError("{$this->ConfPath} is not writable");
			}
			file_put_contents($this->ConfPath, "\n{$vhost}\n", FILE_APPEND);
			
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
			// Prepare
			$ip = preg_quote($ip);
			$host = preg_quote($host);
			
			$retval = false;
			if (!is_readable($this->ConfPath))
			{
				$this->RaiseError("Cannot read {$this->ConfPath}");
			}
			else
			{
				$contents = file_get_contents($this->ConfPath);
				preg_match("/(<IfDefine\s+SSL>{$this->rn}){0,1}<VirtualHost\s+(_default_|{$ip}|\*])(:\d+){0,}>.*?ServerName\s+(www.){0,1}{$host}.*?<\/VirtualHost>{$this->rn}(<\/IfDefine>{$this->rn}){0,1}/msi", $contents, $m);
				$retval = (count($m) > 0);
			}
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
			// Preg chunks
			$host = "([a-zA-Z0-9\-_\.]+)";
			if (!$ip)
				$ip = "([0-9]{1,3}\.){3}[0-9]{1,3}";
				
			if (!is_readable($this->ConfPath))
			{
				$this->RaiseError("Cannot read {$this->ConfPath}");
			}
			else
			{
				$contents = file_get_contents($this->ConfPath);
				preg_match_all("/(<IfDefine\s+SSL>{$this->rn}){0,1}<VirtualHost\s+(_default_|{$ip}|\*])(:\d+){0,}>.*?ServerName\s+(www.){0,1}{$host}{$this->rn}.*?<\/VirtualHost>{$this->rn}(<\/IfDefine>{$this->rn}){0,1}/msi", $contents, $m);

				// Add vhosts to result
				$i = 0;
				foreach ($m[2] as $r)
				{
					$ssl = preg_match("/SSLCertificateFile/mi", $m[0][$i]);
					$retval[] = array($m[6][$i], $r, $ssl);
					$i++;
				}
			}
			return $retval;
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
			$ip = preg_quote($ip);
			$host = preg_quote($host);
			
			$retval = false;
			if (!is_writable($this->ConfPath))
			{
				$this->RaiseError("Cannot read {$this->ConfPath}");
			}
			else
			{
				// Replace vhost with empty string
				$contents = file_get_contents($this->ConfPath);
				$sslhook = $ssl ? "SSLCertificateFile.*?" : "";
				$modified = preg_replace("/{$this->rn}(<IfDefine SSL>{$this->rn}){0,1}<VirtualHost\s+(_default_|{$ip}|\*])(:\d+){0,}>.*?ServerName\s+(www.){0,1}{$host}.*?$sslhook<\/VirtualHost>{$this->rn}(<\/IfDefine>{$this->rn}){0,}/msi", "", $contents);
				//Save back
				file_put_contents($this->ConfPath, $modified);
			}
			return $retval;
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
			
			if (!is_writable($this->ConfPath))
			{
				$this->RaiseError("Cannot read {$this->ConfPath}");
			}
			else
			{
				
				$contents = file_get_contents($this->ConfPath);
				$oldipq = preg_quote($oldip);
				$host = preg_quote($host);
				$pat = "/(<IfDefine SSL>{$this->rn}){0,1}<VirtualHost\s+(_default_|{$oldip}|\*])(:\d+){0,}>.*?ServerName\s+(www.){0,1}{$host}.*?<\/VirtualHost>(<\/IfDefine>{$this->rn}){0,}/msi";
				preg_match($pat, $contents, $m); 
				
				// If vhost exists, replace values
				if (count($m) > 0)
				{
					/*
					Replace IP
					We need to know an old IP because of <VirtualHost> syntax.
					User can edit it manually and set it like <VirtualHost ip1 ip2>
					So, we replace the one defined by cp only.
					*/
					$vhost = str_replace($oldip, $ip, $m[0]);
					
					// Replace username
					$vhost = preg_replace("/User\s+(.*?){$this->rn}/i", "User $username\n", $vhost);
					$vhost = preg_replace("/Group\s+(.*?){$this->rn}/i", "Group $username\n", $vhost);
					
					// Replace email
					$vhost = preg_replace("/ServerAdmin\s+(.*?){$this->rn}/i", "ServerAdmin $email\n", $vhost);
					
					//Save back
					$contents = str_replace($m[0], "$vhost", $contents);
					file_put_contents($this->ConfPath, $contents);
				}
			}
			return $retval;
		}
		
		/**
		* Remove unneeded line returns from conf file
		* @access public
		* @return void
		*/
		public function ConfCleanup()
		{
			// Paranoic eh?
			while (!$this->IsConfClean())
			{
				$this->conf = preg_replace("/\\r/m", "", $this->conf);
				$this->conf = preg_replace("/\\n\\n\\n/m", "\n\n", $this->conf);
			}
		}
		
		
		/**
		* Check either named.conf contains unneeded chars
		* @access public
		* @return bool
		*/
		public function IsConfClean()
		{
			$retval = preg_match("/\\n\\n\\n/m", $this->conf);
			return($retval);
		}
		
	}
?>