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
	
	/**
     * @name       EZBillDriver
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class EZBillDriver extends AbstractPaymentProcessorDriver implements IPaymentProcessorDriver
	{
		
		public $Config = array();
		
		/**
		 * Constructor
		 *
		 */
		function __construct()
		{			

		}
						
		/**
		 * Return name of payment method
		 * @return string
		 */
		public final function GetName()
		{
			return "EZBill";	
		}
		
		/**
		 * Return true if current application use this payment
		 *
		 * @return bool
		 */
		public final function IPNApplicable()
		{
			return ($_POST['id'] && 
				$_POST['mode'] && 
				$_POST['site'] && 
				$_POST['package']
				);
		}
		
		/**
		 * Return OrderID
		 *
		 * @return string
		 */
		public final function GetOrderID()
		{
		    return $_POST["user_1"];
		}
		
		/**
		 * Proccess payment
		 *
		 * @return bool
		 */
		public final function ProccessPayment($expected_amount = false, $expected_currency = false)
		{
			//
			if ($_POST["mode"] && $_POST["user_1"])
				$this->ThrowSuccessTransactionFunction();
			else 
				$this->ThrowFailedTransactionFunction();			
		}
		
		/**
		 * Validate payment data
		 *
		 * @param array $data
		 */
		public function ValidatePaymentData($data)
		{
		    return false;
		}
		
		/**
		 * Send request to payment server
		 * @param float $amount
		 * @param integer $invoiceid
		 * @param string $description
		 * @param string $type 'single or subscription'
		 * @param array $extra
		 * 
		 * @return bool
		 */
		public final function ProceedToPayment($amount, $invoiceid, $description, $type = 'single', $extra = array())
		{
			$data = array(
						"site" 		=> $extra["siteid"],
						"methods" 	=> 1,
						"packages" 	=> $extra["packageid"],
						"user1" 	=> $invoiceid,
						"user_1" 	=> $invoiceid,
						"user_2" 	=> $invoiceid,
						"user2" 	=> $invoiceid,
						"username"	=> $extra["username"],
						"password"	=> $extra["password"]
						);
						
			header("Location: https://secure.vxsbill.com/ezbill.php3?".http_build_query($data));
			exit();
		}		
	}
?>