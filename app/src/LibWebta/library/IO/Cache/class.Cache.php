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
	 * Load CacheDriver
	 */
	Core::Load("interface.CacheDriver.php", dirname(__FILE__)."/Drivers");
	
	/**
     * @name Cache
     * @category   LibWebta
     * @package    IO
     * @subpackage Cache
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class Cache extends Core
	{
		/**
		 * Default cache name
		 *
		 */
		const DEFAULT_CACHE_NAME = 'Cache';
		
		/**
		 * Default expiration time, in seconds
		 *
		 */
		const DEFAULT_EXPIRE_IN = 600;
		
		/**
		 * Driver object
		 *
		 * @var string
		 */
		public $Driver = null;
		
		/**
		 * Cache constructor
		 *
		 * @param string $cachename Cache name
		 * @param string $driver Driver name
		 * @return void
		 */
		function __construct($driver, $cachename = null)
		{
			$this->SetDriver($driver);
			$this->Driver->ExpireIn = self::DEFAULT_EXPIRE_IN;
			
			$cachename = ($cachename) ? $cachename : self::DEFAULT_CACHE_NAME;
			$this->SetCacheName($cachename);
		}

		
		/**
		 * Sets Driver to use
		 * @param string $Driver
		 * @throws CoreException
		 * @todo Use Reflection API instead of eval function
		 */
		public function SetDriver($driver)
		{
			$driverspath = dirname(__FILE__)."/Drivers";
			
			try
			{
				$driver_path = "{$driverspath}/class.{$driver}CacheDriver.php";
				if (file_exists($driver_path))
				{
					require_once($driver_path);
					eval("\$this->Driver = &new {$driver}CacheDriver();");
				}
				else
					throw new CoreException(sprintf(_("No such driver '%s' implemented."), $driver));
				
			} catch (Exception $e)
			{
				throw new CoreException(sprintf(_("Failed to load cache driver '%s'. %s"), $driver, $e->__toString()));
			}
		}
		
		/**
		 * Set Cache name
		 *
		 * @param string $string
		 */
		public function SetCacheName ($cachename)
		{
			$this->Driver->CacheName = $cachename;
		}

		
		/**
		 * Calls SetsOption on assigned Driver
		 *
		 * @param unknown_type $key
		 * @param unknown_type $value
		 * @return bool
		 */
		public function SetDriverOption($key, $value)
		{
	 		return $this->Driver->SetOption($key, $value);
		}

		
		/**
		 * Save object in cache.
		 *
		 * @param string $key Key which should be used to reteive object
		 * @param variant $object
		 * @param bool $force_overwrite Overwrite if object exists
		 * @param int $expire_in Expiration time in seconds. 
		 * @return bool True on success or false on failure
		 */
		public function Set($key, $object, $force_overwrite = true, $cachename = null, $expire_in = null)
		{
			if (!is_null($cachename))
           		$this->SetCacheName($cachename);
        	        	
           	// If expired, Save a new value, either keep an old one
			return $this->Driver->Set($key, $object, $force_overwrite, $expire_in);
		}
		
		
		/**
		 * Get object from cache
		 *
		 * @param string $key Key
		 * @return object Cached object 
		 */
		public function Get($key, $cachename = null)
		{
			if (!is_null($cachename))
           		$this->SetCacheName($cachename);
        	
           	if (!$this->Driver->IsExpired($key))
        		return $this->Driver->Get($key);
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
			return $this->Driver->GetStats();
		}
		
		
		/**
		 * Check either object exists in cache
		 *
		 * @param string $key Key
		 * @return bool True if object sits in the cache 
		 */
		public function Exists($key)
		{
			return $this->Driver->Exists($key);
		}
		
		/**
		 * Check Driver avaiability
		 *
		 * @return bool True if object sits in the cache 
		 */
		public function IsAvaliable()
		{
			return $this->Driver->IsAvaliable();
		}
		
		/**
		 * Clean cache
		 * @return bool
		 */
		public function Clean()
		{
			return $this->Driver->Clean();
		}
		
	}
	
?>
