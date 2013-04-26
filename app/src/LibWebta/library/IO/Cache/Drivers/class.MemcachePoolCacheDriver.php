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
     * @name MemcachePoolCacheDriver
     * @category   LibWebta
     * @package    IO
     * @subpackage Cache
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @todo This driver not working now.
     * @ignore 
     */
	class MemcachePoolCacheDriver extends MemcacheCacheDriver
	{

		function __construct($args = null)
		{
			$this->IsConnected = false;
			
			$this->Options["servers"] = array();
		}
		
		/**
		 * Connect to memcached server
		 * @return void
		 */
		protected function Connect()
		{
			if (!is_object($this->MemCache))
				$this->MemCache = new Memcache;
		  
		    if (count($this->Options["servers"]) > 0)
		    {		
    		    foreach($this->Options["servers"] as $server)
    		    {
    		        $this->MemCache->addServer(
    		                                      $server["host"], 
    		                                      $server["port"], 
    		                                      $server["persist"], 
    		                                      $server["weight"], 
    		                                      $server["timeout"]
    		                                  );
    		    }
    		    
    		    $this->IsConnected = true;
		    }
		}
		
		
		/**
		 * Disconnect from memcached server
		 * @return void
		 */
		protected function Disconnect()
		{
			if (is_object($this->MemCache))
			 $this->MemCache->close();
		}
		
		/**
		 * Set option
		 *
		 * @param $key
		 * @param $value
		 */
		public function SetOption($key, $value)
		{
		    /*
		    
		    This driver support one option: "server"
		    Value must be an associative array
		    
		    host = memcache host
		    port = memchache port
		    [ persist = persistent connection or no
		    [ weight = server weight
		    [ timeout = connection timeout
		    
		    */
		    
			if ($key == "server" && $value["host"] && $value["port"])
			{
			    if (!$value["persist"])
			     $value["persist"] = 1;
			     
			    if (!$value["weight"])
			     $value["weight"] = 1;
			     
			    if (!$value["timeout"])
			     $value["timeout"] = 5;
			    
			    $this->Options["servers"][$value["host"]] = $value;
			}
		}
	}
?>