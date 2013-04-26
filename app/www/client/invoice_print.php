<?

	require_once('src/prepend.inc.php');

	if (!$get_id)
	{
		echo _("Cannot find invoice with this ID!");
		exit();
	}	
	
	try
	{
		$Order = Order::Load($req_id);
	}
	catch(Exception $e)
	{
		
	}
	
	if (!$Order || $Order->UserID != $_SESSION['userid'])
	{
		$errmsg = _("No payable invoices found");
		CoreUtils::Redirect("index.php");
	}
	
	$display["invoices"] = array();
	foreach ((array)$Order->GetInvoiceList() as $invoice)
		array_push($display["invoices"], $invoice);

	if (count($display["invoices"]) == 0)
	{
		$errmsg = _("No payable invoices found");
		CoreUtils::Redirect("index.php");
	}
		
		
	$display["total"] = $Order->GetTotal();
	$display["vat"] = $invoice->GetVATPercent();
	
	$display["orderid"] = $req_id;
	$display["client"] = $db->GetRow("SELECT * FROM users WHERE id=?", array($_SESSION['userid']));
	
	$extra = (array)$db->GetAll("SELECT f.name, v.value FROM client_fields as f 
		LEFT JOIN client_info AS v ON (f.id = v.fieldid) 
		where clientid = ?",
		array($_SESSION["userid"])
	);
	foreach ($extra as $row)
		$display["client_extra"][$row["name"]] = $row["value"];
		
	require_once ("src/append.inc.php");
?>
