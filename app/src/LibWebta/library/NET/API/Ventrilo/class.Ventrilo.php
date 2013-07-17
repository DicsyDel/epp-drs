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
     * @subpackage Ventrilo
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	Core::Load("NET/SSH/class.SSH2.php");
	
	/**
     * @name       Ventrilo
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Ventrilo
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class Ventrilo extends Core
	{
		/**
		 * Path to Ventrilo
		 *
		 * @var string
		 * @access private
		 */
		private $VentriloPath;
		
		/**
		 * Vhost config template
		 *
		 * @var unknown_type
		 * @access private
		 */
		private $ConfigTemplate;
		
		/**
		 * Server host (SSH)
		 *
		 * @var string
		 * @access private
		 */
		private $SSHHost;
		
		/**
		 * Server port (SSH)
		 *
		 * @var integer
		 * @access private
		 */
		private $SSHPort;
		
		/**
		 * SSH Login
		 *
		 * @var string
		 * @access private
		 */
		private $SSHLogin;
		
		/**
		 * SSH Password
		 *
		 * @var string
		 * @access private
		 */
		private $SSHPassword;
		
		/**
		 * SSH Object instance
		 *
		 * @var SSH2
		 * @access private
		 */
		private $SSH2;
		
		/**
		 * Ventrilo Constructor
		 *
		 * @param string $host
		 * @param integer $port
		 * @param array $authinfo
		 * @param string $ventrilo_path
		 * @param string $config_template
		 * @return bool
		 */
		function __construct ($host, $port, $login, $password, $ventrilo_path, $config_template)
		{
			parent::__construct();
			
			$this->VentriloPath = $ventrilo_path;
			$this->ConfigTemplate = $config_template;
			
			$this->SSH2 = new SSH2();
		
			if (!$this->SSH2->TestConnection($host, $port))
			{
			    Core::RaiseWarning(_("Cannot connect to remote server"));
			    die(_("Cannot connect to remote server"));
				return false;
			}
			
			if (!$this->SSH2->Connect($host, $port, $login, trim($password)))
			{
				Core::RaiseWarning(_("Cannot connect to remote server"));
				die(_("Cannot connect to remote server"));
				return false;
			}
			
			$this->SSHHost = $host;
			$this->SSHPort = $port;
			$this->SSHLogin = $login;
			$this->SSHPassword = trim($password);
			
			return true;
		}
		
		/**
		 * Return VHost Object instance
		 *
		 * @param integer $port Virtualhost port
		 * @return VentriloVhost
		 */
		public function GetVhost($port)
		{
			return  new VentriloVhost($this->SSHHost, $this->SSHPort, $this->SSHLogin, $this->SSHPassword, $this->VentriloPath, $this->ConfigTemplate, $port);
		}
		
		/**
		 * Create ventrilo server
		 *
		 * @param array $fields
		 * @return VentriloVhost
		 */
		public function AddServer($fields)
		{
			$port = $fields["port"];
			
			$config = $this->ConfigTemplate;
			
			foreach ($fields as $k=>$v)
				$config = str_replace('{$'.$k.'}', $v, $config);
			
			$remote_path = "{$this->VentriloPath}/etc/{$port}.ini";
			
			$tempfn = tempnam("","");
			$temp = fopen($tempfn, "w+");
			fwrite($temp, $config);
			fclose($temp);
			
			$retval = $this->SSH2->SendFile($remote_path, $tempfn);
			
			@unlink($tempfn);
			if (!$retval)
				return false;
			else
				return new VentriloVhost($this->SSHHost, $this->SSHPort, $this->SSHLogin, $this->SSHPassword, $this->VentriloPath, $this->ConfigTemplate, $port);
		}
		
		/**
		 * Return list of suppoted codecs
		 *
		 * @return array
		 */
		public function GetCodecs()
		{
			$res = $this->SSH2->Exec("{$this->VentriloPath}/bin/ventrilo_srv -?", "\004");
			
			if ($res)
			{
				$lines = explode("\n", $res);
				$codecs = array();
				for($i = 11; $i < count($lines); $i++)
				{
					if ($lines[$i] != '')
					{
						preg_match_all("/^[^0-9]*([0-9]+)[^0-9]*([0-9]+)[^A-Za-z]*(.*)$/si", $lines[$i], $matches);
						
						$tmp = explode("(", $matches[3][0]);
						
						if ($matches[1][0] >= 0 && $matches[2][0] >=0 && $tmp[0] != '')
							$codecs[$matches[1][0]][$matches[2][0]] = array("name" => $tmp[0], "type" => str_replace(")", "", $tmp[1]));
					}
				}
				
				return $codecs;
			}
			else
				return false;
		}
		
		/**
		 * Retrun list of Ventrilo servers (vhosts) and there status
		 *
		 * @return array
		 */
		public function ListServers()
		{
			$res = $this->SSH2->Exec("/bin/ls -al {$this->VentriloPath}/etc | grep .ini", "\004");
			$res2 = $this->SSH2->Exec("{$this->VentriloPath}/vent.sh get_running_ports", "\004");
			
			if ($res)
			{
				preg_match_all("/([0-9]+)\.ini/msi", $res, $servers);
				
				$running_servers = explode("\n", $res2);
				
				$started = array_flip($running_servers);
		          
				$retval = array();
				foreach ($servers[1] as $v)
				{
					if (isset($started[$v]))
						$retval[$v] = 1;
					else
						$retval[$v] = 0;
				}
				
				return $retval;
			}
			else
				return false;
		}

		public function ListServers2()
		{
			$res = $this->SSH2->Exec("/bin/ls -al {$this->VentriloPath}/etc | grep .ini", "\004");
			$res2 = $this->SSH2->Exec("ps -ax -o pid,command | grep 'etc/4496' | grep -v grep | head -n 1 | awk '{print \$1}'");
			
			var_dump($res2);
			exit();
			
			if ($res)
			{
				preg_match_all("/([0-9]+)\.ini/msi", $res, $servers);
				preg_match_all("/([0-9]+)\.pid/msi", $res2, $servers2);
				$started = array_flip($servers2[1]);
		
				$retval = array();
				foreach ($servers[1] as $v)
				{
					if (isset($started[$v]))
						$retval[$v] = 1;
					else
						$retval[$v] = 0;
				}
				
				return $retval;
			}
			else
				return false;
		}
	}
?>