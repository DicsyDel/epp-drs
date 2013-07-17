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
    
    $display["title"] = _("Domain options and permissions");
    
    if ($_POST)
    {
    	try
        {
    		$res = $Registry->UpdateDomainAuthCode($Domain, $post_authCode);
        }
        catch(Exception $e)
        {
        	$errmsg = $e->getMessage();
        }

        if ($res)
        {
			$okmsg = _("Domain password updated");
			CoreUtils::Redirect("domains_view.php");
        }
    }
	    
	$display["id"] = $req_id;	
	
	include ("src/append.inc.php");
?>