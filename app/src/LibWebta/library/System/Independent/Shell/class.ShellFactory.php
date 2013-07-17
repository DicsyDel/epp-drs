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
     * @package    System
     * @subpackage Shell
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
	 * @name       ShellFactory
	 * @category   LibWebta
     * @package    System
     * @subpackage Shell
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 *
	 */
	class ShellFactory 
	{
        /**
         * Return shell insrtance
         *
         * @static 
         * @return object
         */
		public static function GetShellInstance()
		{
			// Yeah, no much stuff here now
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			{
				Core::Load("System/Windows/Shell/Shell");
				return new WinShell();
			}
			else
			{
				Core::Load("System/Unix/Shell/Shell");
				return new Shell();
			}
		}
		
	}
?>