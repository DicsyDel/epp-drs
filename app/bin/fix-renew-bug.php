<?php

	require_once(dirname(__FILE__) . "/../src/prepend.inc.php");
	
	$db = Core::GetDBInstance();
	$RegFactory = RegistryModuleFactory::GetInstance();
	$DbDomain = DBDomain::GetInstance();
	
	$report_data = array();
	$rows = $db->GetAll("select d.id, d.name, d.TLD, d.start_date, d.end_date, d.status, i.status
			from domains as d 
			left join invoices i on (d.id = i.itemid and i.purpose = 'Domain_Renew')
			where d.end_date < NOW() and d.status = 'Delegated'	
			and (i.status = 0 or i.status is null) 
			order by d.id");
	foreach ($rows as $row)
	{
		try 
		{
			$Domain = $DbDomain->Load($row["id"]);
			$Registry = $RegFactory->GetRegistryByExtension($row["TLD"]);
			

			if ((int)$Registry->GetManifest()->GetRegistryOptions()->ability->auto_renewal)
			{
				$base_date = null;
				if ("DotNL" == $Registry->GetModuleName())
				{
					$invoice = $db->GetRow("SELECT * FROM invoices WHERE itemid = ? AND (purpose = ? OR purpose = ?)",
							array($Domain->ID, INVOICE_PURPOSE::DOMAIN_CREATE, INVOICE_PURPOSE::DOMAIN_TRANSFER));
					if ($invoice)
					{
						$base_date = strtotime("+1 year", strtotime($invoice["dtupdated"]));
					}
				}
				if (!$base_date)
				{
					$base_date = $Domain->ExpireDate;
				}
				
				$period = ceil((time() - $base_date)/(365*24*60*60));
				$expire_date = strtotime("+{$period} year", $base_date);
				
				//print "{$Domain->GetHostName()}: ".date("Y-m-d", $expire_date) . "\n";
				$db->Execute("UPDATE domains SET end_date = ? WHERE id = ?", 
						array(date("Y-m-d H:i:s", $expire_date), $row["id"]));
				$report_data[$Domain->GetHostName()] = sprintf("Auto-renewed for %d year(s)", $period);
			}
			else
			{
				$Info = $Registry->GetModule()->GetRemoteDomain($Domain);
				if ($Info->ExpireDate > $Domain->ExpireDate)
				{
					//print "{$Domain->GetHostName()}: ".date("Y-m-d", $Info->ExpireDate) . "\n";					
					$db->Execute("UPDATE domains SET end_date = ? WHERE id = ?", 
							array(date("Y-m-d H:i:s", $Info->ExpireDate), $row["id"]));
					$report_data[$Domain->GetHostName()] = sprintf("Was renewed outside the EPP-DRS up to %s", 
							date("Y-m-d", $Info->ExpireDate));
				}
			}
		}
		catch (Exception $e)
		{
			$report_data["{$row["name"]}.{$row["TLD"]}"] = "Fix cannot be applied. {$e->getMessage()}";
		}
	}
	
	
	ob_start();
	?>
[subject]EPP-DRS critical update[/subject]

There is a bug recently discovered in EPP-DRS related to domain renew operation. 
Some of the domains are renewed without invoicing their owners.

Below is the list of domains affected: <? foreach ($report_data as $domain => $message):?> 
<?=$domain?>	<?=$message?>
<? endforeach ?>


You may wish to create invoices for un-paid domains using AdminCP -> Invoices -> Create new.

We apologize for any inconveniences this may have caused to you.	
EPP-DRS staff
	<?php
	$mail_body = ob_get_clean();
	
	$filename = tempnam(sys_get_temp_dir(), "epp");
	file_put_contents($filename, $mail_body);
	
	$tpldir = $Mailer->Smarty->template_dir;
	$Mailer->SetSmartyTemplateDir(sys_get_temp_dir());
	$Mailer->Send(basename($filename), array(), CONFIG::$EMAIL_ADMIN, CONFIG::$EMAIL_ADMINNAME);
	$Mailer->SetSmartyTemplateDir($tpldir);	
	
	unlink($filename);
	
	