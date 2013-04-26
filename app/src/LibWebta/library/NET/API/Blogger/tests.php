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
     * @subpackage Blogger
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */ 

	$base = dirname(__FILE__);
		
	Core::Load("NET/API/Blogger/Blogger");
	
	/**
	 * @category   LibWebta
     * @package NET_API
     * @subpackage Blogger
     * @name NET_API_Blogger_Test
	 *
	 */
	class NET_API_Blogger_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('NET/API/Blogger test');
        }
        
        function testNET_API_Blogger() 
        {
			
			$Blogger = new Blogger();
			
			$Blogger->Login('bcarrdba');
			$res = $Blogger->GetBlogEntries();
			$this->assertTrue(count($res), 'No blogs found');
			
			$profile = $Blogger->GetProfileByID();
			
			$this->assertTrue($profile, 'No profile received');
			
			if ($profile)
			{
				$details = $profile->GetPersonalDetails();
				$this->assertTrue($details, 'No details received');
			}
			
			/** end tests */
        }
        
    }


?>