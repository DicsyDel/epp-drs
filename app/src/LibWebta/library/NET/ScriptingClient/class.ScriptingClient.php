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
	
	Core::Load("NET/ScriptingClient/Adapters/interface.ScriptingAdapter.php");
	Core::Load("NET/ScriptingClient/Adapters/class.AbstractScriptingAdapter.php");
	
	Core::Load("NET/ScriptingClient/Adapters/class.TelnetScriptingAdapter.php");
	Core::Load("NET/ScriptingClient/Adapters/class.HTTPScriptingAdapter.php");
	
	/**
	 * @name ScriptingClient
	 * @package NET
	 * @subpackage ScriptingClient
	 * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
	 *
	 */
	class ScriptingClient extends Core 
	{
	    /**
	     * Adapter instance
	     *
	     * @var Object
	     * @access private
	     * @static 
	     */
	    private static $Adapter;
	    
	    /**
	     * Set current Adapter
	     *
	     * @param string $adapterName
	     * @param string $append
	     * @static 
	     * @return bool
	     */
        static public function SetAdapter($adapterName, $append = false)
        {
            if (!class_exists("{$adapterName}ScriptingAdapter"))
	           Core::Load("NET/ScriptingClient/Adapters/{$adapterName}ScriptingAdapter");
	           
	        $reflect = new ReflectionClass("{$adapterName}ScriptingAdapter");
	        if ($reflect && $reflect->isInstantiable())
			{
			    if ($append)
			     $args[] = $append;
			    
				if (count($args) > 0)
					self::$Adapter = $reflect->newInstanceArgs($args);
				else 
					self::$Adapter = $reflect->newInstance(true);						
			}
			else 
				Core::RaiseError(_("Object '{$type}ScriptingAdapter' not instantiable."));
				
		    if (self::$Adapter)
                return true;
		    else 
                return false;
		      
        }
	    
        /**
         * Set adapter option
         *
         * @param string $optionName
         * @param string $optionValue
         * @static 
         */
        static public function SetAdapterOption($optionName, $optionValue)
        {
            if (!self::$Adapter)
                Core::RaiseError("Please define Adapter using ScriptingClient::SetAdapter() method");
            
            self::$Adapter->SetOption($optionName, $optionValue);
        }
        
        /**
         * Connect
         *
         * @return bool
         * @static 
         */
        static public function Connect()
        {
            if (!self::$Adapter)
                Core::RaiseError("Please define Adapter using ScriptingClient::SetAdapter() method");
            
            return self::$Adapter->Connect();
        }
        
        /**
         * Disconnect
         * @static 
         */
        static public function Disconnect()
        {
            if (!self::$Adapter)
                Core::RaiseError("Please define Adapter using ScriptingClient::SetAdapter() method");
            
            return self::$Adapter->Disconnect();
        }
        
        /**
         * Return script execution log
         *
         * @return string
         * @static 
         */
        static public function GetScriptExecutionLog()
        {
            if (!self::$Adapter)
                Core::RaiseError("Please define Adapter using ScriptingClient::SetAdapter() method");
            
            return self::$Adapter->GetScriptExecutionLog();
        }
        
        /**
         * Execute script
         *
         * @param string $script
         * @return bool
         * @static 
         */
        static public function Execute($script, $params)
        {
            if (!self::$Adapter)
                Core::RaiseError("Please define Adapter using ScriptingClient::SetAdapter() method");
            
            return self::$Adapter->Execute($script, $params);
        }
   	}
?>