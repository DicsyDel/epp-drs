<?php

	if ($_SESSION['userid'])
	{
		$userinfo = $db->GetRow("SELECT vat, country FROM users WHERE id=?", array($_SESSION['userid']));
		
		if ($userinfo["vat"] > 0)
			$VAT = (float)$userinfo["vat"];
		else
			$VAT = (float)$db->GetOne("SELECT vat FROM countries WHERE code=?", array($userinfo["country"]));
	}
	elseif ($_SESSION['wizard']['newclient']["country"])
		$VAT = (float)$db->GetOne("SELECT vat FROM countries WHERE code=?", array($_SESSION['wizard']['newclient']["country"]));
	else
		$VAT = 0;
	
	$display["operation"] = $_SESSION['wizard']['whois']["operation"];
	
	foreach ($_SESSION['wizard']["cart_confirm"]['register'] as $domain)
	{
		preg_match("/^([^\.]+)\.(.*)?$/", $domain, $matches);
		$TLD = $matches[2];					
		$db_op = ($display["operation"] == "Register") ? INVOICE_PURPOSE::DOMAIN_CREATE : INVOICE_PURPOSE::DOMAIN_TRANSFER;
		
		if ($db_op == INVOICE_PURPOSE::DOMAIN_CREATE)
			$prices[$domain] = $db->GetOne("SELECT cost FROM prices WHERE TLD='{$TLD}' AND purpose='{$db_op}' AND period='{$_SESSION['wizard']['cart_confirm']['period'][$domain]}'");
		else 
			$prices[$domain] = $db->GetOne("SELECT cost FROM prices WHERE TLD='{$TLD}' AND purpose='{$db_op}'");
		
		if ($_SESSION['userid'])
		{
			$packageid = $db->GetOne("SELECT packageid FROM users WHERE id='{$_SESSION['userid']}'");
			
			if ($packageid)
			{
				$discounts[$domain] = $db->GetOne("SELECT discount FROM discounts WHERE TLD='{$TLD}' AND purpose='{$db_op}' AND packageid='{$packageid}'");
				$domaindiscount = round($prices[$domain]/100*$discounts[$domain], 2);
			}
		}
		
		$finalprices[$domain] = round(($domaindiscount) ? $prices[$domain]-$domaindiscount : $prices[$domain], 2);
		
		$grandtotal += $finalprices[$domain];
		$total += $prices[$domain];
		$total_discount += $domaindiscount;
	}
	
	$display["total"] = $total;
	
	if ($VAT > 0)
	{
		$display["VAT"] = round($VAT, 2);
		$display["vat_sum"] = round($grandtotal/100*$VAT, 2);
	}
	else
		$display["vat_sum"] = 0;
		
	$display["grandtotal"] = $grandtotal+$display["vat_sum"];
	$display["total_discount"] = $total_discount;
	$display["prices"] = $prices;
	$display["discounts"] = $discounts;
	$display["finalprices"] = $finalprices;

?>
