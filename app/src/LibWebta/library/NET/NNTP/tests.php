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
     * @subpackage NNTP
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("NET/NNTP/NNTPClient");
	Core::Load("NET/NNTP/NNTPServerStatus");
	Core::Load("NET/NNTP/UsenetPosting");

	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage NNTP
     * @name NET_NNTP_Test
	 *
	 */
	class NET_NNTP_Test extends UnitTestCase 
	{
        function NET_NNTP_Test() 
        {
            $this->UnitTestCase('NET/NNTP test');
        }
        
        function testNNTPServerStatus()
        {
            $NNTPServerStatus = new NNTPServerStatus();
            $NNTPServerStatus->Connect("news.usenetpromotions.com", 119, "test", "test", 5);
            $speed = $NNTPServerStatus->GetDownloadSpeed("alt.bina", 317);
            $this->assertTrue($speed, "Server download speed determinated");
            
            $speed = $NNTPServerStatus->GetSpeedScore("news.usenetpromotions.com");
            $this->assertTrue($speed, "Server speed score calculated");
            
            $NNTPServerStatus->Disconnect();
        }
        
        function testNNTPClient() 
        {
			$NNTPClient = new NNTPClient();
			
			$conn = $NNTPClient->Connect("msnews.microsoft.com", 119, false, false, 5);
			//$conn = $NNTPClient->Connect("204.153.244.171", 119, false, false, 5);
			//$conn = $NNTPClient->Connect("urocyon.critter.net", 119, false, false, 5);
			//$conn = $NNTPClient->Connect("news.usenetpromotions.com", 119, "test", "test", 5);
			
			$this->assertTrue($conn, "Successfully connected to NNTP server");
			
			//$g = "fido7.kiev.kharkovsky";
			//$g = "microsoft.public.za.windowsxp.setup";
			//$g = "rec.gambling.poker";
			//$g = "fur.conventions";
			$g = "alt.binaries.nl";
			
			$group = $NNTPClient->SelectGroup($g);
			$this->assertEqual($group["group"], $g, "Group successfully selected");
			
			$overview = $NNTPClient->GetOverview($group["first"]);			
			$this->assertTrue($overview["Lines"] > 0, "Article overview successfully received");
			
			$head = $NNTPClient->GetArticleHead($group["first"]);
			$this->assertTrue((sizeof($head) > 1) , "Article head successfully received");
			
			$body = $NNTPClient->GetArticleBody($group["first"]);
			$this->assertTrue(strlen($body) > 0 , "Article body successfully received");
			
			$NNTPClient->Disconnect();
        }
    }

?>