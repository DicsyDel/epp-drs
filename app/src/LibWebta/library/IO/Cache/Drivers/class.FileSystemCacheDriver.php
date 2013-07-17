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
     * @subpackage Cache
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
	 * Load ShellFactory
	 */
	Core::Load("System/Independent/Shell/ShellFactory");
	
	/**
	 * Load IOTool
	 */
	Core::Load("IO/Basic/IOTool");
	
	/**
     * @name FileSystemCacheDriver
     * @category   LibWebta
     * @package    IO
     * @subpackage Cache
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class FileSystemCacheDriver extends Core implements ICacheDriver
	{
		

		/**
		* Cache name
		* @var string
		* @access public
		*/
		public $CacheName;
		
		/**
		 * Directory to store cache
		 * @var string
		 * @access public
		 */
		public $CacheDir;
		
		/**
		 * Either to use subfloders
		 * @var bool
		 */
		public $UseSubfolders;
		
		/**
		 * How deep subfloders should follow
		 * @var integer
		 */
		public $SubfoldersDepth;
		
		/**
		 * FileSystemCacheDriver Constructor
		 * @ignore
		 */
		function __construct()
		{
			$this->CacheDir = ini_get("session.save_path");
            if (!$this->CacheDir)
	            $this->CacheDir = "/tmp";
			
			$this->UseSubfolders = true;
			$this->SubfoldersDepth = 2;
		}
		
		/**
		 * IsAvaliable
		 *
		 * @return true
		 */
		public function IsAvaliable()
		{
			return true;
		}
		
		/**
		 * Is object expired
		 *
		 * @param string $key
		 * @return bool
		 */
		public function IsExpired($key)
		{
			$result = $this->ReadCacheFile($key);
						
			if (is_int($result["system"]["expire_in"]))
			{
				return (($result["system"]["expire_in"]+$result["system"]["mtime"]) < time());
			}
			else
				return false;
		}
		
		/**
		 * Clean cache
		 *
		 * @return bool
		 */
		public function Clean()
		{
			$path = "{$this->CacheDir}".DIRECTORY_SEPARATOR."{$this->CacheName}";

			
			// Erase folder
			try 
			{
				
				$Shell = ShellFactory::GetShellInstance();
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
					$retval = $Shell->ExecuteRaw("rmdir /s /q {$path}");
				else
				{
					$retval = $Shell->ExecuteRaw("/bin/rm -rf {$path}");
				}
				
				// Failed to exec. Try native way.
				if (!$retval)
				{
					$retval = IOTool::UnlinkRecursive($path);
				}
					
	 			$retval &= !file_exists($path);
			}
			catch(Exception $e)
			{
				self::RaiseWarning("Failed to clear cache. ".$e->__toString());
			}
			
			// Create a new one
			$retval &= @mkdir($path, 0777, true);
			
 			return $retval;
		}
		
		/**
		 * Get object by key
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function Get($key)
		{
			$result = $this->ReadCacheFile($key);
				
			return $result["data"];
		}


		/**
		 * Get cache statistics
		 *
		 * @return array Stats
		 */
		public function GetStats()
		{
			self::RaiseWarning("GetStats() not implemented in this driver yet");
		}
		
		/**
		 * Add object to cache
		 *
		 * @param string $key
		 * @param mixed $object
		 * @param bool $do_overwrite
		 * @param integer $expire_in
		 * @return bool
		 */
		public function Set($key, $object, $do_overwrite = true, $expire_in = null)
		{
			$CacheID = $this->GetCacheID($key);
	
			if (!$do_overwrite && $this->Exists($key))
				return false;
							
			// Build long subfolder or place in root folder
			if ($this->UseSubfolders)
				$path = $this->BuildSubFolders($CacheID);
			else
			{
				$path = "{$this->CacheDir}/{$this->CacheName}";			
				@mkdir($path, 0777, true);
			}
			
			$data = array(
							"system" => array("expire_in" => $expire_in),
							"data" => $object
						);
			
			// Save content and metadata
			$retval = @file_put_contents("{$path}".DIRECTORY_SEPARATOR."{$CacheID}.cache", serialize($data));
			
			if (!$retval)
				self::RaiseWarning(_("Cannot save cache file. Make sure that web user has access to modify") . $this->CacheDir);
				
			return $retval; 
		}
		
		/**
		 * Return true if object exists in cache
		 *
		 * @param string $key
		 * @return bool
		 */
		public function Exists($key)
		{
			$CacheID = $this->GetCacheID($key);
			if ($this->UseSubfolders)
			{
				$path = "{$this->CacheDir}".DIRECTORY_SEPARATOR."{$this->CacheName}";
			
				for ($i = 1; $i <= $this->SubfoldersDepth; $i++)		
					$path .= DIRECTORY_SEPARATOR.substr($CacheID, $i*2-2, 2);
				
			}
			else 
				$path = "{$this->CacheDir}/{$this->CacheName}";
			
			return (@file_exists("{$path}".DIRECTORY_SEPARATOR."{$CacheID}.cache") && 
					@file_exists("{$this->CacheDir}".DIRECTORY_SEPARATOR."{$this->CacheName}".DIRECTORY_SEPARATOR."{$CacheID}.cache")
				   );
		}
		
		/**
		 * Set Driver option
		 *
		 * @param string $key
		 * @param mixed $value
		 */
		public function SetOption($key, $value)
		{
			switch ($key)
			{
				case "sub_folders_depth";
					$this->SubfoldersDepth = $value;
				break;
				case "cache_path";
					$this->CacheDir = $value;
				break;
				case "use_sub_folders";
					$this->UseSubfolders = $value;
				break;
			}
		}
		
		/**
		 * Build subfolders
		 *
		 * @param string $CacheID
		 * @access private
		 * @return bool
		 */
		private function BuildSubFolders($CacheID)
		{
			$path = "{$this->CacheDir}".DIRECTORY_SEPARATOR."{$this->CacheName}";
			
			for ($i = 1; $i <= $this->SubfoldersDepth; $i++)		
				$path .= DIRECTORY_SEPARATOR.substr($CacheID, $i*2-2, 2);

			if (!file_exists("{$path}"))
			{
				@mkdir("{$path}", 0777, true);
				@chmod("{$path}", 0777); // Sometimes chmodding inside mkdir wont work. Go figure.
				
				$retval = (file_exists("{$path}") && is_writable("{$path}"));
				
			}
			else
				$retval = true;
			
			if (!$retval)
				$path = "{$this->CacheDir}".DIRECTORY_SEPARATOR."{$this->CacheName}";
			
			return $path;
		}
		
		/**
		 * Returns CacheID
		 *
		 * @param string $key
		 * @access private
		 * @return string
		 */
		private function GetCacheID($key)
		{
			return str_pad(dechex(crc32($key)), 8, '0', STR_PAD_LEFT);	
		}
		
		/**
		 * Read cached file
		 *
		 * @param string $key
		 * @access private
		 * @return string
		 */
		private function ReadCacheFile($key)
		{
			$CacheID = $this->GetCacheID($key);
			if ($this->UseSubfolders)
			{
				$path = "{$this->CacheDir}".DIRECTORY_SEPARATOR."{$this->CacheName}";
			
				for ($i = 1; $i <= $this->SubfoldersDepth; $i++)		
					$path .= DIRECTORY_SEPARATOR.substr($CacheID, $i*2-2, 2);
				
			}
			else 
				$path = "{$this->CacheDir}".DIRECTORY_SEPARATOR."{$this->CacheName}";
			
			clearstatcache();
			
			if (file_exists("{$path}/{$CacheID}.cache"))
			{
				$result = unserialize(@file_get_contents("{$path}".DIRECTORY_SEPARATOR."{$CacheID}.cache"));
				$stat = @stat("{$path}/{$CacheID}.cache");
				
				// read file atime
				$result["system"]["mtime"] = $stat["mtime"];
			}
			elseif (file_exists("{$this->CacheDir}".DIRECTORY_SEPARATOR."{$this->CacheName}".DIRECTORY_SEPARATOR."{$CacheID}.cache"))
			{
				$result = unserialize(@file_get_contents("{$this->CacheDir}".DIRECTORY_SEPARATOR."{$this->CacheName}".DIRECTORY_SEPARATOR."{$CacheID}.cache"));
				
				// read file atime
				$stat = @stat("{$this->CacheDir}/{$this->CacheName}".DIRECTORY_SEPARATOR."{$CacheID}.cache");				
				$result["system"]["mtime"] = $stat["mtime"];
			}
			else
				$result = false;
				
			return $result;	
		}
	}
	
?>