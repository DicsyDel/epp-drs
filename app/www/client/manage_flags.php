<?
    include ("src/prepend.inc.php");
    
    try
    {
    	$Domain = DBDomain::GetInstance()->Load($req_id);
    }
    catch(Exception $e)
    {
    	$errmsg = $e->getMessage();
    }
        
    if ($Domain)
    {
    	try
    	{
    		$Registry = $RegistryModuleFactory->GetRegistryByExtension($Domain->Extension);
    		if (!$Registry)
    			$errmsg = sprintf(_("Registry module not defined for '%s' domain extension."), $Domain->Extension);
    	}
    	catch(Exception $e)
    	{
    		$errmsg = $e->getMessage();
    	}
    	
    	if ($Domain->UserID != $_SESSION['userid'])
    		$errmsg = _("Domain not found");
    }
    	
	if ($Domain->Status != DOMAIN_STATUS::DELEGATED)
	{
		$errmsg = _("Domain status prohibits operation");
		CoreUtils::Redirect("domains_view.php");
	}
    
	if ($errmsg)
		CoreUtils::Redirect ("domains_view.php");

	$sflags = $Registry->GetManifest()->GetRegistryOptions()->allowed_domain_flags->children();
	$flags = array();
	foreach ($sflags as $flag)
    {
        $attrs = (array)$flag->attributes();
        $attrs = $attrs["@attributes"];
        
		$flags[] = array(  "sysname"    	=> $attrs["sysname"], 
							"name"       	=> $attrs["name"], 
							"description"	=> (string)$flag,
							"isset"			=> $Domain->HasFlag($attrs["sysname"])
						);
		$chitems[] = $attrs["sysname"]; 
    }
    
    $display["title"] = _("Domain options and permissions");
    
    if ($_POST)
    {
	    $chlist = new Changelist($Domain->GetFlagList());	    
	    foreach ($flags as $flag)
	    {
	        if (!$_POST["flags"][$flag["sysname"]])
	        {
				if ($Domain->HasFlag($flag["sysname"]))
		        	$chlist->Remove($flag["sysname"]);
	        }
			elseif ($_POST["flags"][$flag["sysname"]] == 1)
			{
				if (!$Domain->HasFlag($flag["sysname"]))
					$chlist->Add($flag["sysname"]);
			}
	    }
	    
	    if (count($chlist->GetAdded()) > 0 || count($chlist->GetRemoved()) > 0)
	    {		    
    	    try
    	    {
	    		$res = $Registry->UpdateDomainFlags($Domain, $chlist);
    	    }
    	    catch(Exception $e)
    	    {
    	    	$errmsg = $e->getMessage();
    	    }

    	    if ($res)
    	    {
				if ($Domain->HasPendingOperation(Registry::OP_UPDATE))
					$okmsg = _("Changes were submitted to registry. It will take some time for these to apply.");
				else
					$okmsg = _("Domain permissions updated");
                                    
				CoreUtils::Redirect("domains_view.php");
    	    }
	    }
    }
	    
	$display["id"] = $req_id;
	$display["flags"] = $flags;
	
	
	if ($Domain->HasPendingOperation(Registry::OP_UPDATE))
	{
		$display["dsb"] = "disabled";
		$display["warn"] = _("There are unapplied domain changes pending. You cannot edit flags until previous changes will be approved by registry.");
	}
	
	include ("src/append.inc.php");
?>