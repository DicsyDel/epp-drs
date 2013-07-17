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
     * @subpackage Bookings
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	define("RPC_CACHE_TIME", 10080);
	
	/**
     * @name       Bookings
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Bookings
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class Bookings extends Core
	{
		/**
		 * RPC Server URL
		 *
		 * @var string
		 */
		private $Server;
		
		/**
		 * RPC server path
		 *
		 * @var string
		 */
		private $Path;
		
		/**
		 * RPC Server login
		 *
		 * @var string
		 */
		private $Login;
		
		/**
		 * RPC Server password
		 *
		 * @var string
		 */
		private $Password;
		
		/**
		 * RPC PEAR Client Object
		 *
		 * @var object
		 */
		private $RPClient;
		
		/**
		 * Cache time in minutes
		 *
		 * @var int
		 */
		private $CacheTime;
		
		/**
		 * Language
		 *
		 * @var string
		 */
		public $Lang;
		
		/**
		* Rewite cache
		* @var bool
		*/
		public $RewriteCache;
		
		
		/**
		 * Cache object
		 *
		 * @var Cache
		 */
		private $Cache;
		
		/**
		 * Constructor
		 *
		 * @param string $server RPC Server url (widthout http://)
		 * @param string $path Path to RPC script
		 * @param string $login Server login
		 * @param string $password Server Password
		 */
		function __construct($server, $path, $login, $password)
		{
			$this->RPClient = new XML_RPC_Client($path, $server);
			$this->Cache = new Cache("FileSystem", "rpc");
			
			$this->Cache->SetAdapterOption("cache_path", RPC_CACHE_PATH);
			
			
			if ($login)
				$this->RPClient->setCredentials ($login, $password);
				
			$this->CacheTime = defined("CF_RPC_CACHE_TIME") ? CF_RPC_CACHE_TIME : RPC_CACHE_TIME;
			
			$this->Lang = "it";
			
			$this->RewriteCache = false;
		}
		
		/**
		 * Set Cache time
		 *
		 * @param int $time Cache time in minutes
		 */
		public function SetCacheTime($time)
		{
			$this->CacheTime = $time;
		}
		
		/**
		 * Get CRC Of XML-RPC command
		 *
		 * @param string $request
		 * @return string
		 */
		private function GetCRC($request)
		{
			return str_pad(dechex(crc32($request)), 8, '0', STR_PAD_LEFT);
		}
		
		/**
		 * Get content from cache
		 *
		 * @param string $request
		 * @param object $params
		 * @return bool
		 */
		private function CacheGet($request, $IgnoreRewrite = false)
		{
			if ($this->RewriteCache && !$IgnoreRewrite)
				return false;
		
			return $this->Cache->Get($request);
		}
		
		/**
		 * Put content to cache
		 *
		 * @param string $request
		 * @param object $data
		 */
		private function CachePut($request, $data)
		{
			return $this->Cache->Set($request, $data);
		}
		
		/**
		 * Send request to server
		 *
		 * @access private
		 * @param string $command Commend
		 * @param object $params PEAR Params object
		 */
		private function Request($command, $params, $ignorerewrite = false)
		{
			$msg = new XML_RPC_Message($command, $params);
			
			$cache = $this->CacheGet($msg->serialize(), $ignorerewrite);
			if (!$cache)
			{	
				$this->RPClient->setDebug(0);
				$response = $this->RPClient->send($msg);
				
				if (!$response || !$response->faultCode()) 
				{
					try
					{
				    	$val = $response->value();
				    
				    	$retval = XML_RPC_decode($val);
				    	$this->CachePut($msg->serialize(), $retval);
				    }
					catch(Exception $e)
					{
						return false;
					}
					
				    return $retval;
				} 
				else 
				{
				    /*
				     * Display problems that have been gracefully cought and
				     * reported by the xmlrpc.php script
				     */			    
				    throw new Exception(_("RPC Fail")."(".$response->faultCode().") ".$response->faultString());
				}
			}
			else 
				return $cache;
		}
		
		/**
		 * Get Country code by name
		 *
		 * @param string $name
		 * @return string
		 */
		public function GetCountryCode($name)
		{
			$cache = $this->CacheGet("countrycodes-{$name}");
			if (!$cache)
			{
				$countries = $this->GetCountryList();
				$list = array();
				
				foreach ($countries as $k=>$v)
					$list[$v["name"]] = $v["code"];
				
				$this->CachePut("countrycodes-{$name}", $list);
				
				return $list[$name];
			}
			else 
				return $cache[$name];
		}
		
		/**
		 * Get Country list
		 *
		 * @return array
		 */
		public function GetCountryList()
		{
			$CountryCode = "All";
			$params = array(new XML_RPC_Value(array(new XML_RPC_Value($CountryCode, "string")), "struct"));

			$countries = $this->Request("bookings.getCountryDetails", $params);	
			$retval = array();
			$i = 0;
			
			$filter = array();
			if (defined("CF_LIST_COUNTRIES"))
				$filter = explode(",", CF_LIST_COUNTRIES);
			
			foreach ($countries as $k=>$v)
			{
				if (count($filter) > 0 && in_array($v["code"], $filter))
				{
					$retval[$i] = $v;
					$retval[$i]["numhotels"] = $this->GetNumHotelsInCountry($v["code"]);
					$i++;
				}
			}
			
			return $retval;
		}
		
		/**
		 * Get Number of hotels in country
		 *
		 * @param string $CountryCode
		 * @return integer
		 */
		public function GetNumHotelsInCountry($CountryCode)
		{
			$cache = $this->CacheGet("countrynumhotels-{$CountryCode}");
			if (!$cache)
				return "-";
			else 
				return $cache;
		}
		
		/**
		 * Get city ID by name
		 *
		 * @param string $name
		 * @param string $cc
		 * @return integer
		 */
		function GetCityID($name, $cc)
		{
			$cache = $this->CacheGet("cityids-{$name}-{$cc}");
			
			if (!$cache)
			{
				$cities = $this->GetCityList($cc);
				$list = array();
				
				foreach ($cities as $k=>$v)
					$list[$v["name"]] = $v["city_id"];
				
				$this->CachePut("cityids-{$name}-{$cc}", $list);
				
				return $list[$name];
			}
			else 
				return $cache[$name];	
		}
		
		/**
		 * Get city list of selected country
		 *
		 * @param string $countrycode ISO Country code
		 * @return array
		 */
		public function GetCityList($CountryCode)
		{
			$rows = 40;
			$fullres = array();
			$offset = 0;
			$result = 1;
			
			$cache = $this->CacheGet("cities-{$CountryCode}");
			if (!$cache)
			{
				while(count($result)>0)
				{
					$params = array(new XML_RPC_Value(array("countrycodes"=>new XML_RPC_Value($CountryCode, "string"), "rows"=>new XML_RPC_Value($rows, "int"), "offset"=>new XML_RPC_Value($offset, "int")), "struct"));
					$result = $this->Request("bookings.getCities", $params);	
					
					foreach ($result as $res)
						array_push($fullres, $res);
					$offset +=$rows-1;
				}
				
				$this->CachePut("cities-{$CountryCode}", $fullres);
			}
			else 
				$fullres = $cache;
			
			$cities = array();
			$retval = array();
					
			foreach ($fullres as $k=>$v)
				$cities[$v["city_id"]][$v["languagecode"]] = $v;
			
			
			$total = 0;		
			foreach($cities as $k=>$v)
			{
				if ($v[$this->Lang]["name"])
					$retval[] = $v[$this->Lang];
				else 
					$retval[] = $v["en"];
					
				$total += $v["en"]["nr_hotels"];
			}
			
			$this->CachePut("countrynumhotels-{$CountryCode}", $total);
			
			return $retval;
		}
		
		/**
		 * get Hotel ID by Name
		 *
		 * @param string $name
		 * @param integer $cityID
		 * @return integer
		 */
		function GetHotelID($name, $cityID)
		{
			$cache = $this->CacheGet("hotelids-{$name}-{$cityID}");
									
			if (!$cache)
			{
				$hotels = $this->GetHotelList($cityID);
				$list = array();
				
				foreach ($hotels as $k=>$v)
					$list[$v["name"]] = $v["id"];
				
				$this->CachePut("hotelids-{$name}-{$cityID}", $list);
				
				return $list[$name];
			}
			else 
				return $cache[$name];	
		}
		
		/**
		 * Get Hotel list of selected City
		 *
		 * @param integer $cityID
		 * @return array
		 */
		public function GetHotelList($cityID)
		{
			$params = array(new XML_RPC_Value(array("city_id"=>new XML_RPC_Value($cityID, "int")), "struct"));
			
			return $this->Request("bookings.getHotelList", $params);
		}
		
		/**
		 * Get Hotel details
		 *
		 * @param string $hotelID
		 * @return array
		 */
		public function GetHotelDetails($hotelID)
		{
			$params = array(
							new XML_RPC_Value(
												array(
														"hotel_id"=>new XML_RPC_Value($hotelID, "int"),
														"languagecode"=>new XML_RPC_Value($this->Lang, "string")
												), 
							"struct")
						    );
			
			$params2 = array(
							new XML_RPC_Value(
												array(
														"hotel_id"=>new XML_RPC_Value($hotelID, "int"),
														"languagecode"=>new XML_RPC_Value("en", "string")
												), 
							"struct")
						    );
						      
			$retval["lang"] = $this->Request("bookings.getHotelDetails", $params);	
			$retval["en"] = $this->Request("bookings.getHotelDetails", $params2);	
			
			foreach ($retval["lang"]["descriptions"] as $k=>$v)
			{
				$descinfo = $this->GetHotelDescriptionTypes($v["type_id"]);
				
				foreach ($descinfo as $ik=>$iv)
				{
					if ($iv["languagecode"] == $this->Lang)	
						$retval["lang"]["descriptions"][$k]["name"] = $iv["name"];
					elseif ($iv["languagecode"] == "en")
						$retval["en"]["descriptions"][$k]["name"] = $iv["name"];
				}
				
			}
			
			return $retval;
			
		}
		
		/**
		 * Retrun Room list of selected hotel
		 *
		 * @param integer $hotelID
		 * @return array
		 */
		public function GetRoomsList($hotelID)
		{
			$params = array(new XML_RPC_Value(array("hotel_ids"=>new XML_RPC_Value($hotelID, "int")), "struct"));
			
			$rooms = $this->Request("bookings.getRoomDetails", $params);	
			
			foreach ($rooms as $k=>$room)
			{
				// Info
				$roominfo = $this->GetRoomInfo($room["id"]);
				foreach ($roominfo as $ik=>$iv)
				{
					$inf = $this->GetRoomInfoTypes($iv["roominfotype_id"]);
					
					foreach ($inf as $kk=>$vv)
					{
						if ($vv["languagecode"] == "en")
							$roominfo[$ik]["name"]["en"] = $vv["name"];
						elseif ($vv["languagecode"] == $this->Lang)
							$roominfo[$ik]["name"]["lang"] = $vv["name"];
					}
				}
				
				$rooms[$k]["info"] = $roominfo;
				
				// Facilities
				$facilities = $this->GetRoomFacilities($room["id"]);
				
				$rooms[$k]["facilities"] = $facilities;
				
				// Photos
				$rooms[$k]["photos"] = $this->GetRoomPhotos($room["id"]);
			}		
			
			return $rooms;
		}
		
		/**
		 * Get room photos if avaiable
		 *
		 * @param integer $roomID
		 * @return array
		 */
		public function GetRoomPhotos($roomID)
		{
			$params = array(new XML_RPC_Value(array("room_ids"=>new XML_RPC_Value($roomID, "int")), "struct"));
			
			return $this->Request("bookings.getRoomPhotos", $params);	
		}
		
		/**
		 * Get room facolities
		 *
		 * @param integer $roomID
		 * @return array
		 */
		public function GetRoomFacilities($roomID)
		{
			$params = array(new XML_RPC_Value(array("room_ids"=>new XML_RPC_Value($roomID, "int")), "struct"));
			
		
			$facilities = $this->Request("bookings.getRoomFacilities", $params);	
			$resinfo = array("en"=>array(), $this->Lang=>array());
			
			$info = $this->CacheGet("rfacilityinfo-{$v['roomfacilitytype_id']}-{$v['facilitytype_id']}-{$this->Lang}");
				
			if ($info)
			{
				$resinfo = $info;	
				break;
			}
			else 
			{
			
				foreach((array)$facilities as $k=>$v)
				{
					if ($v["value"] == 1)
					{
						
						$to_cache = true;
						$hinfo = $this->GetRoomFacilityTypes($v["roomfacilitytype_id"]);
						$info = $this->GetFacilityTypes($v["facilitytype_id"]);
						
						foreach ($hinfo as $ik=>$iv)
						{
							if ($iv["languagecode"] == "en" || $iv["languagecode"] == $this->Lang)
							{
								$group = "1";
								foreach($info as $iik=>$iiv)
								{
									if ($iiv["languagecode"] == $iv["languagecode"])
										$group = $iiv["name"];
								}
								
								if (!$resinfo[$iv["languagecode"]][$group])
									$resinfo[$iv["languagecode"]][$group] = array();
									
								array_push($resinfo[$iv["languagecode"]][$group], $iv["name"]);
							}
						}
					}
				}
			}
			
			if ($to_cache)
				$this->CachePut("rfacilityinfo-{$v['roomfacilitytype_id']}-{$v['facilitytype_id']}-{$this->Lang}", $resinfo);
				
			return $resinfo;
		}
		
		/**
		 * Get room faciility types
		 *
		 * @param integer $id
		 * @return array
		 */
		public function GetRoomFacilityTypes($id)
		{
			$params = array(new XML_RPC_Value(array("roomfacilitytype_ids"=>new XML_RPC_Value($id, "int")), "struct"));
			
			return $this->Request("bookings.getRoomFacilityTypes", $params, true);
		}
		
		/**
		 * Get room info types
		 *
		 * @param integer $typeID
		 * @return array
		 */
		public function GetRoomInfoTypes($typeID)
		{
			$params = array(new XML_RPC_Value(array("roominfotype_ids"=>new XML_RPC_Value($typeID, "int")), "struct"));
			
			return $this->Request("bookings.getRoomInfoTypes", $params, true);	
		}
		
		/**
		 * get room info
		 *
		 * @param integer $roomID
		 * @return array
		 */
		public function GetRoomInfo($roomID)
		{
			$params = array(new XML_RPC_Value(array("room_ids"=>new XML_RPC_Value($roomID, "int")), "struct"));
			
			return $this->Request("bookings.getRoomInfo", $params);	
		}
		
		/**
		 * Return room type
		 *
		 * @param integer $typeID
		 * @return array
		 */
		public function GetRoomType($typeID)
		{
			$params = array(new XML_RPC_Value(array("roomtype_ids"=>new XML_RPC_Value($typeID, "int")), "struct"));
			
			return $this->Request("bookings.getRoomTypes", $params, true);
		}
		
		/**
		 * Retrun Hotel facilities
		 *
		 * @param integer $hotelID
		 * @return array
		 */
		public function GetHotelFacilities($hotelID)
		{
			$params = array(new XML_RPC_Value(array("hotel_ids"=>new XML_RPC_Value($hotelID, "int")), "struct"));
			
			$facilities = $this->Request("bookings.getHotelFacilities", $params);
			
			$info = $this->CacheGet("facilityinfo-{$v['hotelfacilitytype_id']}-{$v['facilitytype_id']}-{$this->Lang}");
					
			if ($info)
			{
				$resinfo = $info;	
			}
			else 
			{
				foreach((array)$facilities as $k=>$v)
				{
					if ($v["value"] == 1)
					{
						$to_cache = true;
						$hinfo = $this->GetHotelFacilityTypes($v["hotelfacilitytype_id"]);
						$info = $this->GetFacilityTypes($v["facilitytype_id"]);
												
						foreach ($hinfo as $ik=>$iv)
						{
							if ($iv["languagecode"] == "en" || $iv["languagecode"] == $this->Lang)
							{
								foreach($info as $iik=>$iiv)
								{
									if ($iiv["languagecode"] == $iv["languagecode"])
										$group = $iiv["name"];
								}
								
								$resinfo[$iv["languagecode"]][$group][] = $iv["name"];
							}
						}
					}
				}
		 	}
			
			if ($to_cache)
				$this->CachePut("facilityinfo-{$v['hotelfacilitytype_id']}-{$v['facilitytype_id']}-{$this->Lang}", $resinfo);
				
			return $resinfo;
		}
		
		/**
		 * Retrun hotel facility type
		 *
		 * @param integer $id
		 * @return array
		 */
		public function GetHotelFacilityTypes($id)
		{
			$params = array(new XML_RPC_Value(array("hotelfacilitytype_ids"=>new XML_RPC_Value($id, "int")), "struct"));
			
			return $this->Request("bookings.getHotelFacilityTypes", $params);
		}
		
		/**
		 * Return facility type
		 *
		 * @param integer $id
		 * @return array
		 */
		public function GetFacilityTypes($id)
		{
			$params = array(new XML_RPC_Value(array("facilitytype_ids"=>new XML_RPC_Value($id, "int")), "struct"));
				
			return  $this->Request("bookings.getFacilityTypes", $params, true);
		}
		
		/**
		 * Return Hotel description types
		 *
		 * @param integer $id
		 * @return array
		 */
		public function GetHotelDescriptionTypes($id)
		{
			$params = array(new XML_RPC_Value(array("descriptiontype_ids"=>new XML_RPC_Value($id, "int")), "struct"));
				
			return  $this->Request("bookings.getHotelDescriptionTypes", $params, true);
		}
	}

?>