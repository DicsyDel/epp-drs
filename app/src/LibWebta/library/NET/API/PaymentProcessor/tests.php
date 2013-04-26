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
     * @subpackage PaymentProcessor
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	$base = dirname(__FILE__);
		
	Core::Load("NET/API/PaymentProcessor/class.PaymentProcessor.php");
	
	/**
     * @ignore 
     *
     */
	function SuccessPayment()
	{
		
		
	}
	
	/**
     * @ignore 
     *
     */
	function FailedPayment()
	{
		
		
	}
	
	/**
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @name  NET_API_PaymentProcessor_Test
     *
     */
	class NET_API_PaymentProcessor_Test extends UnitTestCase 
	{
		private $Payment;
		
        function __construct() 
        {
            $this->UnitTestCase('NET/API/PaymentProcessor test');
        }
        
        function testNET_API_Payments() 
        {
			$this->Payment = PaymentProcessor::GetDriver("PayPal");
			$this->DoTests();
			
			$this->Payment = false;
			
			
			$drivers = PaymentProcessor::GetAvailableDrivers();
			$this->assertTrue(is_array($drivers), "Some drivers exists");
			
			// Try to get External Driver
			PaymentProcessor::SetDriversPath(dirname(__FILE__)."/Drivers");
			$this->Payment = PaymentProcessor::GetDriver("PayPal");
			$this->DoTests();
        }
        
        
        function DoTests()
        {
        	$this->assertTrue($this->Payment, "Payment Driver exists");
        }
    }


?>