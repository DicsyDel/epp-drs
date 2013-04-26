<?
    require_once('src/prepend.inc.php');
    
    $display["help"] = "If some domain extensions exposed by more than one module, we can choose which module to use for this extension.";
    
    if (CONFIG::$DEV_DEMOMODE != 1)
	{
	    if ($_POST)
	    {    	
	    	foreach ($post_tld_modules as $TLD=>$module)
	        {
	            $db->Execute("UPDATE tlds SET modulename=? WHERE TLD=?", array($module, $TLD));
	        }
	                
	        if ($post_task == "enable")
	        {
	        	$db->StartTrans();
				try
				{
		        	$chk = $db->GetOne("SELECT COUNT(*) FROM modules WHERE name=?", array($post_module));		        	
		    		if ($chk == 1)
		    			$db->Execute("UPDATE modules SET status='1' WHERE name=?", array($post_module));
		    		else
		    			$db->Execute("INSERT INTO modules SET status='1', name=?", array($post_module));
		    		
		    		// Add module prices to database
					// Try to get module object
					try
					{
						$Registry = $RegistryModuleFactory->GetRegistryByName($post_module, true);
					}
					catch(Exception $e)
					{
						$errmsg = $e->getMessage();
					}
	
					if ($Registry)
					{		
						foreach ($Registry->GetManifest()->GetExtensionList() as $k=>$v)
						{
							if (!$db->GetOne("SELECT id FROM tlds WHERE TLD=?", array($v)))
								$db->Execute("INSERT INTO tlds SET TLD=?, modulename=?, isactive=?", array($v, $Registry->GetModuleName(), 0));
						}
						
						//
						// Update config
						//
						$db->Execute("DELETE FROM modules_config WHERE module_name = ?", 
							array($Registry->GetModuleName())
						);
			        	$settings_fields = $Registry->GetConfig();
						foreach ($settings_fields->ListFields() as $field)
						{
							$defval = (string)$field->DefaultValue;
							
							$db->Execute("INSERT INTO modules_config 
													SET `title`	= ?, 
														`type`	= ?, 
														`key`	= ?, 
														`value`	= ?,
														`module_name` = ?
											", array(	$field->Title, 
														$field->FieldType, 
														$field->Name, 
														$defval ? $Crypto->Encrypt($defval, LICENSE_FLAGS::REGISTERED_TO) : "", 
														$Registry->GetModuleName()
													)
										);
						}
						
						// Run Module::OnModuleEnabled
						$Registry->GetModule()->OnModuleEnabled();
					}
				}
				catch(Exception $e)
				{
					$db->RollbackTrans();
					throw new ApplicationException ($e->getMessage(), $e->getCode());
				}
							
				if (!$errmsg)
				{
					$db->CompleteTrans();
					
					$okmsg = "Module successfully enabled";
					CoreUtils::Redirect("modules_view.php");
				}
	        }
	    }
	}
    

	$extensions_modules = array();
	$modules = $db->GetAll("SELECT * FROM modules WHERE status='1'");    	
	foreach ($modules as $module)
	{
		$Registry = $RegistryModuleFactory->GetRegistryByName($module["name"], false);
		$extensions = $Registry->GetManifest()->GetExtensionList();
		
		foreach ($extensions as $extension)
		{
			if (!$extensions_modules[$extension])
				$extensions_modules[$extension] = array($module["name"]);
			else
				array_push($extensions_modules[$extension], $module["name"]);
		}
	}	
    if ($get_task == "enable" && CONFIG::$DEV_DEMOMODE!= 1)
    {
        $display["task"] = "enable";
        $display["module"] = $get_module;
        
        $get_module = preg_replace("/[^A-Za-z0-9_-]+/", "", $get_module);
	    
    	try
		{
			$Registry = $RegistryModuleFactory->GetRegistryByName($get_module, false);
		}
		catch(Exception $e)
		{
			$errmsg = $e->getMessage();
		}
        
		if (!$errmsg)
		{
			// Pass only module extensions
			
	        $all_extensions_modules = $extensions_modules;
	        $extensions_modules = array();
	        $extensions = $Registry->GetManifest()->GetExtensionList();
     
	        foreach ($extensions as $ext)
	        {
	        	if (!$all_extensions_modules[$ext])
					$extensions_modules[$ext] = array($get_module);
				else
					$extensions_modules[$ext] = array_merge($all_extensions_modules[$ext], array($get_module));
	        }
		}
    }
    
    $display["conflicts"] = array();
    foreach ($extensions_modules as $extension => $extension_modules)
    {
    	if (count($extension_modules) > 1)
    	{
    		$display["conflicts"][$extension] = array(
    			"modules" => $extension_modules, 
    			"selected" => $db->GetOne("SELECT modulename FROM tlds WHERE TLD=?", array($extension)),
    			"disabled" => (bool)($db->GetOne("SELECT COUNT(*) FROM contacts WHERE contacts.TLD=?", array($extension)) || $db->GetOne("SELECT COUNT(*) FROM domains WHERE domains.TLD=?", array($extension))),
    		);
    	}
    }
    
    ksort($display["conflicts"]);
    
    require ("src/append.inc.php");
?>