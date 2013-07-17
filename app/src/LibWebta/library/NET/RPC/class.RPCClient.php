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
     * @subpackage RPC
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	Core::Load("NET/HTTP/HTTPClient");

	/**
	 * @name RPCClient
	 * @package NET
	 * @subpackage RPC
	 * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
	 *
	 */
	class RPCClient extends HTTPClient
	{
		/**
		 * XML RPC Host
		 * with username, password if needed
		 * @var string Host
		 */
		private $Host;
		
		/**
		 * RPC Client Constructor
		 * 
		 * @param string hostname
		 * @param string xml rpc username (optional)
		 * @param string xml rpc password (optional)
		 */
		function __construct($host, $user = "", $pass = "")
		{
			parent::__construct();
			
			$host = str_replace("http://", "", $host);			
			if ($user)
				$host = "{$user}:{$pass}@{$host}";
			
			$this->Host = "http://{$host}";
		}
		
		/**
		 * Call server RPC method
		 * 
		 * @param string method name
		 * @param array arguments
		 * @return string
		 */
		function __call($function, $argv) 
		{
			$request = xmlrpc_encode_request($function, $argv);

			$headers = array(
				'Content-Type: text/xml',
				'Content-Length: '.strlen($request) . "\r\n\r\n" . $request
			);
			
			$this->SetHeaders($headers);
			$this->Fetch($this->Host, array(), true);			
			return xmlrpc_decode($this->Result);
		}
		
	}
	
?>