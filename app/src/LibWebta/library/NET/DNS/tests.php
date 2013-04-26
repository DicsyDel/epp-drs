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
     * @package    NET
     * @subpackage DNS
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource 
     */

    /**
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @name NET_DNS_Test
     *
     */
	class NET_DNS_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('NET/DNS Tests');
        }
        
        /*
        function testZoneCreation() 
        {
        	Core::Load("NET/DNS/AbstractDNSZone");
        	Core::Load("NET/DNS/class.DNSZone.php");
        	
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
			
			$this->assertWantedPattern("/".$SOA["name"]."/msi", $Zone->Content, "Generated DNS zone contains at least SOA name");
			$this->assertWantedPattern("/IN\s+A[\s\t]+127\.0\.0\.7/msi", $Zone->Content, "Generated DNS zone contains added A record");
			
        }
        */
    	
        function testDNSZoneParser()
        {
        	Core::Load("NET/DNS/DNSZoneParser");
        	$base = dirname(__FILE__);
        	
        	$DNSZoneParser = new DNSZoneParser();
			foreach ((array)glob("{$base}/zones/*.db") as $zonefile) 
			{
				$content = file_get_contents($zonefile);
							
				$zone = $DNSZoneParser->ParseZone($content, basename($zonefile));
				$this->assertTrue(is_object($zone), "Zone {$zonefile} successfully parsed.");
			}
        }
        
        function testDNSZone2()
        {
			Core::Load("NET/DNS/class.DNSZone2.php");
			Core::Load("NET/DNS/class.DNSRecord.php");
			
			$dnszone = new DNSZone();
			
			/////
			// Test SOA DNS Record
			//
			Core::Load("NET/DNS/class.SOADNSRecord.php");
			
			// Valid SOA
			$SOA = new SOADNSRecord("test.com","ns.hostdad.com", "test@test.com");			
			$this->assertWantedPattern("/@\s+IN\s+SOA[\s\t]+/msi", $SOA->__toString(), "Generated SOA Record");
			$dnszone->AddRecord($SOA);
			
			// Invalid SOA
			$soa = new SOADNSRecord("test", "ns.hostdad.com", "test@test.com");
			$this->assertFalse($soa->__toString(), "SOA Record NOT generated with invalid params");
			
			/////
			// Test A DNS Record
			//
			Core::Load("NET/DNS/class.ADNSRecord.php");
			
			// subdomain record
			$a1 = new ADNSRecord("test", "192.168.1.1");
			$this->assertWantedPattern("/[A-Za-z0-9]+\s+IN\s+A[\s\t]+/msi", $a1->__toString(), "Generated A Record");
			$dnszone->AddRecord($a1);
			
			//domain record
			$a2 = new ADNSRecord("test.com", "192.168.1.2");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+IN\s+A[\s\t]+/msi", $a2->__toString(), "Generated A Record");
			$dnszone->AddRecord($a2);
			
			//dottify domain record
			$a3 = new ADNSRecord("test.com.", "192.168.1.3");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+IN\s+A[\s\t]+/msi", $a3->__toString(), "Generated A Record");
			$dnszone->AddRecord($a3);
			
			//@ domain record
			$a4 = new ADNSRecord("@", "192.168.1.100");
			$this->assertWantedPattern("/@\s+[0-9]*\sIN\s+A[\s\t]+/msi", $a4->__toString(), "Generated A Record");
			$dnszone->AddRecord($a4);
			
			//invalid record
			$record = new ADNSRecord("-1test.com", "192.168.1");
			$this->assertFalse($record->__toString(), "A Record NOT generated with invalid params");
			
			//////
			// Test MX DNS Record
			//
			Core::Load("NET/DNS/class.MXDNSRecord.php");
			
			//domain record
			$record = new MXDNSRecord("mail", "test.com");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+IN\s+MX[\s\t]+/msi", $record->__toString(), "Generated MX Record");
			$dnszone->AddRecord($record);
			
			//dottify domain record
			$record = new MXDNSRecord("test.com.", "mailtest.com");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+IN\s+MX[\s\t]+/msi", $record->__toString(), "Generated MX Record");
			$dnszone->AddRecord($record);
			
			//@ domain record
			$record = new MXDNSRecord("@", "mail2.test.com");
			
			$this->assertWantedPattern("/@\s+[0-9]*\sIN\s+MX[\s\t]+/msi", $record->__toString(), "Generated MX Record");
			$dnszone->AddRecord($record);
			
			//invalid record
			$record = new MXDNSRecord("-1test.com", "test2");
			$this->assertFalse($record->__toString(), "MX Record NOT generated with invalid params");
			
			///////
			// Test NS DNS Record
			//
			Core::Load("NET/DNS/class.NSDNSRecord.php");
			
			// subdomain record
	
			//domain record
			$record = new NSDNSRecord("test.com", "ns1.test.com");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+IN\s+NS[\s\t]+/msi", $record->__toString(), "Generated NS Record");
			$dnszone->AddRecord($record);
			
			//dottify domain record
			$record = new NSDNSRecord("test.com.", "ns2.test.com");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+IN\s+NS[\s\t]+/msi", $record->__toString(), "Generated NS Record");
			$dnszone->AddRecord($record);
			
			//sub domain record
			$record = new NSDNSRecord("name.com", "ns1");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+[0-9]*\sIN\s+NS[\s\t]+/msi", $record->__toString(), "Generated NS Record");
			$dnszone->AddRecord($record);
			
			//invalid record
			$record = new NSDNSRecord("-1test.com", "asdasda");
			$this->assertFalse($record->__toString(), "NS Record NOT generated with invalid params");
			
			///////
			// Test CNAME DNS Record
			//
			Core::Load("NET/DNS/class.CNAMEDNSRecord.php");
			
			//domain record
			$record = new CNAMEDNSRecord("test", "ns2.test.com");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+IN\s+CNAME[\s\t]+/msi", $record->__toString(), "Generated CNAME Record");
			$dnszone->AddRecord($record);
			
			//sub domain record
			$record = new CNAMEDNSRecord("name", "ns1", 28800);
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+[0-9]+\sIN\s+CNAME[\s\t]+/msi", $record->__toString(), "Generated CNAME Record");
			$dnszone->AddRecord($record);
			
			//invalid record
			$record = new CNAMEDNSRecord("-1test.com", "192.168.1.1");
			$this->assertFalse($record->__toString(), "CNAME Record NOT generated with invalid params");
			
			///////
			// Test TXT DNS Record
			//
			Core::Load("NET/DNS/class.TXTDNSRecord.php");
			
			$record = new TXTDNSRecord("example.com.", "This is a test TXT record");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+[0-9]*\sIN\s+TXT[\s\t]+\"([^\"]+)\"/si", $record->__toString(), "Generated TXT record");
			
			$record = new TXTDNSRecord("test", "This is a test TXT record");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+[0-9]*\sIN\s+TXT[\s\t]+\"([^\"]+)\"/si", $record->__toString(), "Generated TXT record");
			
			$record = new CNAMEDNSRecord("192.168.1.20", "This is a test TXT record");
			$this->assertFalse($record->__toString(), "TXT Record NOT generated with invalid params");
			
			////////
			// Test SPF DNS Record
			//
			Core::Load("NET/DNS/class.SPFDNSRecord.php");
			
			
			// Test data
			$basics = array(
			                 array("?", "include:test.com"),
			                 array("-", "all")
			               );
			$sender_a    = array(
			                     array("", "a"),
			                     array("+", "a:test.com"), 
			                     array("~", "a:test.com/16"), 
			                     array("?", "a/16")
			                     );
			
			$sender_mx   = array(
			                     array("+", "mx"), 
			                     array("-", "mx:test.com"), 
			                     array("~", "mx:test.com/16"), 
			                     array("?", "mx/16")
			                     );
			
			$sender_ptr  = array(
			                     array("+", "ptr"), 
			                     array("", "ptr:test.com")
			                    );
			
			$sender_ip4  = array(
			                     array("~", "ip4:192.168.1.1"),
			                     array("", "ip4:192.168.1.1/16")
			                    );
			
			$sender_ip6  = array(
			                     array("?", "ip6:2001:db8::10"), 
			                     array("", "ip6:2001:db8::10/16")
			                    );
			
			$sender_exists  = array(
			                         array("-", "exists:test.com")
			                       );
			
			$mods = array(array("redirect", "test.net"), array("exp", "test.test.com"));
			
			$record = new SPFDNSRecord("test.com.", $sender_a, $basics, $mods);
			$this->assertTrue($record->__toString(), "Generated SPF TXT Record Width A rules");
			
			$record = new SPFDNSRecord("test.com.", $sender_mx, $basics, $mods);
			$this->assertTrue($record->__toString(), "Generated SPF TXT Record Width MX rules");
			
			$record = new SPFDNSRecord("test.com.", $sender_ptr, $basics, $mods);
			$this->assertTrue($record->__toString(), "Generated SPF TXT Record Width PTR rules");
			
			$record = new SPFDNSRecord("test.com.", $sender_ip4, $basics, $mods);
			$this->assertTrue($record->__toString(), "Generated SPF TXT Record Width IP4 rules");
			
			$record = new SPFDNSRecord("test.com.", $sender_exists, $basics, $mods);
			$this->assertTrue($record->__toString(), "Generated SPF TXT Record Width EXISTS rules");
			
			$record = new SPFDNSRecord("-test.com.", $sender_exists, $basics, $mods);
			$this->assertFalse($record->__toString(), "SPF TXT Record NOT generated Width invalid name");
			
			// Custom SPF record
			$basics = array(
			                 array("", "include:webmail.pair.com"),
			                 array("?", "include:spf.trusted-forwarder.org"),
			                 array("-", "all")
			               );
			$sender = array(
			                 array("", "ip4:72.81.252.18"),
			                 array("", "ip4:72.81.252.19"),
			                 array("", "ip4:70.91.79.100"),
			                 array("?", "a:relay.pair.com")
			               );
            $record = new SPFDNSRecord("kitterman.com.", $sender, $basics);
            $this->assertTrue($record->__toString(), "Custom SPF TXT Record generated");
			
            // Test SPF With macroses
            $basics = array(
			                 array("-", "include:ip4._spf.%{d}"),
			                 array("-", "include:include:ip4._spf.%{d}"),
			                 array("+", "all")
			               );
			$sender = array(
			                 array("", "exists:%{l1r+}.%{d}"),
			                 array("", "exists:%{l1r+}.%{d}")
			               );
            $record = new SPFDNSRecord("kitterman.com.", $sender, $basics);
            $this->assertTrue($record->__toString(), "Custom SPF TXT Record with macroses generated");
            
            // Test SPF With bad macroses
            $basics = array(
			                 array("-", "include:ip4._spf.%{dfhsd}"),
			                 array("-", "include:include:ip4._spf.%{asdfklj}"),
			                 array("+", "all")
			               );
			$sender = array(
			                 array("", "exists:%{l1r+}.%{32}"),
			                 array("", "exists:%{l1r+}.%{sdaf}")
			               );
            $record = new SPFDNSRecord("kitterman.com.", $sender, $basics);
            $this->assertFalse($record->__toString(), "Custom SPF TXT Record with bad macroses NOT generated");
            
			///////
			// Test PTR DNS Record
			//
			Core::Load("NET/DNS/class.PTRDNSRecord.php");
			
			//domain record
			$record = new PTRDNSRecord("2", "c1.test.com");
			$this->assertWantedPattern("/[0-9]+\s+[0-9]*\s+IN\s+PTR[\s\t]+/msi", $record->__toString(), "Generated PTR Record");
			
			//dotify domain record
			$record = new PTRDNSRecord("245", "c2.test.com.");
			$this->assertWantedPattern("/[A-Za-z0-9\.]+\s+[0-9]*\sIN\s+PTR[\s\t]+/msi", $record->__toString(), "Generated PTR Record");

			//invalid record
			$record = new PTRDNSRecord("370", "192.168.1.1");
			$this->assertFalse($record->__toString(), "PTR Record NOT generated with invalid params");

			$content = $dnszone->__toString();  
			$this->assertWantedPattern("/test.com/msi", $content, "Generated DNS zone contains at least SOA name");
			$this->assertWantedPattern("/IN\s+A[\s\t]+192\.168\.1\.100/msi", $content, "Generated DNS zone contains added A record");
        }
	}


?>