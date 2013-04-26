<?php
	require("src/prepend.inc.php");

	if (CONFIG::$DEV_DEMOMODE == 1)
	{
		$errmsg = _("Log viewer is disabled in demo mode.");
		CoreUtils::Redirect("index.php");
	}
	
	$row = $db->GetRow("SELECT * FROM api_log WHERE id = ?", array($req_id));
	if (!$row)
	{
		$err = "Transaction #ID='$req_id' not found";
		CoreUtils::Redirect("api_logs_view.php");
	}
	
	$row["response"] = htmlspecialchars($row["response"]);
	$row["user"] = $row["user_id"] != -1 ? 
				$db->GetOne("SELECT CONCAT(login, ' (', email, ')') FROM users WHERE id = ?", array($row["user_id"])) : 
				"Admin";
	
	$display["title"] = _("API log&nbsp;&raquo;&nbsp;View &raquo; Transaction {$row["transaction_id"]}");
	$display["row"] = $row;
	
	require("src/append.inc.php");		
?>