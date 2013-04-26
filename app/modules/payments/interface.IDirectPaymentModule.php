<?php

	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @package Modules
     * @subpackage PaymentModules
     * @sdk
     * @see http://webta.net/docs/wiki/epp-drs.payment.module.creation.howto
     */

	/**
     * This interface must be implemented by modules that does not redirect users to external webpage and proccesses your payment internally (for example, using XML-RPC, SOAP or other protocol), and the status of the payment is known immediately. 
     * @name IDirectPaymentModule
     * @package Modules
     * @subpackage PaymentModules
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @see http://webta.net/docs/wiki/epp-drs.payment.module.creation.howto
     */
	interface IDirectPaymentModule extends IPaymentModule
	{
		/**
		 * This method is called when user submits a payment form. 
		 * @param Order $order Order object
		 * @param array $post_fields Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return bool True if payment succeed or false if failed. If payment is failed and you return false, $this->GetFailureReason() will also be called.
		 */
		public function ProcessPayment(Order $order, $post_values = array());
	}
	
	
?>