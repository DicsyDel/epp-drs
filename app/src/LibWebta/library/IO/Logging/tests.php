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
     * @subpackage Logging
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	$base = dirname(__FILE__);
	$srcpath = "$base/../../../..";
	
	Core::Load("NET/Mail/class.PHPMailer.php");
	Core::Load("NET/Mail/class.PHPSmartyMailer.php");
		
	Core::Load("Data/DB/ADODB/adodb-exceptions.inc.php", "{$srcpath}/Lib");
	Core::Load("Data/DB/ADODB/adodb.inc.php", "{$srcpath}/Lib");

	define("CF_DEBUG_DB", false);
	define("CF_DATABASE_DSN", "mysql://root:Vfirf<erfirf@192.168.1.200/temp");
	define("CF_EMAIL_DSN", "sergey:sjack@192.168.1.1:25");

	/**
	 * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @name IO_Logging_Test
	 *
	 */
	class IO_Logging_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('IO/Logging test');
        }
        
        function testIO_Logging_Log() 
        {
			
			Core::Load("IO/Logging/Log");
			
			// message to log
			$message 	= "Some Message";
			$level 		= 1;
			$file 		= "/tmp/logger.txt";
			$tablename	= "temp";
			
			// register logger for screen log
			Log::RegisterLogger("Console", "ScreenLog");
			
			// register logger for file log
			Log::RegisterLogger("File", "FileLog", $file);
			
			// register logger for db log
			Log::RegisterLogger("DB", "DBLog", $tablename);
			
			// register Null log
			Log::RegisterLogger("Null", "NullLog");
			
			// register Email log
			Log::RegisterLogger("EMail", "EmailLog", "sergey@local.webta.net");
			
			
			//
			// Logging to screen
			//
			ob_start();
       		Log::Log($message, $level, "ScreenLog");
       		Log::Log($message, $level, "ScreenLog");
       		$content = ob_get_contents();
       		ob_end_clean();
       		
			$this->asserttrue(stristr($content,"Some Message "), "Log To console returned true.");	
       		
       		
       		
			//
			// Loggin to File
			//
			@unlink($file);
 			Log::Log($message, $level, "FileLog");
 			$content = @file_get_contents($file);
			$this->assertEqual($content, "$message, $level\n", "Log To File returned true.");	
			
			
			//
			// Logging to DB
			//
			/*
			$db = Core::GetDBInstance();
			$db->Execute("CREATE TABLE IF NOT EXISTS `$tablename` (`message` TEXT NOT NULL DEFAULT '', `level` INT(3) NOT NULL DEFAULT 0);");
			Log::Log($message, $level, "DBLog");
			$content = $db->GetOne("SELECT `message` FROM `$tablename` WHERE `level` = ?", array($level));
			$db->Execute("DROP TABLE `$tablename`");
			$this->assertEqual($content, $message, "Log To DB returned true. Content '$content'");	
			*/
			
			
			//
			// Logging to null
			//
			ob_start();
			Log::Log($message, $level, "NullLog");
       		$content = ob_get_contents();
       		ob_end_clean();
			$this->assertEqual($content, "", "Log To Null Dev returned true.");	
			
			
			//
			// Logging to Email
			//
 			//$result = Log::Log($message, $level, "EmailLog");
			//$this->assertTrue($result, "Logging To Email returned true");	
        }
    }


?>