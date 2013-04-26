<?php
	try
	{
		$Domain = DBDomain::GetInstance()->LoadByName($_SESSION["domain"], $_SESSION['TLD']);
		if ($Domain->UserID != $_SESSION["userid"])
	    	$errmsg = _("You don't have permissions to manage this domain");

	    try
	    {
	    	$Registry = $RegistryModuleFactory->GetRegistryByExtension($Domain->Extension);
	    }
	    catch(Exception $e)
	    {
	    	$errmsg = $e->getMessage();
	    }
	    
	    if ($Registry)
	    	$Manifest = $Registry->GetManifest();
	    else
			$errmsg = sprintf(_("Registry module not defined for '%s' domain extension"), $Domain->Extension);
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
	}
	
	if ($errmsg)
		CoreUtils::Redirect ("domains_view.php");
?>