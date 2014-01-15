<?php
	require_once('src/prepend.inc.php');

	//
	// If we don't specify module
	//
	if (!$req_module)
		CoreUtils::Redirect("pmodules_view.php");

	if (CONFIG::$DEV_DEMOMODE != 1)
	{
	    if ($_POST)
		{
			//
			// Save config
			//
			foreach ($db->GetAll("SELECT * FROM pmodules_config WHERE module_name=?", array($post_module)) as $v)
			{
				$nval = ($_POST[$v["key"]]) ? $_POST[$v["key"]] : 0;
				$db->Execute("UPDATE pmodules_config SET 
				`value`=? 
				WHERE `key`=? AND module_name=?", array($Crypto->Encrypt($nval, LICENSE_FLAGS::REGISTERED_TO), $v['key'], $post_module));
			}
			
			// redirect
			$okmsg = sprintf(_("%s configuration saved"), $post_module);
			CoreUtils::Redirect("pmodules_view.php");
		}
	}
	else 
	{
		$errmsg = _("Payment modules configuration is disabled in demo mode. No data being displayed or submitted.");
	}

	
	// Get configs
	$Module = $PaymentModuleFactory->GetModuleObjectByName($req_module);	
	$ModuleConfForm = $Module->GetConfigurationForm();	
	$rows = $db->GetAll("SELECT * FROM pmodules_config WHERE module_name=?", array($req_module));
	foreach ($rows as &$row)
	{
	    if (stristr($row['key'], 'pass'))
	    {
	    	$ModuleConfForm->GetFieldByName($row['key'])->FieldType = 'password';
	    }
	       
	    if (CONFIG::$DEV_DEMOMODE == 1)
		    $row["value"] = "";
		else
		{
			if ($row["value"] != '')
				$row["value"] = $Crypto->Decrypt($row["value"], LICENSE_FLAGS::REGISTERED_TO);
		}
		$values[$row['key']] = $row['value'];
	}
	
	$display['values'] = $values;
	$display["module"] = $req_module;
	$display["help"] = $ModuleConfForm->GetInlineHelp();
	$display["fields"] = $ModuleConfForm->ListFields(); 
	
	require_once ("src/append.inc.php");
?>