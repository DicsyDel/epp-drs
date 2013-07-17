<?			
	if ($_SESSION["okmsg"] || $okmsg)
	{
		$display["okmsg"] = $_SESSION["okmsg"] ? $_SESSION["okmsg"] : $okmsg;
		unset($_SESSION["okmsg"]);
		unset($okmsg);
	}
	elseif ($_SESSION["errmsg"] || $errmsg)
	{
		$display["errmsg"] = $_SESSION["errmsg"] ? $_SESSION["errmsg"] : $errmsg;
		unset($_SESSION["errmsg"]);
		unset($errmsg);
	}
	elseif ($_SESSION["mess"] || $mess)
	{
	    $display["mess"] = $_SESSION["mess"] ? $_SESSION["mess"] : $mess;
	    unset($_SESSION["mess"]);
	    unset($mess);
	}
	
	if ($_SESSION["err"])
	{
	    $err = $_SESSION["err"];
	    unset($_SESSION["err"]);
	    //unset($err);
	}
	
	if (is_array($err))
	{
		$display["errmsg"] = $errmsg ? htmlspecialchars($errmsg) : _("The following errors occured:");
		$display["err"] = array_map('htmlspecialchars', $err);
		unset($errmsg);
		unset($err);
	}
	
	// Generate default title from XML
	if (!$display["title"])
		$display["title"] = $XMLNav->GenerateBreadCrumbs();
	
	$smarty->assign($display);
	
	if (!$template_name)
	   $template_name = NOW;
	   
    $smarty->display("{$template_name}.tpl");
?>
