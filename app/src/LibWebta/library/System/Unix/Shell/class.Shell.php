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
     * @package    System_Unix
     * @subpackage Shell
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

    /**
	 * @name       Shell
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage Shell
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	class Shell 
	{

		/**
		* Execute command
		* @access public
		* @param string $cmd Command to be executed
		* @param mixed $args Array of command parameters
		* @return boolean
		*/

		public final  function Execute($cmd, $args) 
		{
			foreach ($args as $arg)
			{													 
				$farg .= " ". escapeshellarg($arg);
			}
			@exec(escapeshellcmd($cmd) . $farg, $notused, $retval);
			return ($retval == 0);
		}
		
		
		/**
		* Execute raw shell string
		* @access public
		* @param string $cmd Command to be executed
		* @return boolean
		*/

		public final  function ExecuteRaw($cmd) 
		{
			$cmd = str_replace(array("\r", "\n"), "", $cmd);
			@exec($cmd, $notused, $retval);
			return ($retval == 0);
		}


		/**
		* Execute command, return stdout and stderr
		* @access public
		* @param string $cmd Command to be executed
		* @param mixed $args Array of command parameters
		* @return string
		*/

		public final  function Query($cmd, $args) 
		{
			foreach ($args as $arg)
			{
				$farg .= " ". escapeshellarg($arg);
			}
			@exec(escapeshellcmd($cmd) . $farg, $retval, $notused);
			$retval = implode("\n", $retval);
			return $retval;
		}


		/**
		* Execute raw command line
		* @access public
		* @param string $cmd Full command line with params
		* @param string $singlestring Either return a imploded single string or array of lines
		* @return string
		*/

		public final  function QueryRaw($cmd, $singlestring = true) 
		{
			$cmd = str_replace(array("\r", "\n"), "", $cmd);
			
			@exec($cmd, $retval);
			
			if ($singlestring)
				$retval = implode("\n", $retval);

			return $retval;
		}


	}
?>