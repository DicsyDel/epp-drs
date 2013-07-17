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
     * @package    PE
     * @subpackage Process
     * Command execution in POSIX environment with proc_open
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 


    /**
     * @name Process 
     * Simplifies Program execution operations
     * @category   LibWebta
     * @package    PE
     * @author Alex Kovalyov <http://webta.net/company.html>
     */	    
	class ManagedProcess extends Core
	{
	
		/**
		* Contents of STDOUT after Execute() call
		* @var int
		* @access public
		*/
		public $StdOut;
		
		/**
		* Contents of STDERR after Execute() call
		* @var int
		* @access public
		*/
		public $StdErr;
		
		
		/**
		* Constructor
		* @access public
		* @return array Mounts
		*/
		function __construct()
		{
			parent::__construct();
		}
		
		
		/**
		* Execute command. Write $stdin to STDIN and read STDOUT/STDERR
		* @access public
		* $out_file_path string Path to the file where STDOUT will be written to
		* $in_file_path string Path to the file where STDIN will be read from
		* @return int The return value of the command (0 on success) or strict false on failure.
		*/
		public function Execute($cmdline, &$stdin = null, $out_file_path = null, $in_file_path = null)
		{
        	
			// Reset STDOUT and STDERR anyway
        	$this->StdErr = null;
        	$this->StdOut = null;
        	
        	$descriptors = array(
	    		0 => array("pipe", "r"), // STDIN
	    		1 => array("pipe", "w"), // STDOUT
	    		2 => array("pipe", "w") // STDERR
        	);

        	// Overwrite STDOUT descriptor with filename if we'll send output to file
        	if ($out_file_path)
        		$descriptors[1] = array("file", $out_file_path, "w");
        	// Overwrite STDIN descriptor with filename if we'll read input from file
        	if ($in_file_path)
        		$descriptors[0] = array("file", $in_file_path, "r");
        		
            $process = @proc_open($cmdline, $descriptors, $pipes);
    		
    		if (is_resource($process)) 
    		{
    			// Write to STDIN
    			if (!empty($stdin))
    				@fwrite($pipes[0], $stdin);
    			// Close STDIN pipe
    			@fclose($pipes[0]);
    			
    			// Prevent from deadlocks 
				// @see http://www.php.net/manual/en/function.proc-open.php#38870
				#stream_set_blocking($pipes[2], false);
				#stream_set_blocking($pipes[1], false);
				#stream_set_write_buffer($pipes[2], 0);
				#stream_set_write_buffer($pipes[1], 0);
		        
		        // Read STDOUT
		        if (!$out_file_path)
		        	$this->StdOut = @stream_get_contents($pipes[1]);
					
		        @fclose($pipes[1]);
		        
		        // Read STDERR
		        $this->StdErr = @stream_get_contents($pipes[2]);
		        @fclose($pipes[2]);
		        
		        // Close process and return exit code
				$retval = @proc_close($process);			
				return (bool)(!$retval);
    		}
    		else
    			return(false);
		}
		
	}
	
?>
