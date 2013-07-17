<?php require_once('src/prepend.inc.php'); ?>
<?

	$display["help"] = "This page lists all payment modules, installed in your EPP-DRS copy. Module must be enabled and then configured before you can use it."; 
	
	if (CONFIG::$DEV_DEMOMODE != 1)
	{
		// Delete
		if ($_GET)
		{	
			// disable module
			if($get_action == "disable" && $get_module)
			{
				// get payment module object
				$payment = $PaymentModuleFactory->GetModuleObjectByName($get_module);				
				if($payment)
				{	
					// Get domains thats use invoices with this payment method		
					$chk_domains = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE payment_module=? AND status='0'", array($get_module));
					if ($chk_domains > 0)
						$err[] = "You can not disable this module because there are invoices associated with it.";
					else
					{
						$db->Execute("UPDATE pmodules SET status='0' WHERE name=?", array($get_module));
						$db->Execute("DELETE FROM pmodules_config WHERE module_name = ?", array($get_module));
						$okmsg = "Module successfully disabled";
						CoreUtils::Redirect("pmodules_view.php");
					}
				}
			}
			
			// enable payment module
			elseif($get_action == "enable" && $get_module)
			{
				$chk = $db->GetOne("SELECT COUNT(*) FROM pmodules WHERE name = ?", array($get_module));
				
				if ($chk)
					$db->Execute("UPDATE pmodules SET status='1' WHERE name = ?", array($get_module));
				else
				{
					$db->Execute("INSERT INTO pmodules SET status='1', name = ?", array($get_module));
				}
				
				//
				// Update config
				//
				$db->Execute("DELETE FROM pmodules_config WHERE module_name = ?", array($get_module));				
    			$payment = $PaymentModuleFactory->GetModuleObjectByName($get_module);
    			
    			$cfg = $payment->GetConfigurationForm();
    			
    			if ($cfg)
    			{
	    			foreach ((array)$cfg->ListFields() as $field)
	    			{
	    				if (!$field->DefaultValue)
	    					$field->DefaultValue = "";	
	    				
	    				$db->Execute("INSERT INTO pmodules_config 
    									SET `title`	= ?,
    										`hint`	= ?, 
    										`type`	= ?, 
    										`key`	= ?, 
    										`value` = ?,
    										`module_name` = ?", 
	    					array($field->Title, $field->Hint, $field->FieldType, $field->Name, $field->DefaultValue ? $Crypto->Encrypt($field->DefaultValue, LICENSE_FLAGS::REGISTERED_TO) : "", $get_module));
	    			}
    			}
								
	            $okmsg = "Module successfully enabled. Please configure the module below.";
			    CoreUtils::Redirect("pmodule_config.php?module={$get_module}");
			}
		}
	}
	else 
	{
		$errmsg = _("Payment modules configuration is disabled in demo mode. No data is displayed or submitted.");
	}
	
	$modules = $PaymentModuleFactory->ListModules(true);
	
	foreach ($modules as $k=>$ModuleName) 
	{
		$display["modules"][$k]["mname"] = $ModuleName;
		$display["modules"][$k]["nameNormal"] = $ModuleName;
		
		$payment = $PaymentModuleFactory->GetModuleObjectByName($ModuleName);
					
		$db_check = $db->GetRow("SELECT * FROM pmodules WHERE name='{$ModuleName}'");
		$display["modules"][$k]["status"] = $db_check["status"];
		
		$display["modules"][$k]["name"] = $payment->GetModuleName();
	}
	
	require_once("src/append.inc.php");
?>