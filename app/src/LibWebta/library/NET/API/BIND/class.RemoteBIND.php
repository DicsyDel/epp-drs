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
     * @subpackage BIND
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
	 * Load SSH
	 */
	Core::Load("NET/SSH/class.SSH2.php");
	
	/**
	 * Load FTP
	 */
	Core::Load("NET/FTP/class.FTP.php");
	
	/**
     * @name RemoteBIND
     * @category   LibWebta
     * @package NET_API
     * @subpackage BIND
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class RemoteBIND extends BIND
	{
		const DEFAULT_TRANSPORT = "ssh";
		/**
		 * Zones on Starting
		 *
		 * @var integer
		 * @access private
		 */
		private $ZonesOnStart;
		
		/**
		 * Have new zones
		 *
		 * @var bool
		 * @access private
		 */
		private $HaveNewZones;
		
		/**
		 * Make backups of zone files and named.conf before edit.
		 *
		 * @var bool
		 * @access private
		 */
		private $DoMakeBackup;
	
		/**
		 * Current transport
		 *
		 * @var string
		 * @access private
		 */
		private $Transport;
		
		/**
		 * Transport host
		 *
		 * @var string
		 * @access private
		 */
		private $Host;
		
		/**
		 * Transport port
		 *
		 * @var integer
		 * @access private
		 */
		private $Port;
		
		/**
		 * Transport auth info
		 *
		 * @var array
		 * @access private
		 */
		private $Authinfo;
		
		/**
		 * FTP Instance
		 *
		 * @var string
		 * @access private
		 */
		private $FTP;
		
		/**
		 * Constructor
		 *
		 * @param string $host
		 * @param string $port
		 * @param array $authinfo
		 * @param string $rndc_path
		 * @param string $namedconf_path
		 * @param string $nameddb_path
		 * @param string $zonetemplate
		 */
		function __construct($host, $port, $authinfo, $rndc_path, $namedconf_path, $nameddb_path, $zonetemplate, $inittransport= true)
		{
			// Call Bind class construct
			parent::__construct($namedconf_path, $nameddb_path, $zonetemplate, $rndc_path, false);	

			$this->Host = $host;
			$this->Port = $port;
			$this->Authinfo = $authinfo;
					
			$this->Transport = self::DEFAULT_TRANSPORT;
			
			if ($inittransport)
				if (!$this->InitTransport())
					Core::RaiseError("Cannot init transport");
			
			$this->DoMakeBackup = false;
			$this->HaveNewZones = 0;
		}
		
		/**
		 * Init transport
		 * @access protected
		 **/
		protected function InitTransport()
		{
			if ($this->Transport == "ssh")
			{
				// Remote part
				$this->SSH2 = new SSH2();
							
				if ($this->Authinfo["type"] == "password")
					$this->SSH2->AddPassword($this->Authinfo["login"], $this->Authinfo["password"]);
				elseif ($this->Authinfo["type"] == "pubkey")
					$this->SSH2->AddPubkey($this->Authinfo["login"], $this->Authinfo["pubkey_path"], $this->Authinfo["privkey_path"], $this->Authinfo["key_pass"]);
					
				if (!$this->SSH2->Connect($this->Host, $this->Port))
				{
					Log::Log(sprintf(_("Cannot connect to %s on port %d!"), $this->Host, $this->Port), E_ERROR);
					return false;
				}
											
				// Fetch named.conf
				$this->Conf = $this->SSH2->GetFile($this->NamedConfPath);
				if (!$this->Conf)				    
				{
					Log::Log(sprintf(_("named.conf does not exist or empty on %s"), $this->Host), E_ERROR);
					return false;
				}
						
				// COunt initial number of zones	
				$this->ZonesOnStart = $this->RndcStatus();
				if (!$this->ZonesOnStart)
				{
					Log::Log(sprintf(_("BIND is not running on %s"), $this->Host), E_ERROR);
					return false;
				}
			}
			elseif ($this->Transport == "ftp")
			{
				$this->FTP = new FTP($this->Host, $this->Authinfo["login"], $this->Authinfo["password"], $this->Port);
				if (!$this->FTP)
				{
					Log::Log(sprintf(_("Cannot connect to %s on port %s!"), $this->Host, $this->Port), E_ERROR);
					return false;
				}
					
				$this->Conf = $this->FTP->GetFile("/", basename($this->NamedConfPath), 1);
				if (!$this->Conf)
				{
					Log::Log(sprintf(_("Cannot fetch named.conf from %s"), $this->Host), E_ERROR);
					return false;
				}
			}
			
			return true;
		}
		
		/**
		 * Set current transport
		 *
		 * @param string $transport ftp|ssh
		 */
		public function SetTransport($transport)
		{
			$this->Transport = $transport;
			return $this->InitTransport();
		}
		
		/**
		* Save zone file
		* @access protected
		* @param string $name Zone name (undotted domain name)
		* @param string $content Zone text
		* @return bool Operation status
		*/
		protected  function SaveZoneFile($name, $content)
		{
			$tempfn = tempnam("","");
			$temp = fopen($tempfn, "w+");
			fwrite($temp, $content);
			fclose($temp);
			
			if ($this->Transport == "ssh")
			{
				$retval = $this->SSH2->SendFile("{$this->RootPath}/{$name}", $tempfn);
				$this->SSH2->Exec("chown named:named {$this->RootPath}/{$name}");
				$this->SSH2->Exec("chmod 0744 {$this->RootPath}/{$name}");
			}
			elseif($this->Transport == "ftp")
				$retval = $this->FTP->SendFile("{$this->RootPath}", "{$name}", $tempfn, 1);
			
			@unlink($tempfn);
			
			return $retval;
		}
		
		
		/**
		* Determine either zone file or zone declaration exist
		* @access public
		* @param string $name Zone name (undotted domain name)
		* @return bool True if zone file or declaration exist
		*/
		public function IsZoneExists($name)
		{
		    preg_match_all("/zone[^A-Za-z0-9]*({$name})[^{]+{[^;]+;([^A-Za-z0-9]+)file[^A-Za-z0-9;]*([A-Za-z0-9\.\/-]+)[^}]+};/msi", $this->Conf, $matches); 
			
			if ($matches[1][0] == $name)
				return $matches[3][0];
			else
				return false;
		}
		
		
		/**
		* Save DNS zne into zone file
		* @access public
		* @param string $name Zone name (undotted domain name)
		* @param string $content Zone content
		* @return bool Operation status
		*/
		public function SaveZone($name, $content, $reloadndc = true)
		{
			// Delete if already exists in named.conf
			$zone_db = $this->IsZoneExists($name);
			
			$filename = "{$name}.db";
			
			if ($zone_db)
			{
				// Make backup
				if ($this->DoMakeBackup)
				{
					$zone_db_bcp = $name.".".time();
					
					// Create backup
					if ($this->Transport == "ssh")
						$this->SSH2->Exec("/bin/mv {$this->RootPath}/{$name} {$this->RootPath}/{$zone_db_bcp}");
					elseif ($this->Transport == "ftp")
						$this->FTP->Rename("/", basename($zone_db), basename($name));
				}
				
				// Save zone contents to zone file
				if (!$this->SaveZoneFile($filename, $content))
					$this->RaiseWarning("Cannot save zone file for {$filename}");
			}
			else
			{
				$template = str_replace("{zone}", $name, $this->Template);
				$template = str_replace("{db_filename}", $filename, $template);
				
				$this->Conf = $this->Conf . $template;
				
				$this->NewZonesCount++;
							
				// Save zone contents to zone file
				if (!$this->SaveZoneFile($filename, $content))
					$this->RaiseWarning("Cannot save zone file for {$filename}");
				else
					// Save named.conf
					$this->SaveConf();
			}	
			
			// Reload rndc and count zones
			if ($reloadndc)
			{
				$this->ReloadRndc();
				$retval = $this->RndcStatus();
				
				$need = (int)($this->ZonesOnStart + $this->NewZonesCount);
			
				if (($this->NewZonesCount == 0 && $retval == $this->ZonesOnStart) || 
					($this->NewZonesCount != 0 && $retval == $need)
			 	  )
				{
					return true;
				}
				else
				{
					if (!$retval)
						Core::RaiseWarning(_("rndc reload failed"));
					else
						Core::RaiseWarning(
							sprintf(_("Cannot save DNS zone. Number of zones dont match. Should be: %d. There are: %d"), 
							$this->ZonesOnStart + $this->NewZonesCount, $retval)
						);
					
					return false;
				}
			}
			else
				return true;		
		}
		
		
		/**
		* Save named.conf
		* @access public
		* @return bool Operation status
		*/
		public function SaveConf()
		{
			$this->ConfCleanup();
			//
			$tempfn = tempnam("","");
			$temp = fopen($tempfn, "w+");
			fwrite($temp, $this->Conf);
			fclose($temp);
			
			if ($this->Transport == "ssh")
			{
				if ($this->DoMakeBackup)
					$this->SSH2->Exec("/bin/mv {$this->NamedConfPath} {$this->NamedConfPath}.".time());
				
				$retval = $this->SSH2->SendFile("{$this->NamedConfPath}", $tempfn);
			}
			elseif ($this->Transport == "ftp")
			{
				if ($this->DoMakeBackup)
				{
					$this->FTP->Rename("/", basename($this->NamedConfPath), basename("{$this->NamedConfPath}.".time()));
				}
				
				$retval = $this->FTP->SendFile("/", basename($this->NamedConfPath), $tempfn, 1);
			}
				
						
			@unlink($tempfn);
			
			return($retval);
		}
		
		
		/**
		* Delete DNS zone
		* @access public
		* @param string $zone Zone name (undotted domain name)
		* @return bool Operation status
		* @todo Delete zonename.db file?
		*/
		public function DeleteZone($name)
		{
			
			preg_match_all("/zone[^A-Za-z0-9]*({$name})[^{]+{[^;]+;([^A-Za-z0-9]+)file[^A-Za-z0-9;]*([A-Za-z0-9\.-]+)[^}]+};/msi", $this->Conf, $matches); 
			if ($matches[1][0] == $name)
			{
				$filename = $matches[3][0];
				$this->Conf = preg_replace("/zone\s+\"{$name}\"\s+\{.*?\};/msi", "", $this->Conf);
				$this->SaveConf();
				$this->ReloadRndc();
				
				if ($this->DoMakeBackup)
				{
					if ($this->Transport == "ssh")
					{
						$this->SSH2->Exec("/bin/mv {$this->RootPath}/{$filename} {$this->RootPath}/{$filename}.".time());
					}
					elseif ($this->Transport == "ftp")
					{
						$this->FTP->Rename("/", basename($filename), basename("{$filename}.".time()));
					}
				}
			}
			return true;
		}
		
		
		/**
		* Load DNS zone
		* @access public
		* @param string $name Zone name (undotted domain name)
		* @return string Zone contents
		*/
		public function LoadZone($name)
		{
			preg_match_all("/zone[^A-Za-z0-9]*({$name})[^{]+{[^;]+;([^A-Za-z0-9]+)file[^A-Za-z0-9;]*([A-Za-z0-9\.-]+)[^}]+};/msi", $this->Conf, $matches); 
			if ($matches[1][0] == $name)
			{
				$filename = $matches[3][0];
				
				if (substr($filename, 0, 1) != "/")
					$filename = "{$this->RootPath}/{$filename}";
					
				if ($this->Transport == "ssh")
				{
					return $this->SSH2->GetFile("{$filename}");
				}
				elseif ($this->Transport == "ftp")
				{
					return $this->FTP->GetFile("/", basename($filename), 1);
				}
			}
			else
				return false;
		}
		
		/**
		 * Return content of zone file
		 *
		 * @param string $filename
		 * @return string
		 */
		public function GetZoneFileContent($filename)
		{
			if (substr($filename, 0, 1) != "/")
				$filename = "{$this->RootPath}/{$filename}";
				
			if ($this->Transport == "ssh")
			{
				return $this->SSH2->GetFile("{$filename}");
			}
			elseif ($this->Transport == "ftp")
			{
				return $this->FTP->GetFile("/", basename($filename), 1);
			}
		}
		
		/**
		* Reload named - issue rndc reload
		* @access public
		* @param string $zone Zone name (undotted domain name)
		* @return bool Operation status
		*/
		public function ReloadRndc()
		{
			if ($this->Transport == "ssh")
			{
				return $this->SSH2->Exec("{$this->Rndc} reload");
			}
			elseif ($this->Transport == "ftp")
			{
				Core::RaiseWarning(_("FTP transport not support RNDC reload command"));
			}
		}
		
		/**
		 * Return Number of zones if BIND worked else false
		 *
		 * @return integer
		 */
		public function RndcStatus()
		{
			if ($this->Transport == "ssh")
			{
				$retval = $this->SSH2->Exec("{$this->Rndc} status");
			
				preg_match_all("/number of zones:[^0-9]([0-9]+)/", $retval, $matches);
			
				if ($matches[1][0] > 0)
					return $matches[1][0];
				else
					return false;
			}
			elseif ($this->Transport == "ftp")
			{
				Core::RaiseWarning(_("FTP transport not support RNDC status command"));
			}
		}
		
		/**
		 * If $state tru, we backup zone file and named.conf before edit.
		 *
		 * @param bool $state
		 */
		public function SetBackup($state)
		{
			$this->DoMakeBackup = $state;
		}
	}
?>