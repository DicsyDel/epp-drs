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
	
	/**
	 * @name SNMP
	 * @package NET
	 * @subpackage SNMP
	 * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
	 *
	 */
	class SNMP extends Core
	{
		
		/**
		 * Default SNMP port
		 *
		 */
		const DEFAULT_PORT = 161;
		
		/**
		 * Connection timeout in milliseconds
		 *
		 */
		const DEFAULT_TIMEOUT = 5;
		
		/**
		 * Connection retries
		 *
		 */
		const DEFAULT_RETRIES = 3;
		
		/**
		* SNMP Connection Timeout
		* @var integer
		*/
		public $Timeout;
		
		/**
		 * Define connection target
		 *
		 * @param string $host
		 * @param int $port
		 * @param string $community
		 */
		public function Connect($host, $port=161, $community="public", $timeout = false, $retries = false, $SNMP_VALUE_PLAIN = false)
		{
			if (is_null($port))
				$port = self::DEFAULT_PORT ;
			
			//$this->Connection = "{$host}:{$port}";
			$this->Connection = "{$host}";
			$this->Community = $community;
			
			if (!$timeout)
				$this->Timeout = (!defined("SNMP_TIMEOUT")) ? self::DEFAULT_TIMEOUT : SNMP_TIMEOUT;
			else 
				$this->Timeout = $timeout;
				
			$this->Timeout = $this->Timeout*100000;
			
			$this->Retries = $retries ? $retries : self::DEFAULT_RETRIES;
			
			if ($SNMP_VALUE_PLAIN == true)
				@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
			else 
				@snmp_set_valueretrieval(SNMP_VALUE_LIBRARY);
		}
		
		
		/**
		 * DEtermine either we have snmp extension installed
		 *
		 * @return bool
		 */
		public function IsInstalled()
		{
			return function_exists("snmpget");
		}
		
		
		/**
		 * Get object with OID $OID
		 *
		 * @param string $OID
		 * @return string Object value
		 */
		public function Get($OID)
		{
			try 
			{
				$retval = @snmpget($this->Connection, $this->Community, $OID, $this->Timeout, $this->Retries);
								
			} catch (Exception $e)
			{
				$this->RaiseWarning("Cannot get SNMP property. ".$e->__toString());
			}
			return $retval;
		}
		
		
		/**
		 * Do snmpwalk
		 *
		 * @param unknown_type $rootOID
		 * @return array Array of values
		 */
		public function GetTree($rootOID = null)
		{
			try 
			{
				$retval = @snmpwalk($this->Connection, $this->Community, $rootOID, $this->Timeout);
				
			} catch (Exception $e)
			{
				$this->RaiseWarning("Cannot walk through {$this->Connection}/{$this->Community}/$rootOID". $e->__toString());
			}
			return $retval;
		}
		
		/**
		 * Do snmpwalkoid
		 *
		 * @param unknown_type $rootOID
		 * @return array Array of values
		 */
		public function GetFullTree($rootOID = null)
		{
			try 
			{
				$retval = @snmpwalkoid($this->Connection, $this->Community, $rootOID, $this->Timeout);
				
			} catch (Exception $e)
			{
				$this->RaiseWarning("Cannot walkoid through {$this->Connection}/{$this->Community}/$rootOID". $e->__toString());
			}
			return $retval;
		}
	}
	
?>
