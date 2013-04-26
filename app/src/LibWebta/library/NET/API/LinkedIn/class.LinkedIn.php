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
	Core::Load("NET/API/LinkedIn/LinkedInProfile");
	
	/**
     * @name LinkedIn
     * @category Libwebta
     * @package NET_API
     * @subpackage LinkedIn
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class LinkedIn extends HTTPClient
	{
		
		const LOGIN_URL 	= 'https://www.linkedin.com/secure/login';
		const FAILED_LOGIN_STRING	= 'What\'s new at LinkedIn?';
		const HOME_URL 		= 'http://www.linkedin.com/home?trk=tab_h';
		const PROFILE_URL	= 'http://www.linkedin.com/myprofile?trk=tab_pro';
		const CONNECTIONS_URL = 'http://www.linkedin.com/connections';
		
		
		/**
		 * MySpace account ID
		 */
		private $AccountID;
		
		private $LoggedIn;
		
		/**
		 * Module name
		 */
		public $Name = 'LinkedIn';
		
		
		function __construct()
		{
			parent::__construct();
		}

		
		function Login($email, $password)
		{
			$this->UseRedirects();
			$this->UseCookies();

			$params = array(
				'session_key' 	=> $email,
				'session_password' 	=> $password,
				'session_login' => 1,
				'session_rikey' => 'invalid key'
			);
			
			$this->IgnoreErrors();
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
		
		
		function GetConnectionsList() 
		{
			if (!$this->LoggedIn) return false;
			
			$page = self::CONNECTIONS_URL;
			
			$this->Fetch($page);

			if (!$this->Result)
				return false;
			
			preg_match("/\"numConnections\"[^\>]*\>([0-9]+)\</msi", $this->Result, $match);
			$total_connections = $match[1] ? $match[1] : 0;
			
			preg_match_all("/\<tr [^\>]*name\=\"connection\".*?\_connection([0-9]+).*?name\=\"fullName\"[^\>]*\>(.*?)\<\/strong\>.*?\"email\"[^\>]*\>([^\@]+\@[^\@]+\.[a-z]{2,6})\<.*?\<\/tr\>/msi", $this->Result, $matches, PREG_SET_ORDER);
			
			$connections = array();
			
			foreach($matches as $match)
			{
				array_push($connections, array(
					'id'	=> $match[1],
					'name'	=> HTMLParser::StripLinks($match[2]),
					'email'	=> $match[3]
				));
			}
			
			return $connections;
		}
		
		/**
		 * Get account profile object
		 */
		function GetProfileByID($accountid = null)
		{
			if (!$accountid) $accountid = $this->AccountID;

			$this->Profile = new LinkedInProfile($accountid, $this->Curl);
			return $this->Profile;
		}
		
	}
