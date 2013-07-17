<?php

	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @package Modules
     * @subpackage PaymentModules
     * @sdk
     * @see http://webta.net/docs/wiki/epp-drs.payment.module.creation.howto
     */

	/**
     * This interface must be implemented if to make a payment, user must leave your site and fill forms on gateway's website.
     * @name IPostBackPaymentModule
     * @package Modules
     * @subpackage PaymentModules
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @see http://webta.net/docs/wiki/epp-drs.payment.module.creation.howto
     */
	interface IPostBackPaymentModule extends IPaymentModule
	{
		/**
		 * This method is called in postback routine, to check either this (and not some other) module must process this postback.
		 * If you return true, OnPaymentComplete() will be called.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool true, if it does seem that payment postback is posted for this module.
		 */
		public function CheckSignature($request);
		
		/**
		 * This method is called when we received a postback from payment proccessor and CheckSignature() returned true. 
		 *
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool True if payment succeed or false if failed. If payment is failed and you return false, $this->GetFailureReason() will also be called.
		 */
		public function OnPaymentComplete($request);
		
		/**
		 * Redirect user to gateway payment form, using HTTP 'Location:' header or UI::RedirectPOST($host, $values);
		 * 
		 * @param float $amount Purchase amount
		 * @param Order $order Order object
		 * @param array $post_values Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return void
		 */
		public function RedirectToGateway(Order $order, $post_values = array());
		
		/**
		 * This method is called if you return false in OnPaymentComplete();
		 * If your payment gateway supports this, you can provide a user with an URL, which can be used to pay this invoice later. This URL will be emailed to user.
		 * This most likely will be the URL that you are redirecting to inside RedirectToGateway().
		 * @param Order $order Order object
		 * @param array $post_values Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return string URL of the payment page, with all parameters.
		 */
		public function GetDeferredPaymentURL(Order $order, $post_values = array());
		
		/**
		 * Must return Order ID. You passed it to payment gateway in RedirectToGateway(), remember? Now it should return it back, so you know what this payment is for. And you return it.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return int order ID
		 */
		public function GetOrderID($request);
	}
?>