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
     * @subpackage LinkedIn
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     * @filesource
     */

	$base = dirname(__FILE__);
		
	Core::Load("NET/API/LinkedIn/LinkedIn");
	Core::Load("NET/API/LinkedIn/LinkedInProfile");
	
	/**
	 * @category   LibWebta
     * @package NET_API
     * @subpackage LinkedIn
     * @name NET_API_LinkedIn_Test
	 *
	 */
	class NET_API_LinkedIn_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('NET/API/LinkedIn test');
        }
        
        function testNET_API_LinkedIn() 
        {
			
			$LinkedIn = new LinkedIn();
			
			$res = $LinkedIn->Login('test@test.com', 'dfgsd');
			$this->assertTrue($res, "Can't login to LinkedIn");
			
			$Profile = $LinkedIn->GetProfileByID(8418679); //7202602);
			$this->assertTrue($Profile, "Can't get LinkedIn profile");
			
			$res = $Profile->GetPersonalDetails();
			$this->assertTrue($res && count($res), "Can't get LinkedIn personal details");
			
			$res = $Profile->GetInterests();
			$this->assertTrue($res, "Can't get LinkedIn interests");
			
			$res = $Profile->GetEmailAddress();
			$this->assertTrue($res, "Can't get LinkedIn email address");
			
			$res = $Profile->GetJobTitle();
			$this->assertTrue($res, "Can't get LinkedIn job title");
			
			$res = $Profile->GetExperience();
			$this->assertTrue($res, "Can't get LinkedIn experience block");

			$res = $Profile->GetEducation();
			$this->assertTrue($res, "Can't get LinkedIn education block");
			
			
			$connections = $LinkedIn->GetConnectionsList();
			$this->assertTrue(count($connections), "No connections found");
			
        }
        
        
        function testNET_API_LinkedInProfile()
        {
        	$Profile = new LinkedInProfile(8418679);
			$this->assertTrue($Profile, "Can't create profile object");
        	
        }
    }


?>