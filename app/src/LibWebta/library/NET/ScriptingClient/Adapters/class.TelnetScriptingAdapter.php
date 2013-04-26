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
	
	Core::Load("NET/Telnet/class.TelnetClient.php");

	/**
	 * @name TelnetScriptingAdapter
	 * @package NET
	 * @subpackage ScriptingClient
	 * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
	 *
	 */
	class TelnetScriptingAdapter extends AbstractScriptingAdapter implements ScriptingAdapter
	{
	    
	    /**
	     * Telnet client instance
	     *
	     * @var TelnetClient
	     */
	    private $TelnetClient;
	    
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
	    	$this->Options["port"] = 23;
	    	$this->Options["loginPromt"] = "login:";
	    	$this->Options["passwordPromt"] = "Password:";
	    }
        
	    /**
	     * Destructor
	     *
	     */
	    public function __destruct()
	    {
	        if ($this->TelnetClient)
	           $this->TelnetClient->Disconnect();
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
		    $this->TelnetClient = new TelnetClient($this->Options["host"], $this->Options["port"], $this->Options["timeout"]);
		    
		    if ($this->TelnetClient->Connect())
		    {
		        if ( $this->Options['login'] && $this->Options['password'])
		        {
    		        if ($this->TelnetClient->Login(   $this->Options['login'], 
    		                                          $this->Options['password'],
    		                                          $this->Options['loginPromt'],
    		                                          $this->Options['passwordPromt'],
    		                                          $this->Options["consolePromt"]
    		                                       )
    		           )
    		        return true;
		        }
		        else
		          return $this->TelnetClient->WaitForString($this->Options["consolePromt"]);
		    }
		    
	        return false;
		}
        
		/**
		 * Execute script
		 *
		 * @param string $script
		 * @return string
		 */
	    public function SendLine($command)
	    {
	        $this->TelnetClient->Send($command);
		    return true;
		}
	    
		/**
		 * Disconnect from server
		 *
		 * @return unknown
		 */
		public function Disconnect()
		{
		    if ($this->TelnetClient)
	           $this->TelnetClient->Disconnect();
	           
	        return true;
		}
	    
		/**
		 * REad first line from response
		 *
		 * @return string
		 */
		public function ReadResponse()
		{
		    return $this->TelnetClient->ReadAll();
		}
		
		/**
		 * Return last response from server in WaitFor function
		 *
		 * @return string
		 */
		public function GetLastResponse()
		{
		    return $this->TelnetClient->Response;
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
		    $state = "NORMAL";		    
		    $this->ScriptExecutionLog = "";
		    
            $script_lines = $this->ParseScript($script);      
            foreach ($script_lines as $linenum=>$line)
            {        
                if (preg_match("/^\[EXPECT '(.*)'\]$/si", $line, $matches))
                {
                    $waitforstring = $this->PrepareScriptLine($matches[1], $params);
                    
                    if (!preg_match("/\[ENDEXPECT\]/si", $script_lines[$linenum-1]))
                    {
                        $starttime = Core::GetTimeStamp(); // Start time
                        $res = $this->WaitForString($waitforstring); // Wait for string
                        $endtime = Core::GetTimeStamp(); // End time
                        
                        $time = round($endtime-$starttime, 4);
                        $this->ScriptExecutionLog .= "RECV[{$time}]: ".$this->GetLastResponse()."\n";
                    }
                    else
                        $res = $this->WaitForString($waitforstring, $this->GetLastResponse());
                    
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
                elseif (preg_match("/^\[SETTIMEOUT ([0-9]+)\]$/si", $line, $matches))
                {
                    $this->TelnetClient->SetTimeout($matches[1]);
                }
                else
                {
                    if ($state == "NORMAL")
                    {
                        if (preg_match("/^\[TERMINATE[\s]*([0-9]+)?\]$/si", $line, $matches))
                        {
                            if ($matches[1] > 0)
                            {
                                $this->ScriptExecutionLog .= "[TERMINATED WITH CODE {$matches[1]}]\n";
                                return false;
                            }
                            else 
                            {
                                $this->ScriptExecutionLog .= "[TERMINATED]\n";
                                return true;
                            }
                        }
                        else 
                        {
                            $line = $this->PrepareScriptLine($line, $params);
                            
                            $starttime = Core::GetTimeStamp(); // Start time
                            $this->SendLine($line);
                            $endtime = Core::GetTimeStamp(); // End time
                        
                            $time = round($endtime-$starttime, 4);
                            
                            $this->ScriptExecutionLog .= "SEND[{$time}]: {$line}\n";
                            
                            if (!preg_match("/^\[EXPECT '(.*)'\]$/si", $script_lines[$linenum+1]))
                            {
                                $starttime = Core::GetTimeStamp(); // Start time
                                $resp = $this->ReadResponse();
                                $endtime = Core::GetTimeStamp(); // End time
                                
                                $time = round($endtime-$starttime, 4);
                                
                                $this->ScriptExecutionLog .= "RECV[{$time}]: {$resp}\n";
                            }
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
		private function WaitForString($string, $buf = false)
		{
		    return $this->TelnetClient->WaitForString($string, false, $buf);
		}
	}
	
?>