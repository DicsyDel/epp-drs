<?php

	include("../src/prepend.inc.php");
	
	class UIPaymentObserver implements IPaymentObserver
	{
		public function __toString()
		{
			return __CLASS__;
		}
		
		public function Notify(AbstractPaymentModule $PaymentModule, $status)
		{
			$db = Core::GetDBInstance();
			Log::Log("UIPaymentObserver: received notify", E_USER_NOTICE);
			
			if ($status == PAYMENT_STATUS::SUCCESS)
			{
				// get invoices numbers
			    $orderid = $PaymentModule->GetOrderID($_REQUEST);
			    $invoices = $db->GetAll("SELECT * FROM invoices WHERE orderid=?", array($orderid));
			        
		        // Update database for each invoice
		        foreach ($invoices as $invoice_info)
		        {      
			        switch ($invoice_info["purpose"])
			        {
			        	case INVOICE_PURPOSE::DOMAIN_CREATE:
			        		$reg++;
			        	break;
			        	
			        	case INVOICE_PURPOSE::DOMAIN_RENEW:
			        		$other++;
			        	break;
			        	
			        	case INVOICE_PURPOSE::DOMAIN_TRANSFER:
			        		$trans++;
			        	break;
			        }
		        }
		        
		        if ($reg > 0 && $trans == 0 && $other == 0)
		        	$op = INVOICE_PURPOSE::DOMAIN_CREATE;
		        elseif ($reg == 0 && $trans > 0 && $other == 0)
		        	$op = INVOICE_PURPOSE::DOMAIN_TRANSFER;
		        else 
		        	$op = "Mixed";
		        	
		        CoreUtils::Redirect("payment_success.php?op={$op}");
			}
			elseif ($status == PAYMENT_STATUS::FAILURE)
			{
				$_SESSION["failure_reason"] = $PaymentModule->GetFailureReason();
				CoreUtils::Redirect("payment_failed.php");
			}
			else 
				throw new Exception(sprintf(_("Undefined PaymentStatus received from %s payment module."), $PaymentModule->GetModuleName()), E_USER_ERROR);
		}
	}

	$logmess = "";
	foreach($_REQUEST as $k=>$v)
		$logmess .= "{$k} = {$v}\n";
	
	Log::Log(sprintf("Received IPN:\n%s", $logmess), E_USER_NOTICE);
	
	try
	{
		$payment_module = $PaymentModuleFactory->GetModuleInstanceByContext($_REQUEST);
		if (!$payment_module)
		{
			Log::Log(_("Cannot find valid Payment module for request."), E_USER_WARNING);
			$_SESSION["failure_reason"] = _("Application error. Administrator notified.");
			CoreUtils::Redirect("payment_failed.php");
		}
		else 
		{
			Log::Log(sprintf('Processing payment with %s module', $payment_module->GetModuleName()), E_USER_NOTICE);
			$payment_module->AttachObserver(new UIPaymentObserver());
			
			$ret = $payment_module->OnPaymentComplete($_REQUEST);
			if ($ret === true) {
				$payment_module->NotifyObservers(PAYMENT_STATUS::SUCCESS);
			} elseif ($ret === false) {
				$payment_module->NotifyObservers(PAYMENT_STATUS::FAILURE);
			} else {
				Log::Log("Payment is pending", E_USER_NOTICE);
			}
		}
	}
	catch (Exception $e)
	{
		Log::Log(sprintf(_("IPN thrown exception: %s on line %s in file %s"), 
							$e->getMessage(), $e->getLine(), $e->getFile()
						), E_ERROR
				);
				
		$_SESSION["failure_reason"] = _("Application error. Administrator notified.");
		CoreUtils::Redirect("payment_failed.php");
	}
?>