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
	Core::Load("NET/API/MySpace/MySpaceProfile");
	Core::Load("Data/Text/HTMLParser");
	
	/**
     * @name MySpace
     * @category Libwebta
     * @package NET_API
     * @subpackage MySpace
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class MySpace extends HTTPClient
	{
		
		const LOGIN_URL 	= 'http://login.myspace.com/index.cfm?fuseaction=login.process';
		const FAILED_LOGIN_STRING	= 'You Must Be Logged-In to do That!';

		const HOME_URL 		= 'http://home.myspace.com/index.cfm?fuseaction=user';
		
		const PROFILE_URL	= 'http://profile.myspace.com/index.cfm?fuseaction=user.viewprofile&amp;friendid=';
		//const FRIENDS_URL 	= 'http://profile.myspace.com/index.cfm?fuseaction=user.viewfriends&friendID=';
		const FRIENDS_URL	= 'http://home.myspace.com/Modules/ViewFriends/FriendsView.aspx?friendID=';
		
		const BLOG_URL		= 'http://blog.myspace.com/index.cfm?fuseaction=blog.ListAll&friendID=';


		/**
		 * Module name
		 */
		public $Name = 'MySpace';
		
		
		
		/**
		 * MySpace account ID
		 */
		private $AccountID;
		
		/**
		 * User logged in
		 */
		private $LoggedIn;
		
		private $Profile;
		private $Friends;
		
		
		/**
		 * Constructor
		 */
		function __construct()
		{
			parent::__construct();
		}

		/**
		 * Login to account
		 * 
		 * @var string email
		 * @var string password
		 */
		function Login($email, $password)
		{
			$this->UseRedirects();
			$this->UseCookies();
			$this->SetTimeouts(60, 30);

			$params = array(
				'email' 	=> $email,
				'password' 	=> $password
			);
			
			$this->Fetch(self::LOGIN_URL, $params, true);
			$this->LoggedIn = false;
			
			if (!$this->Result)
				return false;

			
			$failed_str = preg_quote(self::FAILED_LOGIN_STRING, '/');
			if (preg_match("/{$failed_str}/ms", $this->Result))
				return false;
			
			$this->LoggedIn = true;
			return true;
		}
		
		
		/**
		 * Get account ID of user what logged in
		 * 
		 */
		function GetAccountID()
		{
			if (!$this->LoggedIn || !$this->Result) return false;
			
			
			$profile_str = preg_quote(self::PROFILE_URL, '/');
			if (preg_match("/href\s*\=\s*(\'|\"){$profile_str}([0-9]+)\\1[^>]*>\s*Profile/msi", $this->Result, $matches))
			{
				$this->AccountID = $matches[2];
				return $this->AccountID;
			}
			
			$this->Fetch(self::HOME_URL);
			if (!$this->Result) return false;

			if (preg_match("/href\s*\=\s*(\'|\"){$profile_str}([0-9]+)\\1[^>]*>\s*Profile/msi", $this->Result, $matches))
			{
				$this->AccountID = $matches[2];
				return $this->AccountID;
			}
			
			return false;
		}
		
		
		/**
		 * Get blog entries from myspace account
		 * 
		 * @var int friend account number (if not default)
		 */
		function GetBlogEntries($accountid = 0)
		{
			if (!$this->LoggedIn) return false;
			if (!$accountid) $accountid = $this->AccountID;
			
			$blog_page = self::BLOG_URL . $accountid;
			
			$this->Fetch($blog_page);

			if (!$this->Result)
				return false;
			
			$blogs = array();
			
			$pattern = preg_quote('class="blogTimeStamp"');
			$date_chunks = preg_split("/{$pattern}/msi", $this->Result);
			
			if ($date_chunks) 
			{
				unset($date_chunks[0]);
				$date_chunks = array_values($date_chunks);
			}
			
			foreach($date_chunks as $chunk)
			{
				preg_match("/[\>\n\r\t\s]+([^\<\n]+)[\n\r\t\s]+\</ms", $chunk, $match);
				$date = trim($match[1]);
				
				$pattern = preg_quote('class="blog"');
				$blog_chunks = preg_split("/{$pattern}/msi", $chunk);
				
				if ($blog_chunks) 
				{
					unset($blog_chunks[0]);
					$blog_chunks = array_values($blog_chunks);
				}
				
				foreach($blog_chunks as $blog)
				{
					$small_chunks = preg_split("/\<p\s+class\s*\=\s*(\'|\")blog[a-z]+\\1\s*\>/msi", $blog);
					
					if ($small_chunks) 
					{
						unset($small_chunks[0]);
						$small_chunks = array_values($small_chunks);
					}
					
					if ($small_chunks[0])
					{
						preg_match("/[\n\r\t\s]+([^\<\n]+)[\n\r\t\s]+\<(.*?Category\:[\s\t]*\<a[^\>]+\>([^\<]+)\<\/a\>)?/ms", $small_chunks[0], $match);
						$subject = $match[1];
						$category = $match[3];
					}
					
					if ($small_chunks[1])
					{
						preg_match("/(.*?)\<\/p\>/ms", $small_chunks[1], $match);
						$content = $match[1];
					}
					
					if ($small_chunks[2])
					{
						preg_match("/\<a[^\>]+\>\s*\<b\>(.*?)\<\/b\>.*?([0-9]+)\s*Comments/ms", $small_chunks[2], $match);
						$time = $match[1];
						$comments = $match[2];
					}
					
					$timestamp = strtotime("$date $time");
					
					array_push($blogs, array(
						'date'		=> $date,
						'subject'	=> $subject,
						'category'	=> $category,
						'content'	=> $content,
						'time'		=> $time,
						'comments'	=> $comments,
						'timestamp' => $timestamp
					));
				} // end blog chunks
				
			} // end date chunks
			
			return $blogs;
			
		}
		
		
		/**
		 * Get friends list from myspace account
		 * 
		 * @var int friend account number (if not default)
		 * @var int page
		 * @var string pageurl
		 * 
		 * @return array array of users where item in format 
		 * 		('id'	 =>, 'name'=> , 'img' => )
		 */
		function GetFriendsList($accountid = 0, $page = 1, $pageurl = '')
		{
			if (!$this->LoggedIn) return false;
			if (!$accountid) $accountid = $this->AccountID;
			
			$friend_page = $pageurl ? $pageurl : self::FRIENDS_URL . $accountid;
			
			$this->Fetch($friend_page);

			if (!$this->Result)
				return false;
			
		
			// get form details to form next page url
			preg_match_all("/(friendCount|prevPage|PREVPageFirstONERETURENED|PREVPageLASTONERETURENED).*?value\s*\=\s*(?:\'|\")([0-9]+)(?:\'|\")/ms", $this->Result, $matches, PREG_SET_ORDER);
			$pageurl = self::FRIENDS_URL . $accountid . "&page=" . ($page + 1);
			if ($matches) 
			{
				$matches = array_values($matches);
				foreach($matches as $match)
				{
					$pageurl .= "&" . $match[1] . "=" . $match[2];
				}
			}
			
			// get total pages 
			preg_match_all("/javascript:NextPage\(\'([0-9]+)\'\)/ms", $this->Result, $matches);
			$pages = $matches[1] ? max($matches[1]) : 1;
			
			preg_match_all("/class\s*\=\s*(\'|\")?friend\\1.*?friendid\s*\=\s*([0-9]+)[^\>]+\>([^\<]+)\<.*?src\s*\=\s*(\'|\")(.*?)\\4/msi", $this->Result, $matches, PREG_SET_ORDER);
			
			if ($page == 1)
				$this->Friends = array();
			
			foreach($matches as $match)
			{
				array_push($this->Friends, array(
					'id'	=> $match[2],
					'name'	=> $match[3],
					'img'	=> $match[5]
				));
			}
			
			if ($pages > $page)
				$this->GetFriendsList($accountid, $page+1, $pageurl);
			
			return $this->Friends;
		}
		
		
		
		function GetFriendsList2($accountid = 0, $page = 1, $pageurl = '')
		{
			if (!$this->LoggedIn) return false;
			if (!$accountid) $accountid = $this->AccountID;
			
			$friend_page = $pageurl ? $pageurl : self::FRIENDS_URL . $accountid;
			
			if ($page == 1)
			{
				// get page if first one
				$this->Fetch($friend_page);
			}
			else
			{
				// post method to fetch users
				$tmp = explode("?", $friend_page);
				if ($tmp[1]) 
				{
					$out = array();
					parse_str($tmp[1], $out);
				}
				
				$this->Fetch($tmp[0], $out, true);
			}

			if (!$this->Result)
				return false;
			
			// get form action
			preg_match("/<form[^>]+action\s*\=\s*(?:\'|\")(Friends.*?)(?:\'|\")/msi", $this->Result, $matches);
			$action = $matches[2];
			
			// event target
			preg_match("/javascript:\_\_doPostBack\(\'([^\']+)\'\,\s*\'".($page+1)."\'\)/ms", $this->Result, $matches);
			$target = $matches[1];
			
			// get form details to form next page url
			preg_match_all("/(\_\_VIEWSTATE).*?value\s*\=\s*(?:\'|\")([0-9]+)(?:\'|\")/ms", $this->Result, $matches, PREG_SET_ORDER);
			$pageurl = ($action ? $action : self::FRIENDS_URL . $accountid) . "&__EVENTTARGET={$target}&__EVENTARGUMENT=" . ($page + 1);
			
			if ($matches) 
			{
				$matches = array_values($matches);
				foreach($matches as $match)
				{
					$pageurl .= "&" . $match[1] . "=" . $match[2];
				}
			}
			
			// get total pages 
			preg_match_all("/javascript:\_\_doPostBack\(\'[^\']+\'\,\s*\'([0-9]+)\'\)/ms", $this->Result, $matches);
			$pages = $matches[1] ? max($matches[1]) : 1;
			
			preg_match_all("/viewprofile[^>]+friendid\s*\=\s*([0-9]+)[^>]*>([^<]+)<.*?src\s*\=\s*(\'|\")?(.*?)\\3/msi", $this->Result, $matches, PREG_SET_ORDER);
			
			if ($page == 1)
				$this->Friends = array();
			
			foreach($matches as $match)
			{
				if ($match[1] == $accountid) continue;
				
				array_push($this->Friends, array(
					'id'	=> $match[1],
					'name'	=> $match[2],
					'img'	=> $match[4]
				));
			}
			
				
			if ($pages > $page)
				$this->GetFriendsList2($accountid, $page+1, $pageurl);
			
			return $this->Friends;
		}
		

		/**
		 * Get account profile object
		 */
		function GetProfileByID($accountid = null)
		{
			if (!$accountid) $accountid = $this->AccountID;

			$this->Profile = new MySpaceProfile($accountid);
			return $this->Profile;
		}
		
	}
