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
	
	Core::Load("NET/API/Google/class.GoogleService.php");
	
	/**
     * @name       Picasa
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Google
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class Picasa extends GoogleService 
	{
		private $UserFeed;
		
	    /**
		 * Constuct
		 *
		 */
		function __construct()
		{
			parent::__construct();
			$this->UserFeed = false;
		}
		
		public function EditPhoto($photoid, $albumid, $version, $description, $isretry = false)
		{
		    //TODO: We need to add ability to change all metadata for specified photo.
		    
		    $request = "<entry xmlns='http://www.w3.org/2005/Atom'>
                          <summary>{$description}</summary>
                          <category scheme=\"http://schemas.google.com/g/2005#kind\"
                            term=\"http://schemas.google.com/photos/2007#photo\"/>
                        </entry>";
		    
		    $req = $this->Request(	"http://picasaweb.google.com/data/entry/api/user/{$this->Username}/albumid/{$albumid}/photoid/{$photoid}/{$version}", 
									$request,
									array("Content-type" => "application/atom+xml"),
									"PUT"
								);
		    										
		    if ($req == 200)
		    {
		        $data = new DOMDocument("1.0", "utf-8");
		        $data->loadXML($this->HttpRequest->getResponseBody());
		        $photoinfo = $this->ParsePhotoFeedEntry($data->documentElement);
		        
		        return $photoinfo["version"];
		    }
		    elseif ($req == 409) 
		    {
		        if (!$isretry)
		        {
    		        $data = new DOMDocument("1.0", "utf-8");
    		        $data->loadXML($this->HttpRequest->getResponseBody());
    		        $photoinfo = $this->ParsePhotoFeedEntry($data->documentElement);
    		        
    		        return $this->EditPhoto($photoid, $albumid, $photoinfo['version'], $description, true);
		        }
		        
		        //TODO: Reparse Entry record and try to send request again
		        Core::RaiseWarning("Specified version number doesn't match resource's latest version number. (409)");
			    return false;
		    }
		    else 
		    {
		        Core::RaiseWarning($this->HttpRequest->getResponseBody());
		        return false;
		    } 
		}
		
		private function ParsePhotoFeedEntry($node)
		{
		    $item = array();
		    
		    foreach ($node->childNodes as $itemChild)
			{
			    switch($itemChild->nodeName)
			    {
			        case "title":
			        case "description":
			        case "link":
			            $item[$itemChild->nodeName] = $itemChild->nodeValue;
			            break;
			        case "guid":
			            $pi = parse_url($itemChild->nodeValue);
                        $item["id"] = basename($pi["path"]);
			            break;
			        
			        case "gphoto:version":
			            $item["version"] = $itemChild->nodeValue;
			            break;
			            
			        case "media:group":
			            
			            $thumb = $itemChild->getElementsByTagName("thumbnail")->item(0);
			            $url = $thumb->getAttribute("url");
			            $item["thumb"] = $url;
			            
			            $thumb = $itemChild->getElementsByTagName("content")->item(0);
			            $url = $thumb->getAttribute("url");
			            $item["url"] = $url;
			            
			            break;
			    }
			}
			
			return $item;
		}
		
		public function DeletePhoto($photoid, $albumid, $version)
		{
		    
		    $req = $this->Request(	"http://picasaweb.google.com/data/entry/api/user/{$this->Username}/albumid/{$albumid}/photoid/{$photoid}/{$version}", 
									"",
									array(),
									"DELETE"
								);
		    
		    if (!$req)
		      return false;
		    elseif ($req == 200)
		    {
		        return true;
		    }
		    elseif ($req == 409) 
		    {
		        //TODO: Reparse Entry record and try to send request again
		        Core::RaiseWarning("Specified version number doesn't match resource's latest version number. (409)");
			    return false;
		    }
		    else 
		    {
		        Core::RaiseWarning($this->HttpRequest->getResponseBody());
		        return false;
		    }
		}
		
		public function GetAlbum($albumid, $thumbsize = 144)
		{
		    $retval = false;
		    
		    $params = array("kind" => "photo", "alt" => "rss", "hl" => "en", "thumbsize" => $thumbsize);
		    
		    $req = $this->Request(	"http://picasaweb.google.com/data/feed/api/user/{$this->Username}/albumid/{$albumid}", 
									http_build_query($params),
									array(),
									"GET"
								);
			if (!$req)
                return false;
		    elseif ($req == 200)
			{
    			$data = $this->HttpRequest->getResponseBody();    			
    			$doc = new DOMDocument("1.0", "utf-8");
    			$doc->loadXML($data);
    			$channel = $doc->documentElement->getElementsByTagName("channel")->item(0);
    			foreach ($channel->childNodes as $node)
    			{
    			    switch($node->nodeName)
    			    {
    			        case "title":
    			        case "description":    			            
                            $retval[$node->nodeName] = $node->nodeValue;    			             
    			             break;
    			        case "image":
    			             $retval[$node->nodeName] = $node->childNodes->item(0)->nodeValue;    			             
    			             break;
    			        case "item":    			            
    			            $retval["photos"][count($retval["photos"])] = $this->ParsePhotoFeedEntry($node);
    			            break;
    			    }
    			}
   			}
   			else 
   			{
   			    Core::RaiseWarning($this->HttpRequest->getResponseBody());
    		    return false;
   			}
   			
   			return $retval;
		}
		
		public function GetAlbums($access = "all")
		{
		    $xml = $this->GetUserFeed($access);
    		if ($xml)
    		{
    		    foreach ($xml->channel->item as $item)
                {
                    $albumid = preg_replace("/[^0-9]+/", "", $item->guid);
                    $albuminfo = $this->GetAlbum($albumid);
                    if ($albuminfo)
                    {
                        $retval[] = array_merge(array("id"=>$albumid),$albuminfo);
                    }
                    
                }
                                
                return $retval;
    		}
    		else 
    		  return false;
		}
		
		private function GetUserFeed($access = "all")
		{
		    if (!$this->UserFeed)
		    {
		        $params = array("kind" => "album", "alt" => "rss", "hl" => "en", "access" => $access);
		        
		        $req = $this->Request(	"http://picasaweb.google.com/data/feed/api/user/{$this->Username}", 
									    http_build_query($params),
									    array(),
									    "GET"
								     );
								
    			if (!$req)
                    return false;
		        elseif ($req == 200)
    			{
        			$data = $this->HttpRequest->getResponseBody();
        			$this->UserFeed = new SimpleXMLElement($data);
    		    }
    		    else
    		    {
    		        Core::RaiseWarning($this->HttpRequest->getResponseBody());
    		        return false;
    		    }
		    }
		    
		    return $this->UserFeed;
		}
		
		public function GetUserInfo($access = "all")
		{
		    $xml = $this->GetUserFeed($access);
    		if ($xml)
    		{	
    			//print $data;
    			$retval = array();
    			$retval = array( "username"  => (string)$xml->channel->title, 
	                             "icon"      => (string)$xml->channel->image->url
	                           );
    			    			                              		    
                return $retval;
    		}
    		else 
    		  return false;
		}
	}
?>