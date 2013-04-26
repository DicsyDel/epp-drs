<?php

	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @package Modules
     * @subpackage PaymentModules
     * @sdk
     */

	/**
	 * This interface must implemented by all payment modules.
     * @name IPaymentModule
     * @package Modules
     * @subpackage PaymentModules
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	interface IPaymentModule
	{
		/**
	     * Must return a DataForm object that will be used to draw a configuration form.
	     * @return DataForm object
	     */
		public function GetConfigurationForm();
		
		/**
		 * This method is called on early module initialization stage. 
		 * @param DataForm $ConfigDataForm DataForm object that you returned in GetConfigurationForm(), filled with values.
		 * @return void
		 */
		public function InitializeModule($ConfigDataForm);
		
		/**
		 * Must return module name.
		 * @return string Module name
		 */
		public function GetModuleName();
		
		/**
		 * Must return a DataForm object that will be used to draw a payment form for this module.
		 * @return DataForm object. 
		 */
		public function GetPaymentForm();

		/**
		 * This method is called if payment failes.
		 * Must return a string with explanation of a payment reason.
		 * @return string
		 */
		public function GetFailureReason();
		
		/**
		 * This method is called to validate either user filled all fields of your form properly.
		 * If you return true, ProccessPayment will be called. If you return array, user will be presented with values of this array as errors. 
		 *
		 * @param array $post_values
		 * @return true or array of error messages.
		 */
		public function ValidatePaymentFormData($post_values);
		
		/**
		 * FIXME:
		 *
		 * @return float or false if нет ограничений
		 */
		public function GetMinimumAmount();
	}
?>