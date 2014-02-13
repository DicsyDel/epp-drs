<?php
	$__start = microtime(true);

	session_start();
	include ("./src/prepend.inc.php");
	
	//
	// If we go back
	//
	if ($post_direction == "back")
	{
		$isback = true;
		$step = $_POST["backstep"];
		$_POST = false;
	}
	else
	{
		$isback = false;
	}

	// Check session expiration
	if ($_SESSION["expired"])
	{
	    $_SESSION["expired"] = false;
	    // if session expire show error message
	    $display["errors"][] = _("Your session has been expired.<br>Due to security reasons, you must start ordering proccess over from the beginning.");
	}
	
	// Fisrt step
	if ((!$post_step || $post_step == "whois") && !$isback)
	{
		$step = "whois";
		$_SESSION = array();
	}
	elseif ($post_step && $_POST)
	{
        $step = $post_step;
        
        $steps = array("check_user", "select_user_type", "newclient", "placeorder", "process_payment");
        
        if (in_array($step, $steps))
        	require(dirname(__FILE__)."/order/{$step}.php");
	}
	
	$display["servicename"] = CONFIG::$COMPANY_NAME;
	$display["clienthost"] = sprintf("%s/client", CONFIG::$SITE_URL);	
	$display["allow_transfer"] = false;
	$newTLDs = array();
	
	//var_dump(count($TLDs));
	
	//
	// get TLDs
	//
	foreach ((array)$TLDs as $k=>$v)
	{
		try
		{
			$Registry = $RegistryModuleFactory->FlyRegistryByExtension($v);
		}
		catch(Exception $e)
		{
			throw new ApplicationException($e->getMessage(), $e->getCode());
		}
		
		array_push($newTLDs, 
					array(	"name"			=>$v, 
							"allowtransfer"	=>($Registry->GetManifest()->GetSectionConfig()->domain->transfer->allow == 1) ? 1 : 0
						)
				  );	
				  
		if ($Registry->GetManifest()->GetSectionConfig()->domain->transfer->allow == 1)
            $display["allow_transfer"] = true;
	}
	
	$display["errors"] = $err;
	$display["TLDs"] = $newTLDs;	
	
	// If not TLD configured
	if (count($display["TLDs"]) == 0)
	{
		UI::DisplayInstallationTip("No registry modules or no domain extensions enabled.<br> 1. Configure and enable at least one registry module.<br> 2. Configure pricing for at least one domain extension and enable it.");
	}
	
	if (!$db->GetOne("SELECT id FROM pmodules WHERE status='1'"))
	{
		UI::DisplayInstallationTip("No payment modules enabled. Configure and enable at least one payment module.");
	}
		
	// Assign global vars to templates
	$smarty->assign($display);
	
	switch ($step)
	{
		// First step WHOIS
		case "whois":
				
				if ($_SESSION['wizard']['whois'])
					$display["data"] = $_SESSION['wizard']['whois'];
					
				$display["backstep"] = false;
				$template_name = "index.tpl";
							
			break;

		// Second steep: confirm domain choose
		case "cart_confirm":
		
				$data = ($isback) ? $_SESSION['wizard']['whois'] : $_POST;

				$display["operation"] = $data["operation"];
				
				if (!$isback)
					$_SESSION['wizard']['whois'] = $_POST;
				
				// Select prices for all domains
				$display["total"] = 0;
				$domains = array();
				foreach ($data["domains"] as $k=>$domain)
				{
					if ($data["avail"][$k] == 1)
					{
						try
						{
							$Registry = $RegistryModuleFactory->FlyRegistryByExtension($data["TLDs"][$k]);
							$Manifest = $Registry->GetManifest();
						}
						catch(Exception $e)
						{
							throw new ApplicationException($e->getMessage(), $e->getCode());
						}			
						
						$db_op = ($data["operation"] == "Register") ? INVOICE_PURPOSE::DOMAIN_CREATE : INVOICE_PURPOSE::DOMAIN_TRANSFER;
						
						if ($data["operation"] == "Register")
						{
							$periods = array();
							for ($i = (int)$Manifest->GetSectionConfig()->domain->registration->min_period; 
								 $i<=(int)$Manifest->GetSectionConfig()->domain->registration->max_period;
								 $i++)
							{
								$price = $db->GetOne("SELECT cost FROM prices WHERE TLD=? AND purpose=? AND period=?", array($data["TLDs"][$k], $db_op, $i));
								$periods[] = array("period" => $i, "price" => $price);
							}
						}
						else 
						{
							$domain_price = $db->GetOne("SELECT cost FROM prices WHERE TLD=? AND purpose=?", array($data["TLDs"][$k], $db_op));
							$display["total"] += $domain_price;
						}
						
						$dmns = @array_values($_SESSION['wizard']['cart_confirm']["register"]);
						$checked = (@in_array("{$domain}.{$data["TLDs"][$k]}", $dmns) || !$dmns) ? true : false;
							
						$domains[] = array(	"name" 		=> $domain, 
											"TLD" 		=> $data["TLDs"][$k], 
											"periods"	=> $periods,
											"checked"	=> $checked,
											"dname"		=> "{$domain}.{$data["TLDs"][$k]}",
											"price"	    => $domain_price
										  );
					}
				}

				if (count($domains) > 0)
				{
					$display["domains"] = $domains;
					$display["data"] = $_SESSION['wizard']['cart_confirm'];
					$display["backstep"] = "whois";
					$template_name = "cart_confirm.tpl";
				}
				else
				{
					$display["backstep"] = false;
					$template_name = "index.tpl";
				}
						
			break;
		
		case "check_user":
				
				$data = ($isback) ? $_SESSION['wizard']['check_user'] : $_POST;
			
				if (!$isback && !$user_checked)
					$_SESSION['wizard']['cart_confirm'] = $_POST;
						
				$display["step"] = "select_user_type";
				$display["reg_type"] = ($data["reg_type"]) ? $data["reg_type"] : "newclient";
				
				$display["backstep"] = "cart_confirm";
				$template_name = "check_user.tpl";
			
			break;
			
		case "newclient":
				$data = ($isback) ? $_SESSION['wizard']['newclient'] : $_POST;
				
				if (!$isback && $_POST["step"] == "select_user_type")
					$_SESSION['wizard']['check_user'] = $_POST;
				
				$display = array_merge($display, $data);
				$display["contactinfo"] = $data["add"];
				
				foreach($db->GetAll("SELECT * FROM client_fields") as $v)
				{
                    $display["fields"][$v["title"]] = $v;
                    if ($display["fields"][$v["title"]]["type"] == "SELECT")
                    {
                    	$values = unserialize($v["elements"]);
                    	foreach($values[0] as $kk=>$vv)
                    		$display["fields"][$v["title"]]["values"][$vv] = $values[1][$kk];
                    }
                    
                    if (!$display["contactinfo"][$v["name"]])
                    	$display["contactinfo"][$v["name"]] = $v["defval"];
				}
				
				$display["countries"] = $db->GetAll("SELECT * FROM countries");
				$display['phone_widget'] = Phone::GetInstance()->GetWidget();
				$display["backstep"] = "check_user";
				$template_name = "newclient.tpl";
				
			break;
			
		case "placeorder":
						
				if ($_SESSION['wizard']['check_user']['reg_type'] == "newclient")
				{
					$display["backstep"] = "newclient";

					$data = ($isback) ? $_SESSION['wizard']['newclient'] : $_POST;
					
					if (!$isback)
						$_SESSION['wizard']['newclient'] = $_POST;
				}
				else
				{
					$display["backstep"] = "check_user";
					
					$data = ($isback) ? $_SESSION['wizard']['check_user'] : $_POST;
					
					if (!$isback)
						$_SESSION['wizard']['check_user'] = $_POST;
				}
					
								
				include('order/calculate_placeorder.php');				
				
				/**********************************/			
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
					if ($min_amount != false && $min_amount > $display["grandtotal"])
						$display["mdisabledreason"][$pmodule] = sprintf(_("%s payment method not available because total amount is less than allowed minimum (%s)"), $pmodule, number_format($min_amount, 2));
				}
				
				if ($_SESSION['userid'])
				{
					$Balance = DBBalance::GetInstance()->LoadClientBalance($_SESSION["userid"]);
					if ($Balance->Total < $display["grandtotal"])
					{
						$display["balance_disabled"] = true;
						$display["balance_disabled_reason"] = _("Not enought money for this payment");
					}
					if (CONFIG::$PREPAID_MODE)
						$smarty->assign("payment_modules", null);					
				}
				else
				{
					$display["hide_balance"] = true;
				}
				
				/**********************************/
				
				$template_name = "placeorder.tpl";
				
			break;
			
		case "checkout":
			
				if (!$isback && $_POST["step"] == "checkout")
					$_SESSION['wizard']['checkout'] = $_POST;
				
				$data = ($isback) ? $_SESSION['wizard']['placeorder'] : $_POST;
				
				if (!$_SESSION['userid'])
				{
					// We need for new user
					// create new user
					$password = $Crypto->Sault();
					
					$args = array("login"=>$_SESSION['wizard']['newclient']["login"], "password"=>$password, "client" => $_SESSION['wizard']['newclient']);
					
					
					$Client = new Client($_SESSION['wizard']['newclient']["login"], $Crypto->Hash($password), $_SESSION['wizard']['newclient']["email"]);
					$Client->Name = $_SESSION['wizard']['newclient']["name"];
					$Client->Organization = $_SESSION['wizard']['newclient']["org"];
					$Client->Business = $_SESSION['wizard']['newclient']["business"];
					$Client->Address = $_SESSION['wizard']['newclient']["address"];
					$Client->Address2 = $_SESSION['wizard']['newclient']["address2"];
					$Client->City = $_SESSION['wizard']['newclient']["city"];
					$Client->State = $_SESSION['wizard']['newclient']["state"];
					$Client->Country = $_SESSION['wizard']['newclient']["country"];
					$Client->ZipCode = $_SESSION['wizard']['newclient']["zipcode"];
					$Client->Phone = $_SESSION['wizard']['newclient']["phone"];
					$Client->Fax = $_SESSION['wizard']['newclient']["fax"];
					$Client->Status = CONFIG::$CLIENT_MANUAL_APPROVAL ? 0 : 1;
					
					$Client->SetSettingValue('inline_help', 1);
					try
					{
						foreach ((array)$_SESSION['wizard']['newclient']["add"] as $k=>$v)
							$Client->{$k} = $v;
					}
					catch(Exception $e){}
					
					try
					{
						$_SESSION["userid"] = $Client->Save()->ID;
					}
					catch(Exception $e)
					{
						throw new ApplicationException($e->getMessage(), $e->getCode());
					}
							
					Application::FireEvent('ClientCreated', $Client);
					
					if (CONFIG::$CLIENT_MANUAL_APPROVAL == 1)
					{
						$Client->SetSettingValue("pwd", $password);
						mailer_send("signup_pending.eml", $args, $_SESSION['wizard']['newclient']["email"], $_SESSION['wizard']['newclient']["name"]);
					}
					else
					{
						mailer_send("signup.eml", $args, $_SESSION['wizard']['newclient']["email"], $_SESSION['wizard']['newclient']["name"]);
						$Client->SetSettingValue("welcome_send", 1);
					}
					

				    if ($_SESSION['userid'] && $_SESSION['c_login'] && $_SESSION['c_password'])
				    {
					    try
						{
							$Client = Client::Load($_SESSION['userid']);
						}
						catch(Excepiton $e){}
						
						if ($_SESSION['c_password'] == $Client->Password)
						{
							$sault = $Crypto->Sault();
							$_SESSION["sault"] = $sault;
							$_SESSION["userid"] = $Client->ID;
							$_SESSION["login"] = $Client->Login;
							
							$_SESSION["hash"] = $Crypto->Hash("{$Client->Login}:{$Client->Password}:{$sault}");
						}
				    }
				}
					
				//
				//
				//	
				$Order = new Order($_SESSION["userid"]);
					
				if (!$_SESSION["orderid"])
				{			
					foreach ($_SESSION['wizard']["cart_confirm"]['register'] as $domain)
					{
						preg_match("/^([^\.]+)\.(.*)?$/", $domain, $matches);
						$dname = $matches[1];
						$TLD = $matches[2];
												
						if ($_SESSION['wizard']['whois']["operation"] == "Register")
						{
							$period = $_SESSION['wizard']['cart_confirm']['period'][$domain];
							$purpose = INVOICE_PURPOSE::DOMAIN_CREATE;
							$invoice_description = sprintf(_("%s.%s domain name registration for %s year(s)"), $dname, $TLD, $period);
							$order_op = INCOMPLETE_OPERATION::DOMAIN_CREATE;
						}
						else 
						{
							$purpose = INVOICE_PURPOSE::DOMAIN_TRANSFER;
							$period = "0";
							$invoice_description = sprintf(_("%s.%s domain name transfer"), $dname, $TLD);
							$order_op = INCOMPLETE_OPERATION::DOMAIN_TRANSFER;
						}
											
						$db->Execute("INSERT INTO 
											domains 
										SET 
											`userid`		=?, 
											`name`			=?, 
											`TLD`			=?,
											`c_registrant`	=?,
											`c_admin`		=?,
											`c_billing`		=?,
											`c_tech`		=?,
											`ns1`			=?,
											`ns2`			=?,
											`ns_n`			='',
											`status`		=?,
											`period`		=?,
											`comment`		=?,
											`incomplete_operation` = ?,
											`error_msg` 	=''
										", array($_SESSION["userid"], $dname, $TLD, "", "", "", "", "", "", DOMAIN_STATUS::AWAITING_PAYMENT, $period, "", $order_op)
									);
									
						$domainid = $db->Insert_ID();
							
						// Create new invoice
						$Invoice = new Invoice($purpose,$domainid,$_SESSION['userid']);
						$Invoice->Description = $invoice_description;
						
						// Add invoice to order
						$Order->AddInvoice($Invoice);
					}
					
					// Save order
					$_SESSION["orderid"] = $Order->Save()->ID;
				}
				else
				{
					// Load order from database
					$Order = Order::Load($_SESSION["orderid"]);
				}

				
				// Payment from balance
				if ($_SESSION['wizard']['checkout']["gate"] == "Balance")
				{
					try
					{
						foreach ($Order->GetInvoiceList() as $Invoice)
						{
							if ($Invoice->Purpose != INVOICE_PURPOSE::BALANCE_DEPOSIT 
								&& $Invoice->Status == INVOICE_STATUS::PENDING)
							{
								// Withdraw balance
								$Balance = DBBalance::GetInstance()->LoadClientBalance($_SESSION["userid"]);
								$Operation = $Balance->CreateOperation(BalanceOperationType::Withdraw, $Invoice->GetTotal());
								$Operation->InvoiceID = $Invoice->ID;
								$Balance->ApplyOperation($Operation);
								
								$Invoice->MarkAsPaid(null);
							}
						}
						CoreUtils::Redirect("pdt.php");
					}
					catch (Exception $e)
					{
						$err[] = $e->getMessage();
					}
				}
				
				// Payment from payment module
				else
				{
					try {
						$payment_module = $PaymentModuleFactory->GetModuleObjectByName($_SESSION['wizard']['checkout']["gate"]);
					} catch (Exception $e) {
						$err[] = "Cannot use selected payment method at this time. {$e->getMessage()}";
					}
					
					if ($payment_module) 
					{
						// If Total = 0 then mark all invoices in order as paid
						if ($Order->GetTotal() == 0)
						{
							// Invoice automaticly mark as paid after creation if Total == 0;
							//$Order->MarkAsPaid($payment_module);
							CoreUtils::Redirect("pdt.php");
						}
						else
						{
							$PaymentForm = $payment_module->GetPaymentForm();
							
						    if ($PaymentForm == false)
							{
								$reflect = new ReflectionObject($payment_module);
								if ($reflect->implementsInterface("IPostBackPaymentModule"))
								{
									$payment_module->RedirectToGateway(	
																		$Order,
																		$userinfo
																	   );
								}
								else
								{
									$res = $payment_module->ProcessPayment(	
																			$Order,
																			$userinfo
																	    );
									if ($res)
									    $payment_module->NotifyObservers(PAYMENT_STATUS::SUCCESS);
									else
									    $payment_module->NotifyObservers(PAYMENT_STATUS::FAILURE);
								}
							}
							else
							{	
								$fields = $PaymentForm->ListFields();
								$smarty_fields = array();
								foreach($fields as $field)
								{
									$smarty_fields[$field->Title] = array("name" => $field->Name, "required" => $field->IsRequired, "type" => $field->FieldType, "values" => $field->Options);
									if ($_REQUEST[$field->Name])
										$attr[$field->Title] = $_REQUEST[$field->Name];
								}
								
								
								$display["post"] = $attr;
								$display['phone_widget'] = Phone::GetInstance()->GetWidget();						
								$display["fields"] = $smarty_fields;
								$template_name = "paymentgate.tpl";
							}
						}
					}
					
				}
				
								
				$display["errors"] = $err;
				if (!$template_name)
				{
					$template_name = "placeorder.tpl";
					include('order/calculate_placeorder.php');
				}
			
			break;			
	}
	
	$_SESSION["JS_SESSIONID"] = $display["JS_SESSIONID"] = md5(mt_rand().microtime(true));
	
	// Render page
	$smarty->assign($display);
	$smarty->display($template_name);
	
	$__end = microtime(true);
	//var_dump($__end - $__start);
	
	exit();
?>
