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
     * @subpackage Util
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */    


	Core::Load("/NET/Util");
	
	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage Util
	 * @name Net_Util_Test
	 */
	class Net_Util_Test extends UnitTestCase 
	{
        function Net_Util_Test() 
        {
            $this->UnitTestCase('NET Utils test');
        }
        
        function testIPAddress()
        {
            $IPAddress = new IPAddress("205.12.1.22");
            $this->assertTrue($IPAddress, "IPaddress instance created");
            
            $this->assertTrue($IPAddress->IsExternal(), "Ip address IsExternal returned true");
            $this->assertFalse($IPAddress->IsInternal(), "Ip address IsInternal returned false");
            
            $IPAddress = new IPAddress("asdfsdfasdfsd");
            $this->assertFalse($IPAddress->IP, "IPaddress instance NOT created created");
        }
        
        function testIPUtils() 
        {
			
			$IPUtils = new IPUtils();
			
			$range = $IPUtils->CIDR2List("70.84.23.0/24");			
			$this->assertTrue((count($range) == 256), "CIDR converted to range");
			
			$range = $IPUtils->IPRange2List("192.168.1.1-10");
			$this->assertTrue((count($range) == 10), "Range converted to list");
			
			$subnet_mask = $IPUtils->Bits2SubnetMask(24);
			$this->assertTrue(($subnet_mask == "255.255.255.0"), "Subnet mask successfully generated");
			
			$subnet_mask = $IPUtils->Bits2SubnetMask(27);
			$this->assertTrue(($subnet_mask == "255.255.255.224"), "Subnet mask successfully generated");
			
			$binary = $IPUtils->IP2bin("205.18.18.0");
			$this->assertTrue(($binary == "11001101000100100001001000000000"), "Binary IP successfully generated");
                			
			$normal = $IPUtils->Bin2IP($binary);
			$this->assertTrue(($normal == "205.18.18.0"), "IP successfully generated from binary string");
			
			$bits = $IPUtils->SubnetMask2Bits("255.255.255.240");
			$this->assertTrue(($bits == 28), "Netbits successfully generated");
			
			$subnets = $IPUtils->SplitSubnet("205.18.18.0", 24, 26);
			$this->assertTrue(($subnets[26][0] == "205.18.18.128" && $subnets[26][1] == "205.18.18.192"), "/24 Subnet successfully splited");
			
			$subnets = $IPUtils->SplitSubnet("205.18.0.0", 16, 23);
            $this->assertTrue(($subnets[23][0] == "205.18.252.0" && $subnets[23][1] == "205.18.254.0"), "/16 Subnet successfully splited");
			
			$subnets = $IPUtils->SplitSubnet("205.0.0.0", 8, 20);
			$this->assertTrue(($subnets[20][0] == "205.255.224.0" && $subnets[20][1] == "205.255.240.0"), "/8 Subnet successfully splited");
		  
			$subnets = $IPUtils->SplitSubnet("205.127.0.0", 9, 16);
			$this->assertTrue(($subnets[16][0] == "205.126.0.0" && $subnets[16][1] == "205.127.0.0"), "/9 Subnet successfully splited");
        }
    }


?>