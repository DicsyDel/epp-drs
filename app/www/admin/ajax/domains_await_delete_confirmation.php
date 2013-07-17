<?php
	$enable_json = true;
	require("../src/prepend.inc.php");
	
	$sql = "SELECT * FROM domains WHERE delete_status = '".DOMAIN_DELETE_STATUS::AWAIT."' 
			ORDER BY end_date DESC";
	$response["total"] = $db->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));
	
	$response["data"] = array();
	foreach ($db->GetAll($sql) as $row)
	{
		$row["userlogin"] = $db->GetOne("SELECT login FROM users WHERE id=?", array($row['userid']));

		$responseRow = array();
		foreach (array("id", "userid", "userlogin", "status") as $col)
		{
			$responseRow[$col] = $row[$col];
		}
		$responseRow["name"] = $row["name"].".".$row["TLD"];
		$responseRow["date_create"] = ($t1 = strtotime($row["start_date"])) ? date("M j, Y H:i:s", $t1) : null;
		$responseRow["date_expire"] = ($t2 = strtotime($row["end_date"])) ? date("M j, Y H:i:s", $t2) : null;
		$responseRow["overdue"] = time() > strtotime($row["end_date"]);		
		
		
		$response["data"][] = $responseRow;
	} 
	
	print json_encode($response);