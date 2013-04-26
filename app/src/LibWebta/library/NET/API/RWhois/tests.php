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
     * @subpackage RWhois
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */	
      
    Core::Load("NET/API/RWhois");
	
    /**
     * @category   LibWebta
     * @package    NET_API
     * @subpackage RWhois
     * @name NET_API_RWhois_Test
     *
     */
	class NET_API_RWhois_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('NET/API/RWhois test');
        }
        
        function testRWhoisManager()
        {
            print '2';
            $RWHoisManager = new RWhoisManager(
                                                "65.38.4.218",
                                                "4321", 
                                                "hostmaster@webta.net",
                                                "/usr/local/bin/rwhois_indexer", 
                                                "/usr/local/sbin/rwhoisd", 
                                                "/usr/local/etc/rwhoisd"
                                              );
                                              
            $options = array("12.56.4.5", "22", "root", "skdjhfsdkljh");                                
            $RWHoisManager->SetTransport("SSH", $options);
            
            $netinfo = array(
                                "id"        => "WEBTA3-192.168.4.0/30",
                                "name"      => "WEBTA-LOCAL4-NET",
                                "org"       => "WEBTA",
                                "tech"      => "tech@webta.net",
                                "admin"     => "admin@webta.net", 
                                "dtcreated" => date("Ymd"),
                                "dtupdated" => date("Ymd")
                            );
            
            //$RWHoisManager->AddNetwork("192.168.12.0", 24, $netinfo);
            
            //$RWHoisManager->DeleteNetwork("192.168.4.0", 24);
            
            $RWHoisManager->Reindex();
        }
        
        /*
        function testRWhois() 
        {
            $RWhois = new RWhois();
            
            $result = $RWhois->Request("70.84.53.27");
            $this->assertTrue($result, "RWhois return information for '70.84.53.27'");
            
            $result = $RWhois->Request("6.45.32.14");
            $this->assertTrue($result, "RWhois return information for '6.45.32.14'");
            
            $result = $RWhois->Request("sdfasdfasdf");
            $this->assertTrue($result, "RWhois NOT return information for 'sdfasdfasdf'");
        }
        */
	}
?>