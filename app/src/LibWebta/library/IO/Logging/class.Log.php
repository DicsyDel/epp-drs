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
     * @subpackage Logging
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
	 * Define Logging constants
	 */
			
	Core::Load("Core");
	Core::Load("CoreException");
	
	/**
     * @name Log
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class Log extends Core
	{
	
		/**
		 * Default settings
		 */
		const LOG_DEFAULT_SOURCE 	= "Null"; 			// File, EMail, DB, HTML, Console, Null
		
		/**
	     * The static class Log holds an array of Log instances
	     * in this variable that are created with registerLogger().
	     *
	     * @var      array
	     */
		static private $Instances = array();
	
		/**
	     * The static class Log holds an array of Log instances
	     * in this variable that are created with registerLogger().
	     *
	     * @var      array
	     */
		static private $DefaultLogName = 'LOG';
	
		/**
	     * When this class is instantiated by registerLogger, it is
	     * pushed onto the $_instances associative array.  The $_logName
	     * is the key to instance in this array, and also how the user
	     * will specify the instance when using the other static method
	     *
	     * @var      string
	     */
		protected $LogName = '';
	
		/**
	     * Every instance of Log must contain a child object which
	     * is an implementation of LogAdapter that provides the log
	     * storage.
	     *
	     * @var      LogAdapter
	     */
		protected $Adapter = null;
	
		/**
	     * A string which is automatically prefixed to any message sent
	     * to the Log::log() method.
	     *
	     * @var      string
	     */
		protected $MessagePrefix = '';
	
		/**
	     * A string which is automatically appended to any message sent
	     * to the Log::log() method.
	     *
	     * @var      string
	     */
		protected $MessageSuffix = '';
	
	
	
		protected $Fields = array('message' => '', 'level' => '');
	
	
		/**
	     * Logging level mask, the bitwise OR of any of the
	     * Log::LEVEL_* constants that will be logged by this
	     * instance of Log.  All other levels will be ignored.
	     *
	     * @var      integer
	     */
		protected $LevelMask;

        
		/**
		 * Raise Errors
		 *
		 * @var bool
		 */
		public static $DoRaiseExceptions = true;
		
		/**
	     * Class constructor.  Log uses the singleton pattern.  Only
	     * a single Log static class may be used, however instances
	     * of Log may be stored inside the Log static class by
	     * calling registerLogger().
	     *
	     * @param string $logName Name of the Log instance, which
	     * will be the key to the Log::$_instances array.
	     * 
	     * @param LogAdapter $adapter
	     */
		public function __construct($logName, LogAdapter $adapter)
		{
			parent::__construct();
			
			$this->LevelMask = error_reporting();
			$this->Adapter = $adapter;
			$this->Adapter->LogName = $logName;
		}
	
	
		/**
	     * Returns the instance of Log in the Log::$Instances
	     * array.
	     * 
	     * @param string $logName Key in the Log::$Instances
	     * associate array.
	     */
		private static function GetLogInstance($logName=null)
		{
	        if (is_null($logName)) {
	            $logName = self::$DefaultLogName;
	        }
	
			if (!self::HasLogger($logName))
			{
				if (Log::$DoRaiseExceptions)
		          throw Core::$ReflectionException->newInstanceArgs(array(sprintf(_("No instance of log named '%s'."), $logName), E_WARNING));
		        else 
		          return false;
			}
	
			return self::$Instances[$logName];
		}
	
	
		/**
	     * Instantiates a new instance of Log carrying the supplied LogAdapter and stores
	     * it in the $_instances array.
	     *
	     * @param    LogAdapter $logAdapter     Log adapter implemented from LogAdapter
	     * @param    string          	     $logName        Name of this instance, used to access it from other static functions.
	     * @return   bool            	     True
	     */
		public static function RegisterLogger($type = "Screen", $logName = null, $append = false)
		{
	        if (is_null($logName)) {
	            $logName = self::$DefaultLogName;
	        }
	        
	        switch (true)
	        {
	        	case $type == "File":
	        	case $type == "DB":
	        	case $type == "Console":
	        	case $type == "HTML":
	        	case $type == "EMail":
	        		break;
	        	default: $type = defined("CF_LOG_DEFAULT_SOURCE") ? CF_LOG_DEFAULT_SOURCE : self::LOG_DEFAULT_SOURCE;
	        }
	        
	        if (!class_exists("{$type}LogAdapter"))
	           Core::Load("IO/Logging/Adapters/{$type}LogAdapter");
	           
	        $reflect = new ReflectionClass("{$type}LogAdapter");
	        if ($reflect && $reflect->isInstantiable())
			{
			    if ($append)
			     $args[] = $append;
			    
				if (count($args) > 0)
					$logAdapter = $reflect->newInstanceArgs($args);
				else 
					$logAdapter = $reflect->newInstance(true);						
			}
			else
			{
			    if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(_("Object '{$type}LogAdapter' not instantiable."), E_WARNING));
		        else 
                    return false;
			}
					   
			if ($logAdapter)
			{
			    /* @var $log Log */
				if (!self::HasLogger($logName)) {
					self::$Instances[$logName] = new Log($logName, $logAdapter);
				} else {
				    
				    if (Log::$DoRaiseExceptions)
                        throw Core::$ReflectionException->newInstanceArgs(array(sprintf(_("Cannot register, '%s' already exists."), $logName), E_WARNING));
    		        else 
                        return false;
				}
				return true;
			}	
			return false;
		}
	
	
		/**
	     * Destroys an instance of Log in the $_instances array that was added by registerLogger()
	     *
	     * @param string         	     $logName        Name of this instance, used to access it from other static functions.
	     */
		public static function UnregisterLogger($logName=null)
		{
	        if (is_null($logName)) {
	            $logName = self::$DefaultLogName;
	        }
	
			if (!self::HasLogger($logName)) 
			{
				if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(sprintf(_("Cannot unregister, no instance of log named '%s'."), $logName), E_WARNING));
		        else 
                    return false;
			    
			}
	
			unset(self::$Instances[$logName]);
			return true;
		}
	
	
		/**
		 * Returns True if the specified logName is a registered logger.  If no logName is supplied,
		 * the function returns True if at least one logger exists.
	     *
	     * @param   string $logName   Name of registered logger to check, or null.
	     * @return  bool              Registered logger?
	     */
		public static function HasLogger($logName=null)
		{
		    if (!is_null($logName)) {
		        return isset(self::$Instances[$logName]);
		    }
	
	        return sizeof(self::$Instances) > 0;
		}
	   
		/**
		 * Reload adapter
		 *
		 * @param string $logName
		 */
		public static function Reload($logName)
		{
		    if (self::HasLogger($logName))
		    {
                $logger = self::$Instances[$logName];
		        $logger->Adapter->Reload();
		    }
		}
	
		/**
	     * Returns information about the registered loggers.
	     *
	     * array(2) {
	     *   ["LOG"]=>          array key is the logger name
	     *   array(2) {
	     *      ["adapter"]=>   string, name of the Log_AdapterClass class
	     *      ["default"]=>   bool,   is this the default logger?
	     *    }
	     *  }
	     *
	     * @return  array       Is there at least one registered logger?
	     */
	    public static function GetLoggerInfo()
	    {
	        if (!self::HasLogger()) {
	            return false;
	        }
	
	        $loggerInfo = array();
	        foreach (self::$Instances as $logName => $logger) {
	            $loggerInfo[$logName]['adapter'] = get_class($logger->Adapter);
	            $loggerInfo[$logName]['default'] = ($logName == self::$DefaultLogName);
	        }
	
	        return $loggerInfo;
	    }
	
	
		/**
	     * Sets the default logger.  If no logName is specified, then "LOG" is used.  For any
	     * named logger other than "LOG", the logger must have been registered with registerLogger().
	     *
	     * @param  string        $logName        Name of this instance, used to access it from other static functions.
	     * @return bool          True
	     */
		public static function SetDefaultLogger($logName=null)
		{
	        if (is_null($logName) || $logName == 'LOG') {
	            $logName = 'LOG';
	        } elseif (!self::HasLogger($logName)) 
	        {
	            if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(sprintf(_("Cannot set default, no instance of log named '%s'."), $logName), E_WARNING));
		        else 
                    return false;
	        }
	
	        self::$DefaultLogName = $logName;
	        return true;
		}
	
	
		public static function SetFields($fields, $logName = null)
		{
	        if (is_null($logName)) {
	            $logName = self::$DefaultLogName;
	        }
	
	        if (!array_key_exists('message', $fields)) {
	            $fields['message'] = '';
	        }
	
	        if (!array_key_exists('level', $fields)) {
	            $fields['level'] = '';
	        }
	
	        self::GetLogInstance($logName)->Fields = $fields;
	        return true;
		}
	
	
		public static function GetFields($logName = null)
		{
	        if (is_null($logName)) {
	            $logName = self::$DefaultLogName;
	        }
	
	        return self::GetLogInstance($logName)->Fields;
		}
	
	
		/**
		 * Sends a message to the log.
		 *
		 * @param string $message
		 * @param integer $level
		 * @param mixed $logName_or_fields
		 * @param string $logName
		 * @return boolean
		 */
		public static function Log($message, $level = false, $logName_or_fields = null, $logName = null)
		{
		    if (is_string($logName_or_fields)) {
		        $logName = $logName_or_fields;
		    } else {
	            if (!is_null($logName_or_fields)) {
	    	        // Fields must be specified as key=>value pairs.
	    	        if (!is_array($logName_or_fields)) 
	    	        {
	    	            if (Log::$DoRaiseExceptions)
                            throw Core::$ReflectionException->newInstanceArgs(array(_('Optional fields must be supplied as associative array of key/value pairs.'), E_WARNING));
        		        else 
                            return false;
	    	        }
	
	    	        /**
	    	         * If the first key in the $logName_or_fields array is numeric, we'll assume that this is an array
	    	         * that was generated by array() and as such it's an array of lognames.  Otherwise, assume fields.
	    	         */
	    	        reset($logName_or_fields);
	    	        if (is_numeric(key($logName_or_fields))) {
	    	            $logName = $logName_or_fields;
	    	            $fields = null;
	    	        } else {
	        	        // Fields passed must be in the array with keys matching the keys that were set by setFields().
	        	        $fields = array();
	        	        if (is_array($logName_or_fields))
	        	        foreach ($logName_or_fields as $fieldName => $fieldValue) {
	        	            $fields[$fieldName] = $fieldValue;
	        	        }
	    	        }
	            }
		    }
	
	
		    /**
		     * A log may be specified or the default log will be selected.  A special logname, WebtaLog, exists
		     * only for internal logging of the framework.
		     */
	        if (is_null($logName)) 
	        {
	            $logName = self::$DefaultLogName;
	            if (!self::HasLogger($logName))
                    self::RegisterLogger(self::LOG_DEFAULT_SOURCE, $logName);
	        } 
	        else 
	        {
	            if ($logName == 'WebtaLog' && !isset(self::$Instances['WebtaLog'])) 
	                self::RegisterLogger("Null", 'WebtaLog');
	        }
	
	
		    /**
		     * For any fields that were not specified, use the defaults.
		     */
		    $fields['message'] = $message;
		    $fields['level'] = $level;
		    foreach ((array)self::GetFields($logName) as $fieldName => $fieldValue) {
		        if (!array_key_exists($fieldName, $fields)) {
		            $fields[$fieldName] = $fieldValue;
		        }
		    }
	        
	        if ($level === false)
	        {
	        	$level = parent::$DebugLevel;
	        }
	       
	        /**
		     * If the supplied logName is actually an array of logNames, then
	         * call the function recursively to post to all the logs.
		     */
		    if (is_array($logName)) {
		        foreach ($logName as $l) {
		            self::Log($message, $level, $fields, $l);
		        }
		        return true;
		    }
		
		    // Check to see that the specified log level is actually a level
		    // and not a mask or an invalid level.
		    if (!self::IsLogLevel($level)) 
		    {
		        if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(_('Unknown log level specified.'), E_WARNING));
		        else 
                    return false;
			}
			
		    // Write the message to the log if the current log level will accept it.
		    /* @var $logger Log */
		    $logger = self::GetLogInstance($logName);
		    
		    if ($level & $logger->LevelMask) {
		        $fields['message'] = $logger->MessagePrefix . $message . $logger->MessageSuffix;
		    	return $logger->Adapter->Write($fields);
		    }
	
			return true;
		}
	
	
		/**
	     * Destroy all Log instances in Log::$_instances.  This is equivalent to calling unregister()
	     * for each log instance.
	     *
	     */
		public static function Close()
		{
		    // This will cause the destruction of the instances.  The destructor
		    // in the  LogFile class will clean up on its way out.
		    self::$Instances = null;
	
		    return true;
		}
	
	
		/**
	     * Sets a message prefix.  The prefix will be automatically prepended to any message that is
	     * sent to the specified log.
	     *
	     * @param string         $logName        Name of this instance
	     */
		public static function SetMessagePrefix($prefix, $logName = null)
		{
		    self::GetLogInstance($logName)->MessagePrefix = $prefix;
		    return true;
		}
	
	
		/**
	     * Sets a message suffix.  The suffix will be automatically appended to any message that is
	     * sent to the specified log.
	     *
	     * @param string         $logName        Name of this instance
	     */
		public static function SetMessageSuffix($suffix, $logName = null)
		{
		    self::GetLogInstance($logName)->MessageSuffix = $suffix;
		    return true;
		}
	
		
		/**
		 * Sets the logging level of the log instance to one of the Zend_Log::LEVEL_* constants.  Only
	     * messages with this log level will be logged by the instance, all others will be ignored.
	     *
	     * @param string         $logName        Name of this instance
	     */
		public static function SetLevel($level, $logName = null)
		{
			if (!self::IsLogLevel($level)) 
			{
				if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(_("Unknown log level ('$level') specified."), E_WARNING));
		        else 
                    return false;
			}
	
			self::GetLogInstance($logName)->LevelMask = $level;
			return true;
		}
	
	
		/**
		 * Sets the logging level of the log instance based on a mask.  The mask is the bitwise OR
		 * of any of the Zend_Log::LEVEL_* constants.
	     *
	     * @param string    $logName        Name of this instance
	     */
		public static function SetMask($mask, $logName = null)
		{
			if (!is_int($mask)) 
			{
				if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(_("Unknown log level mask."), E_WARNING));
		        else 
                    return false;
			}
	
			$logger = self::GetLogInstance($logName);
			$logger->LevelMask = $mask;
			return true;
		}
		
		/**
		 * Sets and adapter-specific option.
	     *
	     * @param string    $logName        Name of this instance
	     */
		public static function SetAdapterOption($optionKey, $optionValue, $logName = null)
		{
		    $logger = self::GetLogInstance($logName);
		    return $logger->Adapter->SetOption($optionKey, $optionValue);
		}
	
	
		/**
		 * Tests if the supplied $level is one of the valid log levels (Log::LEVEL_* constants).
	     *
	     * @param   int   $level      Value to test
	     * @return  bool              Is it a valid level?
	     */
		public static function IsLogLevel($level)
		{
			return is_int($level);
		}
		
		public static function Notice ($message)
		{
			self::Log($message, E_USER_NOTICE);
		}
		
		public static function Warning ($message)
		{
			self::Log($message, E_USER_WARNING);
		}
		
		public static function Error ($message, Exception $ex=null)
		{
			$msg = $message;
			if ($ex)
			{
				$msg .= " ".get_class($ex)."(): " . ($ex->getMessage() ? $ex->getMessage() : "''");
				$fields = array("backtrace" => $ex->getTraceAsString());
			}
			self::Log($msg, E_USER_ERROR, $fields); 
		}
	
	}
	
?>