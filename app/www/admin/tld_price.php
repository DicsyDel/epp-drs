<?php 
	require_once('src/prepend.inc.php');
		
    $tldinfo = $db->GetRow("SELECT * FROM tlds WHERE TLD=?", array($req_TLD));
    if (!$tldinfo)
    	CoreUtils::Redirect("tld_view.php");
    
   	$display["title"] = sprintf(_("Prices and discounts for %s"), $req_TLD);
    	
   	try
   	{
    	$Registry = $RegistryModuleFactory->GetRegistryByExtension($req_TLD, false);
   	}
   	catch(Exception $e)
   	{
   		$errmsg = $e->getMessage();
   	}
    
   	if (!$Registry)
   		CoreUtils::Redirect("index.php");
   		
    $periods = array();
    
    $section_config = $Registry->GetManifest()->GetSectionConfig();
	for ($i = (int)$section_config->domain->registration->min_period; 
		 $i<=(int)$section_config->domain->registration->max_period;
		 $i++
		)
			$periods[] = $i;
    
	$display["trade_enabled"] = ($Registry->GetManifest()->GetRegistryOptions()->ability->trade == 1);
	$display["preregistration_enabled"] = ($Registry->GetManifest()->GetRegistryOptions()->ability->preregistration == 1);
			
	if ($_POST) 
	{
		foreach ($post_register as $period => $price)
			$db->Execute("REPLACE INTO prices SET purpose=?, cost=?, TLD=?, period=?", 
				array(INVOICE_PURPOSE::DOMAIN_CREATE, $price, $req_TLD, $period));
		
		foreach ($post_renew as $period => $price)
			$db->Execute("REPLACE INTO prices SET purpose=?, cost=?, TLD=?, period=?", 
				array(INVOICE_PURPOSE::DOMAIN_RENEW, $price, $req_TLD, $period));
							
		$db->Execute("REPLACE INTO prices SET purpose=?, cost=?, TLD=?", 
			array(INVOICE_PURPOSE::DOMAIN_TRANSFER, $post_transfer, $req_TLD));
		
		if ($display["trade_enabled"])
		{
			$db->Execute("REPLACE INTO prices SET purpose=?, cost=?, TLD=?", 
				array(INVOICE_PURPOSE::DOMAIN_TRADE, $post_trade, $req_TLD));
		}
		
		if ($display["preregistration_enabled"])
		{
			foreach ($post_preregister as $period => $price)
				$db->Execute("REPLACE INTO prices SET purpose=?, cost=?, TLD=?, period=?", 
					array(INVOICE_PURPOSE::PREREGISTRATION_DROPCATCHING, $price, $req_TLD, $period));
		}
		
		foreach ((array)$post_discounts as $packageid => $discs)
		{
			$db->Execute("REPLACE INTO discounts SET purpose=?, discount=?, TLD=?, packageid=?", 
				array(INVOICE_PURPOSE::DOMAIN_CREATE, $discs['register'], $req_TLD, $packageid));
				
			$db->Execute("REPLACE INTO discounts SET purpose=?, discount=?, TLD=?, packageid=?", 
				array(INVOICE_PURPOSE::DOMAIN_RENEW, $discs['renew'], $req_TLD, $packageid));
				
			$db->Execute("REPLACE INTO discounts SET purpose=?, discount=?, TLD=?, packageid=?", 
				array(INVOICE_PURPOSE::DOMAIN_TRANSFER, $discs['transfer'], $req_TLD, $packageid));
				
			if ($display["preregistration_enabled"])
			{
				$db->Execute("REPLACE INTO discounts SET purpose=?, discount=?, TLD=?, packageid=?", 
					array(INVOICE_PURPOSE::PREREGISTRATION_DROPCATCHING, $discs['preregister'], $req_TLD, $packageid));
			}
				
			if ($display["trade_enabled"])
			{
				$db->Execute("REPLACE INTO discounts SET purpose=?, discount=?, TLD=?, packageid=?", 
					array(INVOICE_PURPOSE::DOMAIN_TRADE, $discs['trade'], $req_TLD, $packageid));
			}
		}
		
		$okmsg = sprintf(_("Prices & Discounts for '%s' successfully updated."), $req_TLD);
		CoreUtils::Redirect("tld_view.php?pn={$req_pn}&pt={$req_pt}&pf={$req_pf}");
	}
		
	$display["price_transfer"] = $db->GetOne("SELECT cost FROM prices WHERE TLD=? AND purpose=?", 
												array($req_TLD, INVOICE_PURPOSE::DOMAIN_TRANSFER));

	if ($display["trade_enabled"])
	{
		$display["price_trade"] = $db->GetOne("SELECT cost FROM prices WHERE TLD=? AND purpose=?", 
												array($req_TLD, INVOICE_PURPOSE::DOMAIN_TRADE));
	}
												
	$display["price_register"] = array();
	$display["price_renew"] = array();
	$display["price_preregister"] = array();
	foreach ($periods as $period)
	{		
		if ($period > 0)
		{
			$display["price_register"][$period] = $db->GetOne("SELECT cost FROM prices 
																WHERE TLD=? AND purpose=? AND period=?", 
																array($req_TLD, INVOICE_PURPOSE::DOMAIN_CREATE, $period));
																
			$display["price_renew"][$period] = $db->GetOne("SELECT cost FROM prices 
																WHERE TLD=? AND purpose=? AND period=?", 
																array($req_TLD, INVOICE_PURPOSE::DOMAIN_RENEW, $period));
			
			if ($display["preregistration_enabled"])
			{											
				$display["price_preregister"][$period] = $db->GetOne("SELECT cost FROM prices 
																WHERE TLD=? AND purpose=? AND period=?", 
																array($req_TLD, INVOICE_PURPOSE::PREREGISTRATION_DROPCATCHING, $period));
			}
		}
	}
	$display["periods"]	= $periods;
		
	$display["TLD"] = $req_TLD;
	
	$display["discount_packages"] = $db->GetAll("SELECT * FROM packages ORDER BY name ASC");
	foreach ($display["discount_packages"] as &$pkg)
	{
		$pkg["register"] = $db->GetOne("SELECT discount FROM discounts WHERE purpose=? AND TLD=? AND packageid=?", array(INVOICE_PURPOSE::DOMAIN_CREATE, $req_TLD, $pkg["id"]));
		$pkg["renew"] = $db->GetOne("SELECT discount FROM discounts WHERE purpose=? AND TLD=? AND packageid=?", array(INVOICE_PURPOSE::DOMAIN_RENEW, $req_TLD, $pkg["id"]));
		$pkg["transfer"] = $db->GetOne("SELECT discount FROM discounts WHERE purpose=? AND TLD=? AND packageid=?", array(INVOICE_PURPOSE::DOMAIN_TRANSFER, $req_TLD, $pkg["id"]));
		
		if ($display["preregistration_enabled"])
		{
			$pkg["preregister"] = $db->GetOne("SELECT discount FROM discounts WHERE purpose=? AND TLD=? AND packageid=?", array(INVOICE_PURPOSE::PREREGISTRATION_DROPCATCHING, $req_TLD, $pkg["id"]));
		}
		
		if ($display["trade_enabled"])
		{
			$pkg["trade"] = $db->GetOne("SELECT discount FROM discounts WHERE purpose=? AND TLD=? AND packageid=?", array(INVOICE_PURPOSE::DOMAIN_TRADE, $req_TLD, $pkg["id"]));
		}
	}
	
	$display["pn"] = $req_pn;
	$display["pt"] = $req_pt;
	$display["pf"] = $req_pf;
	
	$display["help"] = "If the price is set to 0, any invoice for that extension will be paid automatically, imediately after creation.";
	
	require_once('src/append.inc.php');
?>