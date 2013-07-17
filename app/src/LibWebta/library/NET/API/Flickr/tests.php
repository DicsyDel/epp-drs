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
     * @package NET_API
     * @subpackage Flickr
     * @copyright  Copyright (c) 2006 Facebook, Inc.
     * @license    http://webta.net/license.html
     * @filesource
     */

	$base = dirname(__FILE__);
		
	Core::Load("NET/API/Flickr/class.Flickr.php");
	
	/**
	 * @category   LibWebta
     * @package NET_API
     * @subpackage Flickr
     * @name NET_API_Flickr_Test
	 *
	 */
	class NET_API_Flickr_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('NET/API/Flickr test');
        }
        
        function testNET_API_Flickr() 
        {
			$Flickr = new Flickr("199d34f644d1c9424660bd91c1356238", "fd14718eafbc1ce7");
			$res = $Flickr->GetFrob();
			$this->assertTrue($res, "Frob received");
			if (!$res)
                print Core::GetLastWarning()."<br>";
                
            $res = $Flickr->Login();
            var_dump($res);
        }
        
    }


?>