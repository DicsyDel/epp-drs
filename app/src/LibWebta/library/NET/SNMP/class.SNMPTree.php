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
     * @subpackage SNMP
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
    Core::Load("NET/SNMP/SNMP");
    
	/**
	 * @name SNMPTree
	 * @package NET
	 * @subpackage SNMP
	 * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
	 *
	 */	
	class SNMPTree extends SNMP
	{
		
		/**
		 * Root MIB
		 *
		 * @var string
		 */
		public $MIB;
		
		/**
		 * Prefix that will be added to all nodes
		 *
		 * @var unknown_type
		 */
		public $MIBPrefix;
		
		
		/**
		 * SNMP Tree Constructor
		 *
		 * @ignore
		 */
		function __construct()
		{
			// Default values
			$this->MIB = "RFC1213-MIB"; // Standard MIB
			$this->MIBPrefix = "";
		}
		
		
		/**
		 * Set a root MIB
		 *
		 * @param string $mib MIB name
		 * @param string $prefix Prefix to be added to all nodes
		 */
		public function SetMIB($mib, $prefix = "")
		{
			$this->MIB = $mib;
			$this->MIBPrefix = $prefix;
		}
		
		
		/**
		 * Placeholder for all Get* and GetAll* method calls
		 *
		 * @param string $method Method name
		 * @param array $args Method call arguments
		 * @return unknown
		 */	
		public function __call($method, $args)
   		{
   			// Get an array
     		if (substr($method, 0, 6) == "GetAll")
     		{
     			$key = substr($method, 6);
     			
     			$path = $this->MIB ."::{$this->MIBPrefix}{$key}";
     			//die($path);
     			$retval = $this->GetTree($path);
     			
     			return $retval;
     		}
     				
   			// Get a single value
   			if (substr($method, 0, 3) == "Get")
     		{
     			$index = $args[0] ? $args[0] : "0";
     				
     			$key = substr($method, 3);
     			$path = $this->MIB ."::{$this->MIBPrefix}{$key}.$index";
     			$retval = $this->Get($path);
     			
     			return $retval;
     		}
     		
     		
   		}
   		
   		

		
	}
	
?>
