<?php
    include ("src/prepend.inc.php");

	if (isset($req_domainid))
		require_once("src/set_managed_domain.php");
	else
		require_once("src/get_managed_domain_object.php");
		
		
	CoreUtils::Redirect("bulk_renew.php?id[]=".$Domain->ID);
	die();
	
	// All under this line is a legacy code !
	//
	//   ||	
	//   ||	
	//   ||
	//   ||
	//   ||
	//   ||
	//   \/
	//
	// BUA-A-A !!!
	
	
	if ($Domain->Status != DOMAIN_STATUS::DELEGATED)
	{
		$errmsg = _("Domain status prohibits operation");
		CoreUtils::Redirect("domains_view.php");
	}
		
	// Get User info
	$user = $db->GetRow("SELECT * FROM users WHERE id=?", array($Domain->UserID));
	
	
	if ($Domain->RenewInvoiceID)
   	{
   		try
		{
			if (!$Domain->Period)
    			$Domain->Period = (int)$Manifest->GetSectionConfig()->domain->renewal->min_period;
			
			$renew = $Registry->RenewDomain($Domain, array('period' => $Domain->Period));
		}
		catch(Exception $e)
		{
			$renew = false;
			$errmsg = _("Renew failed: ").$e->getMessage();
			CoreUtils::Redirect("domains_view.php");
		}
		
		if ($renew)
		{
			$okmsg = _("Domain name successfully renewed");
			$Domain->RenewInvoiceID = null;
			DBDomain::GetInstance()->Save($Domain);
			CoreUtils::Redirect("domains_view.php");
		}
   	}
	
	if ($_POST)
	{
	   	
		$check_inv = $db->GetRow("SELECT * 
									FROM invoices 
								WHERE userid=? AND 
									  itemid=? AND 
									  purpose=? AND status='0'
								", array($Domain->UserID, $Domain->ID, INVOICE_PURPOSE::DOMAIN_RENEW));
		if (!$check_inv)
		{		
			$period = (int)$post_period;
    		if ($period < $Manifest->GetSectionConfig()->domain->renewal->min_period || 
    			$period > $Manifest->GetSectionConfig()->domain->renewal->max_period)
    		{
				$period = (int)$Manifest->GetSectionConfig()->domain->renewal->min_period;
    		}
			
    		$Domain->Period = $period;
    		DBDomain::GetInstance()->Save($Domain);
			
    		try
    		{
    			$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_RENEW, $Domain->ID, $Domain->UserID);
    			$Invoice->Cancellable = 1;
    			$Invoice->Description = sprintf(_("%s domain name renewal for %s year(s)"), $Domain->GetHostName(), $Domain->Period);
    			$Invoice->Save();
    		}
    		catch(Exception $e)
    		{
    			$errmsg = $e->getMessage();
    		}
    		  
    		if ($Invoice->Status == INVOICE_STATUS::PENDING)
				CoreUtils::Redirect("checkout.php?invoices[]={$Invoice->ID}");
			else
			{
				// All details emailed to client in Invoice->MarkAsPaid method.
				CoreUtils::Redirect("order_info.php?invoiceid={$Invoice->ID}");
			}
		}
		else
			$errmsg = _("Invoice for renewal of this domain name already exists. Please pay existing invoice instead of creating a new one.");
	}


	$display["title"] = sprintf(_("<a href=\"domains_view.php?id=%s\">%s.%s</a> &nbsp;&raquo;&nbsp; Renew"), $_SESSION['selected_domain'], $_SESSION['domain'], $_SESSION['TLD']);

	//
	$display["check_inv"] = $db->GetRow("SELECT * FROM invoices 
										WHERE userid=? AND 
											  itemid=? AND 
											  purpose=? AND 
											  status='0'
									", array($Domain->UserID, $Domain->ID, INVOICE_PURPOSE::DOMAIN_RENEW));
									
	$expDays = $db->GetRow("SELECT TO_DAYS(FROM_UNIXTIME('{$Domain->ExpireDate}')) as days");
	$nowDays = $db->GetRow("SELECT TO_DAYS(NOW()) as days");
	
	$display["canrenew"] = ($expDays["days"]-$nowDays["days"] <= (int)$Manifest->GetSectionConfig()->domain->renewal->min_days) ? true : false;
	$display["step"] == $post_step;
	
	$display["minDays"] = (int)$Manifest->GetSectionConfig()->domain->renewal->min_days;
	$display["needDays"] = ($expDays["days"]-$nowDays["days"]-(int)$Manifest->GetSectionConfig()->domain->renewal->min_days);

	for($i = (int)$Manifest->GetSectionConfig()->domain->renewal->min_period; $i <= (int)$Manifest->GetSectionConfig()->domain->renewal->max_period; $i++)
		$display["periods"][] = $i;
		
	require_once ("src/append.inc.php");
?>
