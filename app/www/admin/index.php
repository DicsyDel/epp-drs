<?php 
	include("src/prepend.inc.php");
	$display["total_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE hidden=0");
	$display["pending_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE status=?", array(INVOICE_STATUS::PENDING));
	$display["paid_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE status=? AND hidden=0", array(INVOICE_STATUS::PAID));
	$display["failed_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE status=?", array(INVOICE_STATUS::FAILED));
	
	$display["total_domains"] = $db->GetOne("SELECT COUNT(*) FROM domains");
	$display["active_domains"] = $db->GetOne("SELECT COUNT(*) FROM domains WHERE status=?", array(DOMAIN_STATUS::DELEGATED));	
	$display["pending_domains"] = $display["total_domains"] - $display["active_domains"];

	
	$display["total_contacts"] = $db->GetOne("SELECT COUNT(*) FROM contacts");
	$display["total_clients"] = $db->GetOne("SELECT COUNT(*) FROM users");
	$display["total_balance"] = round($db->GetOne("SELECT SUM(total) FROM balance"), 2);
	
	
	$display["help"] = "";
	
	include("src/append.inc.php");
?>
