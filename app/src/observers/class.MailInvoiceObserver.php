<?php
	
	/**
	 * @ignore 
	 */
	class MailInvoiceObserver implements IInvoiceObserver 
	{
		public function __construct ()
		{
		}
		
		public function OnIssued (Invoice $Invoice)
		{
			$db = Core::GetDBInstance();			
			if (CONFIG::$PREPAID_MODE && $Invoice->Purpose != INVOICE_PURPOSE::BALANCE_DEPOSIT)
			{
				// In prepaid mode skip emails about invoices 
				return;
			}
			
			$userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($Invoice->UserID));
			
			$args = array(	"login" => $userinfo["login"],
							"Invoice"		=> $Invoice,
							"client"		=> $userinfo
						  );
			mailer_send("new_invoice.eml", $args, $userinfo["email"], $userinfo["name"]);
		}
		
		public function OnPaid (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
			$db = Core::GetDBInstance();			
			if (CONFIG::$PREPAID_MODE && $Invoice->Purpose != INVOICE_PURPOSE::BALANCE_DEPOSIT)
			{
				// In prepaid mode skip emails about invoices				
				return;
			}
			
			$userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($Invoice->UserID));
			
			$args = array(	"login" => $userinfo["login"],
							"Invoice"		=> $Invoice,
							"client"		=> $userinfo
						  );
			mailer_send("invoice_paid.eml", $args, $userinfo["email"], $userinfo["name"]);
		}
		
		public function OnFailed (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
			//
		}
	}
?>