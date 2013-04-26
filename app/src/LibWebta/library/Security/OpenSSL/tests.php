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
     * @package    Security
     * @subpackage OpenSSL
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */    

	include_once("../Server/System/class.SSLManager.php");
	
	/**
	 * @category   LibWebta
     * @package    Security
     * @subpackage OpenSSL
     * @name SSLManagerTest
	 *
	 */
	class SSLManagerTest extends UnitTestCase 
	{
        function SSLManagerTest() 
        {
            $this->UnitTestCase('SSLManager test');
        }
        
        function testSSLManager() 
        {
			
			$SSLManager = new SSLManager();
			
			//
			// Generate RSA
			//
			$retval = $SSLManager->GenerateRSAKey("cptest", "webta.net");
			$retval = $SSLManager->GenerateRSAKey("cptest", "webta.net");
			$this->assertTrue($retval, "GenerateRSAKey returned true");
			
			//
			// Delete file
			//
			$retval = $SSLManager->DeleteFile("cptest", "webta.net", "key.".date("j-n-Y"));
			$this->assertTrue($retval, "Deleted file succesfully");
			
			//
			// Generate signing request
			//
			$retval = $SSLManager->GenerateSigningRequest("cptest", "webta.net", "test@example.com", "Sevastopol", "UA", "CR", "Webta Labs");
			$this->assertTrue($retval, "Signing request generated succesfully");
			
			//
			// Generate cert
			//
			$retval = $SSLManager->GenerateCert("cptest", "webta.net", "test@example.com", "Sevastopol", "UA", "CR", "Webta Labs");
			$this->assertTrue($retval, "Certificate generated succesfully");
			
			
			
        }
    }


?>