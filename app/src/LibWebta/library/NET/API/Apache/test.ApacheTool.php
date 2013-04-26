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

			$ApacheTool = new ApacheTool();
			
			$ApacheTool->DeleteVHost("127.0.0.1", "cptest.com");
			$ApacheTool->DeleteVHost("127.0.0.3", "cptest.com");
			$ApacheTool->DeleteVHost("127.0.0.5", "cptest-ssl.com", true);
			
			//
			// Add VHost
			//
			$ApacheTool->AddVHost("127.0.0.1", "cptest.com", "alex", "http://webta.net/company.html");
			$retval = $ApacheTool->VHostExists("127.0.0.1", "cptest.com");
			$this->assertTrue($retval, "Recently created vhost exists");

			//
			// Edit vhost
			//
			$retval = $ApacheTool->EditVHost("cptest.com", "127.0.0.1", "127.0.0.3", "bob2", "bob2@webta.net");
			$retval = $ApacheTool->VHostExists("127.0.0.3", "cptest.com");
			$this->assertTrue($retval, "Recently changed vhost exists");
			
			//
			// List VHosts
			//
			$retval = $ApacheTool->ListVHosts();
			$this->assertTrue(is_array($retval) && count($retval) >0, "VHosts listed ok");
			//
			// Delete VHost
			//
			$ApacheTool->DeleteVHost("127.0.0.3", "cptest.com");
			$retval = $ApacheTool->VHostExists("127.0.0.3", "cptest.com");
			$this->assertFalse($retval, "VHost deleted succesfully");
			
			
			
			////////////////////////////////////
			//SSL
			////////////////////////////////////
			
			//
			// Add SSL VHost
			//
			$ApacheTool->AddVHost("127.0.0.5", "cptest-ssl.com", "alexssl", "ssl@webta.net", true);
			$retval = $ApacheTool->VHostExists("127.0.0.5", "cptest-ssl.com");
			foreach ($ApacheTool->ListVHosts() as $f)
			{
				if ($f[2])
					$retval2 = true;
			}			
			$this->assertTrue($retval && $retval2, "Recently created SSL vhost exists");
			
			//
			// Edit SSL Vhost
			//
			$retval = $ApacheTool->EditVHost("cptest-ssl.com", "127.0.0.5", "127.0.0.6", "bob2_ssl", "bob2_ssl@webta.org");
			$retval = $ApacheTool->VHostExists("127.0.0.6", "cptest-ssl.com");
			$this->assertTrue($retval, "Recently edited SSL vhost exists");
		
			
			//
			// Delete SSL Vhost
			//
			$ApacheTool->DeleteVHost("127.0.0.6", "cptest-ssl.com", true);
			$retval = $ApacheTool->VHostExists("127.0.0.6", "cptest-ssl.com");
			$this->assertfalse($retval, "Recently deleted SSL vhost does not exist");
			
			
			
        }
    }


?>