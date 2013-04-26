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
     * @subpackage Facebook
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     * @filesource
     */

	$base = dirname(__FILE__);
		
	Core::Load("NET/API/Facebook/class.Facebook.php");
	
	/**
	 * @category   LibWebta
     * @package NET_API
     * @subpackage Facebook
     * @name NET_API_Facebook_Test
	 *
	 */
	class NET_API_Facebook_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('NET/API/Facebook test');
        }
        
        function testNET_API_Facebook() 
        {
			
			$Facebook = new Facebook();
			
			try { 
				$Facebook->SetAuthData("d3cc71aea881e3d8e0b93ff1ebb67f0d", "879d76bf7a7e265d17c1de2079de48ea");
				// $Facebook->Login("ak@webta.net", "plj,ysq");
				// $Facebook->Login("test@test.com", "password1");
				// $Facebook->Login("s_jack@mail.ru", "password1");
				$Facebook->Login("teamcambier@yahoo.com", "twins02");
				
				$this->assertTrue($Facebook->LoggedIn, "Login to `Facebook.com` failed");
				
				$friends = $Facebook->GetFriendsList();
				$this->assertTrue($friends, "No friends found for account");
				
				$profile = $Facebook->GetProfileByID($friends[0]);
				$this->assertTrue($profile, "No profile found");
				
				if ($profile)
				{
					$result = $profile->GetPersonalDetails();
					$this->assertTrue($result[$profile->AccountID]['name'], "No user info found for first friend");
				}
				
			} catch (FacebookAPIException $e) {
				$this->assertTrue(false, "Exception was occured");
			}
        }
        
    }


?>