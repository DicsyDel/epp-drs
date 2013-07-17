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
     * @package    IO
     * @subpackage PCNTL
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
     * @name ProcessManager
     * @category   LibWebta
     * @package    IO
     * @subpackage PCNTL
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @example tests.php
     * @see tests.php
     */
	class ProcessManager extends Core
    {
        /**
         * SignalHandler
         *
         * @var SignalHandler
         * @access private
         */
        private $SignalHandler;
        
        /**
         * Proccess object
         *
         * @var object
         * @access protected
         */
        protected $ProcessObject;
        
        /**
         * PIDs of child processes
         *
         * @var array
         * @access public
         */
        public $PIDs;
        
        /**
         * Maximum allowed childs in one moment
         *
         * @var integer
         * @access public
         */
        public $MaxChilds;
        
        /**
         * Proccess manager Constructor
         *
         * @param SignalHandler $SignalHandler
         */
        function __construct(&$SignalHandler)
        {
            if ($SignalHandler instanceof SignalHandler)
            {
                $SignalHandler->ProcessManager = &$this;
                $this->SignalHandler = $SignalHandler;
                
                $this->MaxChilds = 5;
                                  
                //Log::Log("Process initialized.", E_NOTICE);
            }
            else 
                self::RaiseError("Invalid signal handler");  
        }
        
        /**
         * Destructor
         * @ignore 
         */
        function __destruct()
        {
  
        }
        
        /**
         * Set MaxChilds
         *
         * @param integer $num
         * @final 
         */
        final public function SetMaxChilds($num)
        {
            if (count($this->PIDs) == 0)
            {
                $this->MaxChilds = $num;
                
                //Log::Log("Number of MaxChilds set to {$num}", E_NOTICE);
            }
            else
                self::RaiseError("You can only set MaxChilds *before* you Run() is executed.");
        }
        
        /**
         * Start Forking
         *
         * @param object $ProcessObject
         * @final 
         */
        final public function Run(&$ProcessObject)
        {
            // Check for ProcessObject existence
            if (!is_object($ProcessObject) || !($ProcessObject instanceof IProcess))
                self::RaiseError("Invalid Proccess object", E_ERROR);    
               
            // Set class property   
            $this->ProcessObject = $ProcessObject;
            
            //Log::Log("Executing 'OnStartForking' routine", E_NOTICE);
                
            // Run routines before threading
            $this->ProcessObject->OnStartForking();  
            
            //Log::Log("'OnStartForking' successfully executed.", E_NOTICE);

            if (count($this->ProcessObject->ThreadArgs) == 0)
            {
                //Log::Log("ProcessObject::ThreadArgs is empty. Nothing to do.", E_NOTICE);
                return true;
            }
            
            //Log::Log("Executing ProcessObject::ForkThreads()", E_NOTICE);
            
            // Start Threading
            $this->ForkThreads();
            
            // Wait while threads working 
            $iteration = 1;          
            while (true)
        	{
        		if (count($this->PIDs) == 0)
        			break;

        		sleep(2);
        	    
        	    if ($iteration++ == 10)
        	    {
        	        //Log::Log("Goin to MPWL. PIDs(".implode(", ", $this->PIDs).")", E_NOTICE);
        	        
        	        //
        	        // Zomby not needed.
        	        //
        	        
        	        $pid = pcntl_wait($status, WNOHANG | WUNTRACED);
        	        if ($pid > 0)
		    		{
		    		    //Log::Log("MPWL: pcntl_wait() from child with PID# {$pid} (Exit code: {$status})", E_NOTICE);
		  
		    		    foreach((array)$this->PIDs as $kk=>$vv)
		    			{
		    				if ($vv == $pid)
		    					unset($this->PIDs[$kk]);
		    			}
		    			
		    			$this->ForkThreads();
		    		}
        	        
        	        foreach ($this->PIDs as $k=>$pid)
        	        {
        	           $res = posix_kill($pid, 0);
        	           //Log::Log("MPWL: Sending 0 signal to {$pid} = ".intval($res), E_NOTICE);
        	           
        	           if ($res === FALSE)
        	           {
        	               //Log::Log("MPWL: Deleting '{$pid}' from PIDs query", E_NOTICE);
        	               unset($this->PIDs[$k]);
        	           }
        	        }

        	        $iteration = 1;
        	    }
        	    
        	}
            
        	//Log::Log("All childs exited. Executing OnEndForking routine", E_NOTICE);
        	   
        	// Run routines after forking
            $this->ProcessObject->OnEndForking();  
            
            //Log::Log("Process complete. Exiting...", E_NOTICE);
                
            exit();
        }
        
        /**
         * Start forking processes while number of childs less than MaxChilds and we have data in ThreadArgs
         * @access private
         * @final 
         */
        final public function ForkThreads()
        {
            while(count($this->ProcessObject->ThreadArgs) > 0 && count($this->PIDs) < $this->MaxChilds)
            {
                $arg = array_shift($this->ProcessObject->ThreadArgs);
                
                $this->Fork($arg);
                
                usleep(500000);
            }
        }
        
        /**
         * Fork child process
         *
         * @param mixed $arg
         * @final
         */
        final private function Fork($arg)
        {
        	$pid = @pcntl_fork();
			if(!$pid) 
			{
			    try
			    {
                    $this->ProcessObject->StartThread($arg);
			    }
			    catch (Exception $err)
			    {
			        //Log::Log($err->getMessage(), E_CORE_ERROR);
			    }
				exit();
			}
			else
			{
			    
				//Log::Log("Child with PID# {$pid} successfully forked", E_NOTICE);
                    
			    $this->PIDs[] = $pid;
			}
        }
    }
?>