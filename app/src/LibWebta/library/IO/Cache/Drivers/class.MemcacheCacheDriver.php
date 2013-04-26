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
     * @name MemcacheCacheDriver
     * @category   LibWebta
     * @package    IO
     * @subpackage Cache
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class MemcacheCacheDriver extends Core implements ICacheDriver
	{
		/**
		* Options
		* @var array
		* @access public
		*/
		public $Options;
		
		/**
		* Cache name
		* @var string
		* @access public
		*/
		public $CacheName;
		
		/**
		 * Memcache object
		 *
		 * @var object
		 * @access protected
		 */
		protected $MemCache;
		
		
		/**
		 * Connection status
		 * @var bool
		 */
		public $IsConnected;
		
		/**
		 * Driver constructor
		 * @ignore 
		 */
		function __construct()
		{
			$this->IsConnected = false;
			
			$this->Options["host"] = "localhost";
			$this->Options["port"] = 11211;
			$this->Options["timeout"] = 3000;
			
		}
		
		/**
		 * Destructor
		 * @ignore
		 */
		function __destruct()
		{
			$this->Disconnect();
		}
		
		
		/**
		 * Connect to memcached server
		 * @return bool
		 * @access protected
		 */
		protected function Connect()
		{
			if (!is_object($this->MemCache))
				$this->MemCache = new Memcache;
				
			try
			{
				$this->IsConnected = @$this->MemCache->connect($this->Options["host"], $this->Options["port"]);
				return $this->IsConnected;
			}
			catch (Exception $e)
			{
				Core::RaiseWarning("Cannot connect to memcached server on 
				".$this->Options["host"] .":". $this->Options["port"]);
				
				return false;
			}
			
			// Would need it anyway
			if (!$retval)
				Core::RaiseWarning("Cannot connect to memcached server on 
				".$this->Options["host"] .":". $this->Options["port"]);
			
			return false;
		}
		
		
		/**
		 * Disconnect from memcached server
		 * @return void
		 * @access protected
		 */
		protected function Disconnect()
		{
			if ($this->IsConnected)
				@$this->MemCache->close();
		}
		
		
		/**
		 * Returns true if caching through this Provider is possible in current environment
		 * @return bool
		 */
		public function IsAvaliable()
		{
			try 
			{
				$ext = @get_loaded_extensions();
				$retval = in_array("memcache", array_map("strtolower", $ext));
				
				$retval &= $this->Connect();
				
				return $retval;
			} 
			catch (Exception $e)
			{
				return false;
			}
		}
		
		/**
		 * Return true if object in cache expired
		 *
		 * @param string $key
		 * @return bool
		 */
		public function IsExpired($key)
		{
			return !$this->Exists($key);
		}
		
		
		/**
		 * Save object in cache
		 *
		 * @param string $key Key which should be used to reteive object
		 * @param variant $object
		 * @param bool $do_overwrite Overwrite if object exists
		 * @return bool True on success or false on failure
		 */
		public function Set($key, $object, $do_overwrite = true, $expire_in = 0)
		{
			if (!$this->IsConnected)
				$this->Connect();
			
			if ($this->IsConnected)
			{
				if ($this->Exists($key) && $do_overwrite)
				{
					// Overwrite
					$retval = $this->MemCache->replace($this->CacheName.$key, $object, false, $expire_in);
				}
				else 
				{
					$retval = $this->MemCache->set($this->CacheName.$key, $object, false, $expire_in);
				}
				
				return $retval;
			}
			else 
				return false;
			
		}
		
		
		/**
		 * Get object from cache
		 *
		 * @param string $key Key
		 * @return object Cached object 
		 */
		public function Get($key)
		{
			if (!$this->IsConnected)
				$this->Connect();
			
			if ($this->IsConnected)
				return $this->MemCache->get($this->CacheName.$key);
			else 
				return false;
		}
		
		
		/**
		 * Get cache statistics
		 *
		 * @return array Stats
		 */
		public function GetStats()
		{
			if (!$this->IsConnected)
				$this->Connect();
			
			if ($this->IsConnected)
				return $this->MemCache->getStats();
			else 
				return false;
		}
		
		
		/**
		 * Check either object exists in cache
		 *
		 * @param string $key Key
		 * @return bool True if object sits in the cache 
		 */
		public function Exists($key)
		{
			if (!$this->IsConnected)
				$this->Connect();
			
			if ($this->IsConnected)
			{	
				$result = $this->MemCache->get($this->CacheName.$key);
				return ($result !== false);
			}
			else 
				return false;
		}
		
		
		/**
		 * Set option
		 *
		 * @param $key
		 * @param $value
		 */
		public function SetOption($key, $value)
		{
			$this->Options[$key] = $value;
			
			// Host or port changed. Have to reconnect.
			if ($key == "host" || $key == "port")
			{
				$this->Disconnect();
				$this->Connect();
			}
		}
		
		
		/**
		 * Set option
		 *
		 * @param $key
		 * @param $value
		 */
		public function Clean()
		{
			if (!$this->IsConnected)
				$this->Connect();
			
			if ($this->IsConnected)	
				return $this->MemCache->flush();
			else 
				return false;
		}
	}
?>