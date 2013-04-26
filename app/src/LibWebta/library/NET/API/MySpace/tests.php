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
     * @subpackage MySpace
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     * @filesource
     */

	$base = dirname(__FILE__);
		
	Core::Load("NET/API/MySpace/class.MySpace.php");
	
	/**
	 * @name NET_API_MySpace_Test
	 * @category   LibWebta
     * @package NET_API
     * @subpackage MySpace
	 */
	class NET_API_MySpace_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('NET/API/MySpace test');
        }
        
        function testNET_API_MySpace() 
        {
			
			$MySpace = new MySpace();
			
			
			// login to Kokaina profile
			$res = $MySpace->Login('kokaina@ua.fm', 'sdfsdfs');
			$this->assertTrue($res, "Can't login to MySpace");

			// get account number
			$res = $MySpace->GetAccountID();
			$this->assertTrue($res, "Can't get user ID");
			
			// get friends list
			// need a lot of time (for all pages)
			$res = $MySpace->GetFriendsList2(129591806); // 1520 records (38 pages) on 78646455
			$this->assertTrue($res && count($res), "Can't get friends list from myspace");
			if (!$res) var_dump($MySpace->Result);
			
			// get blog entries
			$res = $MySpace->GetBlogEntries();
			$this->assertTrue($res, "Can't get blog entries from myspace");
			if (!$res) var_dump($MySpace->Result);

			// get profile object
			$Profile = $MySpace->GetProfileByID();
			$this->assertTrue($Profile, "Can't get user profile");
			if (!$Profile) var_dump($MySpace->Result);
			
			// get country from profile object
			$Profile->GetPersonalDetails();
			$this->assertEqual($Profile->Country, 'Tokelau', "Country isn't Takelau");
			
			/* end of tests*/
			
			
        }
        
        function testNET_API_MySpaceProfile()
        {
			// get Tom profile
			$Profile = new MySpaceProfile(6221);
			$this->assertTrue($Profile, "Can't get Tom profile");
			
			// get personal details
			$details = $Profile->GetPersonalDetails();
			$this->assertEqual($Profile->Country, 'United States', "Country isn't United States");
			
			// get account name 
			$name = $Profile->GetName();
			$this->assertEqual($name, 'Tom', "It's not a Tom!");
			
			// get last login date
			$lastLogin = $Profile->GetLastLogin();
			$this->assertEqual($lastLogin, date("Y-m-d 00:00:00"), "Last login date is not today!");
			/* end profile tests */
        }
        
    }


?>