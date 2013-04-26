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
	
	Core::Load("NET/SSH/class.SSH2.php");

	
	/**
	 * @name SSHScriptingAdapter
	 * @package NET
	 * @subpackage ScriptingClient
	 * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
	 *
	 */
	class SSHScriptingAdapter extends AbstractScriptingAdapter implements ScriptingAdapter
	{
	    
	    /**
	     * Telnet client instance
	     *
	     * @var TelnetClient
	     */
	    private $SSH;
	    
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
	    	$this->Options["timeout"] = 10;
	    	$this->Options["port"] = 22;
	    	$this->Options["loginPromt"] = "login as:";
	    	$this->Options["passwordPromt"] = "Password:";
	    }
        
	    /**
	     * Destructor
	     *
	     */
	    public function __destruct()
	    {
	        if ($this->SSH)
	           $this->SSH = false;
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
		    $this->SSH = new SSH2();
		    
		    return $this->SSH->Connect(   $this->Options["host"], 
		                                  $this->Options["port"], 
		                                  $this->Options["login"],
		                                  $this->Options["password"]
		                              );
		    
		}
        
		/**
		 * Execute script
		 *
		 * @param string $script
		 * @return string
		 */
	    public function SendLine($command)
	    {
		    return false;
		}
	    
		/**
		 * Disconnect from server
		 *
		 * @return unknown
		 */
		public function Disconnect()
		{
		    if ($this->SSH)
	           $this->SSH = false;
	           
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
		    $lastresponse = "";
		    $state = "NORMAL";		    
		    $this->ScriptExecutionLog = "";
		    
            $script_lines = $this->ParseScript($script);      
            foreach ($script_lines as $linenum=>$line)
            {        
                if (preg_match("/^\[EXPECT '(.*)'\]$/si", $line, $matches))
                {
                    $waitforstring = preg_quote($this->PrepareScriptLine($matches[1], $params), '/');
                    $res = preg_match("/{$waitforstring}/si", $lastresponse);
                    
                    if ($res)
                        $state = "NORMAL";
                    else 
                        $state = "SKIP";
                }
                elseif (preg_match("/^\[ELSE\]$/si", $line))
                {
                    if ($state == "NORMAL")
                        $state = "SKIP";
                    else 
                        $state = "NORMAL";
                }
                elseif (preg_match("/^\[ENDEXPECT\]$/si", $line))
                {
                    $state = "NORMAL";
                }
                else
                {
                    if ($state == "NORMAL")
                    {
                        if (preg_match("/^\[TERMINATE[\s]*([0-9]+)?\]$/si", $line, $matches))
                        {
                            $this->ScriptExecutionLog .= "[TERMINATED]\n";
                            return ($matches[1] > 0) ? false : true;
                        }
                        else 
                        {
                            $line = $this->PrepareScriptLine($line, $params);
                            $this->ScriptExecutionLog .= "SEND: {$line}\n";
                            
                            $lastresponse = $this->SSH->Exec($line, $this->Options["consolePromt"]);

                            $this->ScriptExecutionLog .= "RECV: ".$lastresponse."\n";
                        }
                    }
                }
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
		    return $this->ScriptExecutionLog;
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