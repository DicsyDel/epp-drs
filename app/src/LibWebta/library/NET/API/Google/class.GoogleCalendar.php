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
     */
	
	Core::Load("NET/API/Google/class.GoogleService.php");
	
	/**
     * @name       GoogleCalendar
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Google
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class GoogleCalendar extends GoogleService 
	{
		
		/**
		 * Constuct
		 *
		 */
		function __construct()
		{
			parent::__construct();
		}
		
		/**
		 * Add event to calendar
		 *
		 * @param GoogleCalendarEvent $event
		 */
		function AddEvent($event)
		{
			$req = $this->Request(	"http://www.google.com/calendar/feeds/default/private/full", 
									$event->__toString(),
									array("Content-type: application/atom+xml"),
									"POST",
									0
								);
			// Get confirm URL
			preg_match("/Location: ([^\n]+)\n/i", $req, $matches);			
			$confirm_url = $matches[1];
					
			// Get result data
			$result = $this->Request(trim($confirm_url), $event->__toString(), array("Content-type: application/atom+xml"), "POST");

			return stristr($result, "201 Created");
		}
	}
?>