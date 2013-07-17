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
     * @subpackage Shoutcast
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     * @ignore
     */

	
	Core::Load("NET/SSH/class.SSH2.php");
	
	class Ventrilo extends Core
	{
	    /**
	     * Path to shoutcast
	     *
	     * @var string
	     */
	    private $ShoutcastDir;
	    
        /**
	     * Path to accounts directory
	     *
	     * @var string
	     */
	    private $AccountsDir;
	    
	    /**
	     * Path to Account skeleton directory
	     * 
	     * @var string
	     */
	    private $SkeletonDir;
	    
	    /**
		 * Server host (SSH)
		 *
		 * @var string
		 */
		private $SSHHost;
		
		/**
		 * Server port (SSH)
		 *
		 * @var integer
		 */
		private $SSHPort;
		
		/**
		 * SSH Login
		 *
		 * @var string
		 */
		private $SSHLogin;
		
		/**
		 * SSH Password
		 *
		 * @var string
		 */
		private $SSHPassword;
		
		/**
		 * SSH Object instance
		 *
		 * @var SSH2
		 */
		private $SSH2;
		
		function __construct($host, $port, $login, $password, $sc_path, $accounts_dir, $skeleton_dir)
		{
		    parent::__construct();
		    
		    $this->ShoutcastDir = $sc_path;
		    $this->AccountsDir = $accounts_dir;
		    $this->SkeletonDir = $skeleton_dir;
		    
		    $this->SSH2 = new SSH2();
		    
		    if (!$this->SSH2->Connect($host, $port, $login, trim($password)))
			{
				Core::RaiseWarning(_("Cannot connect to remote server"));
				return false;
			}
			
			$this->SSHHost = $host;
			$this->SSHPort = $port;
			$this->SSHLogin = $login;
			$this->SSHPassword = trim($password);
		}
		
		public function GetVhost($port)
		{
		    
		    
		}
		
		public function CreateVhost($port, $info)
		{
		    
		}
		
		public function GetRunningServers()
		{
		    $res = $this->SSH2->Exec("{$this->ShoutcastDir}/sc.sh get_running_ports");
		    if ($res)
			{
				$running_servers = explode("\n", $res);
				return $running_servers;
			}
			else
				return false;
		}
	}
?>