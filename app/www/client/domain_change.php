<?php
	include ("src/prepend.inc.php");

	$req_domainid = $req_id;
	require_once("src/set_managed_domain.php");

	$okmsg = sprintf(_("You are now managing %s. You can find a list of tasks for this particular domain in top menu."), $Domain->GetHostName());	
	CoreUtils::Redirect ("domains_view.php");
?>
