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
     * @subpackage Bookings
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	require_once("class.Bookings.php");
	
	/**
	 * @category   LibWebta
     * @package    NET_API
     * @subpackage Bookings
	 * @name NET_API_Bookings_Test
	 */
	class NET_API_Bookings_Test extends UnitTestCase 
	{
        function NET_API_Bookings_Test() 
        {
            $this->UnitTestCase('NET/API/Bookings test');
        }
        
        function testBookings() 
        {
			
			//
			// load()
			//
			$BookingsAPI = new Bookings("ws.bookings.nl", "/xml-rpc", "username", "password");

			
			// Get countries
			$cc = $BookingsAPI->GetCountryList();
			$this->assertTrue(count($cc) > 0, "Get countries list");	
			
			// Get cities
			$cities = $BookingsAPI->GetCityList($cc[0]["code"]);
			$this->assertTrue(count($cities) > 0, "Get cities list");
			
			// get Hotel list
			$hotels = $BookingsAPI->GetHotelList($cities[0]["city_id"]);
			$this->assertTrue(count($hotels) > 0, "Get hotels list");
			
			// get Hotel details
			$hotel_details = $BookingsAPI->GetHotelDetails($hotels[0]["id"]);
			$this->assertTrue((is_array($hotel_details) && $hotel_details["lang"]["maxrate"]), "Get hotel details");
			
			// Get rooms list
			$rooms = $BookingsAPI->GetRoomsList($hotels[0]["id"]);
			$this->assertTrue(count($rooms) > 0, "Get rooms list");
			
			// get Room type
			$type = $BookingsAPI->GetRoomType($rooms[0]["roomtype_id"]);
			$this->assertTrue(count($type) > 0, "Get roomtype name");
			
			$id = $BookingsAPI->GetHotelID($hotels[0]["name"], $cities[0]["city_id"]);
			$this->assertTrue($id, "Get Hotel id by name");
			
			$id = $BookingsAPI->GetCityID($cities[0]["name"], $cc[0]["code"]);
			$this->assertTrue($id, "Get City id by name");
			
			$id = $BookingsAPI->GetCountryCode($cc[0]["name"]);
			$this->assertTrue($id, "Get Country code by name");
			
			$fac = $BookingsAPI->GetHotelFacilities($hotels[2]["id"]);
			$this->assertTrue(count($fac) > 0, "Get Hotel Facilities");
			
			// get Room info
        }
        
    }


?>