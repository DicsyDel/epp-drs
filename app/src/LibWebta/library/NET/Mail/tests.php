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
     * @package    NET
     * @subpackage Mail
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */


    /**
     * @category   LibWebta
     * @package    NET
     * @subpackage Mail
     * @name PHPSmartyMailerTest
     */
	class PHPSmartyMailerTest extends UnitTestCase 
	{
        function PHPSmartyMailerTest() 
        {
            $this->UnitTestCase('PHPSmartyMailer test');
        }
        
        function testPHPSmartyMailer() 
        {
			$Mailer = new PHPSmartyMailer(CF_EMAIL_DSN);
			
			//$Mailer->SMTPDebug = true;
			
			$Mailer->SmartyBody = array("signup.eml", array("login"=>"TEST", "password"=>"TEST"));
			$Mailer->Subject = "Test Email";
							
			$Mailer->AddAddress("test@example.com", "");
			
			$Mailer->From = CF_EMAIL_ADMIN;
			$send = $Mailer->Send();
			
			$this->assertTrue(!is_array($Mailer->Body), "Parse body");
			$this->assertTrue($send, "Sending mail");
        }
    }


?>