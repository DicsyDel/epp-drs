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
	 * Load ShellFactory
	 */
	Core::Load("System/Independent/Shell/class.ShellFactory.php");
	
	/**
     * @name BIND
     * @category   LibWebta
     * @package NET_API
     * @subpackage BIND
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class BIND extends Core
	{
		
		/**
		* named.conf contents
		* @var string
		* @access public
		*/
		public $Conf;
		
		/**
		* named.conf single zone template
		* @var string
		* @access public
		*/
		protected $Template;
		
		/**
		* Path to a folder where zones fies being stored (/var/named)
		* @var string
		* @access public
		*/
		protected $RootPath;
		
		/**
		* Path to named.conf
		* @var string
		* @access public
		*/
		protected $NamedConf;
		
		/**
		* Path to rndc binary
		* @var string
		* @access public
		*/
		protected $Rndc;
		
		/**
		 * BIND Constructor
		 *
		 * @param string $named_conf
		 * @param string $named_root
		 * @param string $named_tmp
		 * @param string $rndc_path
		 * @param bool $read_conf
		 */
		function __construct($named_conf, $named_root, $named_tmp, $rndc_path, $read_conf = true)
		{			
			$this->RootPath = $named_root;
			$this->NamedConfPath = $named_conf;
			$this->Template = "\n".$named_tmp."\n";
			$this->Rndc = $rndc_path;
			
			// Load named.conf
			if ($read_conf)
				if (!$this->Conf = @file_get_contents($this->ConfPath))
					Core::RaiseWarning("Failed to open named.conf for reading");					
		}
		
		/**
		 * Set Zone template
		 *
		 * @param string $template
		 */
		public function SetZoneTemplate($template)
		{
			$this->Template = "\n".$template."\n";
		}
		
		
		/**
		* Set Path to RNDC
		* @access public
		* @param $path
		* @return void
		*/
		public function SetRndcPath($path)
		{
			$this->Rndc = $path;
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
				$this->Conf = preg_replace("/\\r/m", "", $this->NamedConfPath);
				$this->Conf = preg_replace("/\\n\\n\\n/m", "\n\n", $this->NamedConfPath);
			}
		}
		
		
		/**
		* Check either named.conf contains unneeded chars
		* @access public
		* @return bool
		*/
		public function IsConfClean()
		{
			$retval = preg_match("/\\n\\n\\n/m", $this->NamedConfPath);
			return (bool) !$retval;
		}
		
		
		/**
		* Save named.conf
		* @access public
		* @return bool Operation status
		*/
		public function SaveConf()
		{
			$retval = @file_put_contents($this->ConfPath, $this->NamedConfPath);
			return($retval);
		}
		
		
		/**
		* Save zone file
		* @access public
		* @param string $name Zone name (undotted domain name)
		* @param string $content Zone text
		* @return bool Operation status
		*/
		protected  function SaveZoneFile($name, $content)
		{
			$retval = @file_put_contents("{$this->RootPath}/{$name}.db", $content);
			return($retval > 0);
		}
	
	
		/**
		* Determine either zone file or zone declaration exist
		* @access public
		* @param string $name Zone name (undotted domain name)
		* @return bool True if zone file or declaration exist
		*/
		public function IsZoneExists($name)
		{
			$retval = preg_match("/zone(.*?){$name}(.*?)/i", $this->NamedConfPath); 
			$retval &= file_exists("{$this->RootPath}/{$name}.db");
			return($retval);
		}
		
		
		/**
		* Save DNS zne into zone file
		* @access public
		* @param string $name Zone name (undotted domain name)
		* @param string $content Zone content
		* @param bool $saveconf Either to add a zone to named.conf
		* @return bool Operation status
		*/
		public function SaveZone($name, $content, $saveconf = true)
		{
			
			// Delete if already exists in named.conf
			if ($this->IsZoneExists($name))
				$this->DeleteZone($name);
			
			// Append zone definition at the end of named.conf
			$this->Conf = $this->NamedConfPath . str_replace("{zone}", $name, $this->Template);
			
			// Save named.conf
			if ($saveconf)
				$this->SaveConf();
			
			$this->SaveZoneFile($name, $content);
			
			// Delete line returns
			$this->ConfCleanup();
			
			return true;
			
		}
		
		
		/**
		* Delete DNS zone
		* @access public
		* @param string $zone Zone name (undotted domain name)
		* @return bool Operation status
		*/
		public function DeleteZone($name, $saveconf = true)
		{
			// Search for entries
			$this->Conf = preg_replace("/zone\s+\"{$name}\"\s+\{.*?\};/msi", "", $this->NamedConfPath);
				
			if ($saveconf)
				$this->SaveConf();
				
			@unlink("{$this->RootPath}/{$name}.db");
			
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
			return(@file_get_contents("{$this->RootPath}/{$name}.db"));
		}
		
		/**
		 * Return content of zone file
		 *
		 * @param string $filename
		 * @return string
		 */
		public function GetZoneFileContent($filename)
		{
			return $this->LoadZone(str_replace(".db", "", $filename));
		}
		
		/**
		* Reload named - issue rndc reload
		* @access public
		* @param string $zone Zone name (undotted domain name)
		* @return bool Operation status
		*/
		public function ReloadRndc()
		{
			$Shell = ShellFactory::GetShellInstance();
			
			return $Shell->Execute("{$this->Rndc}", array("reload"));
		}
		
		/**
		 * Return array with all zones
		 * @access public
		 * @return array $zones
		 */
		public function ListZones($ptr_zones = false)
		{
            // Delete multiline comments from named.conf
            $this->Conf = preg_replace("/\/\*(.*?)\*\//ism", "", $this->Conf);
            
            $lines = explode("\n", $this->Conf);
			$retval = array();
			
			foreach ($lines as $line)
			{
				preg_match_all("/^[^\/;#]*zone(.*?)['\"]+(.*?)['\"]+(.*?)\{(.*?)$/si", $line, $matches);
                
				// filter local zones
				if (($matches[2][0] != '' &&
					$matches[2][0] != '.' &&
					$matches[2][0] != 'localhost' &&
					$matches[2][0] != 'localdomain' &&
					!stristr($matches[2][0], "in-addr.arpa") &&
					!stristr($matches[2][0], "ip6.arpa") && !$ptr_zones) || 
					((stristr($matches[2][0], "in-addr.arpa") || stristr($matches[2][0], "ip6.arpa")) && $ptr_zones))
				{
					$in_zone = $matches[2][0];
				}
				
				if (preg_match("/^[^\/;#]*}/", $line, $matches))
					$in_zone = false;
									
				if ($in_zone)
				{
					preg_match_all("/^[^\/;#]*file(.*?)['\"]+(.*?)['\"]+(.*?)$/si", $line, $matches);
					if ($matches[2][0])
					{
						$content = $this->GetZoneFileContent($matches[2][0]);

						if ($content)
							$retval[$in_zone] = $content;
						else 
							Core::RaiseWarning("Cannot get '{$matches[2][0]}'");
					}
				}
			}
			return $retval;
		}
	}

?>