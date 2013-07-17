<?
	require_once('src/prepend.inc.php'); 
	
	$sql = "SELECT * from domains WHERE userid='{$_SESSION['userid']}'";
    
	$paging = new SQLPaging();

	switch($req_op)
	{
		case INCOMPLETE_OPERATION::DOMAIN_CREATE:
			
			$sql .= " AND incomplete_operation='".INCOMPLETE_OPERATION::DOMAIN_CREATE."' AND status = '".DOMAIN_STATUS::PENDING."'";
			$paging->AddURLFilter("op", INCOMPLETE_OPERATION::DOMAIN_CREATE);
			 
			break;
			
		case INCOMPLETE_OPERATION::DOMAIN_TRADE:
			
			$sql .= " AND incomplete_operation='".INCOMPLETE_OPERATION::DOMAIN_TRADE."' AND status = '".DOMAIN_STATUS::DELEGATED."'";
			$paging->AddURLFilter("op", INCOMPLETE_OPERATION::DOMAIN_TRADE);
			break;
			
		case INCOMPLETE_OPERATION::DOMAIN_TRANSFER:
			
			$sql .= " AND incomplete_operation='".INCOMPLETE_OPERATION::DOMAIN_TRANSFER."' AND status = '".DOMAIN_STATUS::PENDING."'";
			$paging->AddURLFilter("op", INCOMPLETE_OPERATION::DOMAIN_TRANSFER);
			break;
			
		case INCOMPLETE_OPERATION::DOMAIN_OUTGOING_TRANSFER:
			$sql .= " AND outgoing_transfer_status='".OUTGOING_TRANSFER_STATUS::REQUESTED."' AND status = '".DOMAIN_STATUS::DELEGATED."'";
			$paging->AddURLFilter("op", INCOMPLETE_OPERATION::DOMAIN_OUTGOING_TRANSFER);
			break;
			
		default:
			
			$sql .= " AND (incomplete_operation IS NOT NULL AND incomplete_operation != '')";
			
			break;
	}
	
	if ($get_status)
	{
		$get_status = preg_replace("/[^A-Za-z]+/", "", $get_status);
		$sql .= " AND status='{$get_status}'";
		$paging->AddURLFilter("status", $get_status);
	}

	
	$paging->SetSQLQuery($sql);
	
	$paging->AdditionalSQL = "ORDER BY name ASC";
	$paging->ApplyFilter($_POST["filter_q"], array("name"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("client/inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("client/inc/paging.tpl");

	$purposes = implode(',', array($db->qstr(INVOICE_PURPOSE::DOMAIN_CREATE), 
				$db->qstr(INVOICE_PURPOSE::DOMAIN_RENEW),
				$db->qstr(INVOICE_PURPOSE::DOMAIN_TRANSFER),
				$db->qstr(INVOICE_PURPOSE::DOMAIN_TRADE)
			));
	
	$display["rows"] = $db->GetAll($paging->SQL);
    foreach ($display["rows"] as &$row)
    {
        $row["operation"] = $row["incomplete_operation"];
        $row["ordered"] = $db->GetOne("SELECT dtcreated FROM invoices WHERE itemid=? AND purpose IN({$purposes})", array($row['id']));
    }
	
	$display["title"] = _("Incomplete orders");
	
	require_once ("src/append.inc.php");
?>
