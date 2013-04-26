<?
	// +--------------------------------------------------------------------------+
	// | This file contains routines to operate LibWebta class library            |
	// +--------------------------------------------------------------------------+
	// | Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html       |
	// +--------------------------------------------------------------------------+
	// | This program is protected by international copyright laws. Any           |
	// | use of this program is subject to the terms of the license               |
	// | agreement included as part of this distribution archive.                 |
	// | Any other uses are strictly prohibited without the written permission    |
	// | of "Webta" and all other rights are reserved.                            |
	// | This notice may not be removed from this source code file.               |
	// | This source file is subject to version 1.1 of the license,               |
	// | that is bundled with this package in the file LICENSE.                   |
	// | If the backage does not contain LICENSE file, this source file is        |
	// | subject to general license, available at http://webta.net/license.html   |
	// +--------------------------------------------------------------------------+
	// | Authors: Alex Kovalyov <http://webta.net/company.html> 									  |
	// +--------------------------------------------------------------------------+

	require_once("{$srcpath}/LibWebta/library/class.Core.php");
	require_once("{$srcpath}/LibWebta/library/class.CoreUtils.php");
	require_once("{$srcpath}/LibWebta/library/class.CoreException.php");
	
	
	// Default custom exception. 
	// Use a stub if one is not defined.
	if (!class_exists("ApplicationException"))
	{
	    class ApplicationException extends Exception
	    {
	        function __construct($message, $code = 0)
	        {
	            parent::__construct($message, $code);
	            
	            if ($code == E_ERROR)
	            {
	               print "Exception: ".$message;
	               exit();
	            }
	        }
	    }
	}
	
	//
	// Set custom error handler. Can be unset in append.inc.php
	//
	$libwebta_old_error_handler = set_error_handler("libwebta_error_handler");
	function libwebta_error_handler($errno, $errstr, $errfile, $errline)
	{
		$message = "Error {$errno} {$errstr}, in {$errfile}:{$errline}";
		
		switch ($errno) {
		    
		case E_CORE_ERROR:
		case E_ERROR:
	    case E_USER_ERROR:
	        
		   Core::ThrowException($message, $errno);
	        
	        break;
	    case E_USER_WARNING:
	    	Core::ThrowException($message, E_USER_WARNING);
	        break;
	
	    case E_USER_NOTICE:
	        break;
	
	    default:
	        
	        if (error_reporting() & $errno)
	    	  Core::RaiseWarning($message);
	        break;
	    }
	}
	
	
	/**
	* Load class or namespace.
	* Priority:
	* 1 - Fully-clarified file name within LIBWEBTA_BASE
	* 2 - Simplified path to a class file
	* 3 - All classes in directory
	* @param string $path Path to load
	* @return bool True is loaded succesfull or false if not found
	* @deprecated since r155
	*/
	function load($path, $loadbase = false)
	{
		Core::Load($path, $loadbase);
	}
	
	
?>