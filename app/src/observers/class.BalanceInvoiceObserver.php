<?php
	
	/**
	 * @ignore 
	 */
	class BalanceInvoiceObserver implements IInvoiceObserver
	{
		public function __construct ()
		{
			$this->HandledPurposes = array();
		}
		
		public function OnIssued (Invoice $Invoice)
		{
			if ($Invoice->Purpose != INVOICE_PURPOSE::BALANCE_DEPOSIT)
			{
				Log::Log("BalanceInvoiceObserver::OnIssued(InvoiceID={$Invoice->ID})", E_USER_NOTICE);
				try
				{
					$Client = Client::Load($_SESSION["userid"] ? $_SESSION["userid"] : $Invoice->UserID);
					if ($Client->GetSettingValue(ClientSettings::AUTO_PAY_FROM_BALANCE))
					{
						if ($Client->GetSettingValue(ClientSettings::AUTO_PAY_NO_RENEW) && 
							$Invoice->Purpose == INVOICE_PURPOSE::DOMAIN_RENEW) {
							return;
						}

						$Balance = DBBalance::GetInstance()->LoadClientBalance($Client->ID);
						$Operation = $Balance->CreateOperation(BalanceOperationType::Withdraw, $Invoice->GetTotal());
						$Operation->InvoiceID = $Invoice->ID;
						$Balance->ApplyOperation($Operation);
						
						$Invoice->MarkAsPaid(null);
					}
				}
				catch (Exception $e)
				{
					Log::Log("BalanceInvoiceObserver::OnIssued() thrown exception: {$e->getMessage()}", E_USER_ERROR);
				}
				Log::Log("BalanceInvoiceObserver::OnIssued Successfully completed.", E_USER_NOTICE);
			}
		}
		
		public function OnPaid (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
			if ($Invoice->Purpose != INVOICE_PURPOSE::BALANCE_DEPOSIT)
				return;
				
			Log::Log("BalanceInvoiceObserver::OnPaid(InvoiceID={$Invoice->ID})", E_USER_NOTICE);
			
			try
			{
				$Balance = DBBalance::GetInstance()->LoadClientBalance($Invoice->UserID);
				$Operation = $Balance->CreateOperation(BalanceOperationType::Deposit, $Invoice->GetTotal());
				$Operation->InvoiceID = $Invoice->ID;
				$Balance->ApplyOperation($Operation);
			}
			catch(Exception $e)
			{
				Log::Log("BalanceInvoiceObserver::OnPaid() thrown exception: {$e->getMessage()}", E_USER_ERROR);
			}
			
			
			// OnPaymentComplete routine succeffully completed.
			Log::Log("BalanceInvoiceObserver::OnPaid Successfully completed.", E_USER_NOTICE);
		}
		
		public function OnFailed (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
		
		}
	}
?>
