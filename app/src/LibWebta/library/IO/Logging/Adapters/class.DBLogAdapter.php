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
	 * Load LogAdapter
	 */
	Core::Load("IO/Logging/Adapters/interface.LogAdapter.php");

    /**
     * @name DBLogAdapter
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class DBLogAdapter extends Core implements LogAdapter
	{
		
		const LOG_DB_TABLE = "log";
	
	    /**
	     * Name of the log table in the database.
	     *
	     * @var string
	     */
	    protected $TableName = null;
	
	    /**
	     * Options to be set by SetOption().  Sets the field names in the database table.
	     *
	     * @var array
	     */
	    protected $Options;
	
	    /**
	     * DBAdapter construcotr
	     *
	     * @param string $tableName Table name
	     */
	    public function __construct($tableName = null)
	    {
			parent::__construct();
			
	        // Get the LOG table name
            $this->TableName = is_null($tableName) ? self::LOG_DB_TABLE : $tableName;
	        
            $this->Options = array('fieldMessage' => 'message', 'fieldLevel' => 'level', 'fieldDatetime' => false, 'fieldTimestamp' => false, 'fieldTrnID' => false, 'fieldBacktrace' => false);
	    }
	
	/**
	 * Destructor
	 * @ignore 
	 *
	 */
		public function __destruct()
		{
			$this->Close();
		}
	
	    /**
	     * Sets either 'fieldMessage' to change the field name where messages are logged,
	     * or 'fieldLevel' to change the field name where the log levels are logged.
	     *
	     * @param string $optionKey
	     * @param string $optionValue
	     */
	    public function SetOption($optionKey, $optionValue)
	    {
	        if (!array_key_exists($optionKey, $this->Options)) {
	            throw Core::$ReflectionException->newInstanceArgs(array("Unknown option \"$optionKey\".", E_WARNING));
	        }
	        
	        $this->Options[$optionKey] = $optionValue;
	    }
	
	    /**
	     * Reload log adapter;
	     *
	     */
	    public function Reload()
	    {
	        $this->DB = Core::GetDBInstance(null, true);
	    }
	    
		/**
		 * Does nothing.
		 *
		 * @return bool
		 */
		public function Open()
		{
	        return true;
		}
	
	
		/**
		 * Does nothing.
		 *
		 * @return bool
		 */
		public function Close()
		{
		    return true;
		}
	
	
		/**
		 * Writes an array of key/value pairs to the database, where the keys are the
		 * database field names and values are what to put in those fields.
		 *
		 * @param array $fields
		 * @return bool
		 */
		public function Write($fields)
		{
		    $DB = Core::GetDBInstance();
		    if (!$DB)
		    	return;
			
			/**
		     * If the field defaults for 'message' and 'level' have been changed
		     * in the options, replace the keys in the $field array.
		     */
		    if ($this->Options['fieldMessage'] != 'message') 
		    {
		        if ($this->Options['fieldMessage'] != false)
                    $fields[$this->Options['fieldMessage']] = $fields['message'];
		          
		        unset($fields['message']);
		    }
	
		    if ($this->Options['fieldLevel'] != 'level') 
		    {
		        if ($this->Options['fieldLevel'] != false)
		           $fields[$this->Options['fieldLevel']] = $fields['level'];
		           
		        unset($fields['level']);
		    }
	       		    
		    if ($this->Options['fieldTimestamp'] != false)
		        $fields[$this->Options['fieldTimestamp']] = microtime(true)*10000;
		    
		    if ($this->Options['fieldTrnID'] != false)
		    	$fields[$this->Options['fieldTrnID']] = defined("TRANSACTION_ID") ? TRANSACTION_ID : "__UNDEFINED__";
		    
		    if ($this->Options['fieldBacktrace'] != false)
		        $fields[$this->Options['fieldBacktrace']] = $fields['backtrace'];
		    	
		    /**
		     * Build an array of field names and values for the SQL statement.
		     */
	        $fieldNames = array();
		    foreach ($fields as $key => &$value) 
		    {
		    	$val = $DB->qstr($value);
		        
		        $fieldNames[] = "{$key}";
		        $value = $val;
		        
		        if ($value == "''") {
		            $value = "NULL";
		        }
		    }
		    
		    // Add Datetime field
		    if ($this->Options['fieldDatetime'] != false)
		    {
		        $fieldNames[] = $this->Options['fieldDatetime'];
		    	$fields[] = $DB->DBTimeStamp(time());
		    }
		    
		    /**
		     * INSERT the log line into the database.  XXX Replace with Prepared Statement
		     */
		    $sql = "INSERT INTO " .$this->TableName. " ("
		         	. implode(', ', $fieldNames) . ') 
		         	VALUES ('
		         	. implode(', ', $fields) .')';
		         	
		    try 
		    {
		       $DB->Execute($sql);
		       
		       $id = $DB->Insert_ID();
		       
		       unset($DB);
		       
		       return $id;
		    }
		    catch (Exception $e)
		    {
		        if (Log::$DoRaiseExceptions)
		          throw Core::$ReflectionException->newInstanceArgs(array($e->getMessage(), E_WARNING));
		        else 
		          return false;
		    }
		    
	        return true;
		}
	
	
	}
	
?>