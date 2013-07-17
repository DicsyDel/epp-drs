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
     * @subpackage Shell
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */    

	Core::Load("System/Unix/Shell/Shell");	

	/**
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage Shell
     * @name System_Unix_Shell_Test
	 *
	 */
	class System_Unix_Shell_Test extends UnitTestCase 
	{
        function System_Unix_Shell_Test() 
        {
            $this->UnitTestCase('System/Unix/Shell test');
        }
        
        function testShell() 
        {
			
			$Shell = new Shell();
			
			//
			// Delete tmp file
			//
			@unlink("/tmp/shelltest");
			$this->assertFalse(file_exists("/tmp/shelltest"), "Tmp file does not exists");
			
			//
			// Create tmp file
			//
			$Shell->Execute("touch", array("/tmp/shelltest"));
			$this->assertTrue(file_exists("/tmp/shelltest"), "Tmp file exists");
			
			//
			// ls -al
			//
			$result = $Shell->Query("ls", array("-al"));
			$this->assertTrue(!empty($result), "Result not empty");
			
			// Query raw
			$Shell->QueryRaw("ls -al");
			$this->assertTrue(!empty($result), "Result not empty");
			
        }
    }


?>