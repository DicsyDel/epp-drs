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
     * @package NET_API
     * @subpackage MySpace
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     */

	Core::Load("NET/HTTP/HTTPClient");
	
    /**
     * @name MySpaceProfile
     * @category Libwebta
     * @package NET_API
     * @subpackage MySpace
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */	    
	class MySpaceProfile extends HTTPClient
	{
		
		/**
		 * Template for member profile URL
		 * 
		 */
		const PROFILE_URL_TEMPLATE = 'http://profile.myspace.com/index.cfm?fuseaction=user.viewprofile&friendid=%d';
		
		/**
		 * User account ID on myspace website
		 * 
		 * @var integer
		 * @access private
		 */
		private $AccountID;
		
		
		/**
		 * Profile information - headline
		 * 
		 * @var string
		 */
		public $Headline;
		
		/**
		 * Profile information - sex
		 * 
		 * @var string
		 */
		public $Sex;
		
		/**
		 * Profile information - user age
		 * 
		 * @var integer
		 */
		public $Age;
		
		/**
		 * Profile information - city
		 * 
		 * @var string
		 */
		public $City;
		
		/**
		 * Profile information - state
		 * 
		 * @var string
		 */
		public $State;
		
		/**
		 * Profile information - country
		 * 
		 * @var string
		 */
		public $Country;
		
		/**
		 * Profile information - count of profile views
		 * 
		 * @var integer
		 */
		public $ProfileViews;
		
		/**
		 * Profile information - last login date
		 * 
		 * @var string
		 */
		public $LastLogin;
		
		/**
		 * Profile information - online status
		 * 
		 * @var integer|bool 1 if user online
		 */
		public $Online;
		
		
		/**
		 * Class constructor. Fetch information from MySpace profile.
		 * 
		 * @param integer $accountid MySpace user account ID
		 * @param resource $curl CURL handler. Needed for session saving in
		 * several queries to the server
		 */
		function __construct($accountid, $curl = null)
		{
			parent::__construct($curl);
			
			$ProfileURL = sprintf(self::PROFILE_URL_TEMPLATE, $accountid);
			
			$this->Fetch($ProfileURL);
			$this->AccountID = $accountid;
		}
		
		
		/**
		 * Get first match from content and supplied patterns
		 * 
		 * @param array $patterns Array of regexp patterns
		 * @param bool $add_tag_depth Flag for adding depth to tags in search
		 * content
		 * @access public
		 */
		function GetMatches(array $patterns, $add_tag_depth = false)
		{
			if (!$this->Result) return false;
			
			if ($add_tag_depth)
				$this->Result = HTMLParser::AddTagDepth($this->Result);
				
			foreach($patterns as $pattern)
			{
				if (preg_match($pattern, $this->Result, $matches))
					return ($add_tag_depth) ? HTMLParser::RemoveTagDepth(trim($matches[2])) : trim($matches[1]);
			}
			
			if ($add_tag_depth)
				$this->Result = HTMLParser::RemoveTagDepth($this->Result);
			
			return false;
		}
		
		
		
		/**
		 * Get user last login
		 * 
		 * @return string last user login date
		 * @access public
		 */
		function GetLastLogin() 
		{
			$patterns = array(
				'/last login:[\t\s\n\r]*([0-9\/]+)/msi', 
				'/last login:[^\w\d]+([0-9\/]+)/msi',
				'/last login:.*?([0-9\/]+)/msi'
			);
			
			$last_login = $this->GetMatches($patterns);
			
			if ($last_login) 
			{
				$tmp = explode("/", $last_login, 3);
				$last_login = "{$tmp[2]}-{$tmp[0]}-{$tmp[1]} 00:00:00";
			}
			
			return $last_login;
		}
		
		
		/**
		 * Get user city
		 * 
		 * @return string User city
		 * @access public
		 */
		function GetCity()
		{
			if (!$this->PersonalInfoBlock) $this->GetPeronalDetailsBlock();
			
			return $this->City;
		}
		
		/**
		 * Get user state
		 * 
		 * @return string User state
		 * @access public
		 */
		function GetState()
		{
			if (!$this->PersonalInfoBlock) $this->GetPeronalDetailsBlock();
			
			return $this->State;
		}
		
		
		/**
		 * Get profile owner name
		 * 
		 * @return string user name
		 * @access public
		 */
		function GetName()
		{
			$patterns = array(
				'/class\s*\=\s*(?:\'|\")nametext[^\>]+\>([^\<]+)\</msi'
			);
			
			return $this->GetMatches($patterns);
		}
		
		
		/**
		 * Parse personal details from content
		 * 
		 * @return array Profile details
		 * Example of output array
		 * <code>
		 * 	[Headline] => "feel easy to live"
		 *  [City]  => www.fakaofo.tk - Fakaofo
		 *  [State]  => State Info
		 *  [Country]  => Tokelau
		 *  [Last  Login] => 11/27/2006
		 *  [Age]  => 25
		 *  [Sex]  => Female
		 *  [Profile  Views] =>
		 *  [Online]  => 1
		 *  [Status]  => Single
		 *  [Here  for] => Networking, Dating, Friends
		 *  [Orientation]  => Not Sure
		 *  [Hometown]  => <a href="http://www.fakaofo.tk/">Fakaofo</a>
		 *  [Body  type] => 5' 2" / Athletic
		 *  [Ethnicity]  => Pacific Islander
		 *  [Religion]  => Other
		 *  [Zodiac  Sign] => <a href="http://collect.myspace.com/index.cfm?
		 * fuseaction=horoscope&sign=11&MyToken=5e6d132b-f9da-4df1-880d-
		 * 06d4a19d0fd2">Aquarius</a>
		 *  [Smoke  / Drink] => Yes / Yes
		 *  [Children]  => Love kids, but not for me
		 *  [Education]  => High school
		 *  [Occupation]  => Stranger
		 *  [General]  => 1. sex 2. drugs 3. rock & roll
		 *  [Music]  => rap, classic music
		 *  [Movies]  => matrix
		 *  [Television]  => mtv
		 *  [Books]  => udar russkix bogov
		 *  [Heroes]  => no heros
		 * </code>
		 * 
		 * @access public
		 */
		function GetPersonalDetails()
		{
			$details = array();
			
	
			$patterns = array(
				'/class\s*\=\s*(?:\'|\")nametext.*?\<table[^\>]+\>(.*?)\<\/table\>/msi'
			);
			
			//
			// Match personal info from top block
			//
			$this->PersonalInfoBlock = $this->GetMatches($patterns);
			
			if (preg_match('/\<td[^\>]+width\s*\=\s*\"193\"[^\>]*\>(.*?)\<\/td\>/msi', $this->PersonalInfoBlock, $matches))
			{
				$entries = preg_split('/\<br[^\>]*\>/ims', $matches[1]);
				
				//
				// parse entries
				//
				if ($entries)
				{
					$entries = array_map('trim', $entries);
					
					if (!in_array(trim($entries[2]), array('Male', 'Female')))
					{
						$this->Headline = $entries[1];
						$location = explode(",", $entries[3], 2);
						$this->City = trim($location[0]);
						$this->State = trim($location[1]);
						$this->Country = $entries[4];
						$this->ProfileViews = preg_replace('/[^0-9]+/msi', '', $entries[6]);
						$this->LastLogin = preg_replace('/[^0-9\/]+/msi', '', $entries[10]);
						if (stristr($entries[7], 'OnlineNow') || stristr($entries[8], 'OnlineNow'))
							$this->Online = true;
					}
					else
					{
						$this->Headline = $entries[0];
						$this->ProfileViews = preg_replace('/[^0-9]+/msi', '', $entries[1]);
						$this->Sex = $entries[2];
						$this->Age = preg_replace('/[^0-9]+/msi', '', $entries[3]);
						$location = explode(",", $entries[4], 2);
						$this->City = trim($location[0]);
						$this->State = trim($location[1]);
						$this->Country = $entries[5];
						$this->LastLogin = preg_replace('/[^0-9\/]+/msi', '', $entries[8]);
						if (stristr($entries[7], 'OnlineNow'))
							$this->Online = true;
					}
					
					$details = array(
						'Headline' 	=> $this->Headline,
						'City'		=> $this->City,
						'State' 	=> $this->State,
						'Country' 	=> $this->Country,
						'Last Login' => $this->LastLogin,
						'Age'		=> $this->Age,
						'Sex'		=> $this->Sex,
						'Profile Views'		=> $this->ProfileViews,
						'Online'	=> $this->Online
					);
				}
			}
			
			
			
			$patterns = array(
				'/\'s Details.*?\<table[^\>]+\>(.*?)\<\/table/msi'
			);
		
			//
			// Match personal info from middle block - Name's Details
			//
			$this->DetailsBlock = $this->GetMatches($patterns);
			
			if ($this->DetailsBlock)
			{
				$entries = preg_split('/\<\/tr[^\>]*\>/ims', $this->DetailsBlock);
				
				//
				// parse entries
				//
				if ($entries)
				{
					$entries = array_map('trim', $entries);
					
					foreach($entries as $entry)
					{
						if (!$entry) continue;
						if (preg_match('/\<span[^\>]+\>([^\<]+)\<\/span.*?\<td[^\>]+\>(.*?)\<\/td/msi', $entry, $match))
						{
							$key = str_replace(':', '', trim($match[1]));
							$details[$key] = HTMLParser::StripLinks(trim($match[2]));
						}
					}
					
				} // end if entries
			}
			


			$patterns = array(
				'/\'s Interests.*?\<([0-9]+)table[^\>]+\>(.*?)\<\\1\/table/msi'
			);
			
			
			//
			// Match Interests block information
			//
			$this->InterestsBlock = $this->GetMatches($patterns, true);
			
			if ($this->InterestsBlock)
			{
				$entries = preg_split('/<tr[\s\t]+id\=[\'\"a-z]+Row[^>]*>/ims', $this->InterestsBlock);
				
				//
				// parse entries
				//
				if ($entries)
				{
					$entries = array_map('trim', $entries);
					
					foreach($entries as &$entry)
					{
						if (!$entry) continue;
						
						if (preg_match('/<[0-9]+span[^>]+>([^<]+)<[0-9]+\/span.*?<([0-9]+)td[^>]+>(.*?)<\\2\/td/msi', HTMLParser::AddTagDepth($entry), $match))
						{
							$match[3] = HTMLParser::RemoveTagDepth(trim($match[3]));
							$key = HTMLParser::RemoveTagDepth(trim($match[1]));
							$key = str_replace(':', '', $key);
							
							// remove link to all groups
							if ($key == 'Groups')
								$match[3] = preg_replace('/\<br[^\>]*\>\<br[^\>]*\>.*$/msi', '', $match[3]);
							
							$details[$key] = HTMLParser::StripBlankLinks(trim($match[3]));
						}
					}
					
				} // end if entries
			}
			
			
			
			return $details;
		}
		
		/**
		 * Gets information from about me block
		 * 
		 */
		function GetAboutMeInfo()
		{
			
			$patterns = array(
				'/<([0-9]+)td[^>]+>[\n\r\s\t]*<[0-9]+span[^>]+>[\n\r\s\t]*About me\:[\n\r\s\t]*<[0-9]+\/span>(?:<[0-9]+br[^>]*>)?(.*?)<\\1\/td>/msi',
				'/<[0-9]+span[^>]+orangetext15[^>]+>About\b.*?<[0-9]+td.*?<([0-9]+)td[^>]+>(.*?)<\\1\/td>/msi'
			);
			
			$content = HTMLParser::StripStyles($this->GetMatches($patterns, true));
			return HTMLParser::StripAds($content);
		}
		
		/**
		 * Get information from  `Who I'd like to meet:` block
		 */
		function GetWhoILikeToMeetInfo()
		{
			$patterns = array(
				'/<([0-9]+)td[^>]+>[\n\r\s\t]*<[0-9]+span[^>]+>[\n\r\s\t]*Who I\\\'d like to meet\:[\s\t\n\r]*<[0-9]+\/span>(?:<[0-9]+br[^>]*>)?(.*?)<\\1\/td>/msi'
			);
			
			$content = HTMLParser::StripStyles($this->GetMatches($patterns, true));
			return HTMLParser::StripAds($content);
		}
	}
