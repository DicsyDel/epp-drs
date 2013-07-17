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
     * @package    NET
     * @subpackage FTP
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("NET/FTP/class.FTP.php");

	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage FTP
     * @name NET_FTP_Test
	 *
	 */
	class NET_FTP_Test extends UnitTestCase 
	{
        function NET_FTP_Test() 
        {
            $this->UnitTestCase('FTP test');
        }
        
        function testFTP() 
        {
			
        	// Create FTP Instance
        	$FTP = new FTP("example.com", "username", "password", 21, true);
        	$this->assertTrue($FTP, "FTP Resource created");
        	
        	// Try get File
        	$file = $FTP->GetFile("/", "80snow.com.db", 1);
        	$this->assertTrue($file, "File '80snow.com.db' received");
        }
    }


?>