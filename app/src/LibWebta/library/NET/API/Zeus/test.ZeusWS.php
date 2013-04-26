<?php
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
     * @package    NET_API
     * @subpackage Zeus
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */     

	include_once("../Server/System/class.SSLManager.php");
	
	/**
	 * @category   LibWebta
     * @package    NET_API
     * @subpackage Zeus
     * @name ZeusWSTest
	 *
	 */
	class ZeusWSTest extends UnitTestCase 
	{
        function ZeusWSTest() 
        {
            $this->UnitTestCase('ZeusWS test');
        }
        
        function testZeusWS() 
        {
			
			$ZeusWS = new ZeusWS("192.168.1.254", 22, "root", ",fyfymjc-3") ;
			
			//
			// Generate RSA
			//
			$retval = $ZeusWS->SetMetaValue("prototype", "aliases", "*.prototype3");
			$this->assertTrue($retval, "SetMetaValue returned true");
		
			
			
			
        }
    }


?>