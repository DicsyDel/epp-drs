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
     * @subpackage SNMP
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */        

    Core::Load("CoreException");
	Core::Load("NET/SNMP");
	
	define("SNMP_AGENT_IP", "192.168.1.254");
	
	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage SNMP
     * @name NET_SNMP_Test
	 *
	 */
	class NET_SNMP_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('NET/SNMP Tests');
        }

        function testNET_SNMP_SNMP() 
        {
        	//
        	// SNMP
        	//
        	$SNMP = new SNMP();
        	$SNMP->Connect(SNMP_AGENT_IP, null, "public");
        	
        	// GetTree
        	$retval = $SNMP->GetTree(".1.3.6.1.2.1.25.2.3.1.6");
        	$this->assertTrue(is_array($retval), "SNMP->GetAll() returned array");
			
        	// Get
        	$retval = $SNMP->Get("system.sysDescr.0");
			$this->assertTrue(is_string($retval), "SNMP->Get() returned string");
			
			// Get
			$retval = $SNMP->Get(".1.3.6.1.2.1.6.13.1.1.0.0.0.0.80.0.0.0.0.0");
			$this->assertTrue(is_string($retval), "SNMP->Get() returned string");
			
			// Get
			$retval = $SNMP->Get(".1.3.6.1.2.1.6.13.1.1.0.0.0.0.3306.0.0.0.0.0");
					
			$this->assertTrue(is_string($retval), "SNMP->Get() returned string");
			
        }
        
        function testNET_SNMP_Tree()
        {
        	//
        	// SNMP
        	//
        	$SNMPTree = new SNMPTree();
        	$SNMPTree->Connect(SNMP_AGENT_IP, null, "public");
        	$SNMPTree->SetMIB("HOST-RESOURCES-MIB", "hr");
        	
        	$result = $SNMPTree->GetSystemUptime();
        	$this->assertTrue(is_string($result), "SNMP->GetSystemUptime() returned string");
        	
        	$result = $SNMPTree->GetAllStorageIndex();
        	$this->assertTrue(is_array($result), "SNMP->GetAllDisks() returned array");
        	
        	//
        	
        	$descr = $SNMPTree->GetAllStorageDescr();
        	$this->assertTrue(is_array($descr), "SNMP->GetAllStorageDescr() returned array");
        	
        	$sizes = $SNMPTree->GetAllStorageSize();
        	$this->assertTrue(is_array($sizes), "SNMP->GetAllStorageSize() returned array");
        	
        	$useds = $SNMPTree->GetAllStorageUsed();
        	$this->assertTrue(is_array($useds), "SNMP->GetAllStorageUsed() returned array");
        	
        	$procs_load = $SNMPTree->GetAllProcessorLoad();
        	$this->assertTrue(is_array($procs_load), "SNMP->GetAllProcessorLoad() returned array");        	
        	
        
        }
        
    }

?>