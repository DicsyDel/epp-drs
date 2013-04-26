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
     * @name ICacheDriver
     * @category   LibWebta
     * @package    IO
     * @subpackage Cache
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html> 
     */
	interface ICacheDriver
	{
		/**
		 * Save object in cache
		 *
		 * @param string $key Key which should be used to reteive object
		 * @param variant $object
		 * @param bool $do_overwrite Overwrite if object exists
		 * @return bool True on success or false on failure
		 */
		public function Set($key, $object, $do_overwrite = true, $expire_in = null);
		
		/**
		 * Get object from cache
		 *
		 * @param string $key Key
		 * @return object Cached object 
		 */
		public function Get($key);
		
		/**
		 * Get cache statistics
		 * @return array Stats 
		 */
		public function GetStats();
		 
		/**
		 * Determine either object expired
		 *
		 * @param string $key Key
		 * @return bool true if an object expired or does not exist
		 */
		public function IsExpired($key);
		
		
		/**
		 * Check either object exists in cache
		 *
		 * @param string $key Key
		 * @return bool True if object sits in the cache 
		 */
		public function Exists($key);
		
		
		/**
		 * Clean cache
		 *
		 */
		public function Clean();
		
		
		/**
		 * Set option
		 *
		 * @param $key
		 * @param $value
		 */
		public function SetOption($key, $value);
		
		
		/**
		 * Returns true if caching through this Provider is possible in current environment
		 * @return bool
		 */
		public function IsAvaliable();
		
	}
	
?>
