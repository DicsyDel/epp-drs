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
     * @subpackage Facebook
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 

	Core::Load("Data/Text/HTMLParser");
	Core::Load("NET/HTTP/HTTPClient");
	Core::Load("NET/API/Facebook/FacebookProfile");
	Core::Load("NET/API/Facebook/FacebookAPI");
	
	/**
     * @name Facebook
     * @category Libwebta
     * @package NET_API
     * @subpackage Facebook
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class Facebook extends HTTPClient
	{
		const API_SERVER_BASE_URL = 'http://api.facebook.com';
		const REST_SERVER_ADDR = 'http://api.facebook.com/restserver.php';
		const API_LOGIN_URL = 'https://api.facebook.com/login.php?skipcookie';
		const LOGIN_URL = 'http://www.facebook.com/login.php?skipcookie';

		/**
		 * Module name
		 */
		public $Name = 'Facebook';
		
		/**
		 * Livejournal username, password
		 */
		private $Username;
		private $Password;
		
		/**
		 * Application api key, secret
		 */
		private $API_Key;
		private $Secret;
		
		/**
		 * Facebook client
		 */
		private $Client;
		
		/**
		 * Boolean value for uccessfully logged in
		 */
		public $LoggedIn;
		
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
		 * Set auth data for application
		 * 
		 * @param string API KEY
		 * @param string SECRET for API KEY
		 */
		function SetAuthData($api, $secret)
		{
			$this->API_Key = $api;
			$this->Secret = $secret;
		}
		
		/**
		 * Login to account
		 * 
		 * @var string email
		 * @var string password
		 */
		function Login($email, $password)
		{
			$this->Username = $email;
			$this->Password = $password;
			$this->LoggedIn = false;
			
			if (!class_exists('FacebookAPI'))
				return;
			
			$this->Client = new FacebookAPI(self::REST_SERVER_ADDR, $this->API_Key, $this->Secret);

			$params = array(
				'email' => trim($this->Username),
				'pass' => trim($this->Password),
				'md5pass' => '',
				'challenge' => md5(time()),
				'noerror' => 1,
				'api_key' => $this->API_Key,
				'next' => '',
				'v' => '1.0'
			);
			
			
			$this->UseRedirects();
			$this->UseCookies();
			$this->ReceiveHeaders();
			$this->Fetch(self::LOGIN_URL, $params, true);

			preg_match("/auth\_token=([a-z0-9]+)/msi", $this->Result, $auth);
			$this->AuthToken = $auth[1];

			if (!$this->AuthToken)
			{
				$form = HTMLParser::GetFormDetails($this->Result, null, 'grant_perm');
				if (!$form) return;
				if ($form['elements']['cancel']) unset($form['elements']['cancel']);

				
				$this->Fetch($form['action'], $form['elements'], true);
				
				if (!$this->Result)
					return false;
	
				preg_match("/auth\_token=([a-z0-9]+)/msi", $this->Result, $auth);
				$this->AuthToken = $auth[1];
			}

			if ($this->AuthToken)
			{
				$this->LoggedIn = true;
				$this->SessionInfo = $this->Client->GetSession($this->AuthToken);
				return true;
			}
		}
		
		
		/**
		 * Get friends list from Facebook account
		 * 
		 * @return array array of friends where item in format
		 * ('username' => , 'fullname' => )
		 */
		function GetFriendsList()
		{
			if (!$this->Client) return;
			
			return $this->Client->GetFriends();
		}
		
				

		/**
		 * Get account profile object
		 */
		function GetProfileByID($accountid = null)
		{
			if (!$accountid) $accountid = $this->SessionInfo['uid'];

			$this->Profile = new FacebookProfile($accountid);
			$this->Profile->SetClient($this->Client);
			return $this->Profile;
		}
		
	}
