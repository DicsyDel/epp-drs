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
     * @filesource
     */

    /**
     * @category   LibWebta
     * @package    IO
     * @subpackage Cache
     * @name IO_Cache_Test
     */
	class IO_Cache_Test extends UnitTestCase 
	{
        
		public $Cache;
		
		function __construct() 
        {
        	load("IO/Cache");
            $this->UnitTestCase('IO/Cache tests');
        }
        
        
        function DoTests()
        {
        	
        	$this->assertTrue(is_object($this->Cache), "Cache object created");		
			
			$test_data = "test text";
			
			$res = $this->Cache->Set("testkey", $test_data, true, null);
			
			$this->assertTrue($res, "Data written to cache");	
			
			
			//
			// Immediate read
			//
			$res = $this->Cache->Get("testkey");			
			
			$this->assertEqual($res, $test_data, "Cache read");
			
			
			//
			// Clean cache
			//
			$res = $this->Cache->Clean();
			$this->assertTrue($res && !($this->Cache->Get("testkey")), "Cache->Clean returned true and deleted item");
			sleep(5); //Need timeout for cleaning cache
			
			//
			// Success to read non-expired item
			//
			
			$test_data = "test text";
			
			
			$res = $this->Cache->Set("testkey", $test_data, true, null, 3);
			
			sleep(1);
			$res = $this->Cache->Get("testkey");
			
			$this->assertEqual($res, $test_data, "Item exists in cache");
			
			sleep(3);
			$res = $this->Cache->Get("testkey");
			$this->assertFalse($res, "Exired item does not exist in cache");
			
        }
        
        
        /**
         * MemCached Driver Tests
         */
        function testFileSystem() 
        {			
			$this->Cache = new Cache("FileSystem", "TestCache");	
			$this->DoTests();	
        }
        
        
        
        /**
         * MemCached Driver Tests
         */
        function testMemCached() 
        {
			if (class_exists("Memcache"))
			{
    			$this->Cache = new Cache("Memcache", "TestCache");	
    			$this->Cache->SetDriverOption("host", "192.168.1.254");
    			$this->DoTests();	
    			
    			
    			// Test failure when connecting to bogus host
    			$Cache = new Cache("Memcache", "TestCache");
    			$Cache->SetDriverOption("host", "bogus");
    			$res = $this->Cache->Get("testkey");
    			$this->assertFalse($res, "Cache->Get returned false when bogus connection info specified");
			}
			else 
			     print "Class 'Memcache' not found. Skipping tests for memcache adapter.\n";
        }
        
        /**
         * MemCached Poll Driver Tests
         */
        function testMemCachedPool() 
        {
			if (class_exists("Memcache"))
			{
    			$this->Cache = new Cache("MemcachePool", "TestCache");	
    			$this->Cache->SetDriverOption("server", array("host" => "192.168.1.254", "port" => 11211));
    			$this->Cache->SetDriverOption("server", array("host" => "192.168.1.253", "port" => 11211));
    			$this->DoTests();	
			}
        }
         
    }
?>