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
     * @package    IO
     * @subpackage Basic
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @ignore
     */

	Core::Load("IO/Basic");
	
	if (!defined("DIRECTORY_SEPARATOR"))
	{
		if (PHP_OS == "WINNT")
			define("DIRECTORY_SEPARATOR", "\\");
		else
			define("DIRECTORY_SEPARATOR", "/");
	}
	
	/**
	 * @category   LibWebta
     * @package    IO
     * @subpackage Basic
     * @name IO_Basic_Test
	 */
	class IO_Basic_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('IO/basic Tests');
        }

        function testIO_Basic() 
        {
        	//
			// Delete tmp file
			//
			$tmpdir = ini_get("session.save_path") ? ini_get("session.save_path") : "/tmp";
			
			$s = DIRECTORY_SEPARATOR;
			$tmpdir = "{$tmpdir}{$s}iotest{$s}1{$s}2{$s}3";
			@mkdir($tmpdir, 0777, true);
			
			
			$this->AssertTrue(file_exists($tmpdir), "Directory exists before IOTool::UnlinkRecursive()");
			
			$p = "{$tmpdir}{$s}iotest";
			IOTool::UnlinkRecursive($p);
			$this->AssertFalse(file_exists($p), "Directory does not exist after IOTool::UnlinkRecursive()");
			
        }
    }

?>