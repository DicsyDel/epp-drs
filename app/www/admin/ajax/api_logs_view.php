<?php
	require("../src/prepend.inc.php");

	if (CONFIG::$DEV_DEMOMODE == 1)
	{
		$response = array(
			"success" => false, 
			"error" => _("Log viewer is disabled in demo mode"), 
			"data" => array()
		);
		die(json_encode($response));
	}
	
	$sql = "SELECT * FROM api_log";
	$where = array();
	$bind = array();
	
	if ($req_userid)
	{
		$where[] = "user_id = ?";
		$bind[] = $req_userid;
	}
	
	if ($req_added_date)
	{
		$where[] = "TO_DAYS(added_date) = TO_DAYS(?)";
		$bind[] = date("Y-m-d", strtotime($req_added_date));
	}
	
	if ($req_query)
	{
		$query = mysql_escape_string($req_query);
		$where[] = "(action LIKE '%$query%' OR request LIKE '%$query%' OR response LIKE '%$query%')";
	}
	
	if ($req_transaction_id)
	{
		$where[] = "transaction_id = ?";
		$bind[] = $req_transaction_id;
	}
		
	$sql .= $where ? " WHERE ".join(" AND ", $where) : "";
	$sql .= " ORDER BY added_date DESC";
	
	$sql_total = preg_replace('/SELECT[^F]+FROM/', 'SELECT COUNT(*) FROM', $sql);
	
	// Apply limits
	$start = $req_start ? (int) $req_start : 0;
	$limit = $req_limit ? (int) $req_limit : 100;
	$sql .= " LIMIT $start, $limit";

	$response = array();
	$response["total"] = $db->GetOne($sql_total, $bind);
	$response["data"] = array();
	
	foreach ($db->GetAll($sql, $bind) as $row)
	{
		$row["transaction_failed"] = strlen($row["error_trace"]) > 0;
		$row["user"] = $row["user_id"] != -1 ? 
				(string)$db->GetOne("SELECT CONCAT(login, ' (', email, ')') FROM users WHERE id = ?", array($row["user_id"])) : 
				"Admin";
		$row["response"] = htmlspecialchars($row["response"]);
		$response["data"][] = $row;
	}
	
	print json_encode($response);
?>
