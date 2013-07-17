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
     * @subpackage ScriptingClient
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("NET/ScriptingClient/class.ScriptingClient.php");

	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage ScriptingClient
     * @name  NET_ScriptingClient_Test
	 */
	class NET_ScriptingClient_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('ScriptingClient test');
        }
        
        function testHTTPAdapter()
        {
            /*
            ScriptingClient::SetAdapter("HTTP");
            
            ScriptingClient::SetAdapterOption("login", "admin");
			ScriptingClient::SetAdapterOption("password", "admin");
			ScriptingClient::SetAdapterOption("DebugMode", 1);
            
            $conn = ScriptingClient::Connect();
			$this->assertTrue($conn, "HTTPAdapter successfully connected to host");
            
            $script = "GET http://emample.com/home.shtml
                       [EXPECT 'Setup Wizard']
                       GET http://emample.com/adv_perform.shtml
                       [ELSE]
                       [TERMINATE]
                       [ENDEXPECT]
                      ";
            
            $script = "POST http://emample.com/apply.cgi?formTcpipSetup dhcp=1&dhcpRangeStart=192.168.1.115&dhcpRangeEnd=192.168.1.120&leaseTime=10800&time=1171301267&submit-url=%2Fh_dhcp.shtml
                       [EXPECT 'Change setting successfully']
                       GET http://emample.com/h_dhcp.shtml
                       [ENDEXPECT]
            ";
            
            
            $params = array("ipaddr" => "emample.com");
			
			$exec = ScriptingClient::Execute($script, $params);
			$this->assertTrue($exec, "HTTPAdapter successfully executed script");
			
			$dicsonn = ScriptingClient::Disconnect();
			$this->assertTrue($dicsonn, "HTTPAdapter successfully disconnected");
			*/
        }
        
        function testTelnetAdapter() 
        {
            /*
			ScriptingClient::SetAdapter("Telnet");
			
			ScriptingClient::SetAdapterOption("host", "emample.com");
			ScriptingClient::SetAdapterOption("login", "telnettest");
			ScriptingClient::SetAdapterOption("password", "fasdfs");
			ScriptingClient::SetAdapterOption("consolePromt", "$");
			
			$conn = ScriptingClient::Connect();
			$this->assertTrue($conn, "TelnetAdapter successfully connected to host");
			
			if ($conn)
			{
    			$script = "touch /tmp/\$filename.txt
    			           rm -rf /tmp/emptyfile.txt
    			           rm -rf /tmp/allok.txt
    			           ls -al /tmp | grep \$filename
    			           [EXPECT '\$filename.txt2']
    			           rm -rf /tmp/\$filename.txt
    			           touch /tmp/allok.txt
    			           [ELSE]
    			           [TERMINATE]
    			           touch /tmp/emptyfile.txt
    			           #touch /tmp/emptyfile2.txt
    			           [ENDEXPECT]
    			          ";
    			
    			$params = array("filename" => "test");
    			
    			$exec = ScriptingClient::Execute($script, $params);
    			$this->assertTrue($exec, "TelnetAdapter successfully executed script");
    			
    			$dicsonn = ScriptingClient::Disconnect();
    			$this->assertTrue($dicsonn, "TelnetAdapter successfully disconnected");
			}
			*/
        }
        
        function testMSSQLAdapter()
        {           
            ScriptingClient::SetAdapter("MSSQL");
            ScriptingClient::SetAdapterOption("host", "192.168.1.6:1434");
			ScriptingClient::SetAdapterOption("login", "test");
			ScriptingClient::SetAdapterOption("password", "test123");
			ScriptingClient::SetAdapterOption("dbname", "test");
			
			$conn = ScriptingClient::Connect();
			$this->assertTrue($conn, "MSSQLAdapter successfully connected to host");
			
			$script = "INSERT INTO test(test,test2) VALUES(1,2); DELETE FROM test;";
			
			if ($conn)
			{
    			$exec = ScriptingClient::Execute($script, $params);
    			$this->assertTrue($exec, "MSSQLAdapter successfully executed script");
    			
    			$dicsonn = ScriptingClient::Disconnect();
    			$this->assertTrue($dicsonn, "MSSQLAdapter successfully disconnected");
			}
        }
        
        function testMySQLAdapter()
        {
            /*
            ScriptingClient::SetAdapter("MySQL");
            ScriptingClient::SetAdapterOption("host", "emample.com");
			ScriptingClient::SetAdapterOption("login", "root");
			ScriptingClient::SetAdapterOption("password", "");
			ScriptingClient::SetAdapterOption("dbname", "test");
			
			$conn = ScriptingClient::Connect();
			$this->assertTrue($conn, "MySQLAdapter successfully connected to host");
			
			$script = "INSERT INTO testaa SET a='1', b='2'; DELETE FROM testaa;";
			
			if ($conn)
			{
    			$exec = ScriptingClient::Execute($script, $params);
    			$this->assertTrue($exec, "MySQLAdapter successfully executed script");
    			
    			$dicsonn = ScriptingClient::Disconnect();
    			$this->assertTrue($dicsonn, "MySQLAdapter successfully disconnected");
			}
			*/
        }
        
        function testSSHAdapter() 
        {
            /*
			ScriptingClient::SetAdapter("SSH");
			
			ScriptingClient::SetAdapterOption("host", "emample.com");
			ScriptingClient::SetAdapterOption("login", "telnettest");
			ScriptingClient::SetAdapterOption("password", "sdfas");
			ScriptingClient::SetAdapterOption("consolePromt", "[telnettest@bsd2 ~]$");
			
			$conn = ScriptingClient::Connect();
			$this->assertTrue($conn, "SSHAdapter successfully connected to host");
			
			$script = "touch /tmp/\$filename.txt
			           rm -rf /tmp/emptyfile.txt
			           rm -rf /tmp/emptyfile2.txt
			           rm -rf /tmp/emptyfile3.txt
			           rm -rf /tmp/emptyfile4.txt
			           rm -rf /tmp/allok.txt
			           ls -al /tmp | grep \$filename
			           [EXPECT '\$filename.txt']
			           rm -rf /tmp/\$filename.txt
			           touch /tmp/allok.txt
			           [ELSE]
			           touch /tmp/emptyfile.txt
			           #touch /tmp/emptyfile2.txt
			           [ENDEXPECT]
			           touch /tmp/emptyfile3.txt
			           [TERMINATE]
			           touch /tmp/emptyfile4.txt
			          ";
			
			$params = array("filename" => "test");
			
			if ($conn)
			{
    			$exec = ScriptingClient::Execute($script, $params);
    			$this->assertTrue($exec, "SSHAdapter successfully executed script");
    			
    			$dicsonn = ScriptingClient::Disconnect();
    			$this->assertTrue($dicsonn, "SSHAdapter successfully disconnected");
			}
			*/
        }
    }


?>