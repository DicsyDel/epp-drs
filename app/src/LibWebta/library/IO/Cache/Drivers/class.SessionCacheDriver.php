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
     * @name SessionCacheDriver
     * @category   LibWebta
     * @package    IO
     * @subpackage Cache
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class SessionCacheDriver extends Core implements ICacheDriver
	{
		

		/**
		* Cache name
		* @var string
		* @access public
		*/
		public $CacheName;
		
		/**
		 * Driver constructor
		 * @ignore 
		 */
		function __construct()
		{
			@session_start();
		}
		
		/**
		 * Return true if driver avaiable
		 *
		 * @return true
		 */
		public function IsAvaliable()
		{
			return true;
		}
		
		/**
		 * IsExpired
		 *
		 * @return false
		 */
		public function IsExpired($key)
		{
			return false;
		}
		
		/**
		 * Clean cache
		 *
		 */
		public function Clean()
		{
			session_destroy();
		}
		
		/**
		 * Retur element from cache
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function Get($key)
		{	
			return $_SESSION[$this->CacheName][$key];
		}
		
		
		/**
		 * Get cache statistics
		 *
		 * @param string $key Key
		 * @return array Stats
		 */
		public function GetStats()
		{
			self::RaiseWarning("GetStats() not implemented in this driver yet");
		}
		
		/**
		 * Add element to cache
		 *
		 * @param string $key
		 * @param mixed $object
		 * @param bool $do_overwrite
		 * @param integer $expire_in
		 */
		public function Set($key, $object, $do_overwrite = true, $expire_in = null)
		{
			if (!($this->Exists($key) && !$do_overwrite))
				$_SESSION[$this->CacheName][$key] = $object;
		}
		
		/**
		 * Return true if object exists in cache
		 *
		 * @param string $key
		 * @return bool
		 */
		public function Exists($key)
		{
			return is_null($_SESSION[$this->CacheName][$key]);
		}
		
		/**
		 * Set adapter option
		 *
		 * @param string $key
		 * @param mixed $value
		 * @return true
		 */
		public function SetOption($key, $value)
		{
			return true;
		}
		
	}
	
?>