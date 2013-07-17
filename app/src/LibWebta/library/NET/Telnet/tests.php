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
     * @subpackage Telnet
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */	

	Core::Load("NET/Telnet/class.TelnetClient.php");

	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage Telnet
     * @name NET_Telnet_Test
	 */
	class NET_Telnet_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Telnet test');
        }
        
        function testTelnetClientBSD() 
        {
			$TelnetClient = new TelnetClient("65.38.4.218");
		
			// Try to connect
			$conn = $TelnetClient->Connect();
			$this->assertTrue($conn, "PHP Telnet client successfully connected to host");
			
			// Try to login with valid password
			$login = $TelnetClient->Login("telnettest", "password", "login:", "Password:", "[telnettest@bsd2 ~]$");
			$this->assertTrue($login, "PHP Telnet client successfully log on");
			
			if ($login)
			{
			    // Send command and wait for response
			   $TelnetClient->Send("whereis php");
		  	   $data = $TelnetClient->WaitForString("/usr/local/bin/php");
			   $this->assertTrue($data, "PHP Telnet client successfully received waitfor string");
			   
			   // Get all response
			   $TelnetClient->Send("whereis php");
			   $data = $TelnetClient->ReadAll();
			   $this->assertTrue($data, "PHP Telnet client successfully received some data ({$data})");
			   
			   $TelnetClient->Send("ls -al");
		  	   $data = $TelnetClient->WaitForString("dfasdfasdfas");
			   $this->assertFalse($data, "PHP Telnet client successfully not received waitfor string thast not found");
			}
			
			// Disconnect
			$this->assertTrue($TelnetClient->Disconnect(), "PHP Telnet client successfully disconnected from server");
        }
        
        /*
		function testTelnetClientWithBadPassword()
		{	
		    $TelnetClient = new TelnetClient("192.168.1.253");
		    
			// Connect to server
			$conn = $TelnetClient->Connect();
			$this->assertTrue($conn, "PHP Telnet client successfully connected");
			
			// Try to login with bad password
			$login = $TelnetClient->Login("telnettest", "sdfsdsd", "login:", "Password:", "[telnettest@bsd2 ~]$");
			$this->assertFalse($login, "PHP Telnet client not loget in with bad password");
			
			// Disconnect from server
			$TelnetClient->Disconnect();
		}	
		*/
		
		/*	
		function testTelnetClientDLINK()
		{	
		    $TelnetClient = new TelnetClient("192.168.1.1");
		    
			// Try connect to DLINK device
			$conn = $TelnetClient->Connect();
			$this->assertTrue($conn, "PHP Telnet client successfully connected to DLINK device");
			
			// Try to login with valid password
			$login = $TelnetClient->Login("root", "password", "login:", "Password:", "#");
			$this->assertTrue($login, "PHP Telnet client successfully log on to DLINK device");
			
			// Try ls -al command
			$TelnetClient->Send("ls -al");
			$res = $TelnetClient->ReadAll();
			$this->assertTrue($res, "PHP Telnet successfully received data");
			
		    // Disconnect from server
			$TelnetClient->Disconnect();
        }
        */
    }


?>