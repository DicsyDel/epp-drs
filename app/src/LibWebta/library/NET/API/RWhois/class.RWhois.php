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
     * @subpackage RWhois
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */	
	
	
    Core::Load("System/Independent/Shell/ShellFactory");    

    /**
     * Rwhois client
     * 
     * @name       RWhois
     * @category   LibWebta
     * @package    NET_API
     * @subpackage RWhois
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class RWhois extends Core
    {
        /**
         * Shell instance
         *
         * @var object
         */
    	private $Shell;
    	
    	/**
    	 * Path to rwhois
    	 *
    	 * @var string
    	 */
    	private $RWhoisBinPath;
    	
    	/**
    	 * RWhois server
    	 *
    	 * @var string
    	 */
    	private $Server;
    	
    	/**
    	 * Default Path to RWhois
    	 *
    	 */
    	const RWHOIS_PATH = "/usr/local/bin/rwhois";
    	
    	/**
    	 * Default RWhois server
    	 *
    	 */
    	const DEF_SERVER = "rwhois.arin.net";
    	
    	/**
    	 * RWHois constructor
    	 * @ignore
    	 *
    	 */
    	function __construct()
    	{     
    	    $this->Shell = ShellFactory::GetShellInstance();
    	    
			$this->RWhoisPath = (defined("CF_RWHOIS_PATH")) ? CF_RWHOIS_PATH : self::RWHOIS_PATH;
    	}
    	
    	/**
    	 * Send Request to RWhois server and return server response
    	 *
    	 * @param string $query Query string
    	 * @param string $server RWHois server host
    	 * @return string Server response or false
    	 */
    	public function Request($query, $server = false)
    	{
    	    $query = escapeshellcmd($query);
    	    
    	    if ($server)
    	       $this->Server = escapeshellcmd($server);
    	    else 
    	       $this->Server = self::DEF_SERVER;
    	       
    	    $res = $this->Shell->QueryRaw("{$this->RWhoisPath} -rs {$this->Server} {$query}");
    	    
    	    if (!stristr($res, "error querying"))
    	       return $res;
    	    else 
    	       return false;
    	}
    }

?>