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
     * @subpackage LinkedIn
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     */

	Core::Load("NET/HTTP/HTTPClient");
	Core::Load("Data/Text/HTMLParser");
	
	/**
     * @name LinkedInProfile
     * @category Libwebta
     * @package NET_API
     * @subpackage LinkedIn
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class LinkedInProfile extends HTTPClient
	{
		
		/**
		 * Member Profile URL Template
		 * 
		 */
		const PROFILE_URL_TEMPLATE = 'http://www.linkedin.com/profile?viewProfile=&key=%d';

		/**
		 * MySpace account ID
		 */
		private $AccountID;
		public $Details;
		
		
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
		 * @var array patterns
		 */
		public function GetMatches(array $patterns)
		{
			if (!$this->Result) return false;
			
			foreach($patterns as $pattern)
			{
				if (preg_match($pattern, $this->Result, $matches))
					return trim($matches[1]);
			}
			
			return false;
		}
		
		
		/**
		 * Get user interests
		 */
		public function GetInterests()
		{
			$patterns = array('/\<h3\>Interests\:\<\/h3\>[\s\t\n\r]+\<p[^\>]*\>(.*?)\<\/p\>/msi');
			$interests = $this->GetMatches($patterns);
			
			return HTMLParser::StripLinks($interests);
		}
		
		
		/**
		 * Get common personal details
		 */
		public function GetPersonalDetails()
		{
			$details = array();
			
			preg_match_all('/name\=\"(fullName|headline|location|webProfileURL)\"[^\>]*\>([^\<]*)\</msi', $this->Result, $matches, PREG_SET_ORDER);
			
			if ($matches)
			{
				foreach($matches as $match)
					$details[$match[1]] = trim($match[2]);
			}
			
			preg_match('/<title[^>]*>[^\:]*[\:\s]*(.*?)<\/title[^>]*>/msi', $this->Result, $matches);
			if ($matches[1])
				$details['fullName'] = $matches[1];
			
			$this->Details = $details;
			return $this->Details;
		}
		
		
		/**
		 * Get email address
		 */
		public function GetEmailAddress()
		{
			return $this->GetMatches(array('/mailto\:(.*?)\"/msi'));
		}
		
		
		/**
		 * Get last job title
		 */
		public function GetJobTitle()
		{
			$patterns = array('/postitle\"\s+name\=\"overviewpos\".*?\<h3[^\>]*\>(.*?)\<\/h3\>/msi');
			$title = $this->GetMatches($patterns);
			
			return HTMLParser::StripLinks($title);
		}
		
		/**
		 * Get array of experiences
		 */
		public function GetExperience()
		{
			$details = array();
			$patterns = array('/id\=\"experience\"[^\>]*\>(.*?)\<\/div\>[\s\t\n\r]+\<h2/msi');
			
			// get experience block
			$Experience = $this->GetMatches($patterns);
			$chunks = preg_split("/\<div[^\>]+name\=\"position\"[^\>]*\>/msi", $Experience);
			
			if ($chunks)
			{
				unset($chunks[0]);
				$chunks = array_values($chunks);
				foreach($chunks as $chunk)
				{
					preg_match_all('/(?:class|name)\=\"(title|company|orgstats|summary)\"[^\>]*\>([^\<]*)\</msi', $chunk, $matches, PREG_SET_ORDER);
					
					if ($matches)
					{
						$tmp = array();
						foreach($matches as $match)
							$tmp[$match[1]] = HTMLParser::StripLinks($match[2]);
							
						array_push($details, $tmp);
							
					}
					
				} // end for each chunks
			}
			
			$template = "";
			
			if ($details)
			{
				foreach($details as $detail)
				{
					$template .= "<div class='experience'>";
					$template .= "<h4>{$detail[title]}</h4>";
					$template .= "<strong>{$detail[company]}</strong>";
					$template .= "<p class='orgstats'>{$detail[orgstats]}</p>";
					$template .= "<p class='descr'>{$detail[summary]}</p>";
					$template .= "</div>";
				}
			}

			return $template;
		}
		
		/**
		 * Get array of educations
		 * 
		 */
		public function GetEducation()
		{
			$patterns = array('/\<div[\s\t]+name\=\"education\"[\s\t]+id\=\"[a-z0-9]+\"[^\>]*\>(.*?)\<\/div\>[\s\t\n\r]+\<h2/msi');
			
			
			// get education block
			$Education = $this->GetMatches($patterns);
			$Education = HTMLParser::StripLinks($Education);
			$Education = preg_replace("/\<p[^\>]+\>[\s\t\n\r]*\<em\>[\s\t\n\r]*Activities and Societies\:.*?\<\/p\>/ms", "", $Education);
			
			return $Education;
		}
		
	}
