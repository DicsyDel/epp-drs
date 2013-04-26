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
     * @package    NET_API
     * @subpackage Whois
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */
    
    Core::Load("NET/API/Whois");
	
    /**
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Whois
     * @name NET_API_Whois_Test
     *
     */
	class NET_API_Whois_Test extends UnitTestCase 
	{
        function NET_API_Whois_Test() 
        {
            $this->UnitTestCase('NET/API/Whois test');
        }
        
        function testWhois() 
        {
            $Whois = new Whois();
            
            $result = $Whois->FetchRecord("www.webta.net");
            $this->assertTrue($result, "Whois return info for 'www.webta.net'");
            flush();
            $result = $Whois->FetchRecord("asdasdasdfgas23423rfsdafasas.com");
            $this->assertFalse($result, "Did not receive info for 'asdasdasdfgas23423rfsdafasas.com'");
            flush();
            $result = $Whois->FetchRecord("webta.com");
            $this->assertTrue($result, "Received info for 'webta.com'");
            flush();
            $result = $Whois->FetchRecord("test.tw");
            $this->assertTrue($result, "Received info for 'test.tw'");
            flush();
            $result = $Whois->FetchRecord("webta.za");
            $this->assertFalse($result, "Did not receive info for 'webta.za'");
            flush();
            
            // Timeout
            $t1 = time();
            $result = $Whois->FetchRecord("ddeded.cc.f");
            
            $t2 = time();
            $this->assertTrue($t2 <= $t1+3, "\$Whois->SetTimeout cut FetchRecord execution time");
            
            $result = $Whois->FetchRecord("webta.net");
            $this->assertTrue($result, "FetchRecord did not have enough time to execute with SetTimeout(0)");
        }
	}
?>