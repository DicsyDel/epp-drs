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
     * @package    NET
     * @subpackage FTP
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
     * @name       FTP
     * @category   LibWebta
     * @package    NET
     * @subpackage FTP
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class FTP extends Core
	{
		/**
		 * FTP host
		 *
		 * @var string
		 */
		private $Server;
		
		/**
		 * FTP Login
		 *
		 * @var string
		 */
		private $Login;
		
		/**
		 * FTP Password
		 *
		 * @var string
		 */
		private $Password;
		
		/**
		 * Port
		 *
		 * @var integer
		 */
		private $Port;
		
		/**
		 * PASV mode
		 *
		 * @var bool
		 */
		private $IsPasv;
		
		/**
		 * Is Logon
		 *
		 * @var bool
		 */
		private $IsLogin;
		
		/**
		 * Status
		 *
		 * @var bool
		 */
		private $Status;
		
		/**
		 * FTP connection
		 *
		 * @var resourse
		 */
		private $Conn;
		
		/**
		 * Constructor
		 *
		 * @param string $Server
		 * @param string $Login
		 * @param string $Password
		 * @param integer $Port
		 * @param bool $IsPasv
		 * @return bool
		 */
		function __construct ($Server, $Login, $Password, $Port = 21, $IsPasv = true)
		{
					
			$this->Server = $Server;
			$this->Login = $Login;
			$this->Password = $Password;
			$this->Port = $Port;
			$this->IsPasv = $IsPasv;
			$this->Status=true;
			$this->Connect();
			
			if ($this->Conn)
			{
				$this->IsLogin = $this->Login();
				if (!$this->IsLogin)
					return false;
			}
			else 
			{
				$this->Status=false;
				return false;
			}
			
			$this->SetPasv();
			
			return true;
		} 
		
		/**
		 * Is loget in
		 *
		 * @return bool
		 */
		public function IsLogon()
		{
			return $this->IsLogin;	
		}
		
		/**
		 * Status
		 *
		 * @return bool
		 */
		public function GetStatus()
		{
			return $this->Status;	
		}
		
		/**
		 * Sets PASV mode
		 *
		 */
		private function SetPasv ()
		{
			if ($this->IsPasv) @ftp_pasv($this->Conn, true);
		}
		
		/**
		 * Login to server
		 *
		 * @return bool
		 */
		private function Login ()
		{
			return @ftp_login($this->Conn, $this->Login, $this->Password);
		}
		
		/**
		 * Connect to server
		 *
		 */
		private function Connect()
		{
			$this->Conn = @ftp_connect($this->Server, $this->Port);
		}
		
		/**
		 * Dissconnect from server
		 *
		 */
		private function Disconnect()
		{
			@ftp_close($this->Conn);
		}
		
		/**
		 * Parse server directory listing
		 *
		 * @param string $list
		 * @return array
		 */
		private function ParseListing($list) {

			if(preg_match_all("(([-d]*)?([stSTrwx-]{9})([\s]*)([0-9]*)([\s]*)([^\s]*)([\s]*)([^\s]*)([\s]*)([0-9]*)([\s]*)(\w{3})\s+(\d+)\s+([\:\d]+)\s+(.+)$)", $list, $ret)) {
				
				$v=array(
					"type"	=> ($ret[1][0]=="-"?"f":$ret[1][0]),
					"perms"	=> $ret[2][0],
					"inode"	=> $ret[4][0],
					"owner"	=> $ret[6][0],
					"group"	=> $ret[8][0],
					"size"	=> $ret[10][0],
					"date"	=> $ret[12][0]." ".$ret[13][0]." ".$ret[14][0],
					"name"	=> $ret[15][0]
				);
			} 
			return $v;
		}
		
		/**
		 * Create folder
		 *
		 * @param string $foldername
		 * @param string $cd
		 * @return bool
		 */
		public function CreateFolder ($foldername, $cd)
		{
			if ($cd!='/')
				@ftp_chdir($this->Conn, $cd);
			
			return @ftp_mkdir($this->Conn, $foldername);
		}
		
		/**
		 * Rename folder or file
		 *
		 * @param string $cd
		 * @param string $oldname
		 * @param string $newname
		 * @return bool
		 */
		public function Rename($cd, $oldname, $newname)
		{
			if ($cd!='/')
				@ftp_chdir($this->Conn, $cd);
						
			return @ftp_rename($this->Conn, $oldname, $newname);
		}
		
		/**
		 * CHMOD file or folder
		 *
		 * @param string $file
		 * @param string $cd
		 * @param integer $perms
		 * @return bool
		 */
		public function Chmod($file, $cd, $perms)
		{
			ftp_chdir($this->Conn, $cd);
		
			$command = "CHMOD $perms $file";
			return @ftp_site($this->Conn, $command);	
		}
		
		/**
		 * Send folder
		 *
		 * @param string $src_dir
		 * @param string $dst_dir
		 * @return bool
		 */
		public function SendFolder($src_dir, $dst_dir) 
		{
			$d = dir($src_dir);
			
			if (!$d)
				return false;
			
			while($file = $d->read()) 
			{
				if ($file != "." && $file != "..") 
				{
					if (is_dir($src_dir."/".$file)) 
					{
						if (!@ftp_chdir($this->Conn, $dst_dir."/".$file)) 
							@ftp_mkdir($this->Conn, $dst_dir."/".$file);
						
						$this->SendFolder($src_dir."/".$file, $dst_dir."/".$file);
					}
					else 
						@ftp_put($this->Conn, $dst_dir."/".$file, $src_dir."/".$file, FTP_BINARY);
				}
			}
			
			$d->close();
			
			return true;
		}
		
		/**
		 * Send File to server
		 *
		 * @param string $cd
		 * @param string $name
		 * @param string $tmp_name
		 * @param integer $type
		 * @return bool
		 */
		public function SendFile($cd, $name, $tmp_name, $type)
		{
			if ($cd!='/')
				@ftp_chdir($this->Conn, $cd);
			
			if ($type == 1)
				return @ftp_put($this->Conn, $name, $tmp_name, FTP_ASCII);
			else
				return @ftp_put($this->Conn, $name, $tmp_name, FTP_BINARY);
		}
		
		/**
		 * Get File from server
		 *
		 * @param string $cd
		 * @param string $filename
		 * @param integer $type
		 * @return string
		 */
		public function GetFile($cd, $filename, $type)
		{
			if ($cd!='/')
				@ftp_chdir($this->Conn, $cd);
			
			$handle = @tmpfile();
				
			if ($type == 1)		
				$get = @ftp_fget($this->Conn, $handle, $filename, FTP_ASCII);
			else 
				$get = @ftp_fget($this->Conn, $handle, $filename, FTP_BINARY);
				
			if (!$get)
				return false;
			
			@fseek($handle, 0);
			
			$retval = "";
			
			while(!feof($handle))
				$retval .= @fread($handle, 1024);
			
			@fclose($handle);
		
			return $retval;
		}
		
		/**
		 * Close connection to server
		 *
		 * @return string
		 */
		public function Close()
		{
			return $this->Disconnect();
		}
		
		/**
		 * Delete folder from server
		 *
		 * @param string $dst_dir
		 * @return bool
		 */
		public  function DeleteFolder($dst_dir)
		{
		  $rs = true;
		  $ar_files = @ftp_rawlist($this->Conn, $dst_dir);
		  if (is_array($ar_files)) { // makes sure there are files
		   foreach ($ar_files as $st_file) { // for each file
		     if (ereg("([-d][rwxst-]+).* ([0-9]) ([a-zA-Z0-9]+).* ([a-zA-Z0-9]+).* ([0-9]*) ([a-zA-Z]+[0-9: ]*[0-9]) ([0-9]{2}:[0-9]{2}) (.+)",$st_file,$regs)) {
		       if (substr($regs[1],0,1)=="d") { // check if it is a directory
		         $this->DeleteFolder($dst_dir."/".$regs[8]); // if so, use recursion 
		       } else { 
		         $dl = @ftp_delete($this->Conn, $dst_dir."/".$regs[8]); // if not, delete the file
		       } 
		     } 
		   }
		  }
		  $dl = @ftp_rmdir($this->Conn, $dst_dir); // delete empty directories
		  if (!$dl) $rs = false;
		  
		  return $rs;
		}

		/**
		 * Parse string chmod perms to string
		 *
		 * @param string $perms
		 * @return integer
		 */
		private function GetNumPerms ($perms)
		{
		
			$o[1] = array(
			'-' => array(0,0),
			'r' => array(4,0)
			);
			
			$o[2] = array(
			'-' => array(0,0),
			'w' => array(2,0)
			);
			
			$o[3] = array(
			'-' => array(0,0),
			'x' => array(1,0),
			's'	=> array(1,4),
			'S' => array(0,4),
			);
			
			$g[1] = array(
			'-' => array(0,0),
			'r' => array(4,0)
			);
			
			$g[2] = array(
			'-' => array(0,0),
			'w' => array(2,0)
			);
			
			$g[3] = array(
			'-' => array(0,0),
			'x' => array(1,0),
			's'	=> array(1,2),
			'S' => array(0,2),
			);
			
			$n[1] = array(
			'-' => array(0,0),
			'r' => array(4,0)
			);
			
			$n[2] = array(
			'-' => array(0,0),
			'w' => array(2,0)
			);
			
			$n[3] = array(
			'-' => array(0,0),
			'x' => array(1,0),
			't'	=> array(1,1),
			'T' => array(0,1),
			);
			
			$p1 = $o[1][$perms[0]][0]+$o[2][$perms[1]][0]+$o[3][$perms[2]][0];
			$root = $o[3][$perms[2]][1];
			
			$p2 = $g[1][$perms[3]][0]+$g[2][$perms[4]][0]+$g[3][$perms[5]][0];
			$root = $root +$g[3][$perms[5]][1];
			
			$p3 = $n[1][$perms[6]][0]+$n[2][$perms[7]][0]+$n[3][$perms[8]][0];
			$root = $root +$n[3][$perms[8]][1];
			
			return $root.$p1.$p2.$p3;
		}
		
	}
	
?>