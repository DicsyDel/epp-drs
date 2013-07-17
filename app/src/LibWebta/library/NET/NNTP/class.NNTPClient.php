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
     * @package    NET
     * @subpackage NNTP
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */	

	define("NNTPCLIENT_RESPONSECODE_GROUP_SELECTED", 211);
	define("NNTPCLIENT_RESPONSECODE_NO_SUCH_GROUP", 411);
	define("NNTPCLIENT_RESPONSECODE_NEED_PASSWORD", 381);
	define("NNTPCLIENT_RESPONSECODE_SUCCESS_AUTH", 281);
	define("NNTPCLIENT_RESPONSECODE_OVERVIEW_FOLLOWS", 224);
	define("NNTPCLIENT_RESPONSECODE_NO_GROUP_SELECTED", 412);
	define("NNTPCLIENT_RESPONSECODE_NO_ARTICLE_SELECTED", 420);
	define("NNTPCLIENT_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER", 423);
	define("NNTPCLIENT_RESPONSECODE_HEAD_FOLLOWS", 221);
	define("NNTPCLIENT_RESPONSECODE_BODY_FOLLOWS", 222);
	define("NNTPCLIENT_RESPONSECODE_GROUPLIST_FOLLOWS", 215);
	
	/**
     * @name NNTPClient
     * @category   LibWebta
     * @package    NET
     * @subpackage NNTP
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class NNTPClient extends Core 
	{

		private $Connection;

		private $Host;
		private $Port;
		private $Username;
		private $Password;
		private $Timeout;
		
		/**
		 * @ignore
		 *
		 */
		function __construct()
		{
			
		}
		
		/**
		 * Connect and auth on NNTP server
		 *
		 * @param string $host
		 * @param int $port
		 * @param string $username
		 * @param string $password
		 * @param int $timeout
		 * @return bool
		 */
		public function Connect($host, $port = 119, $username, $password, $timeout = 5)
		{
			// Set vars
			$this->Host = $host;
			$this->Port = $port;
			$this->Username = $username;
			$this->Password = $password;
			$this->Timeout = $timeout;
			
			// Connect to server
			$this->Connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
						
			if ($this->Connection)
			{
				socket_set_blocking($this->Connection, true);
			
				// get Hello message from server
				$hello = $this->Request();
				
				if ($username != '' && $password != '')
				{
					$response = $this->Request("AUTHINFO USER {$username}");
					
					if ($response["code"] == NNTPCLIENT_RESPONSECODE_NEED_PASSWORD)
					{
						$response = $this->Request("AUTHINFO PASS {$password}");
						
						switch($response["code"])
						{
							case NNTPCLIENT_RESPONSECODE_SUCCESS_AUTH:
								
								return true;
								
							break;
							
							default:
									Core::RaiseWarning($response["body"]);
									return false;
								break;
						}
					}
					else 
					{
						Core::RaiseWarning($response["body"]);
						return false;
					}
				}
				
				return true;
			}
			else 
			{
				Core::RaiseWarning(sprintf(_("Cannot connect to NNTP server using host '%s' and port '%s': %s"), $host, $port, $errstr));
				return false;
			}
		}
		
		/**
		 * Reconnect to NNTP server
		 *
		 * @return bool
		 */
		public function Reconnect()
		{
			$this->Disconnect();
			return $this->Connect($this->Host, $this->Port, $this->Username, $this->Password, $this->Timeout);
		}
		
		/**
		 * Dissconnect from server
		 * 
		 * @return void
		 */
		public function Disconnect()
		{
			if (is_resource($this->Connection))
			{
				$response = @fwrite($this->Connection, "QUIT\n");
				@fclose($this->Connection);
			}
		}
		
		/**
		 * Get Article body
		 *
		 * @param integer $articleID
		 * @return string
		 */
		public function GetArticleBody($articleID)
		{
			$response = $this->Request("BODY {$articleID}");
			
			switch($response["code"])
			{
				case NNTPCLIENT_RESPONSECODE_BODY_FOLLOWS:
					
						$body = $this->GetTextResponse();
					   
						if ($body)
						  return @implode("\n", $body);
						else 
						  return false;
						
					break;
					
				case NNTPCLIENT_RESPONSECODE_NO_GROUP_SELECTED:
					
						Core::RaiseWarning(_("No groups selected"));
						return false;
						
					break;
					
				case NNTPCLIENT_RESPONSECODE_NO_ARTICLE_SELECTED:
					
						Core::RaiseWarning(_("No article selected"));
						return false;
					
					break;
					
				case NNTPCLIENT_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER:
					
						Core::RaiseWarning(_("No such article in selected range"));
						return false;
					
					break;
					
				default:
						
						Core::RaiseWarning($response["body"]);
						return false;
					
					break;
				
			}
				
		}
		
		/**
		 * Get Article headers
		 *
		 * @param integer $articleID
		 * @param bool $implode
		 * @return array
		 */
		public function GetArticleHead($articleID, $implode = false)
		{
			$response = $this->Request("HEAD {$articleID}");
			
			switch($response["code"])
			{
				case NNTPCLIENT_RESPONSECODE_HEAD_FOLLOWS:
					
						$body = $this->GetTextResponse();
					
						if (!$implode)
							return $body;
						else 
							return implode("\n", $body);
						
					break;
					
				case NNTPCLIENT_RESPONSECODE_NO_GROUP_SELECTED:
					
						Core::RaiseWarning(_("No groups selected"));
						return false;
						
					break;
					
				case NNTPCLIENT_RESPONSECODE_NO_ARTICLE_SELECTED:
					
						Core::RaiseWarning(_("No article selected"));
						return false;
					
					break;
					
				case NNTPCLIENT_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER:
					
						Core::RaiseWarning(_("No such article in selected range"));
						return false;
					
					break;
					
				default:
						
						Core::RaiseWarning($response["body"]);
						return false;
					
					break;
				
			}
		}
		
		public function GetGroupsList()
		{
			$response = $this->Request("LIST");
			
			switch($response["code"])
			{
				case NNTPCLIENT_RESPONSECODE_GROUPLIST_FOLLOWS:
					
					$data = $this->GetTextResponse();
					
					$retval = array();
					foreach($data as $row)
					{
						$chunks = @explode(" ", $row);
						if (count($chunks) == 4)
							$retval[] = trim($chunks[0]);
					}
				
				break;
				
				default:
						
						Core::RaiseWarning($response["body"]);
						return false;
					
					break;
			}
			
			return $retval;
		}
		
		/**
		 * Get Article overview
		 *
		 * @param string $range
		 * @return array
		 */
		public function GetOverview($range)
		{
			$response = $this->Request("XOVER {$range}");
			
			switch($response["code"])
			{
				case NNTPCLIENT_RESPONSECODE_OVERVIEW_FOLLOWS:
					
						$data = $this->GetTextResponse();
						
						foreach ($data as $key => $value) 
		    	            $overview = explode("\t", trim($value));
						
		    	       $format = array(
			    	    				'Number'     => false,
			    	    				'Subject'    => false,
    	                                'From'       => false,
    	                                'Date'       => false,
    	                                'Message-ID' => false,
    	    	                        'References' => false,
    	                                'Bytes'      => false,
    	                                'Lines'      => false
    	                                );
		    	        
    	                
	        			// Copy $format
			    	    $f = $format;
			
			    	   	// Field counter
			    	    $i = 0;
					
						// Loop through forld names in format
			    	    foreach ($f as $tag => $full) 
							$f[$tag] = $overview[$i++];
							
						// Replace article 
						$overview = $f;
						
						return $overview;
		    	        					
					break;
					
				case NNTPCLIENT_RESPONSECODE_NO_GROUP_SELECTED:
					
						Core::RaiseWarning(_("No groups selected"));
						return false;
						
					break;
					
				case NNTPCLIENT_RESPONSECODE_NO_ARTICLE_SELECTED:
					
						Core::RaiseWarning(_("No article selected"));
						return false;
					
					break;
					
				case NNTPCLIENT_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER:
					
						Core::RaiseWarning(_("No such article in selected range"));
						return false;
					
					break;
					
				default:
						
						Core::RaiseWarning($response["body"]);
						return false;
					
					break;
			}
		}
		
		/**
		 * Select News Group
		 *
		 * @param string $groupname
		 * @return array
		 */
		public function SelectGroup($groupname)
		{
			$response = $this->Request("GROUP {$groupname}");
			
			switch($response["code"])
			{
				case NNTPCLIENT_RESPONSECODE_GROUP_SELECTED:
						
						$chunks = explode(" ", $response["body"]);
						
						$retval = array("count" => $chunks[0], "first" => $chunks[1], "last" => $chunks[2], "group" => $groupname);
					
					break;
				
				case NNTPCLIENT_RESPONSECODE_NO_SUCH_GROUP:
					
						Core::RaiseWarning(_("No such group"));
						$retval = false;
						
					break;
				
				default:
					
						Core::RaiseWarning(_("Bad command"));
						$retval = false;
						
					break;
			}
			
			return $retval;
		}
		
		function GetTextResponse()
	    {
	        $data = array();
	        $line = '';
	
	        // Continue until connection is lost
	        while(is_resource($this->Connection) && !@feof($this->Connection)) 
	        {
	
	            // Retrieve and append up to 1024 characters from the server.
	            $line .= @fgets($this->Connection, 1024); 
		    
	            // Continue if the line is not terminated by CRLF
	            if (substr($line, -2) != "\r\n" || strlen($line) < 2) 
	                continue;
	
	            // Validate recieved line
	            if (false) 
	            {
	                // Lines should/may not be longer than 998+2 chars (RFC2822 2.3)
	                if (strlen($line) > 1000) 
	               		return false;
	            }
	
	            // Remove CRLF from the end of the line
	            $line = substr($line, 0, -2);
	
	            // Check if the line terminates the textresponse
	            if ($line == '.')
	            {
	                // return all previous lines
	                return $data;
	            }
	
	            // If 1st char is '.' it's doubled (NNTP/RFC977 2.4.1)
	            if (substr($line, 0, 2) == '..')
	                $line = substr($line, 1);
	
	            // Add the line to the array of lines
	            $data[] = $line;
	
	            // Reset/empty $line
	            $line = '';
	        }
	
	    	//
	    	Core::RaiseWarning('Data stream not terminated with period');
	    	return false;
	    }
		
		/**
		 * Send Request to server and return response
		 *
		 * @param string $command
		 * @return array $retval
		 */
		private function Request($command = false)
		{
			if (is_resource($this->Connection))
			{
				if ($command != '')
					@fwrite($this->Connection, "{$command}\r\n");
				
				$retval = @fgets($this->Connection, 4096);
				$retval = trim($retval);
				
				$code = substr($retval, 0, 4);
				$body = substr($retval, 4);
				
				return array("code" => $code, "body" => $body);
			}
			else 
			{
				Core::RaiseWarning(_("No connection to server"));
				return false;
			}
		}
	}
?>