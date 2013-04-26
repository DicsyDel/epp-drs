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
     * @subpackage Zeus
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */    

	define("ZEUS_INSTALL_ROOT", "/usr/local/zeus");
	
	
	/**
     * @name       ZeusWS
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Zeus
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class ZeusWS extends Core 
	{
		
		/**
		* SSH connection resource
		* @var $InstallPath
		* @access public
		*/
		private $InstallPath;
		
		private $IsInit;
		
		/**
		* Constructor
		* @access public
		* @param string $host Host to connect
		* @param int $port Port to connect
		* @param string $login SSH Login to authenticate with
		* @param string $password SSH Password to authenticate with
		* @return bool
		*/
		function __construct($host, $port, $authinfo)
		{
			parent::__construct();
			
			// Default values
			$this->InstallPath = ZEUS_INSTALL_ROOT;
			
			$this->Authinfo = $authinfo;
			
			// SSH2
			$this->SSH2 = new SSH2();
						
			if ($this->Authinfo["type"] == "password")
				$this->SSH2->AddPassword($this->Authinfo["login"], $this->Authinfo["password"]);
			elseif ($this->Authinfo["type"] == "pubkey")
				$this->SSH2->AddPubkey($this->Authinfo["login"], $this->Authinfo["pubkey_path"], $this->Authinfo["privkey_path"], $this->Authinfo["key_pass"]);
			
			$this->SSH2->Connect($host, $port);
				
			if ($this->SSH2->IsConnected())
			{
    			// CD to install path
    			$exec = $this->SSH2->Exec("cd ".$this->InstallPath."/webadmin/bin && pwd");
    			
    			if (stristr($exec, "No such file"))
    			{
    				$this->RaiseWarning("{$this->InstallPath}/webadmin/bin - not found");
    				$this->IsInit = false;
    			}
    			else 
                    $this->IsInit = true;
			}
			else 
                $this->IsInit = false;
		}
		
		public function IsInitialized()
		{
		    return $this->SSH2->IsConnected() && $this->IsInit;
		}
		
		/**
		* Add virtual host
		* @access public
		* @param string $name Domain name
		* @param string $path Full path to content folder
		* @param string $host Domain name
		* @param int $port TCP port to listen on
		* @param int $chown Unix directory permissions
		* @return bool True on success, False on failure
		*/
		public function AddVHost($name, $path, $host, $port, $chown=null)
		{
			// Create directory
			$this->SSH2->Exec("mkdir $path");
			if ($chown)
				$this->SSH2->Exec("chown $chown $path");
			
			
			// Symlink
			// $dir = dirname($path);
			// $folder = "www.".basename($path);
			// $this->SSH2->Exec("ln -s $path {$dir}{$folder}");
			
			
			// Start
			$retval = $this->SSH2->Exec($this->InstallPath."/webadmin/bin/addserver.sh $name $path $host $port");			
			$retval = !(bool)strstr($retval, "ERROR") && !(bool)strstr($retval, "Usage");
			
			if (!$retval)
                Core::RaiseWarning("Cannot create zeus vhost: {$retval}");
			
			return ($retval);
		}
		
		
		/**
		* Directly set virtual host metadata value
		* @access public
		* @param string $vhost_name Virtual host name
		* @param string $param Metadata parameter to set
		* @param string $value Value to assign to $param
		* @return bool True on success, False on failure
		*/
		public function SetMetaValue($vhost_name, $param, $value)
		{
			
			$metafile_path = $this->InstallPath."/webadmin/conf/virtual_servers/sites/{$vhost_name}";
			
			// Backup metafile
			$this->SSH2->Exec("mkdir ".$this->InstallPath."/backup");
			$this->SSH2->Exec("cp $metafile_path ".$this->InstallPath."/backup/{$vhost_name}.".time());	
			
			// Read source site metafile and replace value line
			$source = $this->SSH2->Exec("cat $metafile_path");	
			if (preg_match("/^{$param}[\s\t]+(.*)\n/", $source))		
				$result = preg_replace("/^{$param}[\s\t]+(.*)\n/", "$param	 $value\n",	$source);
			else	
				$result = $source . "\n$param	 $value";
			
			
			// Save metafile
			$tempfn = tempnam("/tmp","");
			$temp = fopen($tempfn, "w+");
			fwrite($temp, $result);
			fclose($temp);
			
			$this->SSH2->SendFile("$metafile_path", $tempfn);
			@unlink($tempfn);
			
			// TODO: grep result file for new stuff. if fails, restore backup and return false
			
			return(true);
			
		}
		
		
		/**
		* Commit changes made to vhost metadata
		* @access public
		* @param string $vhost_name Virtual host name
		* @return bool True on success, False on failure
		*/
		public function CommitVHost($vhost_name)
		{
			$retval = $this->SSH2->Exec($this->InstallPath."/webadmin/bin/webctl --action=commit --vs={$vhost_name}");
			$retval = (bool)strstr($retval, "Ok");
			
			if (!$retval)
                Core::RaiseWarning("Cannot commit zeus vhost {$retval}");
			
			return ($retval);
		}
		
		
		/**
		* Clone virtual host
		* @access public
		* @param string $name Host name
		* @param string $path Full path to content folder
		* @param string $host Domain name
		* @param int $port TCP port to listen on
		* @param int $chown Unix directory permissions
		* @param int $prototype VHost name to be cloned
		* @return bool True on success, False on failure
		*/
		public function CloneVHost($name, $path, $host, $port, $chown=null, $prototype)
		{
			// Create directory
			$this->SSH2->Exec("mkdir $path");
			if ($chown)
				$this->SSH2->Exec("chown $chown $path");
			
			
			// Start
			$retval = $this->SSH2->Exec($this->InstallPath."/webadmin/bin/addserver.sh $name $path $host $port --clone $prototype");			
			$retval = !(bool)strstr($retval, "ERROR") && !(bool)strstr($retval, "Usage");
			
			if (!$retval)
                Core::RaiseWarning("Cannot clone zeus vhost {$retval}");
			
			return ($retval);
		}
		
		
		/**
		* Delete virtual host
		* @access public
		* @param string $name Host name
		* @return bool True on success, False on failure
		*/
		public function DeleteVHost($name)
		{
			$retval = $this->SSH2->Exec($this->InstallPath."/webadmin/bin/delserver.sh $name");
			$retval = (bool)strstr($retval, "successfully");
			
			if (!$retval)
                Core::RaiseWarning("Cannot delete zeus vhost {$retval}");
			
			return ($retval);
		}
		
		
		/**
		* Start virtual host
		* @access public
		* @param string $name Host name
		* @return bool True on success, False on failure
		*/
		public function StartVHost($name)
		{
			$retval =  $this->SSH2->Exec($this->InstallPath."/webadmin/bin/startserver.sh $name");
			$retval = (bool)strstr($retval, "Ok");
			
			if (!$retval)
                Core::RaiseWarning("Cannot start zeus vhost {$retval}");
			
			return ($retval);
		}
		
		
		/**
		* Stop virtual host
		* @access public
		* @param string $name Host name
		* @return bool True on success, False on failure
		*/
		public function StopVHost($name)
		{
			$retval =  $this->SSH2->Exec($this->InstallPath."/webadmin/bin/stopserver.sh $name");
			$retval = (bool)strstr($retval, "Ok");
			
			if (!$retval)
                Core::RaiseWarning("Cannot stop zeus vhost {$retval}");
			
			return ($retval);
		}
		
		
		
	}
        
?>