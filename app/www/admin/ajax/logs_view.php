<? 
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
	
	Core::Load("Data/Formater");
		
	$sql_begin = "SELECT DISTINCT transactionid FROM syslog ";
	$where = array();

	if ($req_query)
	{
	    $query = mysql_escape_string($req_query);
	    $where[] = "(message LIKE '%{$query}%' OR transactionid LIKE '%{$query}%')";
	}
		
	if ($req_severity)
	{
		$stmts = array();
		foreach($req_severity as $severity)
		{
			$stmts[] = "severity = '".(int)$severity."'";
		}
		if ($stmts)
			$where[] = "(".implode(" OR ", $stmts).")";
	}
		
	if ($req_dt)
	{
		$date = strtotime($req_dt);
		$where[] = "TO_DAYS(dtadded) = TO_DAYS(FROM_UNIXTIME('{$date}'))";
	}
	
	$sql_where = $where ? "WHERE ".join(" AND ", $where) : "";	
	$sql_end = " ORDER BY dtadded_time DESC";

	// Build sql queries
	$sql = $sql_begin . $sql_where . $sql_end;
	$sql_total = "SELECT COUNT(DISTINCT transactionid) FROM syslog " . $sql_where;
	
	// Apply limits
	$start = $req_start ? (int) $req_start : 0;
	$limit = $req_limit ? (int) $req_limit : 100;
	$sql .= " LIMIT $start, $limit";
	

	// Execute SQL
	$response["total"] = $db->GetOne($sql_total);
	$response["data"] = array();

	foreach ($db->GetAll($sql) as $row)
	{
		$row = $db->GetRow(
			"SELECT * FROM syslog WHERE transactionid = ? ORDER BY id ASC LIMIT 1",
			array($row["transactionid"])
		);
		$errors = $db->GetOne("
			SELECT COUNT(*) FROM syslog 
			WHERE transactionid='{$row['transactionid']}' 
			AND (
				severity='".E_ERROR."' OR 
				severity='".E_CORE_ERROR."' OR 
				severity='".E_USER_ERROR."'
			)
		");
   	    $warns = $db->GetOne("
   	    	SELECT COUNT(*) FROM syslog 
   	    	WHERE transactionid='{$row['transactionid']}' 
			AND (
				severity='".E_WARNING."' OR
  				severity='".E_USER_WARNING."' OR
	    	    severity='".E_CORE_WARNING."'
			)
		");		
		$dtadded = Formater::FuzzyTimeString(strtotime($row["dtadded"]));
	    $firstEntry = htmlentities($row["message"], ENT_QUOTES, "UTF-8");
	
		$response["data"][] = array
		(
			"id" => $row["id"],
			"dtadded" => $dtadded,
			"warns" => $warns,
			"errors" => $errors,
			"firstEntry" => $firstEntry,
			"transactionid" => $row["transactionid"]
		);
	}
	
	print json_encode($response);

?>
