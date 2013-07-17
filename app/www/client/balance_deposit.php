<?php
	require_once 'src/prepend.inc.php';
	
	if ($_POST)
	{
		if (!$post_amount)
			$err[] = sprintf(_("%s is required"), "Amount");
		$post_amount = abs($post_amount);
		if ($post_amount < CONFIG::$MIN_DEPOSIT)
			$err[] = sprintf(_("Mimimum account refillment amount is %s%d"), CONFIG::$CURRENCY, CONFIG::$MIN_DEPOSIT);
			
		if (!$err)
		{
    		try
    		{			    			    
                $Invoice = new Invoice(INVOICE_PURPOSE::BALANCE_DEPOSIT,0,$_SESSION['userid']);
                $Invoice->Description = sprintf(_("Account refilling for %s%s"), CONFIG::$CURRENCY, $post_amount);
                $Invoice->Cancellable = 1;
                $Invoice->SetTotal($post_amount);
                $Invoice->Save();
                
    			if ($Invoice->Status == INVOICE_STATUS::PENDING)
				{
					CoreUtils::Redirect("checkout.php?invoices[]={$Invoice->ID}");
				}
				else
				{
					CoreUtils::Redirect("order_info.php?invoiceid={$Invoice->ID}");
				}
    		}
    		catch(Exception $e)
    		{
    			$err[] = sprintf(_("Cannot create invoice: %s"), $e->getMessage());
    		}
		}
	}
	
	$display["attr"] = array_merge($_GET, $_POST);
	$display["users"] = $db->GetAll("SELECT * FROM users");
	$display["currency"] = CONFIG::$CURRENCY;

	require_once 'src/append.inc.php';
?>