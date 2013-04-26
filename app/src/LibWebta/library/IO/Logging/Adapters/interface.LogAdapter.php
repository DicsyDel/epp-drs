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
     * @name LogAdapter Interface
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	interface LogAdapter
	{
		/**
		 * Open the storage resource.  If the adapter supports buffering, this may not
		 * actually open anything until it is time to flush the buffer.
		 */
		public function Open();
	
	
		/**
		 * Write a message to the log.  If the adapter supports buffering, the
		 * message may or may not actually go into storage until the buffer is flushed.
		 *
		 * @param $fields     Associative array, contains keys 'message' and 'level' at a minimum.
		 */
		public function Write($fields);
	
	
		/**
		 * Close the log storage opened by the log adapter.  If the adapter supports
		 * buffering, all log data must be sent to the log before the storage is closed.
		 */
		public function Close();
	
	
		/**
		 * Sets an option specific to the implementation of the log adapter.
		 *
		 * @param $optionKey       Key name for the option to be changed.  Keys are adapter-specific
		 * @param $optionValue     New value to assign to the option
		 */
	    public function SetOption($optionKey, $optionValue);
	}
	
?>