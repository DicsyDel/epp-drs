<?php
	require_once 'src/prepend.inc.php';
	
	if (!$req_userid)
		throw new ApplicationException("Parameter 'userid' must be specified");
	
	$DBBalance = DBBalance::GetInstance();
	$Balance = $DBBalance->LoadClientBalance($req_userid);
	
	$paging = new SQLPaging();
	$paging->URLFormat = "?userid=".(int)$req_userid."&pn=%d&pt=%d";
	$paging->SetSQLQuery("SELECT * FROM balance_history WHERE balanceid = '{$Balance->ID}' ORDER BY operation_date DESC");
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	//$display["filter"] = $paging->GetFilterHTML("admin/inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("admin/inc/paging.tpl");
	$display["users"] = (array)$db->GetAll("SELECT * FROM users");
	$display["attr"] = array_merge($_GET, $_POST);
	$display["rows"] = $DBBalance->LoadBalanceHistory($paging->SQL);	
	foreach ($display["rows"] as &$row)
	{
		if ($row->InvoiceID)
		{
			$row->InvoiceDescription = $db->GetOne("SELECT description from invoices WHERE id = ?", array($row->InvoiceID));
		}
	}	
	
	
	$display["title"] = _("Balance &nbsp;&raquo;&nbsp; History");
	
	require_once 'src/append.inc.php';
?>