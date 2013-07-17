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
     * @package    NET_API
     * @subpackage Google
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
     * @name       GoogleService
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Google
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class GoogleService extends Core 
	{
		/**
		 * Auth token
		 *
		 * @var string
		 */
		protected $AuthToken;
		
		/**
		 * Google username
		 *
		 * @var string
		 */
		public $Username;
		
		/**
		 * HttpRequest
		 * @var Httprequest
		 */
		public $HttpRequest;
		
		/**
		 * Account password
		 *
		 * @var string
		 */
		protected $Password;
		
		public $Service;
		
		/**
		 * Constructor
		 *
		 */
		function __construct()
		{
			$this->AuthToken = false;
			$this->HttpRequest = new HttpRequest();
			
			$this->HttpRequest->setOptions(array(    "redirect" => 10, 
			                                         "useragent" => "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)"
			                                    )
			                              );
			                              
			$this->HttpRequest->enableCookies();
		}
		
		/**
		 * Set Auth Token
		 *
		 * @param string $token
		 */
		public function SetAuthToken($token)
		{
		    $this->AuthToken = $token;
		}
		
		/**
		 * Return current Authtoken
		 *
		 * @return string
		 */
		public function GetAuthToken()
		{
		    return $this->AuthToken;
		}
		
		public function SetAuthInfo($username, $password)
		{
		    $this->Username = $username;
		    $this->Password = $password;
		}
		
		/**
		 * Authentificate user in google service
		 *
		 * @param unknown_type $email
		 * @param unknown_type $password
		 * @return unknown
		 */
		public function Authenticate($username = false, $password = false, $service = false)
		{
			if ($username && $password)
                $this->SetAuthInfo($username, $password);
		                
            if (!$this->Username || !$this->Password)
                return false;
                
            if (!$service)
                $service = $this->Service;
                
		    $params = array(
							"accountType" => "HOSTED_OR_GOOGLE",
			                "Email" 	=> "{$this->Username}@gmail.com",
							"Passwd"	=> $this->Password,
							"service"	=> $service,
							"source"	=> "Libwebta",
							"continue"	=> "https://www.google.com/accounts/ManageAccount",
							"nui"		=> 1,
							"PersistentCookie"	=> "yes",
							"rmShown"			=> "1",
							"null"				=> "Sign+in"
							);
			
            $this->AuthToken = false;
			$retval = $this->Request(	"https://www.google.com/accounts/ClientLogin", 
										http_build_query($params)
									);				
			
            if (!$retval)
				return false;
			else 
			{
				if ($retval == 200)
				{
				    preg_match("/Auth=([^\n]+)\n/i", $this->HttpRequest->getResponseBody(), $matches);
    				$this->AuthToken = trim($matches[1]);
				    return true;
				}
				else 
				{
				    if ($retval == 403)
				    {
				        $this->RaiseWarning("Invalid login or password");
				        return false;
				    }
				    else 
				    {
				        $this->RaiseWarning(trim($this->HttpRequest->getResponseBody()));
				        return false;
				    }
				}
			}
		}
		
		/**
		 * Request to google service
		 *
		 * @param string $url
		 * @param string $data
		 * @param array $headers
		 * @param string $method (POST or GET)
		 * @param int $followlocation (Handle redirect)
		 * @return string
		 */
		protected function Request($url, $data, $headers = array(), $method = "POST", $isretry = false)
		{
		    $this->HttpRequest->setUrl($url);
		    $this->HttpRequest->setMethod(constant("HTTP_METH_{$method}"));
		   	 
		    // Add auth token
			if ($this->AuthToken)
                $headers = array_merge($headers, array("Authorization" => "GoogleLogin auth={$this->AuthToken}"));
			    
            $this->HttpRequest->addHeaders($headers);
            
            if ($method == "POST" || $method == "GET")
                $this->HttpRequest->setQueryData($data);
            elseif ($method == "PUT")
                $this->HttpRequest->setPutData($data);
            
            try 
            {
                $this->HttpRequest->send();
                
                if (($this->HttpRequest->getResponseCode() == 500 && stristr($this->HttpRequest->getResponseBody(), "Token expired")) && !$isretry)
                {
                    if($this->Authenticate(false, false, $this->Service))
                    {
                        return $this->Request($url, $data, $headers, $method, true);
                    }
                    else
                    {
                        Core::RaiseWarning("Session expired");
                        return false;
                    }
                }
                else
                    return $this->HttpRequest->getResponseCode();
            }
            catch (HttpException $e)
            {
                Core::RaiseWarning($e->__toString());
		        return false;
            }
		}
	}
?>