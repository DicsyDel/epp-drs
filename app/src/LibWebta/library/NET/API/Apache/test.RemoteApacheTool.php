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
     * @package NET_API
     * @subpackage Apache
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     * @filesource
     */

	include_once("../Server/System/class.ApacheTool.php");
	include_once("../Server/System/class.RemoteApacheTool.php");
	
	/**
	 * @category   LibWebta
     * @package NET_API
     * @subpackage Apache
     * @name ApacheToolTest
	 *
	 */
	class ApacheToolTest extends UnitTestCase 
	{
        function ApacheToolTest() 
        {
            $this->UnitTestCase('HTTPD tools test');
        }
        
        function testApacheTool() 
        {

			$ApacheTool = new RemoteApacheTool("192.168.1.1", "22", "root", ",fyfymjc-");
			$this->assertTrue($ApacheTool, "Cannot connect to remote system");
			
			$ApacheTool->DeleteVHost("127.0.0.1", "cptest.com");
			$ApacheTool->DeleteVHost("127.0.0.3", "cptest.com");
			$ApacheTool->DeleteVHost("127.0.0.5", "cptest-ssl.com", true);
			
			$retval = $ApacheTool->RestartApache();
			$this->assertTrue($retval, "Cannot restart");
			
			//
			// Add VHost
			//
			$retval = $ApacheTool->AddVHost("127.0.0.1", "cptest.com", "/home/test1", "http://webta.net/company.html");
			$this->assertTrue($retval, "Cannot create VHOST");

			
			//
			// Delete VHost
			//
			$retval = $ApacheTool->DeleteVHost("127.0.0.3", "cptest.com");
			$this->assertTrue($retval, "VHost deleted succesfully");
			
			
			
			////////////////////////////////////
			//SSL
			////////////////////////////////////
			
			//
			// Add SSL VHost
			//
			$retval = $ApacheTool->AddVHost("127.0.0.5", "cptest-ssl.com", "/home/test2", "ssl@webta.net", true);			
			$this->assertTrue($retval, "Cannot create vhost");
			
			//
			// Delete SSL Vhost
			//
			$retval = $ApacheTool->DeleteVHost("127.0.0.6", "cptest-ssl.com", true);
			$this->assertTrue($retval, "cannot delete vhost");
			
			
			
        }
    }


?>