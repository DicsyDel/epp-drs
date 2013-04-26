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
     * @subpackage Flickr
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
	 * @name Flickr
	 * @category LibWebta
	 * @package NET_API
	 * @subpackage Flickr
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
    class Flickr extends Core
    {
        private $ApiKey;
        private $Secret;
        private $HttpRequest;
        private $Frob;
        
        const FLICKR_API_URL = "http://api.flickr.com/services/rest/";
        const FLICKR_RESP_FORMAT = "php_serial";
        
        public function __construct($api_key, $secret)
        {
            $this->ApiKey = $api_key;
            $this->Secret = $secret;
            
            $this->HttpRequest = new HttpRequest();
        }
        
        private function SignMessage()
        {
            $args = func_get_args();
            $str = implode("", $args);
            
            return md5("{$this->Secret}api_key{$this->ApiKey}format".self::FLICKR_RESP_FORMAT."{$str}");
        }
        
        public function Login()
        {
            $signature = $this->SignMessage("frob", $this->Frob, "perms", "delete");
            $query = "api_key={$this->ApiKey}&perms=delete&frob={$this->Frob}&api_sig={$signature}";
            
            $request = new HttpRequest("http://flickr.com/services/auth/", HTTP_METH_GET);
            $request->setQueryData($query);
            $request->enableCookies();
            
            
            $request->setOptions(array(    "redirect" => 10, 
		                                   "useragent" => "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)"
		                              )
		                        );
            
            $request->send();
            
            var_dump($request->getResponseCode());
            print_r($request->getResponseBody());
            var_dump($request->getResponseStatus());
        }
        
        public function GetFrob()
        {
            $params = array(    "api_key" => $this->ApiKey,
                                "api_sig" => $this->SignMessage("method", "flickr.auth.getFrob")
                           );
                           
            $response = $this->ApiRequest("flickr.auth.getFrob", $params);
            if ($response['stat'] == 'fail')
            {
                Core::RaiseWarning($response['message']);
                return false;
            }
            else 
            {
                $this->Frob = $response['frob']['_content'];
                return true;
            }
        }
        
        private function ApiRequest($method, $params)
        {
            $this->HttpRequest->setUrl(self::FLICKR_API_URL);
		    $this->HttpRequest->setMethod(HTTP_METH_GET);

		    $params["format"] = self::FLICKR_RESP_FORMAT;
		    $params["method"] = $method;
		    
		    $query_data = http_build_query($params);
		    
            $this->HttpRequest->setQueryData($query_data);
            
            try 
            {
                $this->HttpRequest->send();
                
                if ($this->HttpRequest->getResponseCode() == 200)
                {
                    return unserialize($this->HttpRequest->getResponseBody());
                }
                else 
                {
                    Core::RaiseWarning($this->HttpRequest->getResponseStatus()." (".$this->HttpRequest->getResponseCode().")");
                    return false;
                }
            }
            catch (HttpException $e)
            {
                Core::RaiseWarning($e->__toString());
		        return false;
            }
        }
    }
?>