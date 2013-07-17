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
     * @package    Data_DB
     * @subpackage MySQL
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */
	
    Core::Load("Data/DB/MySQL");
	
	/**
	 * @name Data_DB_MySQL_Test
	 * @category   LibWebta
     * @package    Data_DB
     * @subpackage MySQL
	 *
	 */
	class Data_DB_MySQL_Test extends UnitTestCase 
	{
        function MySQLToolTest() 
        {
            $this->UnitTestCase('MySQLTool test');
        }
        
        function testMySQLTool() 
        {
			
			$MySQLTool = new MySQLTool();
			
			//
			// List databases
			// 
			$retval = $MySQLTool->ListDatabases();
			$this->assertTrue(count($retval)> 0, "There are more than 0 database listed");
			
			//
			// Create DB
			// 
			$retval = $MySQLTool->CreateDatabase("cptest", "test1");
			$this->assertTrue($retval, "Database created succesfully");
			
			//
			// Create User
			// 
			$retval = $MySQLTool->CreateUser("cptest", "test1", "testpass0");
			$this->assertTrue($retval, "User created succesfully");
			
			//
			// Privileges
			// 
			$privileges = array("SELECT", "UPDATE");
			$retval = $MySQLTool->SetPrivileges("cptest_test1", "cptest_test1", $privileges);
			$this->assertTrue($retval, "Granted rights succesfully");
			
			//
			// Delete DB
			// 
			$retval = $MySQLTool->DropDatabase("cptest", "test1");
			$this->assertTrue(!in_array("cptest_test1", $MySQLTool->ListDatabases()), "Deleted DB does not exist");
			
			//
			// Delete User
			// 
			$retval = $MySQLTool->DropUser("cptest_test1");
			$this->assertTrue($retval, "User deleted succesfully");
			
			//
			// Server info
			//
			$retval = $MySQLTool->GetSQLServerVersion();
			$this->assertTrue(count($retval) > 2, "SQL server version retreived");
			
			//
			// List users
			//
			$retval = $MySQLTool->ListUsers();
			$this->assertTrue(is_array($retval) && count($retval) > 0, "Users list is non-empty array");
			
        }
    }


?>