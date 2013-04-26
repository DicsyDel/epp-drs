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
     * @subpackage SRSPlus
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	// Load libraries
	Core::Load("Security/GnuPG");
	Core::Load("NET/API/SRSPlus");

	/**
	 * @category   LibWebta
     * @package    NET_API
     * @subpackage SRSPlus
     * @name SRSPlusTest
	 *
	 */
	class SRSPlusTest extends UnitTestCase 
	{
        function SRSPlusTest() 
        {
            $this->UnitTestCase('SRSPlus tests');
        }
        
        function testCore() 
        {
			
			$args = array(
							"email"	=> "lianna@stoverdatasystems.com",
							"id"	=> "1009084",
							"testmode" => 1,
							"gpgpass" => "JHd78asdhasj6654j",
							"host"	 => "testsrs.srsplus.com"
						);
		
			$srs = new SRSPlus($args);
			
			//1113383
			$req = $srs->QueryTransferId("sss", "tv", "1113383");
			$req = $srs->QueryRejectedTransfer("sss", "tv", "1113383");
			// get account ballance			
			$this->assertTrue($srs->GetBallance(), "Cannot get account ballance");	
			
			// get domain info
			$dinfo = $srs->DomainInfo("webta", "tv");
			$this->assertTrue($dinfo["DOMAIN STATUS"], "Cannot get domain info");	
			
			// whois
			$info = $srs->Whois("webta", "tv");
			$this->assertTrue($info["NOTE"], "Cannot get domain info");
			
			$contact = array(
				'TLD' => 'tv',
				'FNAME' => 'John',
				'LNAME' => 'Public',
				'ORGANIZATION' => 'John Q. Public Co.',
				'EMAIL' => 'johnq@public.com',
				'ADDRESS1' => '123 Main St.',
				'ADDRESS2' => 'Suite 100',
				'CITY'  => 'Metropolis',
				'PROVINCE' => 'CA',
				'POSTAL CODE' => '90024',
				'COUNTRY' => 'US',
				'PHONE' => '(310)555-1212'
			);
			
			// create contact
			$cc = $srs->CreateContact($contact);
			$contactid = $cc["CONTACTID"];
			
			$this->assertTrue($contactid, "Cannot create contact");
			
			// get contact info
			$cinfo = $srs->GetContactInfo($contactid);
			$this->assertTrue($cinfo["COUNTRY"], "Cannot get contact info");
			
			// edit contact
			$edit = $srs->EditContact($contactid, array("FNAME", "Igor"));
			$this->assertTrue($edit["CONTACTID"], "Cannot edit contact");
			
			// Create contact
			$domain = "webattest".rand(10000, 99999);
			$cr = $srs->CreateDomain($domain, "tv", 1, array("RESPONSIBLE PERSON"=>$contactid), array());
			$this->assertTrue($cr["REQUESTID"], "Cannot create domain");
		
			// renew domain
			$cr = $srs->RenewDomain($domain, "tv", 2);
			$this->assertTrue($cr["REQUESTID"], "Cannot renew domain");
			
			// register nameserver
			$ip = gethostbyname("ns.hostdad.com");
			$cr = $srs->RegisterNameserver("ns1.{$domain}.tv", "216.168.229.190");
			$this->assertTrue($cr["REQUESTID"], "Cannot create nameserver");
			
			// NameServerInfo
			$info = $srs->GetNameserverInfo("ns1.{$domain}.tv");
			$this->assertTrue($info["DNS SERVER NAME"], "Cannot get nameserver info");
						
			// Update domain
			$newdata = array("DNS SERVER NAME 1" => "ns1.{$domain}.tv");
			$upd = $srs->DomainUpdate($domain, "tv", $newdata);
			$this->assertTrue($upd["REQUESTID"], "Cannot update domain");
			

			// whois
			$info = $srs->Whois($domain, "tv");
			
			$this->assertTrue($info["EFFECTIVE PRICE"], "Cannot get whois info");
			
			// delete nameserver
			$del = $srs->DeleteNameserver("ns1.{$domain}.tv");
			$this->assertTrue($del["REQUESTID"], "Cannot delete nameserverhost");
			
			$del = $srs->DeleteDomain($domain, "tv");
			$this->assertTrue($del["REQUESTID"], "Cannot delete domain name");
        }
    }
?>