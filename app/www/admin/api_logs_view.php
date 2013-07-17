<?php
	require("src/prepend.inc.php");

	if (CONFIG::$DEV_DEMOMODE == 1)
	{
		$errmsg = _("Log viewer is disabled in demo mode.");
		CoreUtils::Redirect("index.php");
	}
	
	$display["title"] = _("API log&nbsp;&raquo;&nbsp;View");
	$display['load_extjs'] = true;	
	
	require("src/append.inc.php");	
?>