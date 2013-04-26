<?php
	
	/**
	 * @ignore 
	 */
	class PreregistrationInvoiceObserver implements IInvoiceObserver
	{
		public function __construct ()
		{
			$this->HandledPurposes = array(INVOICE_PURPOSE::PREREGISTRATION_DROPCATCHING);
		}
		
		public function OnIssued (Invoice $Invoice)
		{
			
		}
		
		public function OnPaid (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
			if (!in_array($Invoice->Purpose, $this->HandledPurposes))
				return;
				
			Log::Log("PreregistrationInvoiceObserver::OnPaid(InvoiceID={$Invoice->ID})", E_USER_NOTICE);
			
			// Get domain information
			try
			{
				$Domain = DBDomain::GetInstance()->Load($Invoice->ItemID);
			}
			catch(Exception $e)
			{
				Log::Log("PreregistrationInvoiceObserver::OnPaid() thown exception: {$e->getMessage()}", E_USER_ERROR);
			}
			
			if ($Domain)
			{
				$Domain->Status = DOMAIN_STATUS::AWAITING_PREREGISTRATION;				
				DBDomain::GetInstance()->Save($Domain);
			}
			else 
			{
				// Domain not found
				Log::Log(sprintf("Domain width ID '%s' not found.", $Invoice->ItemID), E_ERROR);
			}
			
			// OnPaymentComplete routine succeffully completed.
			Log::Log("PreregistrationInvoiceObserver::OnPaid Successfully completed.", E_USER_NOTICE);
		}
		
		public function OnFailed (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
		
		}
	}
?>