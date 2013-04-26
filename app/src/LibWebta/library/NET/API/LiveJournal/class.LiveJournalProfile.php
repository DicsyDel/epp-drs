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
     * @subpackage LiveJournal
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     */

	Core::Load("NET/HTTP/HTTPClient");
	Core::Load("Data/Text/HTMLParser");
	
	/**
     * @name LiveJournalProfile
     * @category Libwebta
     * @package NET_API
     * @subpackage LiveJournal
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class LiveJournalProfile extends HTTPClient
	{
		
		/**
		 * Member Profile URL Template
		 * 
		 */
		const PROFILE_URL_TEMPLATE = 'http://users.livejournal.com/%s/profile';
		
		/**
		 * Interests in a line separated format. Available at the URL 
		 * 
		 */
		const INTERESTS_URL_TEMPLATE = 'http://www.livejournal.com/misc/interestdata.bml?user=%s';
		
		private $AccountID;
		
		
		/**
		 * Constructor
		 * 
		 * @var int account ID
		 * @var resource CURL handler
		 */
		function __construct($accountid, $curl = null)
		{
			parent::__construct($curl);
			
			$ProfileURL = sprintf(self::PROFILE_URL_TEMPLATE, $accountid);
			
			$this->SetHeaders(array('Accept-Language: en-us,en;q=0.5'));
			
			$this->SetTimeouts(60, 30);
			$this->Fetch($ProfileURL);
			$this->AccountID = $accountid;
		}
		
		
		/**
		 * Get first match from content and supplied patterns
		 * 
		 * @var array patterns
		 */
		function GetMatches($patterns, $add_tag_depth = false)
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
		 * Parse personal details from content
		 * 
		 * @return array profile details
		 * sample
		 *   [userpic]   => http://www.livejournal.com/userpic/38353247/8981002
		 *   [name]   => Natalie
		 *   [website]   => http://www.myspace.com/aggressiva
		 *   [city]   => La Verne
		 *   [state]   => California
		 *   [country]   => United States
		 *   [birthday] => 1971-12-15
		 *   [aboutme]	=> party ... I just believe in parties!
		 */
		function GetPersonalDetails()
		{
			$details = array();
						
			$pattern = '/><[0-9]*b>(user|name|website|location|birthdate|gizmo\/lj talk|bio|e\-mail)\:<[0-9]*\/b><[0-9]*\/td><([0-9]*)td[^>]*>(.*?)<\\2\/td>/msi';
			
			$this->Result = HTMLParser::AddTagDepth($this->Result);
			preg_match_all($pattern, $this->Result, $matches, PREG_SET_ORDER);
			$this->Result = HTMLParser::RemoveTagDepth($this->Result);
			
			foreach($matches as $match)
			{
				$match[2] = HTMLParser::RemoveTagDepth(trim($match[3]));
				
				switch ($match[1])
				{
					case 'Name':
						$details['name'] = $match[2];
						break;
					
					case 'Website':
						if (preg_match("/href=(\'|\")(.*?)\\1/", $match[2], $match2))
						{
							$details['website'] = $match2[2];
						}
						break;
					
					case 'Location':
						preg_match_all("/loc\_(ci|st|cn)\=[^>\&]+>(.*?)</msi", $match[2], $match2, PREG_SET_ORDER);
						
						foreach($match2 as $res)
						{
							if ($res[1] == 'ci')
								$details['city'] = $res[2];
							elseif ($res[1] == 'st')
								$details['state'] = $res[2];
							elseif ($res[1] == 'cn')
								$details['country'] = $res[2];
						}
						break;
					
					case 'Bio':
						$details['aboutme'] = $match[2];
						break;
					
					case 'Birthdate':
						$details['birthday'] = $match[2];
						break;
					
					case 'E-mail':
						$details['email'] = HTMLParser::StripTags($match[2]);
						
						break;
				}
			}
			
			
			if (preg_match("/http\:\/\/([a-z0-9\-]+\.)+[a-z0-9]{2,6}\/userpic\/[0-9]+\/[0-9]+/msi", $this->Result, $matches))
			{
				$details['userpic'] = $matches[0];
			}
			
			//echo '<xmp>'; print_r($details); echo '</xmp>'; exit;
			return $details;
		}
		
		
		/**
		 * Get user interests
		 */
		function GetInterests()
		{
			$tmp_result = $this->Result;
			$InterestsURL = sprintf(self::INTERESTS_URL_TEMPLATE, $this->AccountID);
			
			$interests = array();
			$this->SetTimeouts(30, 10);
			$this->Fetch($InterestsURL);
			
			preg_match_all("/([0-9]+\s){2}(.*?)\n/msi", $this->Result, $matches, PREG_SET_ORDER);
			foreach($matches as $match)
			{
				array_push($interests, $match[2]);
			}
			
			$this->Result = $tmp_result;
			return implode(", ", $interests);
		}
	}
