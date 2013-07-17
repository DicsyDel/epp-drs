<?php

	require_once('src/prepend.inc.php');

	
	$step = (int)$req_step;
	if (!$step)
		$step = 1;
	
	$DbDomain = DBDomain::GetInstance();
		
	if ($step == 2)
	{
		try
		{
			$Client = Client::Load($_SESSION["userid"]);
			$Order = new Order($Client->ID);
			
			$numok = 0;
			foreach ((array)$req_domains as $domain_id => $period)
			{
				try
				{
					$Domain = $DbDomain->Load($domain_id);
					if ($Domain->RenewInvoiceID)
					{
						try
						{
							$Registry = $RegistryModuleFactory->GetRegistryByExtension($Domain->Extension);
							$Registry->RenewDomain($Domain, array('period' => $Domain->Period));
							$Domain->RenewInvoiceID = null;
							DBDomain::GetInstance()->Save($Domain);
							$okmsg = sprintf(_("Domain %s renewed for %d year(s)"), $Domain->GetHostName(), $Domain->Period);
							$numok++;
						}
						catch (Exception $e)
						{
							$err[] = sprintf(_("Cannot renew %s. %s"), $Domain->GetHostName(), $e->getMessage());
						}
					}
					else
					{
						// Set renew period
						$Domain->Period = $period; 
						
						$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_RENEW, $Domain, $Client->ID);
						$Invoice->Description = sprintf(_("%s domain name renewal for %d year(s)"), 
								$Domain->GetHostName(), $period);
						$Invoice->Cancellable = 1;
						$Order->AddInvoice($Invoice);
						$DbDomain->Save($Domain);
					}
				}
				catch (Exception $e)
				{
					$err[] = $e->getMessage();
				}
			}
			
			//var_dump($err); die();
			if ($numok > 1)
			{
				$okmsg = sprintf(_("%d domains successfully renewed"), $numok);
			}
			
			if ($Order->GetInvoiceList())
			{
				$Order->Save();
				
				if ($Client->GetSettingValue(ClientSettings::AUTO_PAY_FROM_BALANCE))
					CoreUtils::Redirect("order_info.php?orderid={$Order->ID}");
				else
					CoreUtils::Redirect("checkout.php?orderid={$Order->ID}");
			}
			else
			{
				CoreUtils::Redirect("domains_view.php");
			}
			
		}
		catch (Exception $e)
		{
			$err[] = $e->getMessage();

			$step = 1;
			$req_id = array_keys((array)$req_domains);			
		}
	}
	if ($step == 1)
	{
		$rows = array();
		foreach ((array)$req_id as $domain_id)
		{
			$row = array
			(
				"id" => $domain_id,
				"name" => $db->GetOne("SELECT CONCAT(name, '.', TLD) FROM domains WHERE id = ?", 
						array($domain_id))
			);
			try
			{
				$Domain = $DbDomain->Load($domain_id);
				if ($Domain->Status == DOMAIN_STATUS::DELEGATED || $Domain->Status == DOMAIN_STATUS::EXPIRED)
				{
					$RenewConfig = $Domain->GetConfig()->renewal;
					
					$expDays = $db->GetRow("SELECT TO_DAYS(FROM_UNIXTIME('{$Domain->ExpireDate}')) as days");
					$nowDays = $db->GetRow("SELECT TO_DAYS(NOW()) as days");
	
					$renewMinDays = (int)$RenewConfig->min_days;
					$renewDays = $expDays["days"] - $nowDays["days"];
										
					if ($renewDays <= $renewMinDays)
					{
						// Check there is no peding renew invoice for this domain
						$check_inv = $db->GetRow("SELECT * FROM invoices 
								WHERE userid=? AND itemid=? AND purpose=? AND status=?", 
								array($Domain->UserID, $Domain->ID, 
								INVOICE_PURPOSE::DOMAIN_RENEW, INVOICE_STATUS::PENDING));
						if (!$check_inv)
						{
							$max_period = (int)$RenewConfig->max_period - floor(($Domain->ExpireDate - time())/(365*24*60*60));
							$min_period = (int)$RenewConfig->min_period;
							if ($max_period >= $min_period)
							{
								foreach (range($min_period, $max_period) as $period)
								{
									$row["periods"][$period] = $period > 1 ? sprintf(_("%s years"), $period) : _("1 year");
								}
							}
							else
							{
								$row["info"] = sprintf(_("Domain is already registered to the maximum period"));
							}
						}
						else
						{
							$row["info"] = _("Invoice for renewal of this domain name already exists. "
									. "Please pay existing invoice instead of creating a new one.");
						}
					}
					else
					{
						$row["info"] = sprintf(_("%d days before %s is not reached. "
								. "You will be able to renew this domain in a %s days"),
								$renewMinDays, date("Y-m-d", $Domain->ExpireDate), $renewDays - $renewMinDays);
					}
				}
				else
				{
					$row["error"] = _("Domain status prohibits operation");
				}
			}
			catch (Exception $e)
			{
				$row["error"] = $e->getMessage();  
			}
			
			$row["icon_cls"] = $row["error"] ? "ico-error" : ($row["info"] ? "ico-info" : "ico-ok");
			$row["show_message"] = $row["error"] || $row["info"];
			$row["message_cls"] = $row["error"] ? "error" : "info";
			$row["message"] = $row["error"] ? $row["error"] : $row["info"];
			
			$rows[] = $row;
		}
		
		if (!$rows)
			CoreUtils::Redirect("domains_view.php");
		
		$display["rows"] = $rows;
		$template_name = "client/bulk_renew_step1";
		$step = 2;
	}
	
	$display["title"] = _("My domains") . "&nbsp;&raquo;&nbsp;" . _("Bulk renew");
	$display["load_extjs"] = true;
	$display["step"] = $step;
	
	
	require_once('src/append.inc.php');
?>