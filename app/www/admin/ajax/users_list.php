<?php
	$enable_json = true;
	require_once('../src/prepend.inc.php');
	
	$rows = $db->GetAll("SELECT id, login, email FROM users ORDER BY login");
	$response = array("data" => array(array(
		"id" => "-1",
		"title" => "Admin"
	)));
	foreach ($rows as $row)
	{
		$response["data"][] = array
		(
			"id" => $row["id"],
			"title" => "{$row["login"]} ({$row["email"]})"  
		);
	}
	
	print json_encode($response);
	
?>