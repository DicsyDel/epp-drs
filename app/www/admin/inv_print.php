<?php

	require_once('src/prepend.inc.php');

	if (!$get_id)
	{
		echo _("Cannot find invoice with this ID!");
		exit();
	}	
		
	try
	{
		$Invoice = Invoice::Load($req_id);
		
		if ($Invoice->OrderID)
			$Order = Order::Load($Invoice->OrderID);
	}
	catch(Exception $e)
	{
		
	}
	
	if (!$Order && !$Invoice)
	{
		$errmsg = _("No payable invoices found");
		CoreUtils::Redirect("index.php");
	}
	
	$display["invoices"] = array();
	if ($Order)
	{
		foreach ($Order->GetInvoiceList() as $invoice)
			array_push($display["invoices"], $invoice);

		$display["vat"] = $invoice->GetVATPercent();
		$display["total"] = $Order->GetTotal();
	}
	else
	{
		// XXX: не должно быть инвойсов без ордера.
		array_push($display["invoices"], $Invoice);
		$display["total"] = $Invoice->GetTotal();
	}
	
	$display["orderid"] = $Order->ID;
	$display["client"] = $db->GetRow("SELECT * FROM users WHERE id=?", array($Invoice->UserID));
	$display["logo_exists"] = file_exists(CACHE_PATH."/logo.gif");
	
	$template_name = "client/invoice_print";
	require_once ("src/append.inc.php");
?>
