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
	
	/**
     * @name       VentriloVhost
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Ventrilo
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class VentriloVhost extends Core
	{
		/**
		 * Path to ventrilo
		 *
		 * @var string
		 */
		private $VentriloPath;
		
		/**
		 * Vhost Config template
		 *
		 * @var string
		 */
		private $ConfigTemplate;
		
		/**
		 * Virtual host port (UDP)
		 *
		 * @var integer
		 */
		private $Port;
		
		/**
		 * SSH2 Instance
		 *
		 * @var SSH2
		 */
		private $SSH2;
		
		/**
		 * Constructor
		 *
		 * @param string $host
		 * @param integer $port
		 * @param array $authinfo
		 * @param string $ventrilo_path
		 * @param string $config_template
		 * @param integer $vport
		 * @return bool
		 */
		function __construct ($host, $port, $login, $password, $ventrilo_path, $config_template, $vport)
		{
			parent::__construct();
			
			$this->VentriloPath = $ventrilo_path;
			$this->ConfigTemplate = $config_template;
			
			$this->SSH2 = new SSH2();
			
			if (!$this->SSH2->Connect($host, $port, $login, $password))
			{
				Core::RaiseWarning(_("Cannot connect to remote server"));
				return false;
			}
		
			$this->Port = $vport;
			
			return true;
		}
		
		public function ChangePort($newport)
		{
			// Stop server
			$this->Stop();
			
			$res = $this->SSH2->Exec("/bin/ls {$this->VentriloPath}/etc | grep {$this->Port}.*", "\004");
			if ($res)
			{
				$files = explode("\n", $res);
				foreach ($files as $file)
				{
					$file = trim($file);
					if ($file)
					{
						$pi = pathinfo($file);
						$this->SSH2->Exec("/bin/mv {$this->VentriloPath}/etc/{$file} {$this->VentriloPath}/etc/{$newport}.{$pi["extension"]}");
					}
				}
			}
			
			return true;
		}
		
		/**
		 * Save vhost config
		 *
		 * @param string $fields
		 * @return bool
		 */
		public function SaveConfig($fields, $restart = false)
		{
			// Stop server
			if ($restart)
				$this->Stop();
		
			$config = $this->ConfigTemplate;
			
			foreach ($fields as $k=>$v)
				$config = str_replace('{$'.$k.'}', $v, $config);
			
			$remote_path = "{$this->VentriloPath}/etc/{$this->Port}.ini";
			
			$tempfn = tempnam("","");
			$temp = fopen($tempfn, "w+");
			fwrite($temp, $config);
			fclose($temp);
			
			// Try to save config. If success try to Start server else return false
			$retval = $this->SSH2->SendFile($remote_path, $tempfn);
			@unlink($tempfn);
			
			if ($restart)
				$this->Start();
			
			if (!$retval)
				return false;
			else
				return true;
		}
		
		/**
		 * Remove ventrilo virtualhost
		 *
		 * @return bool
		 */
		public function Delete ()
		{
			// Stop server
			$this->Stop();
			
			// Remove all server files
			$res = $this->SSH2->Exec("rm -rf {$this->VentriloPath}/etc/{$this->Port}.*", "\004");
		
			if ($res)
				return true;
			else
				return false;
		}
		
		/**
		 * Restart virtualhost
		 *
		 * @return bool
		 */
		public function Restart ()
		{
			$res = $this->SSH2->Exec("{$this->VentriloPath}/vent.sh restart {$this->Port}", "\004");
			if ($res)
			{
				if (stristr($res, "Cannot find"))
					return false;
				elseif (stristr($res, "Done."))
					return true;
				else
					return false;
			}
			else
				return false;
		}
		
		/**
		 * Stop virtualhost
		 *
		 * @return bool
		 */
		public function Stop ()
		{
			$res = $this->SSH2->Exec("{$this->VentriloPath}/vent.sh stop {$this->Port}", "\004");
			if ($res)
			{
				if (stristr($res, "Cannot find"))
					return false;
				elseif (stristr($res, "Done."))
					return true;
				else
					return false;
			}
			else
				return false;
		}
		
		/**
		 * Start virtualhost
		 *
		 * @return bool
		 */
		public function Start()
		{
			$res = $this->SSH2->Exec("{$this->VentriloPath}/vent.sh start {$this->Port}", "\004");
			
			if ($res)
			{
				if (stristr($res, "Cannot find"))
				{
					Core::RaiseWarning(_("Cannot find virtualhost"));
					return false;
				}
				elseif (stristr($res, "Cannot start server on"))
				{
					$lines = explode("\n", $res);
										
					$error = $lines[count($lines)-5]."\n".$lines[count($lines)-4];	
					Core::RaiseWarning($error);
					return false;
				}
				elseif (stristr($res, "Done."))
					return true;
				else
				{
					Core::RaiseWarning(_("Unknown error"));
					return false;
				}
			}
			else
				return false;
		}
		
		public function GetStat($ipaddr)
		{
			$res = $this->SSH2->Exec("{$this->VentriloPath}/bin/ventrilo_status -c2 -t{$ipaddr}:{$this->Port}", "\004");
			if ($res)
			{
				$chunks = explode("\n", $res);
				foreach ((array)$chunks as $chunk)
				{
					$chunk = trim($chunk);
					$line = explode(":", $chunk);
					
					$key = trim($line[0]);
					$value = trim($line[1]);
					
					if (!empty($key))
						$retval[$key][] = urldecode($value);
				}
				
				// Parse channels
				if ($retval["CHANNELCOUNT"][0] > 0)
				{
					
					foreach ((array)$retval["CHANNEL"] as $chan)
					{
						$chunks = explode(",", $chan);
						foreach ((array)$chunks as $chunk)
						{
							$tmp = explode("=",$chunk);
							$channel[trim($tmp[0])] = trim($tmp[1]);
						}
						
						$retval["CHANNELS"][] = $channel;
					}
				}
				
				// Parse Users
				if ($retval["CLIENTCOUNT"] > 0)
				{
					foreach ((array)$retval["CLIENT"] as $cl)
					{
						$chunks = explode(",", $cl);
						foreach ((array)$chunks as $chunk)
						{
							$tmp = explode("=",$chunk);
							$client[trim($tmp[0])] = trim($tmp[1]);
						}
						
						$retval["CLIENTS"][] = $client;
					}
				}
			}
			else 
				$retval = false;
			
			return $retval;
		}
	}
?>