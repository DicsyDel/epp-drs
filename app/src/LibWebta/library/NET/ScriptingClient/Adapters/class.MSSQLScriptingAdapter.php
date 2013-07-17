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
     * @subpackage ScriptingClient
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
	 * @name MSSQLScriptingAdapter
	 * @package NET
	 * @subpackage ScriptingClient
	 * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
	 *
	 */
	class MSSQLScriptingAdapter extends AbstractScriptingAdapter implements ScriptingAdapter
	{
	    
	    /**
	     * Telnet client instance
	     *
	     * @var TelnetClient
	     */
	    private $DB;
	    
	    /**
	     * Array with adapter options
	     *
	     * @var array
	     */
	    private $Options;
	    
	    
	    /**
	     * Log
	     *
	     * @var string
	     */
	    private $ScriptExecutionLog;
	    /**
	    * Class Constructor
	    *
	    */
	    public function __construct()
	    {
	    	parent::__construct();
	    	
	    	$this->Options = array();
	    	$this->Options["UseCookies"] = true;
	    }
        
	    /**
	     * Destructor
	     *
	     */
	    public function __destruct()
	    {
	        $this->DB = null;
	    }
	    
	    /**
	     * Function sets adapter options
	     *
	     * @param string $optionKey
	     * @param string $optionValue
	     * @return bool
	     */
	    public function SetOption($optionKey, $optionValue)
	    {
	        $this->Options[$optionKey] = $optionValue;
	        return true;
	    }
	   
	    /**
	     * Connect to telnet
	     *
	     * @return bool
	     */
		public function Connect()
		{  
		    $connection_info["host"] = $this->Options["host"];
		    $connection_info["user"] = $this->Options["login"];
		    $connection_info["pass"] = $this->Options["password"];
		    $connection_info["name"] = $this->Options["dbname"];
	    
		    $this->DB = Core::GetDBInstance($connection_info, true, 'mssql');
		    if (!$this->DB)   	     
                return false;

	        return true;
		}
        
		/**
		 * Execute script
		 *
		 * @param string $script
		 * @return string
		 */
	    public function SendLine($command)
	    {
	        Core::RaiseError("SendLine not supported in MSSQLScriptingAdapter");
		}
	    
		/**
		 * Disconnect from server
		 *
		 * @return unknown
		 */
		public function Disconnect()
		{          
	        return true;
		}
	    
		/**
		 * REad first line from response
		 *
		 * @return string
		 */
		public function ReadResponse()
		{
		    return false;
		}
		
		/**
		 * Return last response from server in WaitFor function
		 *
		 * @return string
		 */
		public function GetLastResponse()
		{
		    return false;
		}
		
		/**
		 * Execute script
		 *
		 * @param string $script
		 * @param array $params
		 * @return bool
		 */
		public function Execute($script, $params)
		{
		    $script = $this->PrepareScriptLine($script, $params);
		    $lines = explode(";", $script);
		    
            $this->DB->BeginTrans();
            try 
            {
                foreach ($lines as $line)
                {
                    if (strlen($line) > 0)
                        $this->DB->Execute(trim($line));
                }
                
                $this->DB->CommitTrans();
            }
            catch(Exception $e)
            {
                $this->DB->RollbackTrans();
                return false;
            }
		    
		    return true;
		}
		
		/**
		 * Return log of last Execute command
		 *
		 * @return string
		 */
		public function GetScriptExecutionLog()
		{
		    return false;
		}
		
		/**
		 * Wait for string
		 *
		 * @param string $string
		 * @return bool
		 */
		private function WaitForString($string)
		{
		    return false;
		}
	}
	
?>