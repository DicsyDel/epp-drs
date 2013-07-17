<?
	$enable_json = true;
	define("NO_TEMPLATES", true);
	include(dirname(__FILE__)."/../src/prepend.inc.php");
	
	$attr = array_merge($_GET, $_POST);

	if ($attr["JS_SESSIONID"] != $_SESSION["JS_SESSIONID"] && !$_SERVER['HTTP_X_FLASH_VERSION'])
		die();
	
    //
    $chunks = explode(".", $attr["name"]);
    $attr["name"] = array_shift($chunks);
    $stld = implode(".", $chunks);
    //
    	    
	$TLD = $attr["TLD"];
	$res = "";

	
	try
	{
		$Registry = $RegistryModuleFactory->GetRegistryByExtension($TLD);
	}
	catch(Exception $e)
	{
		$res = "FAIL_TO_GET_REGISTRY_OBJECT";
	}	
			
	if ($res != 'FAIL_TO_GET_REGISTRY_OBJECT')
	{
		$registry_config = $Registry->GetManifest()->GetSectionConfig();
		    		
		$Domain = $Registry->NewDomainInstance();
		$Domain->Name = $attr["name"];
		
		try
		{			
			if ($registry_config->idn)
	    	{
	    		$allowed_utf8_chars = $registry_config->idn->xpath("//allowed-utf8-chars");
			    $disallowed_utf8_chars = $registry_config->idn->xpath("//disallowed-utf8-chars");
			    
			    if ($allowed_utf8_chars[0] || $disallowed_utf8_chars[0])
			    {
				    $Validator = new Validator();
				    if (!$Validator->IsDomain("{$attr["name"]}.{$TLD}", null, null, (string)$allowed_utf8_chars[0], (string)$disallowed_utf8_chars[0]))
				    	throw new Exception(_("Domain name contains non-supported characters"));
			    }
	    	}
	    	else
	    	{
			    $Validator = new Validator();
			    if (!$Validator->IsDomain("{$attr["name"]}.{$TLD}"))
			    	throw new Exception(_("Domain name contains non-supported characters"));
	    	}
			
			if ($attr["isTransfer"] == "false")
			{
				if ($Registry->DomainCanBeRegistered($Domain)->Result && !DBDomain::GetInstance()->ActiveDomainExists($Domain))
					$res = "AVAIL";
				else
					$res = "NOT_AVAIL";
			}
			else
			{
				if ($Registry->DomainCanBeTransferred($Domain) && !DBDomain::GetInstance()->ActiveDomainExists($Domain))
					$res = "AVAIL";
				else
					$res = "NOT_AVAIL";
			}
		}
		catch(Exception $e)
		{
			$res = "CHECK_ERROR";
		}
	}
	
	$response["status"] = true;
	$response["data"] = array("res" => $res, "domain" => $attr["name"], "TLD" => $TLD);
	
	echo json_encode($response);
?>
