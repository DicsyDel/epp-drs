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
     * @subpackage vBulletin
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     */
	
    /**
     * @name vBulletinConnector
     * @category Libwebta
     * @package NET_API
     * @subpackage vBulletin
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
    class vBulletinConnector
    {
    	/**
    	 * Minimum allowed version
    	 */
    	const MIN_VERSION = 35;
    	
    	
    	/**
    	 * Forum tables prefix
    	 */
    	private $TablePrefix;
    	
    	/**
    	 * vBulletin forum cookie prefix
    	 */
    	private $CookiePrefix;
    	
    	/**
    	 * Db instance
    	 */
    	private $DB;
    	
    	
    	/**
    	 * Forum usergroups
    	 */
    	private $Groups;


		/**
		 * Constructor
		 * 
		 * @param string path to vbulletin config
		 */
    	function __construct($config_path)
    	{
	    	if (!file_exists($config_path)) 
	    		return false;
	    	
	    	// version checking
	    	$includes = dirname($config_path);
	    	if (file_exists("$includes/class_core.php"))
	    	{
	    		require_once("$includes/class_core.php");
	    		preg_match("/^(\d)\.(\d)/", FILE_VERSION, $m);
	    		$ver = intval($m[1] . $m[2]);
	    	}
	    	
	    	if (!$ver || $ver < self::MIN_VERSION)
	    		Core::RaiseError(sprintf(_("vBulletin forum version '%s' installed. %s or higher required."), $ver, self::MIN_VERSION));
	    	
	    	$config = array();
	    	require($config_path);

	    	$this->TablePrefix = $config['Database']['tableprefix'];
	    	$this->CookiePrefix = $config['Misc']['cookieprefix'];
    		
    		$conf = array(
    			'host'	=> $config['MasterServer']['servername'],
    			'user'	=> $config['MasterServer']['username'],
    			'pass'	=> $config['MasterServer']['password'],
    			'name'	=> $config['Database']['dbname']
    		);
    		
			$this->DB = Core::GetDBInstance($conf, true, $config['Database']['dbtype']);
			$this->Crypto = Core::GetInstance('Crypto', CF_CRYPTOKEY);
			
			// user groups 
			$groups = $this->DB->GetAll("SELECT * FROM {$this->TablePrefix}usergroup");
			foreach($groups as $group)
			{
				$this->Groups[$group['title']] = $group['usergroupid']; 
			}
    	}
		
		
		/**
		 * Check if user already exists sin forum database
		 * (checking by username or email)
		 * 
		 * @param string username
		 * @param string email
		 * 
		 * @return boolean user exists
		 */
		function UserExists($username, $email)
		{
	    	return $this->DB->GetOne("
				SELECT COUNT(*) FROM {$this->TablePrefix}user WHERE username=? OR email=?
			", array(
				$username, $email
			));
		}
		

	    /**
	     * Register user on forum
	     * 
	     * @param string username
	     * @param string password
	     * @param string email
	     * 
	     * @return integer userid in vBulletin database
	     */
	    function Register($username, $password, $email, $group = '') 
	    {
	    	
	    	if (!$username || !$email)
	    		return false;
	
	    	if ($this->UserExists($username, $email)) 
	    		return false;
	    	
	    	$this->DB->Execute("
				INSERT INTO {$this->TablePrefix}user SET
				`usergroupid`	= ?,
				`username`		= ?,
				`email`			= ?,
				`styleid`		= ?,
				`showvbcode`	= 1,
				`showbirthday`	= 0,
				`usertitle`		= ?,
				`joindate`		= UNIX_TIMESTAMP(NOW()),
				`reputationlevelid` = 5,
				`timezoneoffset`= 0,
				`startofweek`	= -1,
				`ipaddress`		= ?,
				`languageid`	= ?
			", array(
				(int)$this->Groups[$group],
				$username,
				$email,
				$this->DB->GetOne("SELECT styleid FROM {$this->TablePrefix}style ORDER BY styleid ASC"),
				$this->DB->GetOne("SELECT title FROM {$this->TablePrefix}usertitle ORDER BY minposts ASC"),
				$_SERVER['REMOTE_ADDR'],
				$this->DB->GetOne("SELECT languageid FROM {$this->TablePrefix}language ORDER BY languageid ASC")
			));
			
			$userid = $this->DB->Insert_ID();
			
	    	$this->ResetPassword($userid, $password);
	    	
			$this->DB->Execute("INSERT INTO {$this->TablePrefix}userfield SET userid=?", $userid);
			$this->DB->Execute("INSERT INTO {$this->TablePrefix}usertextfield SET userid=?", $userid);
			
			return $userid;
	    }


	    /**
	     * Update password (reset) for forum user
	     * 
	     * @param integer userid
	     * @param string password
	     * @param string email
	     * 
	     * @return boolean password reseted
	     */
	    function ResetPassword($userid, $password)
	    {
	    	if (!$userid) return false;
	
	    	$salt = $this->Crypto->Sault(3);
	    	$password = $this->Crypto->Hash($password, 'MD5') . $salt;
	    	$password = $this->Crypto->Hash($password, 'MD5');
	    	
	    	$this->DB->Execute("UPDATE {$this->TablePrefix}user SET 
				password=?, salt=?, `passworddate` = NOW() 
				WHERE userid=?
			", array(
				$password, $salt, $userid
			));
			
			return true;
	    }
	    
	    
	    /**
	     * Set new email address for user
	     * 
	     * @param integer userid
	     * @param string email
	     */
	    function SetEmail($userid, $email)
	    {
			if (!$userid || !$email) return false;
			
			$this->DB->Execute("UPDATE {$this->TablePrefix}user SET email=? WHERE userid=?", array($email, $userid));
			
			return true;
	    }
	    
	    
	    /**
	     * Change status of user
	     * if status = 0 then - ban user
	     * 
	     * @param integer userid
	     * @param boolean status
	     * 
	     * @return boolean status changed
	     */
	    function SetStatus($userid, $status = false, $group = 'Banned Users')
	    {
	    	if (!$userid) return false;
	    	
	    	$this->DB->Execute("
				UPDATE {$this->TablePrefix}user SET `usergroupid` = ? WHERE userid=?
			", array(
				($status ? (int)$this->Groups[$group] : (int)$this->Groups['Banned Users']), $userid
			));
				
	    	return true;
	    }
	    
	    
	    /**
	     * Logout from forum
	     * 
	     * @param integer userid
	     */
	    function Logout($userid = 0)
	    {
	    	$this->DB->Execute("
				DELETE FROM {$this->TablePrefix}session WHERE sessionhash=? OR userid=?
			", array(
				session_id(), $userid
			));
	    }
	    
	    
	    /**
	     * Get ID Hash for session table on vBulletin forum
	     */
	    function GetIDHash()
	    {
			if (isset($_SERVER['HTTP_CLIENT_IP']))
			{
				$alt_ip = $_SERVER['HTTP_CLIENT_IP'];
			}
			else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches))
			{
				// make sure we dont pick up an internal IP defined by RFC1918
				foreach ($matches[0] AS $ip)
				{
					if (!preg_match("#^(10|172\.16|192\.168)\.#", $ip))
					{
						$alt_ip = $ip;
						break;
					}
				}
			}
			else if (isset($_SERVER['HTTP_FROM']))
				$alt_ip = $_SERVER['HTTP_FROM'];
			else
				$alt_ip = $_SERVER['REMOTE_ADDR'];
	    	
	    	return $this->Crypto->Hash($_SERVER['HTTP_USER_AGENT'] . $alt_ip, 'MD5');
	    }

 
		/**
		 * Login to forum
		 * 
		 * @param integer userid
		 * @param boolean permanent login
		 */
	    function Login($userid, $permanent = 0)
	    {
	    	if (!$userid) return false;
	    	$sessionhash = session_id();
	    	
	    	$this->DB->Execute("
				INSERT INTO {$this->TablePrefix}session SET
				`sessionhash` = ?,
				`userid` = ?,
				`host` = ?,
				`idhash` = ?,
				`lastactivity` = UNIX_TIMESTAMP(NOW()),
				`loggedin` = 2,
				`location` = '/forum/',
				`useragent` = ?
			", array(
				$sessionhash,
				$userid,
				$_SERVER['REMOTE_ADDR'],
				$this->GetIDHash(),
				$_SERVER['HTTP_USER_AGENT']
			));
			
			$this->SetCookies('sessionhash', $sessionhash, $permanent);
	    }
	    
	    
	    /**
	     * Set cookies with prefix
	     * 
	     * @param string variable name
	     * @param string variable value
	     * @param string permanent setup
	     */
	    function SetCookies($name, $value = '', $permanent = true)
	    {
			$expire = ($permanent) ? time() + 60 * 60 * 24 * 365 : 0;
			$name = $this->CookiePrefix . $name;
			
			if (!headers_sent())
			 setcookie($name, $value, $expire);
	    }

    }

?>