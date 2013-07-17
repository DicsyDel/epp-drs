<?php

	/**
	 * Invoicing observer
	 * 
	 * @package Invoicing
	 * @sdk
	 */
	interface IInvoiceObserver
	{
		public function OnIssued (Invoice $Invoice);
		
		public function OnPaid (Invoice $Invoice, AbstractPaymentModule $payment_module = null);
		public function OnFailed (Invoice $Invoice, AbstractPaymentModule $payment_module = null);
	}
?>