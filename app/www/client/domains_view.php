<?
	require_once('src/prepend.inc.php'); 

	$DbDomain = DBDomain::GetInstance();
	if (isset($req_task))
	{
		$id = $req_id ? $req_id : $_SESSION['selected_domain'];
		
		try
		{
			$Domain = $DbDomain->Load($id);
		}
		catch(Exception $e)
		{
			$errmsg = $e->getMessage();
		}
		
		if ($Domain)
		{
			if ($Domain->UserID != $_SESSION['userid'])
				$errmsg = _("Domain not found");
			else
			{
				try
			    {
			    	$Registry = $RegistryModuleFactory->GetRegistryByExtension($Domain->Extension);
			    }
			    catch(Exception $e)
			    {
			    	$errmsg = $e->getMessage();
			    }
			}
		}
			
    	if (!$errmsg)
    	{
			switch($req_task)
			{
				case "delete":
					
					if (!$req_confirmed)
			    	{
			    		$display["Domain"] = $Domain;
			    		$display["ability"]["scheduled_delete"] = (int)$Registry->GetManifest()->GetRegistryOptions()->ability->scheduled_delete;
			    		$template_name = "client/domain_remove_confirmation";
			    		require_once("src/append.inc.php");
			    		exit();
			    	}
			    	else 
			    	{
			    		try
			    		{
			    			$deleteDate = $post_scheduled_delete && $post_sd_date ? strtotime($post_sd_date) : null; 
			    			
				    		$result = $Registry->DeleteDomain($Domain, $deleteDate);
				    		
				    		if ($result)
				    		{
				    			$okmsg = _("Domain successfully deleted.");
				    		}
			    		}
			    		catch(Exception $e)
			    		{
			    			$errmsg = sprintf(_("Cannot delete domain name. Reason: %s"),$e->getMessage());
			    		}
			    	}
					
					break;
					
				case "lock":
					
					try
		    		{
			    		if ($Domain->IsLocked)
			    		{
		    				$optype = _("unlock");
			    			$result = $Registry->UnlockDomain($Domain);
		    				$okmsg = _("Domain successfully unlocked.");
			    		}
		    			else
		    			{
		    				$optype = _("lock");
		    				$result = $Registry->LockDomain($Domain);
		    				$okmsg = _("Domain successfully locked.");
		    			}
		    		}
		    		catch(Exception $e)
		    		{
		    			$errmsg = sprintf(_("Cannot %s domain name. Reason: %s"), $optype, $e->getMessage());
		    		}
					
					break;
			}
			
    		if ($result)
	    		CoreUtils::Redirect("domains_view.php");
    	}
	}
	
	if (isset($req_action))
	{
		switch ($req_action)
		{
			case "setRenewDisabled":
			case "setRenewEnabled":
				if ($req_id)
				{
					foreach ((array)$req_id as $id)
					{
						try
						{
							$Domain = $DbDomain->Load($id);
							$Registry = $RegistryModuleFactory->GetRegistryByExtension($Domain->Extension);
							$RegistryOptions = $Registry->GetManifest()->GetRegistryOptions();
							if ($RegistryOptions->ability->auto_renewal)
							{
								if($Registry->GetModuleName() == 'EPPZA') {
									$chlist = new Changelist($Domain->GetFlagList());
									if ($Domain->HasFlag('autoRenew') && $req_action == 'setRenewDisabled')
										$chlist->Remove('autoRenew');
									else if (!$Domain->HasFlag('autoRenew') && $req_action == 'setRenewEnabled')
										$chlist->Add('autoRenew');
									
									if (count($chlist->GetAdded()) > 0 || count($chlist->GetRemoved()) > 0) {
										$Registry->UpdateDomainFlags($Domain, $chlist);
										//$Domain = $Registry->GetRemoteDomain($Domain);
									}
								}
								$Domain->RenewDisabled = $req_action == "setRenewDisabled";
								$DbDomain->Save($Domain);
							}
						}
						catch (Exception $e)
						{
							$errmsg = $e->getMessage();
						}
					}
				}
				break;
		}
		
		if (!$errmsg)
			CoreUtils::Redirect("domains_view.php");
	}
	
	
	// Selected domain dashboard
    if ($_SESSION["selected_domain"])
    {
    	try 
    	{
    		$SelectedDomain = $DbDomain->Load($_SESSION["selected_domain"]);
    		$display['SelectedDomain'] = $SelectedDomain;
    		
    		// Pending operations
    		$display['seldomain_pending_operations'] = $db->GetAll(
    			"SELECT *, LOWER(operation) as operation_name 
    			FROM pending_operations WHERE objectid=? AND objecttype=?",
	    		array($SelectedDomain->ID, Registry::OBJ_DOMAIN)
	    	); 

	    	// Extra fields 
	    	// TODO: maybe $Domain->GetExtraFieldNames() ?
	    	$seldomain_extra_fields = array();
	    	$domainConfig = $SelectedDomain->GetConfig();
	    	if (count($domainConfig->extra_fields))
	    	{
	    		foreach ($domainConfig->extra_fields->field as $field)
	    		{
	    			$fieldName = $field->attributes()->name;
	    			// Show NOT NULL fields  
	    			if (isset($SelectedDomain->{$fieldName}))
	    			{
	    				$seldomain_extra_fields[] = array
	    				(
	    					'name' => $fieldName,
	    					'description' => $field->attributes()->description,
	    					'value' => $SelectedDomain->{$fieldName}
	    				);	    				
	    			}
	    		}
	    	}
	    	$display['seldomain_extra_fields'] = $seldomain_extra_fields;
	    	
	    	// Operation abilities
	    	try
	    	{
	    		$Factory = RegistryModuleFactory::GetInstance();
	    		$Manifest = $Factory->GetRegistryByExtension($SelectedDomain->Extension)->GetManifest();
	    		$registryOptionsConfig = $Manifest->GetRegistryOptions();
	    		
		    	$display['seldomain_allow'] = array
		    	(
		    		'change_authcode' => $registryOptionsConfig->ability->change_domain_authcode == 1,
		    		'ns_hosts' => !(bool)$registryOptionsConfig->ability->hostattr,
		    		'lock' => $registryOptionsConfig->ability->domain_lock == 1,
		    		'flags' => (bool)$registryOptionsConfig->allowed_domain_flags->flag
		    	);
	    	}
	    	catch (Exception $e)
	    	{
	    		$err[] = $e->getMessage();
	    	}
    	} 
    	catch (Exception $ignore)
    	{
    		// SESSION may contains a dirty value. 
    	}
    }
    

	$display["help"] = _("This page displays all your domains. <br/> &bull;&nbsp;You can learn about possible values for status field <a target='blank' href='http://webta.net/docs/wiki/domain.status.codes'>here</a>.<br/> &bull;&nbsp;When domain is locked, noone can change it's nameservers and essential contacts until it is unlocked.<br/> &bull;&nbsp;Domain password is commonly used in transfer procedure.");
	$display["load_extjs"] = true;

	require_once ("src/append.inc.php");
