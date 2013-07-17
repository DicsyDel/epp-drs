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

	Core::Load("Data/XML/RSS/RSSReader");
	Core::Load("NET/HTTP/HTTPClient");
	Core::Load("NET/API/Blogger/BloggerProfile");
	
	/**
     * @name Blogger
     * @category Libwebta
     * @package NET_API
     * @subpackage Blogger
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class Blogger extends HTTPClient
	{
		const BLOG_ATOM_URL = 'http://%s.blogspot.com/feeds/posts/default';
		const BLOG_PAGE_URL = 'http://%s.blogspot.com';

		/**
		 * Module name
		 * @var string
		 */
		public $Name = 'Blogger';
		
		
		/**
		 * Username for blogger account ID
		 * @var string
		 */
		private $AccountID;
		
		/**
		 * Profile object
		 * @var BloggerProfile
		 */		
		private $Profile;
		
		
		/**
		 * Constructor
		 * @ignore 
		 */
		function __construct()
		{
			parent::__construct();
		}

		/**
		 * Login to account
		 * 
		 * @param string $username
		 * @return bool
		 */
		function Login($username)
		{
			$this->AccountID = $username;
			
			$blog_page = sprintf(self::BLOG_PAGE_URL, $this->AccountID);
			$this->Fetch($blog_page);
			if (!$this->Result || preg_match("/<title>[^<]*404[^<]*<\/title>/msi", $this->Result))
				return false;
			
			return true;
		}
		
		
		/**
		 * Get blog entries from myspace account
		 * 
		 * @var int friend account number (if not default)
		 */
		function GetBlogEntries($accountid = 0)
		{
			if (!$accountid) $accountid = $this->AccountID;
			
			$blog_page = sprintf(self::BLOG_ATOM_URL, $accountid);

			$http = new HTTPClient();
			$http->SetTimeouts(60, 30);
			$http->Fetch($blog_page);
			$blog_xml = $http->Result;

			$Reader = new RSSReader();
			$Reader->Parse($blog_xml);
			$data = $Reader->GetData();

			if (!$data) 
				return false;
			
			$blogs = array();
			
			foreach($data['item']['title'] as $key => $subject)
			{
				$timestamp = strtotime($data['item']['published'][$key]);
				
				array_push($blogs, array(
					'date'		=> date("Y-m-d", $timestamp),
					'subject'	=> $subject,
					'category'	=> "",
					'content'	=> $data['item']['content'][$key],
					'time'		=> date("H:i", $timestamp),
					'comments'	=> "",
					'timestamp' => $timestamp
				));
				
			} 
			
			return $blogs;
			
		}
		
		

		/**
		 * Get account profile object
		 */
		function GetProfileByID($accountid = null)
		{
			if (!$accountid) $accountid = $this->AccountID;
			
			$blog_page = sprintf(self::BLOG_PAGE_URL, $accountid);
			$this->Fetch($blog_page);
			if (preg_match("/http\:\/\/[a-z0-9\.\_\-]+\/profile\/([0-9]+)/i", $this->Result, $matches))
			{
				$this->Profile = new BloggerProfile($matches[1]);
			}
			return $this->Profile;
		}
		
	}
