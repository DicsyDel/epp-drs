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
     * @subpackage PCNTL
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

    declare(ticks = 1);
    Core::Load("IO/PCNTL/class.ProcessManager.php");
    Core::Load("IO/PCNTL/class.SignalHandler.php");
    Core::Load("IO/PCNTL/interface.IProcess.php");
    Core::Load("IO/PCNTL/class.JobLauncher.php");
    
    /**
	 * Tests for IO/Transports
	 * 
	 * @category   LibWebta
     * @package    IO
     * @subpackage Transports
     * @name IO_Transports_Test
	 *
	 */
	class IO_PCNTL_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('IO/PCNTL Tests');
        }
    
        function testJobLauncher()
        {
            $JobLauncher = new JobLauncher(dirname(__FILE__));
            $JobLauncher->Launch();
        }
	}
?>