<?			
	if ($_SESSION["okmsg"] || $okmsg)
	{
		$display["okmsg"] = $_SESSION["okmsg"] ? $_SESSION["okmsg"] : $okmsg;
		$_SESSION["okmsg"] = false;
	}
	elseif ($_SESSION["errmsg"] || $errmsg)
	{
		$display["errmsg"] = $_SESSION["errmsg"] ? $_SESSION["errmsg"] : $errmsg;
		$_SESSION["errmsg"] = false;
	}
	elseif ($_SESSION["mess"] || $mess)
	{
		$display["okmsg"] = $_SESSION["mess"] ? $_SESSION["mess"] : $mess;
	    $_SESSION["mess"] = false;
	}
	
	if ($_SESSION["err"])
	{
	    $err = $_SESSION["err"];
	    $_SESSION["err"] = false;
	}
	
	if (is_array($err))
	{
		$display["errmsg"] = $errmsg ? $errmsg : "The following errors occured:";
		$display["err"] = $err;
	}
	
	// Generate default title from XML
	if (!$display["title"])
		$display["title"] = $XMLNav->GenerateBreadCrumbs();
	
	$smarty->assign($display);
	
	if (!$template_name)
	   $template_name = NOW;
	
    $smarty->display("{$template_name}.tpl");
?>
