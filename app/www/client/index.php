<? 
	include("src/prepend.inc.php");
	
	$display["total_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE userid = ?", array($_SESSION['userid']));
	$display["pending_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE status=? AND userid=?", array(INVOICE_STATUS::PENDING, $_SESSION['userid']));
	$display["paid_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE status=? AND userid=?", array(INVOICE_STATUS::PAID, $_SESSION['userid']));
	$display["failed_invoices"] = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE status=? AND userid=?", array(INVOICE_STATUS::FAILED, $_SESSION['userid']));
	
	$display["total_domains"] = $db->GetOne("SELECT COUNT(*) FROM domains WHERE userid=?", array($_SESSION['userid']));
	
	$display["pending_domains"] = $db->GetOne("SELECT COUNT(*) FROM domains WHERE status!=? AND userid=?", array(DOMAIN_STATUS::DELEGATED, $_SESSION['userid']));
	$display["active_domains"] = $db->GetOne("SELECT COUNT(*) FROM domains WHERE status=? AND userid=?", array(DOMAIN_STATUS::DELEGATED, $_SESSION['userid']));
	
	$display["total_contacts"] = $db->GetOne("SELECT COUNT(*) FROM contacts WHERE userid=?", array($_SESSION['userid']));
	
	$display["help"] = "";
	
	include("src/append.inc.php");
?>
