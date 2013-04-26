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

	Core::Load("Data/XML/RSS/RSSReader");
	Core::Load("NET/RPC/RPCClient");
	Core::Load("NET/HTTP/HTTPClient");
	Core::Load("NET/API/LiveJournal/LiveJournalProfile");
	
	/**
     * @name LiveJournal
     * @category Libwebta
     * @package NET_API
     * @subpackage LiveJournal
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class LiveJournal extends HTTPClient
	{
		const PROFILE_URL	= 'http://users.livejournal.com/%s/profile';
		const BLOG_URL = 'http://users.livejournal.com/%s/data/rss';
		const BLOG_URL_SIMPLE = 'http://%s.livejournal.com/data/rss';
		const RPC_URL = 'http://www.livejournal.com/interface/xmlrpc';

		/**
		 * Module name
		 */
		public $Name = 'LiveJournal';
		
		
		/**
		 * Username for livejournal account ID
		 */
		private $AccountID;
		private $Password;
		
		/**
		 * Profile object
		 */		
		private $Profile;
		
		
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
		function Login($username, $password)
		{
			$this->AccountID = $username;
			$this->Password = $password;
			
			$rpc = new RPCClient(self::RPC_URL);
			
			$result = $rpc->__call("LJ.XMLRPC.login", array(
				"username" => $this->AccountID,
				"password" => $this->Password,
				"ver" => 1
			));
			
			if (!$result['userid']) return false;

			return true;
		}
		
		
		/**
		 * Get blog entries from myspace account
		 * 
		 * @var int friend account number (if not default)
		 */
		function GetBlogEntries($accountid)
		{
			if (!$accountid) $accountid = $this->AccountID;
			
			$blog_page = sprintf(self::BLOG_URL, $accountid);
			
			$http = new HTTPClient();
			$http->SetTimeouts(60, 30);
			$http->Fetch($blog_page);
			
			if (preg_match("/<title>[^<]*302 Found[^<]*<\/title>/msi", $http->Result))
			{
				$blog_page = sprintf(self::BLOG_URL_SIMPLE, $accountid);
				
				$http = new HTTPClient();
				$http->SetTimeouts(60, 30);
				$http->Fetch($blog_page);
			}
			
			$blog_xml = $http->Result;
			
			$Reader = new RSSReader();
			$Reader->Parse($blog_xml);
			$data = $Reader->GetData();

			if (!$data) 
				return false;
			
			$blogs = array();
			
			foreach((array)$data['item']['title'] as $key => $subject)
			{
				$timestamp = strtotime($data['item']['pubdate'][$key]);
				
				array_push($blogs, array(
					'date'		=> date("Y-m-d", $timestamp),
					'subject'	=> $subject,
					'category'	=> "",
					'content'	=> $data['item']['description'][$key],
					'time'		=> date("H:i", $timestamp),
					'comments'	=> "",
					'timestamp' => $timestamp
				));
				
			} 
			
			return $blogs;
			
		}
		
		
		/**
		 * Get friends list from LiveJournal account
		 * 
		 * @return array array of friends where item in format
		 * ('username' => , 'fullname' => )
		 */
		function GetFriendsList()
		{

			$rpc = new RPCClient(self::RPC_URL);
			
			$result = $rpc->__call("LJ.XMLRPC.getfriends", array(
				"username" => $this->AccountID,
				"password" => $this->Password,
				"ver" => 1
			));
			
			
			foreach($result['friends'] as $k => &$friend)
			{
				if (is_object($friend['fullname']) && $friend['fullname']->xmlrpc_type == 'base64')
					$result['friends'][$k]['fullname'] = $friend['username'];
			}
			
			return $result['friends'];
		}
		
				

		/**
		 * Get account profile object
		 */
		function GetProfileByID($accountid = null)
		{
			if (!$accountid) $accountid = $this->AccountID;

			$this->Profile = new LiveJournalProfile($accountid);
			return $this->Profile;
		}
		
	}
