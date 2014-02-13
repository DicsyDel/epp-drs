<?php
	class DBPaymentObserver implements IPaymentObserver
	{
		public function __toString()
		{
			return __CLASS__;
		}
		
		public function Notify(AbstractPaymentModule $PaymentModule, $status)
		{
			Log::Log("DBPaymentObserver: received notify", E_USER_NOTICE);
			
			$db = Core::GetDBInstance();
			
			// get invoices numbers
		    $orderid = (int)$PaymentModule->GetOrderID($_REQUEST);
		    
		    $Order = Order::Load($orderid);
		    
		    $userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($invoices[0]['userid']));
		    
			if ($status == PAYMENT_STATUS::SUCCESS)
			{	        
		        $Order->MarkAsPaid($PaymentModule);
			}
			elseif ($status == PAYMENT_STATUS::FAILURE)
			{
				$Order->MarkAsFailed($PaymentModule);
				
				// If multiple invoices processed
				$args = array(	"client" 	=> $userinfo, 
								"invoices"	=> $Order->GetInvoiceList(),
								"orderid"	=> $Order->ID,
								"reason"	=> $PaymentModule->GetFailureReason()
						 );
				mailer_send("order_payment_failed.eml", $args, $userinfo["email"], $userinfo["name"]);
			}
			else 
				throw new Exception(sprintf(_("Undefined PaymentStatus received from %s payment module."), $PaymentModule->GetModuleName()), E_USER_ERROR);
		}
	}
?>