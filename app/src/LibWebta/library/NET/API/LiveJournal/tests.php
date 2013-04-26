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
     * @subpackage LiveJournal
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     * @filesource
     */

	$base = dirname(__FILE__);
		
	Core::Load("NET/API/LiveJournal/class.LiveJournal.php");
	
	/**
	 * @category   LibWebta
     * @package NET_API
     * @subpackage LiveJournal
     * @name NET_API_LiveJournal_Test
	 *
	 */
	class NET_API_LiveJournal_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('NET/API/LiveJournal test');
        }
        
        function testNET_API_LiveJournal() 
        {
			
			$LiveJournal = new LiveJournal();
			
			$LiveJournal->Login('test', 'sdfsdfsd');
			$Profile = $LiveJournal->GetProfileByID("aggressiva");
			$this->assertTrue($Profile, "No profile found for `aggressiva`");
			
			$details = $Profile->GetPersonalDetails();
			$this->assertEqual($details['birthday'], '1971-12-15', "Wrong birthday for `aggressiva`");
			
			$interests = $Profile->GetInterests();
			$this->assertTrue(count($interests) > 0, "No interests returned");
			
			/* end of tests*/
        }
        
    }


?>