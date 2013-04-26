<?php require_once('src/prepend.inc.php'); ?>
<?
	$display["title"] = "Invoice &nbsp;&raquo;&nbsp; Details";

	$display["invoice_details"] = $db->GetRow("SELECT * FROM invoices WHERE id=?", array($_GET["id"]));
	
	require_once("src/append.inc.php");
?>