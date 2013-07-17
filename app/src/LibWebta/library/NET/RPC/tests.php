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
     * @package    NET
     * @subpackage RPC
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource 
     */

	Core::Load("NET/RPC/RPCClient");
	
	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage RPC
     * @name NET_RPC_RPC_Test
	 */
	class NET_RPC_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('NET/RPC/RPC test');
        }
        
        function testNET_PRC_RPCClient() 
        {
			
			$rpc = new RPCClient("http://www.livejournal.com/interface/xmlrpc");
			
			$result = $rpc->__call("LJ.XMLRPC.getfriends", array(
				"username" => "username",
				"password" => "password",
				"ver" => 1
			));
			
			$this->assertTrue($result['friends'], "Invalid success response");
			
			$result = $rpc->__call("exec", array());
			
			$this->assertTrue($result['faultString'], "Invalid fault response");
			
        }
        
    }

?>