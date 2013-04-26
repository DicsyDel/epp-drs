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
     * @package    System_Unix
     * @subpackage Stats
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("/System/Unix/Stats/SystemStats");
	
	/**
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage Stats
     * @name System_Unix_Stats_Test
	 *
	 */
	class System_Unix_Stats_Test extends UnitTestCase 
	{
        function System_Unix_Stats_Test() 
        {
            $this->UnitTestCase('System/Unix/Stats Test');
        }
        
        function testSystemStats() 
        {
			
			$SystemStats = new SystemStats();
			
			//
			// Get uptime
			//
			$retval = $SystemStats->GetUptime();
			$this->assertTrue( is_double($retval), "System uptime is double number");
			
			//
			// Get linux ver
			//
			$retval = $SystemStats->GetLinuxVersion();
			$this->assertTrue(is_string($retval), "System version is string");
			
			//
			// Get linux name
			//
			$retval = $SystemStats->GetLinuxName();
			$this->assertTrue(is_string($retval), "System version is string");
			
			
        }
    }


?>