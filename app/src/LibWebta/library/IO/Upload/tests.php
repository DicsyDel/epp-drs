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
     * @subpackage Upload
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("Core");
	Core::Load("CoreException");
	Core::Load("IO/Upload/UploadManager");
	
	/**
	 * Tests for IO/Upload
	 * 
	 * @category   LibWebta
     * @package    IO
     * @subpackage Upload
     * @name IO_Upload_Test
	 *
	 */
	class IO_Upload_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('IO/Upload Tests');
        }

        function testIO_Upload_UploadManager() 
        {
			$base = dirname(__FILE__);
			
			$Uploadmanager = new UploadManager();
			
			$path 		= "/tmp/sjack/tmp";
			$filename 	= "filename.gif";

			$Uploadmanager->SetDestinationDir("/tmp/test.file");
			
			//
			// Uplaod from URL
			//
			$res = $Uploadmanager->UploadFromURL("http://webta.net/images/webta_guy.jpg");
			$this->assertTrue($res, "File uploaded from URL");
			
			print_r($GLOBALS['warnings']);
			
			$file = array(
				"name" => $filename,
				"size" => 1023,
				"type" => "image/txt",
				"error"=> 0
			);
			
			// check upload
			$res = $Uploadmanager->Upload($file);
			$this->assertFalse($res, "File not uploaded");
				
			// check Generate Path
			$result = $Uploadmanager->BuildDir($path, $filename);
			$md5  = md5($filename);
			$newpath = $path . "/". substr($md5, 0, 2) ."/". substr($md5, 2, 2);
			
			$this->assertEqual($result, $newpath, "Result path is valid: $result");
			
			
			// check SetDestination
			$Uploadmanager->SetDestinationDir($result ."/". $filename);
			$this->assertTrue(is_dir($result), "Destination created $result");
			
			$valid = array("txt", "jpeg", "tar", "rar", "zip", "gif");
			$Uploadmanager->SetValidExtensions($valid);
			
			// check Validate function / must be public access
			//$res = $Uploadmanager->Validate();
			//$this->assertFalse($res, "Uploaded File Not validated");
        }
    }

?>