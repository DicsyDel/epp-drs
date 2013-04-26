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
     * @package    Core
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */ 
	
	/**
	 * @category   LibWebta
     * @package    Core
     * @name Core_Test
	 *
	 */
	class Core_Test extends UnitTestCase 
	{
		
        function __construct() 
        {
            $this->UnitTestCase('Core test');
        }
        
        function testCore_Test_Core() 
        {
			
			//
			// Core load() function
			//
			
			// Load single class
			Core::Load("Core");
			$this->assertTrue(class_exists("Core"), "Core class is loaded");
			
			// Load single class
			Core::Load("NET/API/WHM");
			$this->assertTrue(class_exists("WHM") && class_exists("CPanel"), "WHM and CPanel classes loaded");
			
			$memory_start = @memory_get_usage();
			
			//Check GetInstance			
			$class = Core::GetInstance("WHM", array("hostname" => "test", "login" => "login"));
			$this->assertTrue(($class instanceOf WHM && $class->Host == "test"), "WHM instance created");
			
						
			for($i = 0; $i < 5000; $i++)
			{
				$class = Core::GetInstance("WHM", array("hostname" => "test", "login" => "login"));
			}
			
			$memory_end = @memory_get_usage();
			
			$change = abs(round(($memory_start-$memory_end)/$memory_start*100, 2));
			$this->assertTrue(($change < 50), "No memory leaks detected. Memory before test {$memory_start}, after test {$memory_end}");
        }
        
        /**
         * @todo Test CoreException here
         */
        function testCore_Test_CoreException() 
        {
        	
        }
        
    }


?>