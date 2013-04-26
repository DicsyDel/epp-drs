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
     * @subpackage Google
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("NET/API/Google/class.GoogleService.php");
	Core::Load("NET/API/Google/class.GoogleCalendar.php");
	Core::Load("NET/API/Google/class.GoogleCalendarEvent.php");
	
	Core::Load("NET/API/Google/class.Picasa.php");
	
	/**
	 * @category   LibWebta
     * @package    NET_API
     * @subpackage Google
     * @name NET_API_Google_Test
	 *
	 */
	class NET_API_Google_Test extends UnitTestCase 
	{
        function NET_API_Google_Test() 
        {
            $this->UnitTestCase('NET/API/GoogleCalendar test');
        }
        
        function _GoogleCalendarTests()
        {
            $calendar = new GoogleCalendar();
			
			if (!$_SESSION["GoogleAuthToken"])
			{
				// Try login to system
				$login = $calendar->Authenticate("dicsydel@gmail.com", "GhNjkdFtg4", "cl");
				$this->assertTrue($login, "Success login to google system");
			}
			
			$event = new GoogleCalendarEvent("Test event", "Test Description", time());
			$res = $calendar->AddEvent($event);
			$this->assertTrue($res, "Event successfully added");
        }
        
        function _PicasaTests()
        {
            $picasa = new Picasa();
			// Try login to system
			$login = $picasa->Authenticate("dicsydel", "GhNjkdFtg4", "lh2");
			$this->assertTrue($login, "Success login to google system");
			
			if ($login)
			{
    			$picasa->Username = "dicsydel";
    			
    			$res = $picasa->GetUserInfo();
    			$this->assertTrue($res["username"], "User info received");
    			if (!$res)
    			     print $picasa->GetLastWarning()."<br>";
    			
    		    $res = $picasa->GetAlbums();
    		    
    		    var_dump($res[0]);
    		    
    			$this->assertTrue(is_array($res), "Albums list received");
    			if (!$res)
    			     print $picasa->GetLastWarning()."<br>";
    			     
    			$res = $picasa->EditPhoto($res[0]['photos'][0]["id"], $res[0]['id'], $res[0]['photos'][0]["version"], "test123");
    			$this->assertTrue($res, "Photo metadata updated");
    			if (!$res)
    			     print $picasa->GetLastWarning()."<br>";
			}
        }
        
        function testGoogleService() 
        {
            $this->_PicasaTests();
           
        }
        
    }


?>