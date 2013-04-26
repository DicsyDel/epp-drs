<?php
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
     * @package    Data_DB
     * @subpackage MySQL
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 
	
	 /**
     * @name MySQLTool
     * @category   LibWebta
     * @package    Data_DB
     * @subpackage MySQL
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @todo Full code refactoring
     */
	class MySQLTool extends Core 
	{
		
		/**
		* Dababase object
		* @var $SDB
		* @access potected
		* @static
		*/
		protected static $DB;
		
		
		/**
		* List of supported MySQL privileges
		* @var $PrivsSuported
		* @static 
		* @access private
		*/
		private static $PrivsSuported;
		
		

		function __construct()
		{
			parent::__construct();
			$this->Shell = CP::GetShellInstance();
			
			// DSN
			$dsn = str_replace("{PASSWD}", CF_MYSQL_PASSWD, CF_MYSQL_DSN);
			
			// Connect to server as root
			self::$SDB = DB::connect($dsn);
			if (DB::isError(self::$SDB))     
				$this->RaiseError("Cannot connect to local MySQL server! ");
			self::$SDB->setFetchmode(DB_FETCHMODE_ASSOC);
			
			$this->PrivsSuported = array("SELECT",
				"INSERT",
				"INDEX", 
				"UPDATE",
				"DELETE",
				"CREATE",
				"DROP",
				"REFERENCES",
				"ALTER",
				"CREATE TEMPORARY TABLES",
				"LOCK TABLES"
			);
			
		}
		


		/**
		* Create database with username prefix
		* @access public
		* @param string $username System user name
		* @param string $dbname Database name without username part
		* @return string New database name or false in case of error
		*/

		public function CreateDatabase($username, $dbname)
		{
			$dbname = "{$username}_{$dbname}";
			if (strlen($dbname) > 64)
				$dbname = substr($dbname, 0, 64-1-strlen($username));
				
			$retval = self::$SDB->query("create database `{$dbname}`");
			
			if(self::$SDB->isError($retval)) 
			{
				$this->RaiseWarning($retval->getMessage());
				return false;
			}
			return $dbname;
		}
		
		
		/**
		* Delete database
		* @access public
		* @param string $username System user name
		* @param string $dbname Database name without username part
		* @return bool Success or failure
		*/

		public function DropDatabase($username, $dbname)
		{
			return $this->DropSystemDatabase("{$username}_{$dbname}");
		}
		
		
		/**
		* Delete database
		* @access public
		* @param string $dbname Database with username part
		* @return bool Success or failure
		*/

		public function DropSystemDatabase($dbname)
		{
			$retval = self::$SDB->query("drop database `{$dbname}`");
			
			// Return
			if(self::$SDB->isError($retval)) 
			{
				$this->RaiseWarning($retval->getMessage());
				return false;
			}
			
			return true;
		}
		
		
		/**
		* Set privileges for specific user on atabase
		* @access public
		* @param string $dbusername Database user name
		* @param string $dbname Database name with username part
		* @return bool Success or failure
		*/

		public function SetPrivileges($dbusername, $dbname, $privileges = "ALL", $host="localhost")
		{
			
			// Revoke
			$retval = self::$SDB->query("REVOKE ALL PRIVILEGES ON `$dbname` . * FROM '$dbusername'@'$host'");
			
			// Sanity
			foreach ($privileges as $priv)
			{
				if (!in_array($priv, $this->PrivsSuported))
					$this->RaiseError("Incorrect privilege: $priv");		
			}
			
			// Implode
			if (count($privileges) > 0)	
				$privileges = implode(",", $privileges);
			else	
				return true;
			// Grant
			$retval = self::$SDB->query("GRANT $privileges ON `$dbname`.* TO '$dbusername'@'$host' WITH GRANT OPTION");
			
			// Return
			if(self::$SDB->isError($retval)) 
			{
				$this->RaiseWarning($retval->getMessage());
				return false;
			}
			
			return true;
		}
		
		
		/**
		* Get privileges for specific user on database
		* @access public
		* @param string $dbusername Database user name
		* @param string $dbname Database name with username part
		* @return array Array of $this->PrivsSuported
		*/

		public function GetPrivileges($dbusername, $dbname, $privileges = "ALL", $host="localhost")
		{
			//TODO: implement GetPrivileges
		}
		
		
		
		/**
		* List all databases
		* @access public
		* @return array Single array of database names
		*/

		public function ListDatabases()
		{	
			$retval = self::$SDB->getCol("show databases");
			return($retval);
		}
		
		
		/**
		* Create user
		* @access public
		* @param string $username Full DB user name
		* @param string $password Password
		* @param string $host Hostname
		* @param string $maxqueries Max quesries per hour (0 is unlimited)
		* @param string $maxconnections Max connections per hour (0 is unlimited)
		* @param string $maxupdates Max updates per hour (0 is unlimited)
		* @return bool Success or failure
		*/

		public function CreateSystemUser($dbusername, $password, $host = "localhost", $maxqueries=0, $maxconnections=0, $maxupdates=0)
		{	
			$ver = $this->GetSQLServerVersion();
			
			// I think these are available since MySQL4
			if ($ver[0] >= 4)
				$limits = "WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0";
			
			$retval = self::$SDB->query("GRANT USAGE 
			ON * . * 
			TO '$dbusername'@'$host' 
			IDENTIFIED BY '$password' 
			$limits");
			
			// Return
			if(self::$SDB->isError($retval)) 
			{
				$this->RaiseWarning($retval->getMessage());
				return false;
			}
			return true;
		}
		
		
		/**
		* Create user
		* @access public
		* @param string $username System user name
		* @param string $dbusername DB user name
		* @param string $password Password
		* @param string $host Hostname
		* @param string $maxqueries Max quesries per hour (0 is unlimited)
		* @param string $maxconnections Max connections per hour (0 is unlimited)
		* @param string $maxupdates Max updates per hour (0 is unlimited)
		* @return bool Success or failure
		*/

		public function CreateUser($username, $dbusername, $password, $host = "localhost", $maxqueries=0, $maxconnections=0, $maxupdates=0)
		{	
			$dbusername = "{$username}_{$dbusername}";
			if (strlen("{$dbusername}") > 16)
				$dbusername = substr($dbusername, 0, 16-1-strlen($username));

			return $this->CreateSystemUser($dbusername, $password, $host = "localhost", $maxqueries=0, $maxconnections=0, $maxupdates=0);
		}
		
		
		/**
		* Delete user
		* @access public
		* @param string $dbusername Full user name with username prefix
		* @param string $host Hostname (default is 'localhost')
		* @param bool $flush Flush privileges or not
		* @return bool Success or failure
		*/

		public function DropSystemUser($dbusername, $host="localhost", $flush=true)
		{
			
			$retval = self::$SDB->query("DELETE FROM `user` WHERE User = '$dbusername' AND Host = '$host'");
			self::$SDB->query("DELETE FROM `db` WHERE User = '$dbusername' AND Host = '$host'");
			self::$SDB->query("DELETE FROM `tables_priv` WHERE User = '$dbusername' AND Host = '$host'");
			self::$SDB->query("DELETE FROM `columns_priv` WHERE User = '$dbusername' AND Host = '$host'");
			
			if ($flush)
				self::$SDB->query("FLUSH PRIVILEGES");
			
			// Return
			if(self::$SDB->isError($retval)) 
			{
				$this->RaiseWarning($retval->getMessage());
				return false;
			}
			
			return true;
		}
		
		
		/**
		* Delete user
		* @access public
		* @param string $username System user name
		* @param string $dbusername Full user name with username prefix
		* @param string $host Hostname (default is 'localhost')
		* @param bool $flush Flush privileges or not
		* @return bool Success or failure
		*/

		public function DropUser($username, $dbusername, $host="localhost", $flush=true)
		{
			return $this->DropSystemUser("{$username}_{$dbusername}", $host="localhost", $flush=true);
		}
		
		
		/**
		* Get SQL server version
		* @access public
		* @return array Single array of 0 => Minor, 1 => Major, 3 => Release
		*/

		public function GetSQLServerVersion()
		{	
			$res = self::$SDB->getOne("select version()");
			$retval = explode(".", $res);
			return($retval);
		}
		
		
		/**
		* List all users
		* @access public
		* @return array Single array of server info
		*/

		public function ListUsers()
		{	
			$retval = self::$SDB->getListOf("users");
			return($retval);
		}
		
		
		
		
	}

?>