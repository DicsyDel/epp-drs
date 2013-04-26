<?
	require_once('src/prepend.inc.php');
	
	class UIPaymentObserver implements IPaymentObserver
	{
		public function __toString()
		{
			return __CLASS__;
		}
		
		public function Notify(AbstractPaymentModule $PaymentModule, $status)
		{
			Log::Log("UIPaymentObserver: received notify", E_USER_NOTICE);
			
			if ($status == PAYMENT_STATUS::SUCCESS)
			{
				$GLOBALS["okmsg"] = _("Thank you for your payment!");
	            if (!$_SESSION["success_payment_redirect_url"])
		        	CoreUtils::Redirect("inv_view.php");
		        else 
		        	CoreUtils::Redirect($_SESSION["success_payment_redirect_url"]);
			}
			elseif ($status == PAYMENT_STATUS::FAILURE)
			{
				$PaymentForm = $PaymentModule->GetPaymentForm();
				
				$smarty = Core::GetSmartyInstance("SmartyExt");
				
				$fields = $PaymentForm->ListFields();
				$smarty_fields = array();
				foreach($fields as $field)
				{
					$smarty_fields[$field->Title] = array("name" => $field->Name, "required" => $field->IsRequired, "type" => $field->FieldType, "values" => $field->Options);
					if ($_REQUEST[$field->Name])
						$attr[$field->Title] = $_REQUEST[$field->Name];
				}
				
				$display["errmsg"] = "The following errors occured";
				$display["err"] = explode("\n", $PaymentModule->GetFailureReason());				
				$display["gate"] = $PaymentModule->GetModuleName();
				$display["orderid"] = $PaymentModule->GetOrderID(false);
				$display["fields"] = $smarty_fields;
				$display["post"] = $attr;
				$template_name = "client/paymentdata.tpl";
				
				$smarty->assign($GLOBALS["display"]);
				$smarty->assign($display);
				$smarty->display($template_name);
			}
			else 
				throw new Exception(sprintf(_("Undefined PaymentStatus received from %s payment module."), $PaymentModule->GetModuleName()), E_USER_ERROR);
		}
	}
	
	
	
	
	
	if (!$_REQUEST["invoices"] && !$_REQUEST["string_invoices"] && !$_REQUEST["orderid"] && !$_SESSION["orderid"])
	{
		$errmsg = _("No invoices to pay");
		CoreUtils::Redirect("inv_view.php");
	}
	else
	{
		if ($_REQUEST["string_invoices"])
			$_REQUEST["invoices"] = explode(",", $_REQUEST["string_invoices"]);
	}
		
	$invoices = array();
	$total = 0;
	$display["rows"] = array();
	
	try
	{
		if (!($_SESSION['orderid'] || $_REQUEST["orderid"]) 
				|| ($_REQUEST["invoices"] && md5(implode($_REQUEST["invoices"])) != $_SESSION["inv_md5"]))
		{
			$_SESSION["inv_md5"] = md5(implode($_REQUEST["invoices"]));
			$Order = new Order($_SESSION["userid"]);
			foreach ((array)$_REQUEST["invoices"] as $invoiceid)
			{
				$Invoice = Invoice::Load($invoiceid);
				if ($Invoice->Status != INVOICE_STATUS::PAID)
					$Order->AddInvoice($Invoice);
			}
			
			$Order->Save();
			$_SESSION['orderid'] = $Order->ID; 
		}
		else
		{
			$Order = Order::Load($req_orderid ? $req_orderid : $_SESSION['orderid']);
		}
	}
	catch (Exception $e)
	{
		$errmsg = $e->getMessage();
		CoreUtils::Redirect("inv_view.php");
	}
	
	$display["rows"] = $Order->GetInvoiceList();	
	if (!$Order->ID || count($display["rows"]) == 0)
	{
		$errmsg = _("No invoices to pay");
		CoreUtils::Redirect("inv_view.php");
	}
	
	$display["vat"] = $display["rows"][0]->GetVATPercent();
	
	$total = round($Order->GetTotal(), 2);
	$display["total"] = $total;
	
	try
	{
		$payment_modules = $PaymentModuleFactory->ListModules();
	}
	catch (Exception $e)
	{
		throw new CustomException($e->getMessage(), $e->getCode());
	}
	
	foreach ($payment_modules as $pmodule)
	{
		$payment_module = $PaymentModuleFactory->GetModuleObjectByName($pmodule);
		$min_amount = $payment_module->GetMinimumAmount();
		if ($min_amount != false && $min_amount > $total)
			$display["mdisabledreason"][$pmodule] = sprintf(_("%s payment method not available because total amount is less than allowed minimum (%s)"), $pmodule, number_format($min_amount, 2));
	}

	// If invoice is for balance deposit, we must hide 'pay from balance' option
	$deposit_payment = true;
	foreach ($display["rows"] as $Invoice)
		$deposit_payment &= $Invoice->Purpose == INVOICE_PURPOSE::BALANCE_DEPOSIT;
	if ($deposit_payment)
		$display["hide_balance"] = true;
	
	if (CONFIG::$PREPAID_MODE && !$deposit_payment)
		$smarty->assign("payment_modules", null);
	
	$Balance = DBBalance::GetInstance()->LoadClientBalance($_SESSION["userid"]);
	if ($Balance->Total < $total)
	{
		$display["balance_disabled"] = true;
		$display["balance_disabled_reason"] = _("Insufficient balance for this payment.");
	}
	
	if ($_REQUEST["gate"])
	{
		if ($_REQUEST["gate"] == "Balance")
		{
			try
			{
				foreach ($Order->GetInvoiceList() as $Invoice)
				{
					if ($Invoice->Purpose != INVOICE_PURPOSE::BALANCE_DEPOSIT 
						&& $Invoice->Status == INVOICE_STATUS::PENDING)
					{
						// Withdraw balance
						$Operation = $Balance->CreateOperation(BalanceOperationType::Withdraw, $Invoice->GetTotal());
						$Operation->InvoiceID = $Invoice->ID;
						$Balance->ApplyOperation($Operation);
						
						$Invoice->MarkAsPaid(null);
					}
				}
				$okmsg = _("Payment successfully complete");
				UI::Redirect("order_info.php?orderid={$Order->ID}");
			}
			catch (Exception $e)
			{
				$err = $e->getMessage();
			}
		}
		else
		{
			$payment_module = $PaymentModuleFactory->GetModuleObjectByName($_REQUEST["gate"]);
			
			// If Total = 0 then mark all invoices in order as paid
			if ($Order->GetTotal() == 0)
			{
				$Order->MarkAsPaid($payment_module);
				$okmsg = _("Payment successfully complete");
				UI::Redirect("order_info.php?orderid={$Order->ID}");
			}
			else
			{		
				$payment_module->AttachObserver(new UIPaymentObserver());
				$client_row = $db->GetRow("SELECT * FROM users WHERE id='{$_SESSION["userid"]}'");
				
				if ($_POST && $post_action == "proceed")
				{
				    $chk = $payment_module->ValidatePaymentFormData($_POST);
			
				    if ($chk === true)
					{
						$reflect = new ReflectionObject($payment_module);
						if ($reflect->implementsInterface("IPostBackPaymentModule"))
							$payment_module->RedirectToGateway($Order, array_merge($client_row, $_POST));
						else
						{
							$res = $payment_module->ProcessPayment($Order, array_merge($client_row, $_POST));
							
							if ($res)
							    $payment_module->NotifyObservers(PAYMENT_STATUS::SUCCESS);
							else
							    $payment_module->NotifyObservers(PAYMENT_STATUS::FAILURE);
						}
						exit();
				    }
				    else 
				       $err = $chk;
				}
						
				$PaymentForm = $payment_module->GetPaymentForm();
				
			    if ($PaymentForm == false)
				{
					
					$reflect = new ReflectionObject($payment_module);
					if ($reflect->implementsInterface("IPostBackPaymentModule"))
						$payment_module->RedirectToGateway($Order, $client_row);
					else
					{
						$res = $payment_module->ProcessPayment($Order, $client_row);
						
						if ($res)
						    $payment_module->NotifyObservers(PAYMENT_STATUS::SUCCESS);
						else
						    $payment_module->NotifyObservers(PAYMENT_STATUS::FAILURE);
					}
					exit();
				}
				else
				{	
					$fields = $PaymentForm->ListFields();
					$smarty_fields = array();
					foreach($fields as $field)
						$smarty_fields[$field->Title] = array("name" => $field->Name, "required" => $field->IsRequired, "type" => $field->FieldType, "values" => $field->Options);
					
					$display["gate"] = $_REQUEST["gate"];
					$display["orderid"] = $_SESSION["orderid"];
					$display["fields"] = $smarty_fields;		
					$template_name = "client/paymentdata";
				}
			}
		}
	}

	if (!$template_name)
		$template_name = "client/make_payment";
		
	require_once('src/append.inc.php');
?>
