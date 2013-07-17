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
	 * @name       SystemUser
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage Accounting
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	class SystemUser extends Core 
	{
		/**
		 * Path to useradd
		 *
		 */
		const USERADD_PATH = "/usr/sbin/useradd";
		
		/**
		 * Path to chpasswd
		 *
		 */
		const CHPASSWD_PATH = "/usr/sbin/chpasswd";
		
		/**
		 * path to userdel
		 *
		 */
		const USERDEL_PATH = "/usr/sbin/userdel";
		
		/**
		 * path to usermod
		 *
		 */
		const USERMOD_PATH = "/usr/sbin/usermod";
		
		/**
		 * Password file (/etc/shadow on linux systems and /etc/master.passwd on FreeBSD)
		 *
		 */
		const PASSWORD_FILE_PATH = "/etc/shadow";
		/**
		* 
		* @var string
		* @access public
		*/
		public  $Username;

		/**
		* 
		* @var string
		* @access public
		*/
		public  $PwdHash;

		/**
		* 
		* @var string
		* @access public
		*/
		public  $ShellPath;
		
		/**
		* 
		* @var string
		* @access public
		*/
		public  $Home;

		/**
		* Unix user ID
		* @var int
		* @access public
		*/
		public  $UID;

		/**
		* 
		* @var int
		* @access public
		*/
		public  $GID;
		
		/**
		 * Path to useradd
		 *
		 * @var string
		 */
		private $UserAddPath;
		
		/**
		 * Path to chpasswd
		 *
		 * @var string
		 */
		private $ChpasswdPath;
		
		/**
		 * Path to userdel
		 *
		 * @var string
		 */
		private $UserDelPath;
		
		/**
		 * Path to usermod
		 *
		 * @var string
		 */
		private $UserModPath;
		
		/**
		 * Password file path
		 *
		 * @var string
		 */
		private $PassFilePath;
		
		/**
		 * System UserConstructor
		 *
		 * @param integer $uid
		 */
		function __construct($uid)
		{
			
			parent::__construct();
			$this->Shell = ShellFactory::GetShellInstance();
			$this->SystemStats = Core::GetInstance("SystemStats");
			
			$this->UserAddPath = (defined("CF_USERADD_PATH")) ? CF_USERADD_PATH : self::USERADD_PATH;
			$this->ChpasswdPath = (defined("CF_CHPASSWD_PATH")) ? CF_CHPASSWD_PATH : self::CHPASSWD_PATH;
			$this->UserDelPath = (defined("CF_USERDEL_PATH")) ? CF_USERDEL_PATH : self::USERDEL_PATH;
			$this->UserModPath = (defined("CF_USERMOD_PATH")) ? CF_USERMOD_PATH : self::USERMOD_PATH;
			
			$this->PassFilePath = (defined("CF_PASSWORD_FILE_PATH")) ? CF_PASSWORD_FILE_PATH : self::PASSWORD_FILE_PATH;
			
			
			
			if (!is_readable("/etc/passwd"))
				Core::RaiseError(_("/etc/passwd not readable"));
			
			// Get user details
			$res = $this->Shell->QueryRaw("cat /etc/passwd | grep ':[\*x]:$uid:'");
			$rowarr = explode(":", $res);
			
			$this->Username = $rowarr[0];
			$this->UID = $rowarr[2];
			$this->GID = $rowarr[3];
			$this->Home = $rowarr[5];
			$this->ShellPath = $rowarr[6];
			
			// get password
			$this->GetPwdHash();
		}

		/**
		* Delete user
		* @access public
		* @param bool $deletehome Either user home directory should be deleted
		* @return bool true on success
		*/

		public function Delete($deletehome = true) 
		{
			//Check userdel tool
			if (!is_executable($this->UserDelPath))
				Core::RaiseError(sprintf(_("%s not executable"), $this->UserDelPath));
				
			$deletehome = $deletehome ? "-r" : "";
							
			if ($this->SystemStats->IsLinux)				
				$args = "{$deletehome} {$this->Username}";
			elseif ($this->SystemStats->IsFreeBSD)
				$args = "userdel {$this->Username} $deletehome";
			else 
				Core::RaiseError(_("This method available only on *nix systems."));
					
			$retval = $this->Shell->ExecuteRaw("{$this->UserDelPath} {$args}");
			return $retval;
		}
		
		/**
		* Change unix password
		* @access public
		* @param string $password Password, plain text
		* @return bool
		*/

		public final  function SetPassword($password) 
		{
			//Check chpasswd tool
			if (!is_executable($this->ChpasswdPath))
				Core::RaiseError(sprintf(_("%s not executable"), $this->ChpasswdPath));
			
			if ($this->SystemStats->IsFreeBSD)
				$retval &= $this->Shell->ExecuteRaw("echo '{$password}' | {$this->ChpasswdPath} usermod {$this->Username} $1 -h 0");
			else
				$retval = $this->Shell->ExecuteRaw("echo {$this->Username}:{$password} | $this->ChpasswdPath");
				
			return($retval);
		}


		/**
		* Change user shell path
		* @access public
		* @param string $shellpath Path to shell
		* @return bool
		*/

		public final  function SetShell($shellpath) 
		{
			//Check usermod tool
			if (!is_executable($this->UserModPath))
				Core::RaiseError(sprintf(_("%s not executable"), $this->UserModPath));
				
			if ($this->SystemStats->IsFreeBSD)
				$retval = $this->Shell->ExecuteRaw("{$this->UserModPath} usermod {$this->Username} -s $shellpath");
			else
				$retval = $this->Shell->ExecuteRaw("{$this->UserModPath} -s $shellpath {$this->Username}");
				
			$this->ShellPath = $shellpath;
			return($retval);
		}

	
		/**
		* Get password hash
		* @access public
		* @return string Password hash, $this->PwdHash
		*/

		public final function GetPwdHash() 
		{
			if (!is_readable($this->PassFilePath))
				Core::RaiseError(_("{$this->PassFilePath} not readable"));
			
			$res = $this->Shell->QueryRaw("cat {$this->PassFilePath} | grep ^{$this->Username}:");
			$rowarr = explode(":", $res);
			$this->PwdHash = $rowarr[1];
			return($this->PwdHash);
		}
		

		/**
		* Get user filesystem quota
		* @access public
		* @return int
		*/

		public final  function GetQuotaLimit() 
		{
			
		}


		/**
		* Get disk space useage, in bytes
		* @access public
		* @return int
		*/

		public final  function GetQuotaUsage() 
		{

		}


	}
?>