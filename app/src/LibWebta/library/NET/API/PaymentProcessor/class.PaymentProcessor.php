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
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	Core::Load("Interface.PaymentProcessorDriver.php", dirname(__FILE__)."/Drivers");
	Core::Load("class.AbstractPaymentProcessorDriver.php", dirname(__FILE__)."/Drivers");	

	/**
	 * Payment Processor
	 * 
     * @name       PaymentProcessor
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class PaymentProcessor extends Core
	{
		/**
		 * External Drivers folder
		 *
		 * @var string
		 * @static 
		 */
		static public $DriversPath;
	
		/**
		 * Sets external Drivers folder
		 *
		 * @param string $path
		 * @static 
		 */
		static public function SetDriversPath($path)
		{
			self::$DriversPath = $path;
		}
		
		/**
		 * Return true if class $name is PaymentProcessor driver
		 *
		 * @param string $name
		 * @static 
		 * @return bool
		 */
		static private function IsDriver($name)
		{
			$interfaces = @class_implements("{$name}");
			$extended = @class_parents("{$name}");
			
			return (isset($interfaces["IPaymentProcessorDriver"]) && isset($extended["AbstractPaymentProcessorDriver"]));
		}
		
		/**
		* Return all available drivers
		*
		* @static 
		* @return array $drivers
		*/
		static public function GetAvailableDrivers()
		{
			$retval = array();
			
			$extrenal_drivers = @glob(self::$DriversPath."/class.*Driver.php");
			$drivers = @glob(dirname(__FILE__)."/Drivers/class.*Driver.php");
			
			if (!is_array($extrenal_drivers))
                $extrenal_drivers = array();
			
			$drivers = @array_merge($drivers, $extrenal_drivers);
			
			foreach((array)$drivers as $driver)
			{
				$pi = pathinfo($driver);
				
				Core::Load($pi["basename"], $pi["dirname"]);
				preg_match("/class\.([A-Za-z0-9_]+)\.php/si", $pi["basename"], $matches);
				
				if (class_exists($matches[1]) && self::IsDriver($matches[1]))
					$retval[] = substr($matches[1], 0, -6);
			}
			
			return $retval;
		}
		
		/**
		 * Return Driver Instance
		 *
		 * @param string $drivername
		 * @return Object
		 * @static 
		 */
		static public function GetDriver($drivername = false)
		{
			// Get LibWebta Drivers
			$extrenal_drivers = @glob(self::$DriversPath."/class.*Driver.php");
			$drivers = @glob(dirname(__FILE__)."/Drivers/class.*Driver.php");
			
			if (!is_array($extrenal_drivers))
                $extrenal_drivers = array();
			
			$drivers = @array_merge($drivers, $extrenal_drivers);
							
			foreach((array)$drivers as $driver)
			{
				$pi = pathinfo($driver);
				Core::Load($pi["basename"], $pi["dirname"]);
				
				preg_match("/class\.([A-Za-z0-9_]+)\.php/si", $pi["basename"], $matches);
				$dname = $matches[1];
				
				if (class_exists($matches[1]) && self::IsDriver($matches[1]))
				{
					$reflect = new ReflectionClass($matches[1]);
					$Driver = $reflect->newInstance(true);
					
					if ((!$drivername && $Driver->IPNApplicable()) || $drivername == substr($matches[1], 0, -6))
						return $Driver;
				}
			}
			
			return false;
		}
		
	}
?>