<?php

	require_once('src/prepend.inc.php');

	
	$step = (int)$req_step;
	if (!$step)
		$step = 1;
	
	$DbDomain = DBDomain::GetInstance();
	$RegFactory = RegistryModuleFactory::GetInstance();
		
	if ($step == 2)
	{
		$succeed = 0;
		foreach ((array)$req_domains as $domain_id => $period)
		{
			try
			{
				$Domain = $DbDomain->Load($domain_id);
				// Set renew period
				$Domain->Period = $period;

				$Registry = $RegFactory->GetRegistryByExtension($Domain->Extension);
				$Registry->RenewDomain($Domain, array('period' => $Domain->Period));
				
				
				$Domain->DeleteStatus = DOMAIN_DELETE_STATUS::NOT_SET;
				DBDomain::GetInstance()->Save($Domain);
				
				$succeed++;
			}
			catch (Exception $e)
			{
				$err[] = $e->getMessage();
			}
		}
		
		$msg = sprintf(_("%s domain(s) renewed"), $succeed);
		CoreUtils::Redirect("domains_view.php");
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
		$template_name = "admin/bulk_renew_step1";
		$step = 2;
	}
	
	$display["title"] = _("Domains") . "&nbsp;&raquo;&nbsp;" . _("Bulk renew");
	$display["load_extjs"] = true;
	$display["step"] = $step;
	
	
	require_once('src/append.inc.php');
?>