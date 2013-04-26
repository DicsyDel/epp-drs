<?php
	$enable_json = true;	
	require("../src/prepend.inc.php");

	$sql = "SELECT id, login, email, dtregistered, packageid, status from users WHERE id>0";
	
	// If specified user id
	if ($req_userid)
	{
		$sql .= " AND id='".(int)$req_userid."'";
	}
	
    if ($req_packageid)
    {
		$sql .= " AND packageid='".(int)$req_packageid."'";
	}
	
	if ($req_id)
	{
		$sql .= " AND id='".(int)$req_id."'";
	}
	
	// If we need to get users with specified status
	if (isset($req_status))
	{
		$sql .= " AND status='{$req_status}'";
	}
	
	// Filter
	if ($req_query)
	{
		$filter = mysql_escape_string($req_query);
		foreach(array("login", "email") as $field)
		{
			$likes[] = "$field LIKE '%{$filter}%'";
		}
		$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND ";
		$sql .= join(" OR ", $likes);
	}	

	$sort_names = array
	(
		"date_reg" => "dtregistered",
	);	
	$sort = $req_sort ? key_exists($req_sort, $sort_names) ? $sort_names[$req_sort] : mysql_escape_string($req_sort) : $sort_names["login"];
	$dir = $req_dir ? mysql_escape_string($req_dir) : "ASC";
	$sql .= " ORDER BY $sort $dir";

	// Total rows
	$response["total"] = $db->GetOne(preg_replace('/SELECT [^F]+FROM/is', 'SELECT COUNT(*) FROM', $sql, 1));

	$start = $req_start ? (int) $req_start : 0;
	$limit = $req_limit ? (int) $req_limit : 20;
	$sql .= " LIMIT $start, $limit";

	// Rows
	$response["data"] = array();
	$DbBalance = DBBalance::GetInstance();
	foreach ($db->GetAll($sql) as $row)
	{
		$row["num_domains"]  = $db->GetOne("SELECT COUNT(*) FROM domains WHERE userid='{$row['id']}'");
		$row["num_contacts"] = $db->GetOne("SELECT COUNT(*) FROM contacts WHERE userid='{$row['id']}'");
		$row["num_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE userid='{$row['id']}' AND hidden=0");
		$row["isactive"] = $row["status"] == 1;
		unset($row["status"]);
		
		try 
		{
			$Balance = $DbBalance->LoadClientBalance($row["id"]);
			$row["balance"] = $Balance->Total;
		} 
		catch (Exception $ignore) 
		{
			$row["balance"] = 0;
		}
		
		$row["package"] = $row["packageid"] > 0 ? $db->GetOne("SELECT name FROM packages WHERE id='{$row['packageid']}'") : 'Default';
		$row["date_reg"] = $row["dtregistered"] ? date("M j, Y H:i:s", $row["dtregistered"]) : null;
//		$row["date_reg"] = null;
		
		
		// Add row to response
		$response["data"][] = $row;
	}

	print json_encode($response);
?>
