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
	
	Core::Load("NET/API/Google/class.GoogleCalendar.php");
	
	/**
     * @name       GoogleCalendarEvent
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Google
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class GoogleCalendarEvent extends Core
	{
		/**
		 * Event title
		 *
		 * @var string
		 */
		private $Title;
		
		/**
		 * Event description
		 *
		 * @var string
		 */
		private $Description;
		
		/**
		 * Event status
		 *
		 * @var string (canceled, confirmed, tentative)
		 */
		private $Status;
		
		/**
		 * Event transparency
		 *
		 * @var string (opaque, transparent)
		 */
		private $Transparency;
		
		/**
		 * Event start time
		 *
		 * @var string
		 */
		private $StartTime;
		
		/**
		 * Event end time
		 *
		 * @var string
		 */
		private $EndTime;
		
		/**
		 * Constructor
		 *
		 * @param string $title
		 * @param string $description
		 * @param string $starttime
		 * @param string $endtime
		 */
		function __construct($title, $description, $starttime, $endtime = false)
		{
			$this->Title = $title;	
			$this->Description = $description;
			
			$this->StartTime = date("Y-m-d\TH:i:s", $starttime);
			
			if ($endtime)
				$this->EndTime = date("Y-m-d\TH:i:s", $endtime);
			else 
				$this->EndTime = false;
			
			$this->Status = "confirmed";
			
			$this->Transparency = "opaque";
		}
		
		/**
		 * Retrunx XML Event times
		 *
		 * @return string
		 */
		private function GetTimeXML()
		{
			if (!$this->EndTime)
				return "<gd:when startTime='{$this->StartTime}' endTime='{$this->StartTime}'></gd:when>";
			else 
				return "<gd:when startTime='{$this->StartTime}' endTime='{$this->EndTime}' /></gd:when>";
		}
		
		/**
		 * Return XML representation of event
		 *
		 * @return string
		 */
		function __toString()
		{
			$event  = "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:gd='http://schemas.google.com/g/2005'>";
			$event .= "<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/g/2005#event' />";
			
			// Add title
			$event .= "<title type='text'>{$this->Title}</title>";
			// Add description
			$event .= "<content type='text'>{$this->Description}</content>";
			
			// Add time
			$event .= $this->GetTimeXML();
			
			// Set Event transparency
			$event .= "<gd:transparency
					   	value='http://schemas.google.com/g/2005#event.{$this->Transparency}'>
					   </gd:transparency>";

			
					
			// Set Event status
			$event .="<gd:eventStatus
					    value='http://schemas.google.com/g/2005#event.{$this->Status}'>
					  </gd:eventStatus>";
			$event .= "</entry>";
			
			return $event;
		}
		
	}
?>