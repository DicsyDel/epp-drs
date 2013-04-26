<?
	$display["help"] = "This page lists all installed registry modules.
	<br>After you enable any module, you must configure and test it. If two or more modules expose same extension, you can <a href='manage_tld_conflicts.php'>resolve such conflicts</a>.
	<br>All modules passed registry tests and were certified by registries.";
	
	require_once('src/prepend.inc.php');
	
	if ($get_action == "test" && $get_module)
	{
		$get_module = preg_replace("/[^A-Za-z0-9_-]+/", "", $get_module);
	    $result = true;
	    
	    Log::Log("Testing module '{$get_module}'", E_USER_NOTICE);
	    
	    Log::Log("Validating '{$get_module}' manifest", E_USER_NOTICE);
	    
	    $res = RegistryManifest::Validate(MODULES_PATH."/registries/{$get_module}/module.xml");
	    if ($res !== true)
	    {
	    	Log::Log(sprintf("Manifest validation failed: %s", $res), E_USER_ERROR);
	    	$result = false;
	    }
	    else
	    {
		    try
		    {
		    	$r = $RegistryModuleFactory->GetRegistryByName($get_module, true);
		    }
		    catch(Exception $e)
		    {
		    	$err[] = $e->getMessage();
		    }
	    }
	    
	    if ($r)
	    {
	    	try
	    	{
				$Domain = $r->NewDomainInstance();
				$Domain->Name = "eppdrstest";
	    		
	    		$chk = $r->DomainCanBeRegistered($Domain)->Result;
	    	}
	    	catch(Exception $e)
	    	{
	    		$result = false;
	    		$err[] = $e->getMessage();			
	    	}
	    }

	    
		if ($result)
			$okmsg = _("Module passed all tests and is operational.");
		else 
			$errmsg = _("Module did not pass one or more tests.");
			
		CoreUtils::Redirect("modules_view.php");
	}
	
	if ($get_action == 'certtest' && $get_module)
	{
		
	}
	
	//
	// Disable module
	//
	if($get_action == "disable" && $get_module)
	{
		if (CONFIG::$DEV_DEMOMODE != 1)
		{
			// Try to get module object
			$get_module = preg_replace("/[^A-Za-z0-9_-]+/", "", $get_module);
			
			$db->StartTrans();
			try
			{
				try
				{
			    	$Registry = $RegistryModuleFactory->GetRegistryByName($get_module, false);
				}
				catch (Exception $e)
				{
					Log::Log($e->getMessage(), E_USER_ERROR);
					$err[] = $e->getMessage();
				}
				
				if($Registry)
				{
					// Generate array with TLD's exported by this module
					$T = array();
					$TLDrows = $db->Execute("SELECT * FROM tlds WHERE modulename=?", array($get_module));
					while($row = $TLDrows->FetchRow())
						array_push($T, "'{$row['TLD']}'");
		
					$module_tlds = implode(",", $T);
					
					// Check domains with one of this TLD's
					$chk_domains = $module_tlds ? (int)$db->GetOne("SELECT COUNT(*) FROM domains WHERE TLD IN ({$module_tlds})") : 0;
					$chk_contacts = $module_tlds ? (int)$db->GetOne("SELECT COUNT(*) FROM contacts WHERE TLD IN ({$module_tlds})") : 0;
					if ($chk_domains || $chk_contacts)
						$err[] = sprintf(_("You can not disable module %s while there are domains or contacts associated with it."), $get_module);
					else
					{
						$tlds = $db->GetAll("SELECT * FROM tlds WHERE modulename=?", array($get_module));
						foreach($tlds as $mTLD)
		                {
		                    $db->Execute("DELETE FROM prices WHERE TLD=?", array($mTLD["TLD"]));
		                }
		                $db->Execute("DELETE FROM tlds WHERE modulename=?", array($get_module));
		                
					    $db->Execute("UPDATE modules SET status='0' WHERE name=?", array($get_module));
					}
					
					// Run Module::OnModuleEnabled
					$Registry->GetModule()->OnModuleDisabled();
				}
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				throw new ApplicationException ($e->getMessage(), $e->getCode());
			}
				
			$db->CompleteTrans();
			if (!$err)
			{
				$okmsg = "Module successfully disabled";
				CoreUtils::Redirect("modules_view.php");
			}
		}
	}
	
	// Enable modules
	elseif($get_action == "enable" && $get_module)
	{
		// Check if this module licensed
		if (!License::IsModuleLicensed($get_module))
			throw new LicensingException("Your license does not permit module {$get_module}. For additional module purchases, please contact sales@webta.net");
 
		if (CONFIG::$DEV_DEMOMODE != 1)
		{
			$get_module = preg_replace("/[^A-Za-z0-9_-]+/", "", $get_module);
		    
			// Validate Manifest
			RegistryManifest::LoadCompiledManifest(CONFIG::$PATH."/modules/registries/{$get_module}/module.xml", true);
			$res = RegistryManifest::Validate(CONFIG::$PATH."/modules/registries/{$get_module}/module.xml");
			if ($res !== true)
			{
				Log::Log($res, E_USER_ERROR);
				$errmsg = sprintf(_("Module %s XML manifest did not pass validation. The following errors occured:"), $get_module);
				$err[] = $res;
			}
			
			if (!$errmsg)
			{
				try
				{
			    	$Registry = $RegistryModuleFactory->GetRegistryByName($get_module, true);
				}
				catch (Exception $e)
				{
					Log::Log($e->getMessage(), E_USER_ERROR);
					$err[] = $e->getMessage();
				}
			}			
									
			if ($Registry && !$err)
			{
			    $moduleTLDS = $Registry->GetManifest()->GetExtensionList();
			    foreach($moduleTLDS as $mTLD)
			    {
			        if ($db->GetOne("SELECT id FROM tlds WHERE TLD=?", array($mTLD)))
			        {
			            $redir = true;
			            break;
			        }
			    }
			    
			    if (count($err) == 0)
			    {
				    if ($redir)
				        CoreUtils::Redirect("manage_tld_conflicts.php?module={$get_module}&task=enable");
			    
				    $db->BeginTrans();
				        
				    try
				    {
				    	// Check module in database
			    		$chk = $db->GetOne("SELECT COUNT(*) FROM modules WHERE name=?", array($get_module));
			    		if ($chk)
			    			$db->Execute("UPDATE modules SET status='1' WHERE name=?", array($get_module));
			    		else
			    			$db->Execute("INSERT INTO modules SET status='1', name=?", array($get_module));
			    		   			
			    		foreach ($moduleTLDS as $k=>$v)
							$db->Execute("REPLACE INTO tlds SET TLD=?, isactive=?, modulename=?", array($v, 0, $get_module));
						
						//
						// Update config
						//
						$db->Execute("DELETE FROM modules_config WHERE module_name = ?", array($get_module));
						
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
														$get_module
													)
										);
						}
						
						// Run Module::OnModuleEnabled
						$Registry->GetModule()->OnModuleEnabled();
				    }
				    catch(Exception $e)
				    {
				    	$db->RollbackTrans();
						throw new ApplicationException ($e->getMessage(), $e->getCode());
					}
						
					$db->CompleteTrans();
					
					$okmsg = "Module successfully enabled";
		    		CoreUtils::Redirect("modules_view.php");
			    }
			}
		}
	}
	
	// Scan modules folder
	$modules = @glob(MODULES_PATH."/registries/*", GLOB_ONLYDIR);
	$exclude_list = array(".svn","observers");
	
	foreach ($modules as $k=>$v) 
	{
		if (in_array(basename($v), $exclude_list))
			continue;
		
		$tmp = explode("/", $v);
		$ModuleName = $tmp[sizeof($tmp)-1];
		
		if (strtoupper($ModuleName) != 'SAMPLE')
		{
			$display["modules"][$k]["name"] = strtoupper($ModuleName);
			$display["modules"][$k]["nameNormal"] = $ModuleName;
			
			try
			{
				$Registry = $RegistryModuleFactory->GetRegistryByName($ModuleName, false);
				
				$display["modules"][$k]["expTLDs"] = implode(", ", $Registry->GetManifest()->GetExtensionList());
				$display["modules"][$k]["description"] = $Registry->GetManifest()->GetModuleDescription();
				$status = $db->GetOne("SELECT status FROM modules WHERE name=?", array($ModuleName));
    			$display["modules"][$k]["status"] = $status;
    			
    			
    			$Module = $Registry->GetModule();
    			$RefModule = new ReflectionObject($Module);
    			if ($RefModule->hasMethod('RunTest'))
    			{
    				$display["modules"][$k]["run_test"] = 1;
    			}
			}
			catch(Exception $e)
			{
				$display["modules"][$k]["expTLDs"] = $e->getMessage();
				$display["modules"][$k]["status"] = 3;
			}
		}
	}
	
	if (CONFIG::$DEV_DEMOMODE == 1)
	{
		$errmsg = _("Registry modules configuration is disabled in demo mode. No data being displayed or submitted.");
	}
	
	require ("src/append.inc.php");
?>