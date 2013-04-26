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
     * @name SignalHandler
     * @category   LibWebta
     * @package    IO
     * @subpackage PCNTL
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class SignalHandler extends Core
    {
        /**
         * Processmanager instance
         *
         * @var ProcessManager
         */
        public $ProcessManager;
        
        /**
         * Constructor
         * @ignore 
         */
        function __construct() 
        {
            if (!function_exists("pcntl_signal"))
                self::RaiseError("Function pcntl_signal() not found. PCNTL must be enabled in PHP.", E_ERROR);
            
            //Log::Log("Begin add handler to signals...", E_NOTICE);
                
            // Add default handlers
            $res = @pcntl_signal(SIGCHLD, array(&$this,"HandleSignals"));
            //Log::Log("Handle SIGCHLD = {$res}", E_NOTICE);
            
            $res = @pcntl_signal(SIGTERM, array(&$this,"HandleSignals"));
            //Log::Log("Handle SIGTERM = {$res}", E_NOTICE);
            
            $res = @pcntl_signal(SIGSEGV, array(&$this,"HandleSignals"));
            //Log::Log("Handle SIGSEGV = {$res}", E_NOTICE);
            
            $res = @pcntl_signal(SIGABRT, array(&$this,"HandleSignals"));
            //Log::Log("Handle SIGABRT = {$res}", E_NOTICE);
        }
        
        /**
         * Signals handler function
         *
         * @param integer $signal
         * @final 
         */
        final public function HandleSignals($signal)
        {
            //Log::Log("HandleSignals received signal {$signal}", E_NOTICE);            
            $pid = @pcntl_wait($status, WNOHANG | WUNTRACED);
            
            if ($pid > 0)
    		{
    		    //Log::Log("Application received signal {$signal} from child with PID# {$pid} (Exit code: {$status})", E_NOTICE);
  
    		    foreach((array)$this->ProcessManager->PIDs as $kk=>$vv)
    			{
    				if ($vv == $pid)
    				{
    					unset($this->ProcessManager->PIDs[$kk]);
    					$known_child = true;
    					break;
    				}
    			}
    			
    			if ($known_child)
    				$this->ProcessManager->ForkThreads();
    			else
    			{
    				//Log::Log("Signal received from unknown child.", E_NOTICE);
    			}
    		}
        }
        
        /**
         * Add new handler on signal
         *
         * @param integer $signal
         * @param mixed $handler
         * @final 
         */
        final public function AddHandler($signal, $handler = false)
        {
            $signal = (int)$signal;
            
            if (!$handler)
                $handler = array(&$this,"HandleSignals");
            
            @pcntl_signal($signal, $handler);
            
            //Log::Log("Added new handler on signal {$signal}.", E_NOTICE);
        }
    }
?>