<?
	require_once('src/prepend.inc.php');

	$display["title"] = "Update report";
	
	if ($get_id)
		$updateinfo = $db->GetRow("SELECT * FROM updatelog WHERE id=?", array($get_id));
	
	if (!$updateinfo)
		CoreUtils::Redirect("index.php");
		
	$display["report"] = $updateinfo["report"];
	$display["transid"] = $updateinfo["transactionid"];
	
	require_once ("src/append.inc.php");
?>