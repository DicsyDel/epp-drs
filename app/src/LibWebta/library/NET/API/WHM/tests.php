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
     * @subpackage WHM
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */
    
    Core::Load("NET/API/WHM");
	
    /**
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Whois
     * @name NET_API_Whois_Test
     *
     */
	class NET_API_WHM_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('NET/API/WHM test');
        }
        
        function testWHMXML() 
        {
        	
            $WHM = new WHMXML("web2.hostdad.com");
            $WHM->ConnectTimeout = 10;
            $WHM->ExecTimeout = 90;
            
            $result = $WHM->Authenticate("root", "610defcd53c51950cbc4fab5cb8760a0
50d0266b829fedc851d82403e85d81f3
86137b4cd7a138e86db9aee020137e7c
254d2921cc302c8f297a97ce68843a4e
ee906feacd27eddfc1f67d729c2f3df0
bd828abd23fab8580fc4cd93c0ffb375
8fb65655f31483814a1b4ff6d8c0f513
1582b911654b436a8a855c4e78714003
8532b921d676974e71f4feba03b03b7e
1c92481ce5ec9e8dad626ddab0729e3b
756af28421075b932668ccdb3de9f1b2
d3ddb491ee667b996b375d08be2b8d43
c35c429dbc0872a0987e5c999e7923f1
8416778e6bd696e57a5e0992e919fc26
e36f6c9f03ac181cfe72201b77d41b4b
f91159268625e6744514f81337361079
bdacb0712b0f810ff0e06d2334a33cbf
0e12d3b6387a409e3a12b5ac01fb9efc
895d7244e4a0a506b528c138a2247e20
ad9dc0b0f970346776e986a2f840d471
190eb3efc9212f43ef0f41de12f703fd
d696a4f14029134c97b5dcd2da52e4a6
22e4760e85f8c94e680aff54a006f8d8
7f5853874f9f1b719f861203e9a2a34b
7d1623f198c85cc0c2eabb97ba76db2c
65a49e32100e7834b372ec31e64c7be4
1905107abb815c05eb812f48aade5741
d2d8c2a84f13a3e721b6a9629cad75bd
c0543c5723b74faac2081cad0edad309");
            #$this->assertTrue($result, "Authenticate()");

            #$result = $WHM->Applist();
            #$this->assertTrue($result, "Applist returned Array");
            
            $u = "webta". rand(0,999);
            /*
            $result = $WHM->CreateAccount(
            $u,
            "$u.info",
            "", 
			300, 
			md5(uniqid()), 
			$ip, 
			$cgi, 
			$frontpage, 
			$hasshell,
			$contactemail,
			$cpmod,
			$maxftp,
			$maxsql,
			$maxpop,
			$maxlst,
			$maxsub,
			$maxpark,
			$maxaddon,
			$bwlimit,
			$customip,
			$useregns,
			$hasuseregns,
			$reseller
            );
            $this->assertTrue(!empty($result["nameserver"]), "result[nameserver] not empty");
            */
			#$result = $WHM->ListAccounts();
			#$this->assertTrue(!empty($result[0]["disklimit"]), "Rsulting array seems to be consistent");
			
			#$result = $WHM->GetAccountSummary("webta910");
			#$this->assertTrue(!empty($result["disklimit"]), "Rsulting array seems to be consistent");
			
			#$result = $WHM->SuspendAccount("webta910");
			#$this->assertTrue($result == true, "Result is true");
			
			#$result = $WHM->UnsuspendAccount("webta910");
			#$this->assertTrue($result == true, "Result is true");
			
			#$result = $WHM->TerminateAccount("webta628");
			#$this->assertTrue($result == true, "Result is true");
			
			#$result = $WHM->UpgradeAccount("webta120", "test");
			#$this->assertTrue($result == true, "Result is true");

			/*$result = $WHM->AddPackage("pkg1", 
			"Default", 
			300, 
			true, 
			true, 
			true, 
			"x",
			10,
			10,
			10,
			10,
			10,
			10,
			10,
			100);
			$this->assertTrue($result == true, "Result is true");
			*/
			#$result = $WHM->RemovePackage("pkg1");
			#$this->assertTrue($result == true, "Result is true");
			#$result = $WHM->AddResellerPrivileges("webta120", true);
			#$this->assertTrue($result == true, "Result is true");

			#$result = $WHM->RemoveResellerPrivileges("webta120", true);
			#$this->assertTrue($result == true, "Result is true");
			
			#$result = $WHM->ListResellerACLs("webta120", true);
			#$this->assertTrue($result == true, "Result is true");
			
			#$result = $WHM->ListSavedACLs();
			#$this->assertTrue(is_array($result), "ListSavedACLs returned array");
			
			#$result = $WHM->ListResellers();
			#$this->assertTrue(is_array($result), "Result is array");
			
			#$result = $WHM->TerminateReseller("webta120", true);
			#$this->assertTrue(is_array($result), "Result is true");

			#$result = $WHM->GetHostName();die($result);
			#$this->assertTrue(!empty($result), "GetHostName() returned non-empty string");
			
			$result = $WHM->GetAPIVersion();
			$this->assertTrue(!empty($result), "GetAPIVersion() returned non-empty string");
			
			#$result = $WHM->RestartService("ssh");
			#$this->assertTrue($result, "RestartService() returned true");
			
			//$result = $WHM->GenerateSSLCert("ak@webta.net", "webtatest001.com", "UA", "Crimea", "Sevastopol", "Webta", "WWW", "ak@webta.net", "JJJcgjU87Ca-");
			//$this->assertTrue($result, "RestartService() returned true");
        }
	}
?>