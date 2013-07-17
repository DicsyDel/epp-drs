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
     * @package    System_Unix
     * @subpackage Accounting
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
	 * @name       SystemUserManager
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage Accounting
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	class SystemUserManager extends Core 
	{
		
		/**
		 * Path to default shell
		 *
		 */
		const SHELL_PATH = "/bin/noshell";
		
		/**
		 * Users home dir
		 *
		 */
		const HOME_DIR = "/home";
		
		/**
		 * Path to Add user utility
		 *
		 */
		const USERADD_PATH = "/usr/sbin/useradd";
		
		/**
		 * Path to chpasswd utility
		 *
		 */
		const CHPASSWD_PATH = "/usr/sbin/chpasswd";
		
		/**
		 * Default users Group
		 *
		 */
		const USER_GROUP = "www";
		
		/**
		 * useradd path
		 *
		 * @var string
		 */
		private $UserAddPath;
		
		/**
		 * chpasswd path
		 *
		 * @var string
		 */
		private $ChpasswdPath;
		
		/**
		 * Path to skeleton dir
		 *
		 * @var string
		 */
		private $SkelDir;
		
		/**
		 * User group
		 *
		 * @var string
		 */
		private $UserGroup;
		
		/**
		 * SystemStats instance
		 *
		 * @var SystemStats
		 */
		private $SystemStats;
		
		/**
		 * Shell instance
		 *
		 * @var Shell
		 */
		private $Shell;
		
		/**
		 * Constructor
		 *
		 */
		function __construct()
		{
			parent::__construct();
			
			$this->SystemStats = Core::GetInstance("SystemStats");
			
			$this->Shell = ShellFactory::GetShellInstance();
			
			$this->UserAddPath = (defined("CF_USERADD_PATH")) ? CF_USERADD_PATH : self::USERADD_PATH;
			$this->ChpasswdPath = (defined("CF_CHPASSWD_PATH")) ? CF_CHPASSWD_PATH : self::CHPASSWD_PATH;
			
			$this->SkelDir = (defined("CF_SKEL_DIR")) ? CF_SKEL_DIR : false;
			
			$this->UserGroup = (defined("CF_USER_GROUP")) ? CF_USER_GROUP : self::USER_GROUP;
			
			$this->ShellPath = (defined("CF_SHELL_PATH")) ? CF_SHELL_PATH : self::SHELL_PATH;
		}
		
		/**
		* Create new system user
		* @access public
		* @param string $username System username
		* @param string $password Password
		* @param string $homedir Home directory
		* @param string $shell Shell path
		* @return SystemUser SystemUser instance 
		*/

		public function Create($username, $password, $homedir = NULL, $shell = NULL) 
		{
			
			// Check if skel exists	
			if ($this->SkelDir && !is_readable($this->SkelDir))
				Core::RaiseError(sprintf(_("User home skeleton directory (%s) not readable"), self::SKEL_DIR));
				
			// Get default shell
			if (!$shell)
				$shell = $this->ShellPath;
				
			//Get default homedir
			if (!$homedir)
				$homedir = self::HOME_DIR ."/". $username;
				
			// Check useradd tool
			if (!is_executable($this->UserAddPath))
				Core::RaiseError(sprintf(_("%s not executable"), $this->UserAddPath));
			
			//Check chpasswd tool
			if (!is_executable($this->ChpasswdPath))
				Core::RaiseError(sprintf(_("%s not executable"), $this->ChpasswdPath));
			
			if ($this->SkelDir)
				$skeldir = "-k {$this->SkelDir}";
				
			if ($this->SystemStats->IsFreeBSD)
				$args = "useradd {$username} -d {$homedir} -g {$this->UserGroup} {$skeldir} -s {$shell}";
			else 
				$args = " -d {$homedir} -s {$shell} -m {$skeldir} {$username}";
				
			$retval = $this->Shell->ExecuteRaw("{$this->UserAddPath} {$args}");
			
			if ($this->SystemStats->IsFreeBSD)
				$retval &= $this->Shell->ExecuteRaw("echo '{$password}' | {$this->ChpasswdPath} usermod {$username} $1 -h 0");
			else 
				$retval &= $this->Shell->ExecuteRaw("echo {$username}:{$password} | {$this->ChpasswdPath}");
			
			// Return SystemUser or false
			$retval = $retval ? $this->GetUserByName($username) : false;	
			
			return($retval);
		}
	


		/**
		* List all users
		* @access public
		* @param string $query regexp filter results
		* @return array
		*/

		public function GetList($minuid = 500, $filter = NULL) 
		{
			if (!is_readable("/etc/passwd"))
				Core::RaiseError(_("/etc/passwd not readable"));
			
			$filter = $filter ? "|grep $filter" : "";
			$res = $this->Shell->QueryRaw("cat /etc/passwd $filter");
			$res = explode("\n", $res);
			foreach ($res as $row)
			{
				$rowarr = explode(":", $row);
				if($rowarr[2] >= $minuid)
					$retval[] = new SystemUser($rowarr[2]);
			}
			return $retval;
			
		}
		
		
		/**
		* Find user by username
		* @access public
		* @param string $username username
		* @return SystemUser SystemUser object
		*/

		public function GetUserByName($username) 
		{
			if (!is_readable("/etc/passwd"))
				Core::RaiseError(_("/etc/passwd not readable"));
			
			$res = $this->Shell->QueryRaw("cat /etc/passwd | grep '^{$username}:'");
			$rowarr = explode(":", $res);
						
			if ($rowarr[2])
				$retval = new SystemUser($rowarr[2]);
			else 
				$retval = false;
			
			return $retval;
		}
		
		
		/**
		* Find user by username
		* @access public
		* @param string $username username
		* @return SystemUser SystemUser object
		*/

		public function GetUserByUID($uid) 
		{
			if (!is_readable("/etc/passwd"))
				Core::RaiseError(_("/etc/passwd not readable"));
			
			$res = $this->Shell->QueryRaw("cat /etc/passwd | grep ':[\*x]:$uid:'");
			$rowarr = explode(":", $res);
			$retval = new SystemUser($rowarr[2]);
			return $retval;
		}


	}
?>