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
     * @subpackage BIND
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */     


	Core::Load("NET/DNS");
	Core::Load("NET/API/BIND");	
	
	/**
	 * @category   LibWebta
     * @package NET_API
     * @subpackage BIND 
	 * @name NET_API_BIND_Test
	 */
	class NET_API_BIND_Test extends UnitTestCase 
	{
        function NET_API_BIND_Test() 
        {
            $this->UnitTestCase('BIND test');
        }
        
        function testBIND() 
        {
			$this->BIND = new BIND("/etc/named.conf", "/var/named/etc/namedb", "", "/usr/sbin/rndc");
			$this->doTests();
        }
        
        function testRemoteBind() 
        { 	
        	$authinfo = array("type" => "password", "login" => "root", "password" => "PaSsWoRd");
        	
        	$this->BIND = new RemoteBIND("xxx.xxx.xxx.xxx", 22, $authinfo, "/usr/sbin/rndc", "/etc/named.conf", "/var/named", "");	
        	
        	$this->doTests();
        }
       
        function testRemoteFTP() 
        { 	
        	$authinfo = array("type" => "password", "login" => "named", "password" => "[fhfrbhb");
        	
        	$this->BIND = new RemoteBIND("example.com", 21, $authinfo, "/usr/sbin/rndc", "/etc/named.conf", "/var/named", "", false);	
        	$this->BIND->SetTransport("ftp");
        	$this->doTests();
        }
         
        function testRemoteBindKeyAuth() 
        { 	
        	$authinfo = array(	"type" => "pubkey", 
        						"login" => "root", 
        						"pubkey_path" => dirname(__FILE__)."/../../SSH/keys/key.pub",
        						"privkey_path" => dirname(__FILE__)."/../../SSH/keys/key",
        						"key_pass"		=> "PaSsWoRd"
        					);
        	
        	$this->BIND = new RemoteBIND("example.net", 22, $authinfo, "/usr/sbin/rndc", "/etc/named.conf", "/var/named", "");	
        	
        	$this->doTests();
        }
        
        function testRemoteBindKeyAuth_local() 
        { 	
        	$authinfo = array(	"type" => "pubkey", 
        						"login" => "root", 
        						"pubkey_path" => dirname(__FILE__)."/../../SSH/keys/local.pub",
        						"privkey_path" => dirname(__FILE__)."/../../SSH/keys/local",
        						"key_pass"		=> "111111"
        					);
        	
        	$this->BIND = new RemoteBIND("xxx.xxx.xxx.xxx", 22, $authinfo, "/usr/sbin/rndc", "/etc/named.conf", "/var/named", "");	
        	
        	$this->doTests();
        }
        
        function doTests()
        {
        	// prepare
			$zone_tpl = '; !Automatically generated - do not edit!
						; Zone file for {name}
						$TTL {ttl}
						@      IN      SOA     {origin} {person} (
						       {serial}    ; serial, todays date+todays
						       {refresh}        ; refresh, seconds
						       {retry}        ; retry, seconds
						       {expire}        ; expire, seconds
						       {minimum} )    ; minimum, seconds
						
						; Records
						{records}';
			
			define("CF_DNSZONETPL", $zone_tpl);
			
			$Zone = new DNSZone();
			
			//
			// Generation
			//
			$SOA = array(
			"name" => "domain-dns.com",
			"serial" => "2005052700",
			"origin" => "ns.hostdad.com.",
			"person" => "ak.webta.net.",
			"ttl" => 14400,
			"refresh" => 14400,
			"retry" => 7200,
			"expire" => 3600000,
			"minimum" => 86400
			);
			if ($Zone->SetSOAValue($SOA))
			{
				$params = array("*", "127.0.0.7");
				$Zone->AddRecord("A", $params, $rec["ttl"]);
			}
			$Zone->Generate();
			
			$template = 'zone "{zone}" {
								   type master;
								   file "{db_filename}";
								};';
        	
			
			$this->BIND->SetZoneTemplate($template);
			
			$retval = $this->BIND->ListZones();
			
			$this->BIND->ConfCleanup();
			$retval = $this->BIND->SaveConf();
			
			$this->assertTrue($retval, "named.conf saved ok");
			$this->assertNoUnwantedPattern("/\\n\\n\\n/m", "named.conf does not contain unneeded stuff");
			
			
        	$res = $this->BIND->SaveZone($Zone->Name, $Zone->Content);
        	$this->assertTrue($res, "Zone successfully saved");
        	
        	$res = $this->BIND->DeleteZone($Zone->Name);
        	$this->assertTrue($res, "Zone successfully deleted");
        	
        	$retval = $this->BIND->IsZoneExists($Zone->Name);
        	$this->assertFalse($retval, "Zone file does not exists");
        	
        }
    }


?>