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
     * @package    IO
     * @subpackage Transports
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
    
    Core::Load("NET/SSH");
    
    /**
     * SSH Transport
     * 
     * @name       SSHTransport
     * @category   LibWebta
     * @package    IO
     * @subpackage Transports
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class SSHTransport implements ITransport 
    {
        /**
         * SSH Connection
         *
         * @var SSH2
         */
        private $SSHConnection;
        
        /**
         * SSH Transport constructor
         *
         * @param string $ssh_host SSH Host
         * @param string $ssh_port SSH Port
         * @param string $ssh_login SSH Login
         * @param string $ssh_password SSH Password
         */
        public function __construct($ssh_host, $ssh_port, $ssh_login = false, $ssh_password = false)
        {
            $this->SSHConnection = Core::GetInstance("SSH2");
            
            $this->SSHHost = $ssh_host;
            $this->SSHPort = $ssh_port;
            
            if ($ssh_login && $ssh_password)
                $this->Auth("password", $ssh_login, $ssh_password);
            
            if ($this->SSHConnection instanceof SSH2)
                $this->Connect();
            else 
                Core::RaiseError("Transports/SSHTransport: Cannot get SSH2 instance.");
        }
        
        /**
         * Authenticate user
         *
         * @param string $authmethod
         * @param variable-length argument lists
         */
        public function Auth($authmethod)
        {
            switch($authmethod)
            {
                case "password":
                    
                    $this->SSHConnection->AddPassword(func_get_arg(1), func_get_arg(2));
                    
                    break;
                    
                case "pubkey":
                    
                    $this->SSHConnection->AddPubkey(func_get_arg(1), func_get_arg(2), func_get_arg(3), func_get_arg(4));
                    
                    break;
            }
        }
        
        /**
         * Return avaiable auth methods for this transport
         *
         * @return unknown
         */
        public function GetAuthMethods()
        {
            return array("password" => "Authenticate using login and password", "pubkey" => "Authenticate using a public key");
        }
        
        /**
         * @ignore
         *
         */
        public function __destruct()
        {
            unset($this->SSHConnection);
        }
        
        /**
         * Connect to SSH Server
         *
         * @access private
         * @return bool
         * @return bool
         */
        private function Connect()
        {
            if (!$this->SSHConnection->Connect($this->SSHHost, $this->SSHPort))
                Core::RaiseError(sprintf(_("Transports/SSHTransport: Cannot connect to %s:%s."), $this->SSHHost, $this->SSHPort));
            else 
                return true;
        }
        
        /**
         * Read file
         *
         * @param string $filename
         * @return bool
         */
        public function Read($filename)
        {
            if (!$this->SSHConnection->IsConnected())
                $this->Connect();
                
            return $this->SSHConnection->GetFile($filename);
        }
	    
        /**
         * Write file
         *
         * @param string $filename
         * @param string $content
         * @param bool $overwrite
         * @return bool
         */
	    public function Write($filename, $content, $overwrite = true)
	    {
	        if (!$this->SSHConnection->IsConnected())
                $this->Connect();
            
            $tp = ($overwrite) ? "w+" : "a+";
            return $this->SSHConnection->SendFile($filename, $content, $tp, false);
	    }
	    
	    /**
	     * Copy file or folder
	     *
	     * @param string $old_path
	     * @param string $new_path
	     * @return bool
	     */
	    public function Copy($old_path, $new_path, $recursive = true)
	    {
	        if (!$this->SSHConnection->IsConnected())
                $this->Connect();
                
            $params = ($recursive) ? "-R" : "";
            return $this->SSHConnection->Exec("cp {$params} {$old_path} {$new_path}");
	    }
	    
	    /**
	     * Remove file or folder
	     *
	     * @param string $path
	     * @return bool
	     */
	    public function Remove($path, $recursive = true)
	    {
	        if (!$this->SSHConnection->IsConnected())
                $this->Connect();
                
            $params = ($recursive) ? "-rf" : "-f";
            return $this->SSHConnection->Exec("rm {$params} {$path}");
	    }
	    
	    /**
	     * Chmod file or folder
	     *
	     * @param string $path
	     * @param integer $perms
	     * @return bool
	     */
	    public function Chmod($path, $perms, $recursive = true)
	    {
	        if (!$this->SSHConnection->IsConnected())
                $this->Connect();
            
            $params = ($recursive) ? "-R" : "";
            return $this->SSHConnection->Exec("chmod {$params} {$perms} {$path}");
	    }
	    
	    /**
	     * Move file or folder (rename)
	     *
	     * @param string $old_path
	     * @param string $new_path
	     * @return bool
	     */
	    public function Move($old_path, $new_path, $recursive = true)
	    {
	        if (!$this->SSHConnection->IsConnected())
                $this->Connect();
                
            $params = ($recursive) ? "-R" : "";
            return $this->SSHConnection->Exec("mv {$params} {$old_path} {$new_path}");
	    }
	    
	    /**
	     * Create net directory
	     *
	     * @param string $path
	     * @return bool
	     */
	    public function MkDir($path)
	    {
	        if (!$this->SSHConnection->IsConnected())
                $this->Connect();
                
            return $this->SSHConnection->Exec("mkdir {$path}");
	    }
	    
	    /**
	     * Execute command
	     *
	     * @param string $command
	     * @return bool
	     */
	    public function Execute($command)
	    {
	        if (!$this->SSHConnection->IsConnected())
                $this->Connect();
                
            $res = $this->SSHConnection->Exec($command);
            if ($res === true)
                $res = $this->SSHConnection->StdErr;
                
            return $res;
	    }
    }
?>