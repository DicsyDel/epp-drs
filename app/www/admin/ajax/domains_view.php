<?php 
	$enable_json = true;
	require("../src/prepend.inc.php");


	if ($req_task == "deldb")
	{
		try
		{
			$DbDomain = DBDomain::GetInstance();
			$Domain = $DbDomain->Load($req_domainid);
			$DbDomain->Delete($Domain);
		}
		catch (Exception $e)
		{
			$response["error"] = "Cannot delete domain. {$e->getMessage()}";
		}
	}
		
	
	//$sql = "SELECT * from domains WHERE id>0 AND status != '".DOMAIN_STATUS::PENDING_DELETE."'";
	$sql = "SELECT * from domains WHERE id>0";
	
	// Show domains only for this user
	if ($req_userid)
	{
		$sql .= " AND userid='".(int)$req_userid."'";
	}
    
	if ($req_id)
	{
		$sql .= " AND id='".(int)$req_id."'";
	}
				
	// Show onluy expired soon domains
	if ($req_act == "expsoon")
	{
		$sql .= " AND (TO_DAYS(end_date)-TO_DAYS(NOW()) <= 90 AND TO_DAYS(end_date)-TO_DAYS(NOW()) >= 0)";		
	}
	
	// Show domains thats uses current Contact
	if ($req_clid)
	{
	    $clid = preg_replace("/[^A-Za-z0-9_\-\{\}]+/", "", $req_clid);
	    $sql .= " AND (`c_registrant` = '{$clid}' OR `c_admin` = '{$clid}' OR `c_tech` = '{$clid}' OR `c_billing` = '{$clid}')";
	}

	if ($req_query)
	{
		$filter = mysql_escape_string($req_query);
		foreach(array("name", "CONCAT(name,'.',TLD)") as $field)
		{
			$likes[] = "$field LIKE '%{$filter}%'";
		}
		$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND ";
		$sql .= join(" OR ", $likes);
	}

	$sort_names = array
	(
		"date_expire" => "end_date",
		"date_create" => "start_date",
		"userlogin" => "userid"
	);
	
	$sort = $req_sort ? key_exists($req_sort, $sort_names) ? $sort_names[$req_sort] : mysql_escape_string($req_sort) : $sort_names["name"];	
	$dir = $req_dir ? mysql_escape_string($req_dir) : "ASC";
	$sql .= " ORDER BY $sort $dir";

	// Total rows
	$response["total"] = $db->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));

	$start = $req_start ? (int) $req_start : 0;
	$limit = $req_limit ? (int) $req_limit : 20;
	$sql .= " LIMIT $start, $limit";

	
	// Rows
	$response["data"] = array();
	foreach ($db->GetAll($sql) as $row)
	{
		$row["userlogin"] = $db->GetOne("SELECT login FROM users WHERE id=?", array($row['userid']));
		
		// Count num invoices
		$purposes = implode(',', array($db->qstr(INVOICE_PURPOSE::DOMAIN_CREATE), 
			$db->qstr(INVOICE_PURPOSE::DOMAIN_RENEW),
			$db->qstr(INVOICE_PURPOSE::DOMAIN_TRANSFER),
			$db->qstr(INVOICE_PURPOSE::DOMAIN_TRADE)
		));
		$row["num_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE itemid=? AND purpose IN ({$purposes})", array($row['id']));
		
		// Count num contacts
		$row["num_contacts"] = 0;
		foreach (array("c_registrant", "c_admin", "c_billing", "c_tech") as $col)
		{
			if ($row[$col])
			{
				$row["num_contacts"]++;
			}
		}
		
		// Construct response row
		$responseRow = array();
		foreach (array("id", "userid", "userlogin", "num_invoices", "num_contacts", "status", "pw") as $col)
		{
			$responseRow[$col] = $row[$col];
		}
		$responseRow["name"] = $row["name"].".".$row["TLD"];
		$responseRow["date_create"] = ($t1 = strtotime($row["start_date"])) ? date("M j, Y H:i:s", $t1) : null;
		$responseRow["date_expire"] = ($t2 = strtotime($row["end_date"])) ? date("M j, Y H:i:s", $t2) : null;
		$responseRow["renew_disabled"] = (bool)$row["renew_disabled"];
		
		$response["data"][] = $responseRow;
	}
	
	print json_encode($response);
	
?>
