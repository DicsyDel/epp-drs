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
     * Error codes and descriptions for the Facebook API.
     */

define(API_EC_SUCCESS, 0);

/*
 * GENERAL ERRORS
 */
define(API_EC_UNKNOWN, 1);
define(API_EC_SERVICE, 2);
define(API_EC_METHOD, 3);
define(API_EC_TOO_MANY_CALLS, 4);
define(API_EC_BAD_IP, 5);

/*
 * PARAMETER ERRORS
 */
define(API_EC_PARAM, 100);
define(API_EC_PARAM_API_KEY, 101);
define(API_EC_PARAM_SESSION_KEY, 102);
define(API_EC_PARAM_CALL_ID, 103);
define(API_EC_PARAM_SIGNATURE, 104);
define(API_EC_PARAM_USER_ID, 110);
define(API_EC_PARAM_USER_FIELD, 111);
define(API_EC_PARAM_SOCIAL_FIELD, 112);
define(API_EC_PARAM_ALBUM_ID, 120);

/*
 * USER PERMISSIONS ERRORS
 */
define(API_EC_PERMISSION, 200);
define(API_EC_PERMISSION_USER, 210);
define(API_EC_PERMISSION_ALBUM, 220);
define(API_EC_PERMISSION_PHOTO, 221);

/*
 * DATA EDIT ERRORS
 */
define(API_EC_EDIT, 300);
define(API_EC_EDIT_USER_DATA, 310);
define(API_EC_EDIT_PHOTO, 320);

$api_error_descriptions = array(
    API_EC_SUCCESS           => 'Success',
    API_EC_UNKNOWN           => 'An unknown error occurred',
    API_EC_SERVICE           => 'Service temporarily unavailable',
    API_EC_METHOD            => 'Unknown method',
    API_EC_TOO_MANY_CALLS    => 'Application request limit reached',
    API_EC_BAD_IP            => 'Unauthorized source IP address',
    API_EC_PARAM             => 'Invalid parameter',
    API_EC_PARAM_API_KEY     => 'Invalid API key',
    API_EC_PARAM_SESSION_KEY => 'Session key invalid or no longer valid',
    API_EC_PARAM_CALL_ID     => 'Call_id must be greater than previous',
    API_EC_PARAM_SIGNATURE   => 'Incorrect signature',
    API_EC_PARAM_USER_ID     => 'Invalid user id',
    API_EC_PARAM_USER_FIELD  => 'Invalid user info field',
    API_EC_PARAM_SOCIAL_FIELD => 'Invalid user field',
    API_EC_PARAM_ALBUM_ID    => 'Invalid album id',
    API_EC_PERMISSION        => 'Permissions error',
    API_EC_PERMISSION_USER   => 'User not visible',
    API_EC_PERMISSION_ALBUM  => 'Album not visible',
    API_EC_PERMISSION_PHOTO  => 'Photo not visible',
    API_EC_EDIT              => 'Edit failure',
    API_EC_EDIT_USER_DATA    => 'User data edit failure',
    API_EC_EDIT_PHOTO        => 'Photo edit failure'
);

    /**
     * @name FacebookAPI
     * @category Libwebta
     * @package NET_API
     * @subpackage Facebook
     * @copyright Copyright (c) 2006 Facebook, Inc.
     */
class FacebookAPI extends HTTPClient 
{
	public $Secret;
	public $SessionKey;
	public $API_Key;
	public $URL;
	public $IsDesktop;
	public $SessionSecret;
	public $DebugMode;

	/**
	 * Create the client.
	 * @param string $session_key if you haven't gotten a session key yet, leave
	 * this as null and then set it later by just directly accessing the
	 * $session_key member variable.
	 * @param bool   $desktop     set to true if you are a desktop client
	 */
	public function __construct($url, $api_key, $secret, $session_key = null, $desktop = false, $throw_errors = true) 
	{
	    parent::__construct();
	    
	    $this->URL 		= $url;
	    $this->Secret 	= $secret;
	    $this->SessionKey = $session_key;
	    $this->API_Key 	= $api_key;
	    $this->IsDesktop 	= $desktop;
	    $this->ThrowErrors = $throw_errors;
	    $this->IsLastCallSuccess = true;
	    $this->LastError = array();
	
		$this->ProfileFields = array("about_me", "affiliations", "birthday", "books",
		    "clubs", "current_location", "first_name", "gender", "hometown_location",
		    "hs_info", "interests", "last_name", "meeting_for", "meeting_sex", "pic",
		    "movies", "music", "name", "notes_count", "political", "profile_update_time",
		    "quote", "relationship_status", "religion", "school_info", "work_history", 
			"significant_other_id", "status", "timezone", "tv", "wall_count"	    
		);
		    
	    if ($this->DebugMode) 
	    {
			$this->CurID = 0;
			echo "
				<script type=\"text/javascript\">
				var types = ['params', 'xml', 'php', 'sxml'];
				function toggleDisplay(id, type) {
				  for each (var t in types) {
				    if (t != type || document.getElementById(t + id).style.display == 'block') {
				      document.getElementById(t + id).style.display = 'none';
				    } else {
				      document.getElementById(t + id).style.display = 'block';
				    }
				  }
				  return false;
				}
				</script>
			";
	    }
	}

	/**
	 * Retrieves the events of the currently logged in user between the provided UTC
	 * times
	 * @param int $start_time UTC lower bound
	 * @param int $end_time UTC upper_bound
	 * @return array of friends
	 */
	public function GetEventInWindow($start_time, $end_time) 
	{
		return $this->CallMethod('facebook.events.getInWindow', array('start_time' => $start_time, 'end_time' => $end_time));
	}

	/** Retrieves the friends of the currently logged in user.
	 * @return array of friends
	 */
	public function GetFriends() 
	{
		return $this->CallMethod('facebook.friends.get', array());
	}

	/**
	* Retrieves the friends of the currently logged in user, who are also users
	* of the calling application.
	* @return array of friends
	*/
	public function GetAppFriends() 
	{
		return $this->CallMethod('facebook.friends.getAppUsers', array());
	}

	/**
	 * Retrieves the list of id's of people who requested to be the current user's
	 * friend.
	 * @return array of pending friends
	 */
	public function GetFriendsRequests() 
	{
		return $this->CallMethod('facebook.friends.getRequests', array());
	}

	/**
	 * Retrieves the friends of the currently logged in user,filtered by socialmap
	 * type
	 * @param string link_type type denoted by string, e.g. "Coworkers"
	 * @return array of friends
	 */
	public function GetFriendsTyped($link_type) 
	{
		return $this->CallMethod('facebook.friends.getTyped', array('link_type' => $link_type));
	}

	/**
	 * Retrieves the requested info fields for the requested set of user.
	 * @param array $users_arr an array of user ids to get info for
	 * @param array $field_array an array of strings describing the info fields
	 * desired
	 * @return an array of arrays of info fields, which may themselves be arrays :)
	 */
	public function GetUsersInfo($users_arr, $field_array) 
	{
		return $this->CallMethod('facebook.users.getInfo', array('users' => $users_arr, 'fields' => $field_array));
	}
	
	/**
	 * Retrieves the photos that have been tagged as having the given user.
	 * 
	 * @param string $id  the id of the user in the photos.
	 * @param int    $max the number of photos to get (if 0 returns all of them)
	 * @returns an array of photo objects.
	 */
	public function GetPhotosOfUser($id, $max=0) 
	{
		return $this->CallMethod('facebook.photos.getOfUser', array('id'=>$id, 'max'=>$max));
	}

	/**
	 * Retrieves the albums created by the given user.
	 * 
	 * @param string $id the id of the user whose albums you want.
	 * @returns an array of album objects.
	 */
	public function GetPhotosAlbums($id) 
	{
		return $this->CallMethod('facebook.photos.getAlbums', array('id'=>$id));
	}

	/**
	 * Retrieves the photos in a given album.
	 * @param string $aid the id of the album you want, as returned by
	 * photos_getAlbums.
	 * @param string $uid the id of the user whose albums you want.
	 * @returns an array of photo objects.
	 */
	public function GetPhotosFromAlbum($aid, $uid) 
	{
		return $this->CallMethod('facebook.photos.getFromAlbum', array('aid'=>$aid, 'id'=>$uid));
	}

	/**
	 * Retrieves the counts of unread and total messages for the current user.
	 * @return an associative array with keys 'unread' and 'total'
	 */
	public function GetMessagesCount() 
	{
		return $this->CallMethod('facebook.messages.getCount', array());
	}

	/**
	 * Retrieves the number of comments on the current user's photos.
	 * @return an int representing the number of comments
	 */
	public function GetPhotoCommentCount() 
	{
		return $this->CallMethod('facebook.photos.getCommentCount', array());
	}

	/**
	 * Retrieves the number of pokes (unseen and total) for the current user.
	 * @return an associative array with keys 'unseen' and 'total'
	 */
	public function GetPokesCount() 
	{
		return $this->CallMethod('facebook.pokes.getCount', array());
	}

	/**
	 * Retrieves whether or not two users are friends (note: this is reflexive, the
	 * params can be swapped with no effect).
	 * @param array $id1: array of ids of some length X
	 * @param array $id2: array of ids of SAME length X
	 * @return array of elements in {0,1} of length X, indicating whether each pair
	 * are friends
	 */
	public function AreFriends($id1, $id2) 
	{
		return $this->CallMethod('facebook.friends.areFriends', array('id1'=>$id1, 'id2'=>$id2));
	}

	/**
	 * Intended for use by desktop clients.  Call this function and store the
	 * result, using it first to generate the appropriate login url and then to
	 * retrieve the session information.
	 * @return assoc array with 'token' => the auth_token string to be passed into
	 * login.php and auth_getSession.
	 */
	public function CreateToken() 
	{
		return $this->CallMethod('facebook.auth.createToken', array());
	}

	/**
	 * Call this function to retrieve the session information after your user has
	 * logged in.
	 * @param string $auth_token the token returned by auth_createToken or passed
	 * back to your callback_url.
	 */
	public function GetSession($auth_token) 
	{
		if ($this->IsDesktop) 
		{
		  $real_server_addr = $this->URL;
		  $this->URL = str_replace('http://', 'https://', $real_server_addr);
		}
		$result = $this->CallMethod('facebook.auth.getSession', array('auth_token' => $auth_token));
		$this->SessionKey = $result['session_key'];
		if ($this->IsDesktop) 
		{
		  $this->SessionSecret = $result['secret'];
		  $this->URL = $real_server_addr;
		}
		return $result;
	}
	
	
	/**
	 * Call remote method
	 * 
	 * @param string remote method name
	 * @param array params for called method
	 */
	private function CallMethod($method, $params) 
	{
		$this->IsLastCallSuccess = true;
		$this->LastError = array();
		
		$xml = $this->SendPost($method, $params);
		$sxml = simplexml_load_string($xml);
		$result = self::SimpleXML2Array($sxml);
		if ($this->DebugMode) 
		{
		  // output the raw xml and its corresponding php object, for debugging:
		  print '<div style="margin: 10px 30px; padding: 5px; border: 2px solid black; background: gray; color: white; font-size: 12px; font-weight: bold;">';
		  $this->CurID++;
		  print $this->CurID . ': Called ' . $method . ', show ' .
		        '<a href=# onclick="return toggleDisplay(' . $this->CurID . ', \'params\');">Params</a> | '.
		        '<a href=# onclick="return toggleDisplay(' . $this->CurID . ', \'xml\');">XML</a> | '.
		        '<a href=# onclick="return toggleDisplay(' . $this->CurID . ', \'sxml\');">SXML</a> | '.
		        '<a href=# onclick="return toggleDisplay(' . $this->CurID . ', \'php\');">PHP</a>';
		  print '<pre id="params'.$this->CurID.'" style="display: none; overflow: auto;">'.print_r($params, true).'</pre>';
		  print '<pre id="xml'.$this->CurID.'" style="display: none; overflow: auto;">'.htmlspecialchars($xml).'</pre>';
		  print '<pre id="php'.$this->CurID.'" style="display: none; overflow: auto;">'.print_r($result, true).'</pre>';
		  print '<pre id="sxml'.$this->CurID.'" style="display: none; overflow: auto;">'.print_r($sxml, true).'</pre>';
		  print '</div>';
		}
		
		if (is_array($result) && isset($result['fb_error'])) 
		{
		  $this->LastCallSuccess = false;
		  $this->LastError = $result['fb_error'];
		
		  if ($this->ThrowErrors) 
		  {
		  	throw new FacebookAPIException($result['fb_error']['msg'], $result['fb_error']['code']);
		  }
		}
		    
	   return $result;
	}
	
	/**
	 * Send rest request
	 */	
	public function SendPost($method, $params) 
	{
		$params['method'] = $method;
		$params['session_key'] = $this->SessionKey;
		$params['api_key'] = $this->API_Key;
		$params['call_id'] = microtime(true);
		
		$secret = ($this->IsDesktop && $method != 'facebook.auth.getSession' && $method != 'facebook.auth.createToken') 
	  		? $this->SessionSecret 
	  		: $this->Secret;
		
		foreach($params as $k => &$param)
		{
			if (is_array($param))
				$params[$k] = implode(",", $param);
		}
		
		$params = http_build_query($params);
		parse_str($params, $params);
		
		$params['sig'] = $this->GenerateSig($params, $secret);
		
		$this->SetParams(array());
		$this->Fetch($this->URL, $params, true);
		return $this->Result;
	}
	
	/**
	 * Convert simple XML to array
	 * @param resource simple xml
	 */
	public static function SimpleXML2Array($sxml) 
	{
		$arr = array();
		if ($sxml) 
		{
		  foreach ($sxml as $k => $v) 
		  {
		    if ($v['id']) {
		      $arr[(string)$v['id']] = self::SimpleXML2Array($v);
		    } else if (substr($k, -4) == '_elt') {
		      $arr[] = self::SimpleXML2Array($v);
		    } else {
		      $arr[$k] = self::SimpleXML2Array($v);
		    }
		  }
		}

		return (sizeof($arr) > 0) ? $arr : (string)$sxml;
	}
	  
	/**
	 * Generate a signature for the API call.  Should be copied into the client
	 * library and also used on the server to validate signatures.
	 *
	 * @author ari
	 */
	function GenerateSig($params, $secret) 
	{
		$str = '';
		
		ksort($params);
		foreach ($params as $k => $v) 
		{
			if ($k != 'sig')
				$str .= "$k=$v";
		}
		
		$str .= $secret;
		
		return md5($str);
	}
	

}

class FacebookAPIException extends Exception 
{
	
}

?>