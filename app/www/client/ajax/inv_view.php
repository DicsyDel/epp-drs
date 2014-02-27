<?php
	$enable_json = true;
	include("../src/prepend.inc.php");
	
	if (isset($req_purposes))
	{
		$response["data"] = $db->GetAll("SELECT `key` AS `value`, name AS `text` FROM invoice_purposes");
		foreach ($response["data"] as &$row)
			$row["text"] = _($row["text"]);		
		print json_encode($response);
		die(); 
	}
	
	
	
	$sql = "SELECT * from invoices WHERE id!='' AND userid='{$_SESSION['userid']}' AND hidden=0";
	
	if (isset($req_status))
	{
		$sql .= " AND status='".intval($req_status)."'";
	}
			
	//
	// Show invoices for specified domain name
	//
	if (isset($req_domainid))
	{
		$domainid = (int)$req_domainid;
		$purposes = implode(',', array(
			$db->qstr(INVOICE_PURPOSE::DOMAIN_CREATE), 
			$db->qstr(INVOICE_PURPOSE::DOMAIN_RENEW),
			$db->qstr(INVOICE_PURPOSE::DOMAIN_TRANSFER),
			$db->qstr(INVOICE_PURPOSE::DOMAIN_TRADE)
		));
		$sql .= " AND itemid='{$domainid}' AND purpose IN ({$purposes})";
	}
		
	
	if ($req_filter) 
	{
		switch($req_filter)
		{
			case "Q":
				switch($req_quick_date)
				{
					case "today":
						$filter_sql = "TO_DAYS(dtcreated) = TO_DAYS(NOW())";
						break;
						
					case "yesterday":
						$filter_sql = "TO_DAYS(dtcreated) = TO_DAYS(NOW())-1";
						break;
						
					case "last7days":
						$filter_sql = "TO_DAYS(dtcreated) > TO_DAYS(NOW())-7";
						break;
						
					case "lastweek":
						if (date("D") != 'Mon')
						{
							$monday = date("Y-m-d 00:00:00", strtotime("last Monday"));
						}
						else
						{
							$monday = date("Y-m-d 00:00:00");
						}
						$filter_sql = "(TO_DAYS(dtcreated) >= TO_DAYS('{$monday}') AND TO_DAYS(dtcreated) <= DATE_ADD('{$monday}', INTERVAL 6 DAY))";
						break;
						
					case "lastbusinessweek":
						if (date("D") != 'Mon')
							$monday = date("Y-m-d 00:00:00", strtotime("last Monday"));
						else
							$monday = date("Y-m-d 00:00:00");
						$filter_sql = "(TO_DAYS(dtcreated) >= TO_DAYS('{$monday}') AND TO_DAYS(dtcreated) <= DATE_ADD('{$monday}', INTERVAL 4 DAY))";
						break;
						
					case "lastmonth":
						$first_day = date("Y-m-d 00:00:00", mktime(0,0,0, date("m")-1, 1, date("Y")));
						$last_day = date("Y-m-d 00:00:00", mktime(0,0,0, date("m")-1, date("t", strtotime($first_day)), date("Y")));
						$filter_sql = "(TO_DAYS(dtcreated) >= TO_DAYS('{$first_day}') AND TO_DAYS(dtcreated) <= TO_DAYS('{$last_day}'))";
						
						break;
					case "thismonth":
						$first_day = date("Y-m-d 00:00:00", mktime(0,0,0, date("m"), 1, date("Y")));
						$filter_sql = "(TO_DAYS(dtcreated) >= TO_DAYS('{$first_day}'))";
						break;
				}
				break;
			
			case "E":
				$date_from = $req_dt;
				$date_to = $req_dt2;
				$filter_sql = "(TO_DAYS(dtcreated)>=TO_DAYS('{$date_from}') AND TO_DAYS(dtcreated)<=TO_DAYS('{$date_to}'))";
				break;
				
			case "P":
				$filter_sql = "purpose = '".mysql_escape_string($req_purpose)."'";
				break;
				
			case "O": 
				$filter_sql = "orderid = '".(int)$req_order_id."'";
				break;
		}
		
		if ($filter_sql)
			$sql .= " AND ($filter_sql)";
	}
	
	$sort = array
	(
		"dtcreated" => "UNIX_TIMESTAMP(dtcreated)",
		"custom_id" => "customid"
	);
	
	$sort = $req_sort ? key_exists($req_sort, $sort) ? $sort[$req_sort] : mysql_escape_string($req_sort) : $sort["dtcreated"];
	$dir = $req_dir ? mysql_escape_string($req_dir) : "DESC";
	$sql .= " ORDER BY $sort $dir";
		
	$response["total"] = $db->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));	
	
	$start = $req_start ? (int) $req_start : 0;
	$limit = $req_limit ? (int) $req_limit : 100;
	$sql .= " LIMIT $start, $limit";
	
	$response["data"] = array();
	foreach ($db->GetAll($sql) as $row)
	{
		$total = "{$display["Currency"]} ".number_format($row["total"], 2);
		if ((int)$row["vat"] > 0)
		{
			$total .= " ".sprintf(_("(Incl. VAT %.2f%%)"), $row["vat"]);
		} 
		
		$response["data"][] = array
		(
			"id" => $row["id"],
			"order_id" => $row["orderid"],
			"custom_id" => $row["customid"],
			"description" => $row["description"],
			"total" => $total,
			"vat" => (float)$row["vat"],
			"dtcreated" => ($t = strtotime($row["dtcreated"])) ? date("M j, Y H:i:s", $t) : null,
			"status" => $row["status"],
			"gate" => $row["status"] == INVOICE_STATUS::PAID ? ucfirst($row["payment_module"]) : ""
		);
	}
	
	print json_encode($response);
?>
