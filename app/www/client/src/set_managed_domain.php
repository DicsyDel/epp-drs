<?php

    // �������� �������� ����� �� ����
	try
    {
		$Domain = DBDomain::GetInstance()->Load($req_domainid);
    }
    catch(Exception $e)
    {
    	$errmsg = $e->getMessage();
    }
	
    if (!$errmsg)
    {
	    // ���� ������ ����
	    if (!$Domain)
	    	$errmsg = _("No such domain in database");
	    
	    // ���� ������� ������ �� ��������� � ������ � ����
	    if ($Domain && $Domain->UserID != $_SESSION["userid"])
	    	$errmsg = _("You don't have permissions to manage this domain");
    }
    	
    if (!$errmsg)
    {
		// ���� � ������� ��� ��, �� �������� ������� ������ Registry ��� ��� TLD.
    	try
    	{
    		$Registry = $RegistryModuleFactory->GetRegistryByExtension($Domain->Extension);
    	}
    	catch(Exception $e)
    	{
    		$errmsg = $e->getMessage();
    	}
    	
    	if (!$Registry)
    		$errmsg = sprintf(_("Registry module not configured for '%s' domain extension"), $Domain->Extension);
    	else
    		$Manifest = $Registry->GetManifest();
    }
    	
    // ���� �������� ������
    if ($errmsg)
    	CoreUtils::Redirect ("domains_view.php");
        	
    $_SESSION["domain"]	= $Domain->Name;
    $_SESSION["selected_domain"] = $Domain->ID;
    $_SESSION["TLD"] = $Domain->Extension;
?>