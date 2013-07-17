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
     * @name       IPaymentProcessorDriver
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	interface IPaymentProcessorDriver
	{
		/**
		 * Get name of payment routine
		 * @return string
		 */
		public function GetName();
		
		/**
		 * Detects either a payment was made using this module/method.
		 *
		 * @return bool True if transaction was made via this module.
		 */
		public function IPNApplicable();
		
		/**
		 * Verify transaction. Check against invoices table.
		 * @param float $expected_amount
		 * @param string $expected_currency
		 * 
		 * @return bool Either payment transaction passed or declined.
		 */
		public function ProccessPayment($expected_amount = false, $expected_currency = false);
		
		/**
		 * Redirect customer to checkout form on payment gateway server or send payment transaction on gateway server (for example, useing HTTP POST, HTTP GET or XML-RPC).
		 * @param float $amount Payment amount
		 * @param integer $invoiceid Invoice ID, taken from invoices table.
		 * @param string $description Payment description, as it will appear in invoice.
		 * @param array $extra Extra variables that are being passed to gateway.
		 * 
		 * @return bool
		 */
		public function ProceedToPayment($amount, $invoiceid, $description, $extra = array());
		
		
		/**
		 * Verify all fields, submitted by user. $_POST fields can be extracted from $data variable.
		 *
		 * @param ar $data
		 */
		public function ValidatePaymentData($data);
		
		/**
		 * Return OrderID
		 *
		 */
		public function GetOrderID();
	}
?>