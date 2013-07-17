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
     * @subpackage Telnet
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */	

	/**
	 * @name TelnetClient
	 * @package NET
	 * @subpackage Telnet
	 * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
	 *
	 */	
	class TelnetClient extends Core 
	{
	    /**
	     * Host
	     *
	     * @var string
	     * @access private
	     */
	    private $Host;
	    
	    /**
	     * Port
	     *
	     * @var integer
	     * @access private
	     */
	    private $Port;
	    
	    /**
	     * Socket
	     *
	     * @var object
	     * @access private
	     */
	    private $Socket;
	    
	    /**
	     * Connect timeout
	     *
	     * @var integer
	     * @access private
	     */
	    private $Timeout;
	    
	    /**
	     * Stream buffer
	     *
	     * @var string
	     * @access private
	     */
	    private $Buffer;
	    
	    /**
	     * Console promt
	     *
	     * @var string
	     * @access private
	     */
	    private $ConsolePromt;
	    
	    /**
	     * Last response
	     *
	     * @var string
	     * @access private
	     */
	    public $Response;
	    
	    /**
	     * Is logged in
	     * @var bool
	     * @access private
	     */
	    private $IsLoggetIn;
	    
	    /**
	     * TelNet client Constructor
	     *
	     * @param string $host
	     * @param integer $port
	     * @param integer $timeout
	     */
	    public function __construct($host, $port = 23, $timeout = 10)
	    {
	        $this->Host = $host;
	        $this->Port = $port;
	        $this->Timeout = 5;
	    }
	    
	    
	    public function SetTimeout($timeout)
	    {
	        $this->Timeout = $timeout;
	    }
	    
	    /**
	     * Open socket connection
	     *
	     * @return bool
	     */
	    public function Connect()
	    {
	        $this->Socket = @fsockopen("tcp://".$this->Host, $this->Port, $errno, $errstr, $this->Timeout);
	        $this->IsLoggetIn = false;
	        
	        if ($this->Socket)
	        {
	            // for telnet session we must use non bloking socket connection
	            stream_set_blocking($this->Socket, false);          
	            
	            // Initialize telnet session
	            $this->InitTelnetSession();    
	            return true;
	        }
	        else
	        {
                return false;
	        }
	    }

	     /**
	     * Login to server
	     *
	     * @param string $login
	     * @param string $password
	     * @param string $loginPromt
	     * @param string $passPromt
	     * @param string $consolePromt
	     * @return bool
	     */
	    public function Login($login, $password, $loginPromt, $passPromt, $consolePromt)
	    {
	        $this->ConsolePromt = $consolePromt;
	        
	        if (!$this->Socket)
	           $this->Connect();
	           
	        do
	        {
                if (!$this->WaitForString($loginPromt))
                    return false;
                  
                $this->Send($login);
                if(!$this->WaitForString($passPromt))
                    return false;
                
                $this->Send($password);
                if (!$this->WaitForString($consolePromt))
                    return false;
	        }
	        while(false);
	        
	        $this->IsLoggetIn = true;
	        
	        return true;
	    }
	    
	    /**
	     * System Command exchange
	     *
	     * @param string $char
	     * @return string
	     */
	    private function SysCmdExchange($char)
	    {
	        Log::Log("TelnetClient::SysCmdExchange Start...", LOG_LEVEL_DEBUG);
	        
	        while ($char == 255) 
            {
                $command = $this->ReadCharFromBuffer();
                
                Log::Log("TelnetClient::SysCmdExchange RECV command {$command}", LOG_LEVEL_DEBUG);
                
    			if ($command == 255)
    				break;
     
    			# WILL, WONT, DO, DONT have options
    			if ($command >= 251 && $command <= 254) 
    			{
    				$option = $this->ReadCharFromBuffer();
    				Log::Log("TelnetClient::SysCmdExchange RECV option {$option}", LOG_LEVEL_DEBUG);
    			}
    			else 
    				$option = false;
    
    			# process telnet command
    			$this->PrepareCommand($command, $option);
    
    			# get next character and continue
    			$char = $this->ReadCharFromBuffer();
    			
    			Log::Log("TelnetClient::SysCmdExchange RECV char {$char}", LOG_LEVEL_DEBUG);
		    }
		    
		    Log::Log("TelnetClient::SysCmdExchange End...", LOG_LEVEL_DEBUG);
		    
		    $this->Buffer = $char.$this->Buffer;
		    return true;
	    }
	    
	    /**
	     * Initialize telnet session
	     *
	     * @return bool
	     */
	    public function InitTelnetSession()
	    {
	        Log::Log("TelnetClient::InitTelnetSession Start Init Telnet session", LOG_LEVEL_DEBUG);
	        while (!feof($this->Socket))
	        {
	            $char = $this->ReadCharFromBuffer();
	            if (!$char)
	            {
                    Log::Log("TelnetClient: Server return empty string. Exiting.", LOG_LEVEL_DEBUG);
                    Core::RaiseError("TelnetClient: Cannot get response from server for session init", LOG_LEVEL_DEBUG);
	            }
	            else 
	            {
	                Log::Log("TelnetClient::InitTelnetSession Got {$char} first char in string.", LOG_LEVEL_DEBUG);
	                if ($char == 255)
    	                $this->SysCmdExchange($char);
    	              
    	            Log::Log("TelnetClient::InitTelnetSession Telnet session successfully initialized.", LOG_LEVEL_DEBUG);  
	                return true;
	            }
	        }
	    }
	            
        /**
         * Prepare system command
         *
         * @param integer $command
         * @param integer $option
         */
        private function PrepareCommand($command, $option = false)
        {
            
            if ($command == 253) {
    			# respond to DO commands
    			switch ($option) 
    			{
    				case 31:	# Window size
    				case 32:	# Term speed
    				case 33:	# Remote flow control
    				case 34:	# LINEMODE
    				case 35:	# X Display
    				case 36:	# Old Env.
    				case 39:	# New Env.
    				case 37:	# Authentication
    				default:
    					$this->SendCommand(252, $option);
    					break;
    
    				case 24:	# TERMINAL-TYPE
    					$this->SendCommand(251, $option);
    					break;
    			}
    		} 
    		else if ($command == 251) 
    		{
    			# respond to WILL commands
    			switch ($option) 
    			{
    				case 3:		# Suppress go ahead
    				case 5:		# Give status
    					$this->SendCommand(253, $option);
    					break;
    
    				case 1:		# Echo
    				case 38:	# Encrypt
    				default:
    					$this->SendCommand(254, $option);
    					break;
    			}
    		} 
    		else if ($command == 250) 
    		{
    			$option = $this->ReadCharFromBuffer();
    			$params = array();
    			$next = $this->ReadCharFromBuffer();
    			while ($next !== 255) 
    			{
       				$params[] = $next;
    				$next = $this->ReadCharFromBuffer();
    			}
    			
    			$end = $this->ReadCharFromBuffer();
    			if ($end != 240) 
    			    Core::RaiseError("Telnet::PrepareCommand - error in subnegotiation");
    			
    			if ($option == 24) 
    			{
        			if ($params[0] == 1) 
        			{
        				$prm = array(chr(0),$this->ttype);
        				$this->SendCommand(250, 24);
                		for ($i=0; $i<count($prm); $i++) 
                			$this->Send($prm[$i], false);

                		$this->SendCommand(240);
        			}
        		} 
        		else 
                    Core::RaiseError("Telnet::PrepareCommand - unsupported option");
    		} 
    		else 
                Core::RaiseError("Telnet::PrepareCommand - unsupported command ({$command})");            
        }
        
        /**
         * Send command (IAC)
         *
         * @param string $command
         * @param string $option
         */
        private function SendCommand($command, $option = null)
        {
            Log::Log("TelnetClient::SendCommand SEND {$command} {$option}", LOG_LEVEL_DEBUG);
            
            if ($option !== null) 
                @fwrite($this->Socket, chr(255).chr($command).chr($option));
            else 
                @fwrite($this->Socket, chr(255).chr($command));
        }
        
        /**
         * Read ord($char) from buffer
         *
         * @return char
         */
        private function ReadCharFromBuffer()
        {
            if (!$this->Buffer)
            {
                while($line = $this->ReadLine())
                {
                    if($line)
                    {
                        $this->Buffer .= $line;
                        break;
                    }
                }
            }
                    
            if ($this->Buffer)
            {
                $char = substr($this->Buffer, 0, 1);
        		$this->Buffer = substr($this->Buffer, 1, strlen($this->Buffer)-1);
        		
        		return ord($char);
            }
            else 
                return false;
        }
        
        /**
         * Read data from socket to buffer
         *
         * @return unknown
         */
        private function ReadLine()
        {   
            $line = false;
            $count = 0;
            $starttime = microtime(true);
            
            stream_set_timeout($this->Socket, $this->Timeout);
            
            while ($count++  < 100)   
            {
                $line = @fread($this->Socket, 1024);
               
                if (strlen($line) > 0)
                    return $line;
                else 
                    usleep(125000);
                    
                if (strlen($line) == 0 && microtime(true) - $starttime >= $this->Timeout)
	               return false;
            }
            
            return false;
        }
        
        /**
         * Readl all data from socket while waitfor consolepromt
         *
         * @return string
         */
        public function ReadAll($timeout = false)
        {
            $timeout = ($timeout) ? $timeout : $this->Timeout;
            
            $this->Buffer = false;
            $this->WaitForString($this->ConsolePromt, $timeout);
            return $this->Response;
        }
        
        /**
         * Wait for string
         *
         * @param string $string
         * @param integer $timeout Timeout in seconds
         * @return bool
         */
	    public function WaitForString($string, $timeout = false, $buffer = false)
	    {     
	        $timeout = ($timeout) ? $timeout : $this->Timeout;
	        
	        //Start time
	        $starttime = microtime(true);
	        
	        // Get data from buffer
	        $this->Response = $this->Buffer;
	        
	        if ($buffer)
	           $this->Response .= $buffer;
	        
	        // Clear buffer
	        $this->Buffer = false;           
	        
	        // Start waiting for string
	        $retval = false;
	        while (true)
	        {
	            if (preg_match("/".preg_quote($string, '/')."/si", $this->Response))
	               $retval = true;
	               
	            if ($retval && (preg_match("/".preg_quote($this->ConsolePromt, '/')."/si", $this->Response) || !$this->IsLoggetIn))
	               return true;
	            
	            if (!$retval && preg_match("/".preg_quote($this->ConsolePromt, '/')."/si", $this->Response))
	               return false;
	                  
	            // Check timeout
	            if (microtime(true) - $starttime >= $timeout)
	            {
	               Log::Log("TelnetClient::WaitForString Timeout", LOG_LEVEL_DEBUG);
	               return $retval;
	            }
	            
	            if (!$buffer)
	               $this->Response .= $this->ReadLine();
	               
	            $buffer = false;
	        }
	        
	        Log::Log("TelnetClient::WaitForString String '{$string}' found in '{$this->Response}'", LOG_LEVEL_DEBUG);
	        
	        return $retval;
	    }
	    
	    /**
	     * Send string to server
	     *
	     * @param string $response
	     * @param bool $endline
	     * @return bool
	     */
	    public function Send($response, $endline = true)
	    {      
	        Log::Log("TelnetClient::Send '{$response}'", LOG_LEVEL_DEBUG);
            $endline = ($endline) ? chr(10) : "";
	        return @fwrite($this->Socket, $response.$endline);
	    }
	    
	    /**
	     * Dissconnect from server
	     *
	     * @return bool
	     */
	    public function Disconnect()
	    {
	        return @fclose($this->Socket);
	    }
	}
?>