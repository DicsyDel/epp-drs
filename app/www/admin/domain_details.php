<?php
	require_once('src/prepend.inc.php');
	
	$domaininfo = $db->GetRow("SELECT * FROM domains WHERE id=?", array($req_domainid));
	if (!$domaininfo)
		CoreUtils::Redirect("domains_view.php");
	
	try
	{
		$Registry = $RegistryModuleFactory->GetRegistryByExtension($domaininfo["TLD"]);
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
		CoreUtils::Redirect("domains_view.php");
	}

	$Domain = DBDomain::GetInstance()->Load($req_domainid, $Registry->GetManifest());
	
	try
	{
		$Domain = $Registry->GetRemoteDomain($Domain);
	}
	catch(Exception $e)
	{
		$errmsg = sprintf(_("Cannot get full information about domain name. Reason: %s"), $e->getMessage());
		CoreUtils::Redirect("domains_view.php");
	}	
	
	if (!($Domain instanceOf Domain))
	{
		$errmsg = sprintf(_("Cannot get full information about domain name. Domain object failed"));
		CoreUtils::Redirect("domains_view.php");
	}
	
	$reflect = new ReflectionObject($Domain);
	foreach((array)$reflect->getProperties() as $Prop)
	{
		if ($Prop->isPublic())
		{
			if ($Prop->name != "CreateDate" && $Prop->name != 'ExpireDate')
				$info[$Prop->name] = $Prop->getValue($Domain);
			else
				$info[$Prop->name] = date("Y-m-d H:i:s", $Prop->getValue($Domain));
		}
	}
		
	foreach((array)$Domain->GetContactList() as $type => $Contact)
		$info[sprintf(_("%s contact"), ucfirst($type))] = $Contact->CLID;

		
	$ns_list = $Domain->GetNameserverList();
	foreach((array)@array_reverse($ns_list) as $k=>$Nameserver)
		$info[sprintf(_("Nameserver #%s"), ($k+1))] = $Nameserver->HostName;
		
	$display["info"] = $info;
	

	$display["title"] = _("Domain &nbsp;&raquo;&nbsp; Full information");
	
	require_once ("src/append.inc.php");
?>