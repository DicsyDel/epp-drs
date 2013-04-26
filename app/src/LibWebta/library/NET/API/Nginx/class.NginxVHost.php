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
     * @subpackage Nginx
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/* Template example
	server {
        listen  %listen_host%:%listen_port%;
        server_name %server_names%;

        location / {
            root   %doc_root%;
            index  index.html index.htm;
        }
	}
	*/
	
	/**
     * @name       NginxVhost
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Nginx
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class NginxVhost extends Core
	{
		/**
		 * Vhost config template
		 *
		 * @var string
		 * @access private
		 */
		private $Template;
		
		/**
		 * Listen host
		 *
		 * @var string
		 * @access private
		 */
		private $ListenHost;
		
		/**
		 * Listen port
		 *
		 * @var integer
		 * @access private
		 */
		private $ListenPort;
		
		/**
		 * Server names
		 *
		 * @var array
		 * @access private
		 */
		private $ServerNames;
		
		/**
		 * Docuemnt root
		 *
		 * @var string
		 * @access private
		 */
		private $DocRoot;
		
		/**
		 * Nginx Virtualhost Constructor
		 *
		 * @param string $template Virtualhost config template
		 */
		public function __construct($template)
		{
			$this->Template = $template;
			$this->ServerNames = array();
		}
		
		/**
		 * Listen params
		 *
		 * @param string $host
		 * @param integer $port
		 */
		public function SetListen($host, $port = 80)
		{
			$this->ListenHost = $host;
			$this->ListenPort = $port;
		}
		
		/**
		 * Add Virtualhost server name
		 *
		 * @param string $name
		 * @return bool
		 */
		public function AddServerName($name)
		{
			if (!in_array($name, $this->ServerNames))
			{
				$this->ServerNames[] = $name;
			}
			else 
			{
				Core::RaiseWarning("This name already added.");
				return false;
			}
		}
		
		/**
		 * Remove Virtualhost server name
		 *
		 * @param string $name
		 * @return bool
		 */
		public function RemoveServerName($name)
		{
			foreach($this->ServerNames as &$row)
			{
				if ($row == $name)
					$row = null;
			}
			
			return true;
		}
		
		/**
		 * Set document root for virtualhost
		 *
		 * @param string $path
		 */
		public function SetDocRoot($path)
		{
			$this->DocRoot = $path;
		}
		
		public function GetConfigString()
		{
			// Check server names
			if (count($this->ServerNames) == 0)
			{
				Core::RaiseWarning(_("You must add at least one server name."));
				return false;
			}
			
			// Check Listen Host
			if (!$this->ListenHost)
			{
				Core::RaiseWarning(_("Please specify listen host."));
				return false;
			}
			
			// Check document root
			if (!$this->DocRoot)
			{
				Core::RaiseWarning(_("Please specify document root folder."));
				return false;
			}
			
			$server_names = implode(' ', $this->ServerNames);
			
			$replace = array("%listen_host%", "%listen_port%", "%server_names%", "%doc_root%");
			$vars = array($this->ListenHost, $this->ListenPort, $server_names, $this->DocRoot);
			
			$retval = str_replace($replace, $vars, $this->Template);
			
			return $retval;
		}
	}
?>