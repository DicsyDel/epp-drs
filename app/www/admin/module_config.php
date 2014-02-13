<?php

	require_once('src/prepend.inc.php');

	if (!$req_module)
		CoreUtils::Redirect("modules_view.php");


    $skipPasswordUpdate = false;
	if (CONFIG::$DEV_DEMOMODE != 1)
	{
	
		if ($_POST)
		{
		    $Registry = $RegistryModuleFactory->GetRegistryByName($req_module);
		    $Module = $Registry->GetModule();
		    
			$reflect = new ReflectionObject($Module);
		    $method = $reflect->getMethod("ValidateConfigurationFormData");
		    $validation_result = $method->invoke(NULL, $_POST);
			
		    if ($validation_result === true)
		    {
				foreach ($db->GetAll("SELECT * FROM modules_config WHERE module_name=?", array($req_module)) as $v)
				{
					$nval = ($_POST[$v["key"]]) ? $Crypto->Encrypt($_POST[$v["key"]], LICENSE_FLAGS::REGISTERED_TO) : "";
												
					$db->Execute("UPDATE modules_config SET 
									`value`=? WHERE 
									`key`=? 
									AND module_name=?", 
					array($nval, $v['key'], $req_module));
				}
		    }
		    else
		    	$err = $validation_result;
						
			if (!$err)
			{
			  $okmsg = "Module config saved";
			  CoreUtils::Redirect("modules_view.php");
			}
		}
				
		$display["module"] = $req_module;
		$display["title"] = "Modules > Settings";
		$display["show_notice"] = false;
		
		try
		{
			$Registry = $RegistryModuleFactory->GetRegistryByName($req_module, false);
		}
		catch(Exception $e)
		{
			$errmsg = $e->getMessage();
		}
		
    	if($Registry)
    	{
    	    foreach ($Registry->GetManifest()->GetExtensionList() as $k=>$v)
    	    {
    	        if ($db->GetRow("SELECT * FROM tlds WHERE TLD=? AND modulename=?", array($v, $Registry->GetModuleName())))
    	        {
	    	        $num_contacts = $db->GetOne("SELECT COUNT(*) FROM contacts WHERE TLD=?", array($v));
	    	        $num_domains = $db->GetOne("SELECT COUNT(*) FROM domains WHERE TLD=?", array($v));
	    	        if ($num_contacts > 0 || $num_domains > 0)
	    	        {
	                    $display["show_notice"] = true;
	                    break;
	    	        }
    	        }
    	    }
    	}
	}
	else 
	{
		$errmsg = _("Registry modules configuration is disabled in demo mode. No data being displayed or submitted.");
	}
	
	try
	{
		$Registry = $RegistryModuleFactory->GetRegistryByName($req_module);
		
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
	}
	
	$display["rows"] = $db->GetAll("SELECT * FROM modules_config WHERE module_name=?", array($req_module));
	foreach ($display["rows"] as &$row)
	{
	    if (stristr($row['key'], 'pass') || stristr($row['key'], 'pwd'))
	       $row["type"] = 'password';
	       
	    if (CONFIG::$DEV_DEMOMODE == 1)
		    $row["value"] = "";
		else
		{
			if (isset($_POST[$row['key']]))
			{
				$row['value'] = $_POST[$row['key']];
			}
			elseif ($row["value"] != '')
			{
				$row["value"] = $Crypto->Decrypt($row["value"], LICENSE_FLAGS::REGISTERED_TO);
			}
		}
		
		$row["hint"] = $Registry->GetConfig()->GetFieldByName($row['key'])->Hint;
	}
		
	if ($Registry && $Registry->GetConfig())
		$display["help"] = $Registry->GetConfig()->GetInlineHelp();
	
	$login = $db->GetOne("SELECT value FROM modules_config WHERE module_name=? AND `key`='Login'", array($req_module));
	if ($login)
		$display["curr_login"] = $Crypto->Decrypt($login, LICENSE_FLAGS::REGISTERED_TO);
		
	$display["nofilter"] = true;
	require_once ("src/append.inc.php");
?>