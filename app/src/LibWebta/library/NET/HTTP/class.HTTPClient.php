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
     * @subpackage HTTP
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
	 * @name HTTPClient
	 * @category LibWebta
	 * @package NET
	 * @subpackage HTTP
	 * @todo Enable in HTTP Client socket connections if curl functions are disabled
	 * @author Sergey Koksharov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	class HTTPClient extends Core
	{
		
		/**
		 * Default user agent for HTTP Client
		 *
		 */
		const USER_AGENT = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.4) Gecko/20060508 Firefox/1.5.0.4';
		
		/**
		 * Default cookie file (when cookies enabled)
		 * 
		 */
		const COOKIE_FILE = '/tmp/cookies.txt';
		
		/**
		 * The number of seconds to wait whilst trying to connect. Use 0 to wait
		 * indefinitely
		 * 
		 */
		const CONNECT_TIMEOUT = 60;
		
		/**
		 * The maximum number of seconds to allow CURL functions to execute.
		 * 
		 */
		const CURL_TIMEOUT = 60;
		 
		/**
		 * Max redirects
		 * 
		 */
		const MAX_REDIRECTS = 3;
		
		/**
		 * Default debug file (when debugging enabled)
		 * 
		 */
		const DEBUG_FILE = "/tmp/HTTPClient_curl_verbose.log";
		
		
		/**
		 * CURL handler
		 * 
		 * @var resource
		 * @access protected
		 */
		protected $Curl;
		
		/**
		 * Parameters query string
		 * 
		 * @var array
		 * @access private
		 */
		private $Params;
		
		/**
		 * User agent for curl query
		 * 
		 * @var string
		 * @access private
		 */
		private $UserAgent; 
		
		/**
		 * Result of HTTP query
		 * 
		 * @var string
		 * @access public
		 */
		public $Result;
		
		/**
		 * HTTP headers for request
		 * 
		 * @var array
		 * @access private
		 */
		private $Headers;
		
		/**
		 * Connection status
		 * 
		 * @var bool
		 * @access public
		 */
		public $Connected;
		
		
		/**
		 * Debug mode (true if enabled)
		 * 
		 * @var bool
		 * @access private
		 */
		private $Debug;
		
		/**
		 * Path to file for debugging
		 * 
		 * @var string
		 * @access private
		 */
		private $DebugFile;
		
		/**
		 * File handler for debug file
		 * 
		 * @var resource
		 * @access private
		 */
		private $DebugHandler;
		
		
		/**
		 * Timeout for curl connection
		 * 
		 * @var integer
		 * @access private
		 */
		private $ConnectTimeout;
		
		/**
		 * Timeout for curl function execute
		 * 
		 * @vars integer
		 * @access private
		 */
		private $CurlTimeout;
		
		/**
		 * Usage of redirects (true when enabled)
		 * 
		 * @var bool
		 * @access private
		 */
		private $UseRedirects;
		
		/**
		 * Max allowed redirects
		 * 
		 * @var integer
		 * @access private
		 */
		private $MaxRedirects;
		
		/**
		 * Cookies for http query header
		 * 
		 * @var string
		 * @access private
		 */
		private $Cookies;
		
		/**
		 * Receive headers from server or not
		 * 
		 * @var bool
		 * @access private
		 */
		private $NeedHeaders;
		
		/**
		 * Use cookies while redirecting
		 * 
		 * @var bool
		 * @access private
		 */
		private $UseCookies;
		
		/**
		 * Path to cookie file
		 * 
		 * @var string
		 * @access private
		 */
		private $CookiePath;
		
		/**
		 * Ignore curl errors while execute query
		 * 
		 * @var bool
		 * @access private
		 */
		private $IgnoreErrors;
		
		/**
		 * User authentication data in format [login]:[pass]
		 * 
		 * @var string
		 * @access private
		 */
		private $Credentials;
		
		/**
		 * Class constructor. HTTP client uses CURL functions for connection.
		 * 
		 * @param resource $curl Opened curl handler
		 */
		function __construct($curl = null)
		{
			parent::__construct();
			
			$this->SetUserAgent(self::USER_AGENT);
			$this->SetTimeouts(self::CONNECT_TIMEOUT, self::CURL_TIMEOUT);
			$this->UseRedirects(false, self::MAX_REDIRECTS);
			
			$this->Curl = (is_resource($curl)) ? $curl : curl_init();

			if ($this->UserAgent)
				curl_setopt($this->Curl, CURLOPT_USERAGENT, $this->UserAgent);
		}
				

		/**
		 * Connecting to server by given URL with given parameters and fetching
		 * content
		 * 
		 * @param string $url URL to the server
		 * @param array $params Params to send in the query
		 * @param bool $post If variable value is true then query method will
		 * be POST. Otherwise uses GET method
		 * @return string Result content if success
		 * @access public
		 */
		public function Fetch($url, $params = array(), $post = false)
		{
			if (!$this->Curl) 
                return false;
			
			$chunks = parse_url($url);
			
			if ($this->Params && is_array($params))
				$this->Params = array_merge($this->Params, $params);
			else
				$this->SetParams($params);
			
			if ($chunks['scheme'] == 'https')
			{
				curl_setopt($this->Curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($this->Curl, CURLOPT_SSL_VERIFYHOST,1);
			}
			
			curl_setopt($this->Curl, CURLOPT_RETURNTRANSFER,1);
			
			curl_setopt($this->Curl, CURLOPT_URL, $url); 

			if ($this->Headers) 
				curl_setopt($this->Curl, CURLOPT_HTTPHEADER, $this->Headers);
			
		    if ($this->Credentials)
		        curl_setopt($this->Curl, CURLOPT_USERPWD, $this->Credentials);
				
			if ($post)
			{
				curl_setopt($this->Curl, CURLOPT_POST,1);
				
				if (count($this->Params) > 0)
                    curl_setopt($this->Curl, CURLOPT_POSTFIELDS, http_build_query($this->Params));
			}
							
			if ($this->Cookies)
				curl_setopt($this->Curl, CURLOPT_COOKIE, $this->Cookies);
			
			if ($this->UseCookies)
				curl_setopt($this->Curl, CURLOPT_COOKIEFILE, $this->CookiePath);
			
			if ($this->ConnectTimeout)
				curl_setopt($this->Curl, CURLOPT_CONNECTTIMEOUT, $this->ConnectTimeout);

			if ($this->CurlTimeout)
				curl_setopt($this->Curl, CURLOPT_TIMEOUT, $this->CurlTimeout);

			if ($this->UseRedirects)
			{
				curl_setopt($this->Curl, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($this->Curl, CURLOPT_MAXREDIRS, $this->MaxRedirects);
			}
			else
				curl_setopt($this->Curl, CURLOPT_FOLLOWLOCATION, 0);
			
			
			if ($this->NeedHeaders)
				curl_setopt($this->Curl, CURLOPT_HEADER, 1);
			
			if ($this->Debug)
			{
				curl_setopt($this->Curl, CURLOPT_VERBOSE, 1);
				curl_setopt($this->Curl, CURLOPT_STDERR, $this->DebugHandler);
			}
						
			$this->Result = trim(curl_exec($this->Curl));
			
			if (curl_error($this->Curl) && !$this->IgnoreErrors)
			{
				Core::RaiseWarning(curl_error($this->Curl));
				
				$this->Result = '';
				return false;
			}
			
			return $this->Result;
		}
		
		
		/**
		 * Set user agent for query
		 * 
		 * @param string $agent Name of the user agent
		 * @access public
		 */
		public function SetUserAgent($agent)
		{
			$this->UserAgent = $agent;
		}
		
		
		/**
		 * Set query parameters
		 * 
		 * @param array $params Array of query parameters
		 * @access public
		 */
		public function SetParams($params)
		{
			if (is_array($params))
				$this->Params = $params;
		}
		
		
		/**
		 * Set HTTP headers
		 * 
		 * @param array $headers Array of HTTP headers
		 * @access public
		 */
		public function SetHeaders($headers)
		{
			if (is_array($headers))
				$this->Headers = $headers;
		}
		
		
		/**
		 * Set max timeout for connect and cURL function execution
		 * 
		 * @param integer $timeout Timemout for curl connection
		 * @param integer $func_timeout Timeout for curl function execution
		 * @access public
		 */
		public function SetTimeouts($timeout, $func_timeout)
		{
			$this->ConnectTimeout = $timeout;
			$this->CurlTimeout = $func_timeout;
		}
		
		/**
		 * Set credentials for user autentication
		 * 
		 * @param string $login User name/login
		 * @param string $pass User password
		 * @access public
		 */
		public function SetCredentials($login, $pass)
		{
		    $this->Credentials = "{$login}:{$pass}";
		}
		
		/**
		 * Usage redirects while query
		 * 
		 * @param bool $use True if needed to use redirects, otherwise - false.
		 * By default this variable value is true. 
		 * @param integer $max_redir Count of max allowed redirects. If not
		 * determined then used MAX_REDIRECTS constant
		 * @uses MAX_REDIRECTS Class constant for max allowed redirects
		 * @access public
		 */
		public function UseRedirects($use = true, $max_redir = null)
		{
			$this->MaxRedirects = (is_numeric($max_redir)) ? $max_redir : self::MAX_REDIRECTS;
			$this->UseRedirects = (bool)$use;
		}
		
		/**
		 * Set cookies function
		 * 
		 * @param string|array $cookies Cookies for query. If variable type is
		 * array then used http_build_query function
		 * @access public
		 */
		public function SetCookies($cookies)
		{
			$this->Cookies = is_array($cookies) ? http_build_query($cookies) : $cookies;
		}
		
		/**
		 * Receiving headers in result content
		 * 
		 * @param bool $receive Boolean variable for indication requirement of
		 * headers in result fetching string
		 * @access public
		 */
		public function ReceiveHeaders($receive = true)
		{
			$this->NeedHeaders = $receive;
		}
		
		
		/**
		 * Get cookies from result. Parse result string and return all cookies
		 * in headers.
		 * 
		 * @return array Array of cookies in result headers
		 * @access public
		 */
		public function GetCookies()
		{
			if (!$this->Result) 
                return '';
			
			preg_match("/^Set\-Cookie: (.*)\n/m", $this->Result, $matches);
			return $matches[1];
		}
		
		/**
		 * Usage of cookies
		 * 
		 * @param bool $use Use cookies while sending query
		 * @param string $path Path to cookie file. If not defined then path
		 * will be constant COOKIE_FILE value
		 * @uses COOKIE_FILE Default cookie file path
		 * @access public
		 */
		public function UseCookies($use = true, $path = null)
		{
			$this->UseCookies = $use;
			$this->CookiePath = $path ? $path : self::COOKIE_FILE;
		}
		
		/**
		 * Switch debug mode
		 * 
		 * @param bool $turnon Enable debug mode
		 * @param string $filename Name of the debug file. If not defined then
		 * used DEBUG_FILE constant
		 * @uses DEBUG_FILE Default debug filename 
		 * @access public
		 */
		public function Debug($turnon = false, $filename = false)
		{
			$this->Debug = $turnon;
			$this->DebugFile = $filename ? $filename : self::DEBUG_FILE;
			@touch($this->DebugFile);
			@chmod($this->DebugFile, 0777);
			
			if ($turnon)
				$this->DebugHandler = fopen($this->DebugFile, "a+");
			else
			{
				@fclose($this->DebugHandler);
				$this->DebugHandler = null;
			}
		}
		
		/**
		 * Ignoring errors in result content
		 * 
		 * @param bool $turnon Ignoring errors flag. True by default
		 * @access public
		 */
		public function IgnoreErrors($turnon = true)
		{
			$this->IgnoreErrors = $turnon;
		}
		
		
		/**
		 * Class desctructor. Clearing curl handler and unlink cookie file
		 * 
		 */
		function __destruct()
		{
			$this->Curl = null;
			if ($this->CookiePath) @unlink($this->CookiePath);
			$this->Debug(false);
		}
		
		public static function HTTPDigestParse($txt)
        {
            // protect against missing data
            $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
            $data = array();
        
            preg_match_all('@(\w+)=([\'"]?)([a-zA-Z0-9=./\_-]+)\2@', $txt, $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $data[$m[1]] = $m[3];
                unset($needed_parts[$m[1]]);
            }
            
            return $needed_parts ? false : $data;
        }
	}
