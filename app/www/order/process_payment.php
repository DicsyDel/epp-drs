<?
	class UIPaymentObserver implements IPaymentObserver
	{
		public function __toString()
		{
			return __CLASS__;
		}
		
		function Notify(AbstractPaymentModule $PaymentModule, $status)
		{		
			global $PaymentModuleFactory;
			
			if ($status == PAYMENT_STATUS::SUCCESS)
			{
				UI::Redirect("payment_success.php?op=".$_SESSION['wizard']['whois']["operation"]);
			}
			else
			{
				$smarty = Core::GetSmartyInstance("SmartyExt");
				
				$PaymentForm = $PaymentModule->GetPaymentForm();
				
				$payment_module = $PaymentModuleFactory->GetModuleObjectByName($_SESSION['wizard']['checkout']["gate"]);
					
				$fields = $PaymentForm->ListFields();
				$smarty_fields = array();
				foreach($fields as $field)
				{
					$smarty_fields[$field->Title] = array("name" => $field->Name, "required" => $field->IsRequired, "type" => $field->FieldType, "values" => $field->Options);
					if ($_REQUEST[$field->Name])
						$attr[$field->Title] = $_REQUEST[$field->Name];
				}
				
				$display["errors"] = explode("\n", $PaymentModule->GetFailureReason());
				$display["fields"] = $smarty_fields;
				$display["post"] = $attr;
				$display['phone_widget'] = Phone::GetInstance()->GetWidget();
				$template_name = "paymentgate.tpl";
				
				$smarty->assign($GLOBALS["display"]);
				$smarty->assign($display);
				$smarty->display($template_name);
			}
		}
	}

	$payment_module = $PaymentModuleFactory->GetModuleObjectByName($_SESSION['wizard']['checkout']['gate']);
	if(!$payment_module)
	{
		$err[] = "Invalid payment method";
		$step = "placeorder";
	}
	else 
	{
		$chk = $payment_module->ValidatePaymentFormData($_POST);
		
		if ($chk === true)
		{
			$client_row = $db->GetRow("SELECT * FROM users WHERE id=?", array($_SESSION["userid"]));
		    
			$Order = Order::Load($_SESSION["orderid"]);
			
			$payment_module->AttachObserver(new UIPaymentObserver());
			
			$reflect = new ReflectionObject($payment_module);
			if ($reflect->implementsInterface("IPostBackPaymentModule"))
			{
				$payment_module->RedirectToGateway(	
													$Order,
													array_merge($client_row, $_POST)
												   );
			}
			else
			{
				$res = $payment_module->ProcessPayment(	
														$Order,
														array_merge($client_row, $_POST)
												    );
				if ($res)
				    $payment_module->NotifyObservers(PAYMENT_STATUS::SUCCESS);
				else
				    $payment_module->NotifyObservers(PAYMENT_STATUS::FAILURE);
			}
			exit();
		}
		else
			$err = $chk;
			
		if (count($err) != 0)
			$step = "checkout";	
	}
?>