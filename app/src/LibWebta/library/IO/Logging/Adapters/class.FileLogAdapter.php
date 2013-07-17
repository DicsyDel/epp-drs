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
     * @name FileLogAdapter
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class FileLogAdapter extends Core implements LogAdapter
	{
		
		const DEFAULT_LOG_DIR	= "/tmp"; 
		
	    /**
	    * Holds the PHP resource for an open file, or null.
	    *
	    * @var      +resource
	    *           +null
	    * @access private
	    */
	    private $FileResource = null;
	
	
	    /**
	    * Filename on the filesystem where the log file is stored.
	    *
	    * @var      string
	    * @access private
	    */
	    private $Filename = '';
	
	
	    /**
	    * PHP access mode of the file, either 'a'ppend or over'w'rite
	    *
	    * @var      string
	    * @access private
	    */
	    private $AccessMode = '';
	
	
	    /**
	    * Termination character(s) that are automatically appended to each line.
	    *
	    * @var      string
	    * @access private
	    */
	    private $LineEnding = "\n";
	
	
	    /**
	    * Buffer, array of lines waiting to be written to the filesystem.
	    *
	    * @var      array
	    * @access private
	    */
	    private $Buffer = array();
	
	
	    /**
	    * Number of lines in the buffer
	    *
	    * @var      string
	    * @access private
	    */
	    private $BufferedLines = 0;
	
	
	    /**
	    * Options:
	    *   buffer          True:  use buffering
	    *                   False: no buffering, write immediately
	    *
	    *   bufferLines     Maximum number of lines in the buffer
	    *
	    *   keepOpen        True:  keep file resource open between writes
	    *                   False: close the resource immediately after each write
	    * @var      array
	    * @access private
	    */
	    private $Options = array('buffer'      => false,
	                              'bufferLines' => 20,
	                              'keepOpen'    => false,
	                              'format' => '%message%, %level%');
	
	
	    /**
	    * Class Constructor
	    *
	    * @var      filename    Name of the file on the filesystem to write the log.
	    * @ignore 
	    */
	    public function __construct($filename, $accessMode = 'a')
	    {
	    	parent::__construct();
	    	
	    	if (!$filename)
	    		$filename = self::DEFAULT_LOG_DIR . "/" . date("ymd") . ".log";
	    	
	        $this->Filename = $filename;
	        $this->SetAccessMode($accessMode);
	        return true;
	    }
	
	
	    /**
	    * Class Destructor
	    *
	    * Always check that the file has been closed and the buffer flushed before destruction.
	    * @ignore 
	    */
	    public function __destruct()
	    {
	        $this->Flush();
	        $this->Close();
	    }
	
	
		/**
		 * Sets the access mode of the log file on the filesystem
		 *
		 * @param  $accessMode     Access mode: either 'a' append or 'w' overwrite
		 * @return bool            True
		 * @access protected
		 */
	    protected function SetAccessMode($accessMode)
	    {
	        // Check for valid access mode
	        $accessMode = substr(strtolower($accessMode), 0, 1);
	        
	        if ($accessMode != 'w' && $accessMode != 'a') 
	        {
	            if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(_("Illegal access mode specified.  Specify 'a' for append or 'w' for overwrite."), E_WARNING));
		        else 
                    return false;
	        }
	        
	        $this->AccessMode = $accessMode;
	
	        return true;
	    }
	
	
		/**
		 * Sets an option specific to the implementation of the log adapter.
		 *
		 * @param  $optionKey      Key name for the option to be changed.  Keys are adapter-specific
		 * @param  $optionValue    New value to assign to the option
		 * @return bool            True
		 */
	    public function SetOption($optionKey, $optionValue)
	    {
	        if (!array_key_exists($optionKey, $this->Options)) {
	            if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(_("Unknown option \"$optionKey\"."), E_WARNING));
		        else 
                    return false;
	        }
	        
	        $this->Options[$optionKey] = $optionValue;
	
	        return true;
	    }
	
	
		/**
		 * Sets an option specific to the implementation of the log adapter.
		 *
		 * @param  $optionKey      Key name for the option to be changed.  Keys are adapter-specific
		 * @param  $optionValue    New value to assign to the option
		 * @return bool            True
		 */
		public function Open($filename = null, $accessMode = 'a+')
		{
	        if (is_null($filename)) {
	            $filename = $this->Filename;
	        }
	
	        $this->Filename = $filename;
	        $this->SetAccessMode($accessMode);
	
	        if (!$this->FileResource = @fopen($filename, $accessMode, false)) 
	        {
	            if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(_("Log file \"$filename\" could not be opened"), E_WARNING));
		        else 
                    return false;
	        }
	
	        return true;
		}
	
	
		/**
		 * Write a message to the log.  This function really just writes the message to the buffer.
		 * If buffering is enabled, the message won't hit the filesystem until the buffer fills
		 * or is flushed.  If buffering is not enabled, the buffer will be flushed immediately.
		 *
		 * @param  $message    Log message
		 * @param  $level      
		 * @return bool        True
		 */
	    public function Write($fields)
	    {
		    // Add the message to the buffer.
		    $this->Buffer[] = $this->ParseLogLine($fields);
		    $this->BufferedLines += 1;
	
		    // If the buffer is full, or buffering is not used,
		    // then flush the contents of the buffer to the filesystem now.
	        if (!$this->Options['buffer'] || $this->BufferedLines >= $this->Options['bufferLines']) {
	            $this->Flush();
	        }
	
		    return true;
		}
	
	
		/**
		 * Format a line before sending into the storage.
		 *
		 * @param string $message
		 * @param int $level
		 * @return string
		 * @access protected
		 */
		protected function ParseLogLine($fields)
		{
	        $output = $this->Options['format'];
		    foreach ($fields as $fieldName => $fieldValue) 
		    {
		        $output = str_replace("%$fieldName%", $fieldValue, $output);
		    }
		    
		    return $output;
		}
	
		/**
		 * Write a message to the log.  This function really just writes the message to the buffer.
		 *
		 * @param  $message    Log message
		 * @param  $level      
		 * @return bool        True
		 */
		public function Flush()
		{
		    // Nothing to flush if the buffer is empty.
		    if (!$this->BufferedLines) {
		        return false;
		    }
	
		    // If the file resource is not yet open, then open it now.
		    if (!is_resource($this->FileResource)) {
	            $this->Open();
		    }
	
		    // Flush the buffer to the filesystem
		    foreach ($this->Buffer as $line) 
		    {
		        if (!@fwrite($this->FileResource, $line . $this->LineEnding)) 
		        {
		            if (Log::$DoRaiseExceptions)
                        throw Core::$ReflectionException->newInstanceArgs(array(_("Log file \"{$this->_filename}\" could not be written."), E_WARNING));
    		        else 
                        return false;
		        }
		    }
	
		    // Clean the buffer
	        $this->Buffer = array();
	        $this->BufferedLines = 0;
	
	        // If the file is not to be kept open, close it until the next flush.
	        if (!$this->Options['keepOpen']) {
	            $this->Close();
	        }
	
	        return true;
		}
	
	
		/**
		 * Closes the file resource for the logfile.  Calling this function does not write any
		 * buffered data into the log, so flush() must be called before close().
		 *
		 * @return bool        True
		 */
		public function Close()
		{
		    // If a file resource is open, close it.
		    if (is_resource($this->FileResource)) 
		    {
		        @fclose($this->FileResource);
				@chmod($this->Filename, 0777);
		        $this->FileResource = null;
		    }
	
		    return true;
		}
	
	
	}
	
?>