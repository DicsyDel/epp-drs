<?php
    include ("src/prepend.inc.php");

	if (isset($req_domainid))
		require_once("src/set_managed_domain.php");
	else
		require_once("src/get_managed_domain_object.php");
    
	if ($Domain->Status != DOMAIN_STATUS::DELEGATED)
	{
		$errmsg = _("Domain status prohibits operation");
		CoreUtils::Redirect("domains_view.php");
	}
		
    try
    {
		$Domain = $Registry->GetRemoteDomain($Domain);
					
		if ($Domain->RemoteCLID && ($Domain->RemoteCLID != $Registry->GetRegistrarID()))
		{
			$Domain->Status = DOMAIN_STATUS::TRANSFERRED;
		}
		
		DBDomain::GetInstance()->Save($Domain);
		
		$okmsg = _("Domain successfully synchronized");
		CoreUtils::Redirect("domains_view.php");
    }
    catch(Exception $e)
    {
    	$errmsg = $e->getMessage();
    	CoreUtils::Redirect("domains_view.php");	
    }
		
	require_once ("src/append.inc.php");
?>
