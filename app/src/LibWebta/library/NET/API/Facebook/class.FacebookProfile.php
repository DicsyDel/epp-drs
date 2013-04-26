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
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     */
	
    /**
     * @name FacebookProfile
     * @category Libwebta
     * @package NET_API
     * @subpackage Facebook
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class FacebookProfile extends HTTPClient
	{
		public $AccountID;
		
		
		/**
		 * Constructor
		 * 
		 * @var int account ID
		 * @var resource CURL handler
		 */
		function __construct($accountid, $curl = null)
		{
			parent::__construct($curl);
			$this->AccountID = $accountid;
		}
		
		/**
		 * Set client for facebook api
		 */
		function SetClient($client)
		{
			$this->Client = $client;
		}
		
		/**
		 * Get user info 
		 * 
		 * @param string uniqe ID
		 */
		function GetPersonalDetails($uid = 0)
		{
			if (!$this->Client) return;
			if (!$uid) $uid = $this->AccountID;
			
			return $this->Client->GetUsersInfo(
				array($uid), array('about_me', 'birthday', 'current_location', 
								'gender', 'interests', 'name', 'pic', 'religion')
			);
		}
		
	}
