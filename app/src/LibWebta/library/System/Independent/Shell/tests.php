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
     * @filesource
     */
	
	/**
	 * @category   LibWebta
     * @package    System
     * @subpackage Shell
     * @name  System_Independent_Shell_Test
	 *
	 */
	class System_Independent_Shell_Test extends UnitTestCase 
	{
		
        function __construct() 
        {
        	Core::Load("System/Independent/Shell/ShellFactory");
            $this->UnitTestCase('System/Independent/Shell test');
        }
        
        function testFactory() 
        {
			
			$Shell = ShellFactory::GetShellInstance();
			$this->assertTrue(is_a($Shell, "Shell") || is_a($Shell, "WinShell"), 
			"ShellFactory::GetShellInstance returned Shell or WinShell instance");
							
        }
        
    }


?>