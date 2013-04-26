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
     * @subpackage Blogger
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 

	Core::Load("NET/HTTP/HTTPClient");
	Core::Load("Data/Text/HTMLParser");
	
	/**
     * @name BloggerProfile
     * @category Libwebta
     * @package NET_API
     * @subpackage Blogger
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class BloggerProfile extends HTTPClient
	{
		
		/**
		 * Member Profile URL Template
		 * 
		 */
		const PROFILE_URL = 'http://www.blogger.com/profile/%s';
		
		
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
			
			$ProfileURL = sprintf(self::PROFILE_URL, $accountid);

			$this->SetHeaders(array('Accept-Language: en-us,en;q=0.5'));
			
			$this->SetTimeouts(60, 30);
			$this->UseRedirects(true, 5);
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
		 */
		function GetPersonalDetails()
		{
			if (!$this->Result) return;
			
			$details = array();
						
			$pattern = '/<li>[\s\t\r\n]*<strong>[\s\t\r\n]*(age|gender|industry|occupation|location)\:[\s\t\r\n]*<\/strong>[\s\t\r\n]*(.*?)[\s\t\r\n]*<\/li>/msi';
			
			preg_match_all($pattern, $this->Result, $matches, PREG_SET_ORDER);
			 			
			foreach($matches as $match)
			{
				switch ($match[1])
				{
					case 'Age':
						$details['age'] = $match[2];
						break;
					
					case 'Gender':
						$details['gender'] = $match[2];
						break;
					
					case 'Location':
						$locations = explode(":", $match[2]);
						$locations = array_map('trim', $locations);
						
						$details['city'] = HTMLParser::StripLinks($locations[0]);
						$details['state'] = HTMLParser::StripLinks($locations[1]);
						$details['country'] = HTMLParser::StripLinks($locations[2]);
						break;
					
					case 'Industry':
						$details['industry'] = $match[2];
						break;
					
					case 'Occupation':
						$details['occupation'] = $match[2];
						break;
					
				}
			}
			
			
			$pattern = '/<h2>(About\sMe|Interests)<\/h2>[\s\t\r\n]*<([a-z]+)\b[^>]*>(.*?)<\/\\2>/msi';
			preg_match_all($pattern, $this->Result, $matches, PREG_SET_ORDER);
			 			
			foreach($matches as $match)
			{
				switch ($match[1])
				{
					case 'About Me':
						$details['aboutme'] = $match[3];
						break;
					
					// #todo - separators needed
					case 'Interests':
						$details['interests'] = HTMLParser::StripTags($match[3]);
						break;
				}
			}			

			return $details;
		}
		
		
	}
