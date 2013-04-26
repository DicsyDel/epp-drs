<?php 
    require_once('src/prepend.inc.php');
    @set_time_limit(999999);
    
    if (Client::Load($_SESSION['userid'])->GetSettingValue('domain_preorder') != 1)
    	CoreUtils::Redirect("index.php");
    
    if ($_POST)
    {
    	$Validator = new Validator();
    	$lines = explode("\n", $post_domains);
    	
    	$err = array();
    	foreach ($lines as $k => $line)
    	{
    		try
    		{
	    		$ErrorList = new ErrorList();
	    		
	    		$chunks = explode(",", $line);
	    		$domainname = trim($chunks[0]);
	    		$expiredate = trim($chunks[1]);
	    		
	    		if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $expiredate) || !strtotime($expiredate))
	    		{
	    			$ErrorList->AddMessage(sprintf(_("Incorrect expiration date (%s) for domain %s."), $expiredate, $domainname));
	    			throw $ErrorList;
	    		}
	    		
	    		if (!$Validator->IsDomain($domainname))
	    		{
	    			$ErrorList->AddMessage(sprintf(_("Incorrect domain name %s"), $domainname));
	    			throw $ErrorList;
	    		}
	    			
	    		$dmn_chunks = explode(".", $domainname);
	    		$domain_name = array_shift($dmn_chunks);
				$extension = implode(".", $dmn_chunks);
				
				if (!$Registries[$extension])
				{
					try
					{
						$Registries[$extension] = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($extension);
					}
					catch(Exception $e)
					{
						$ErrorList->AddMessage(sprintf(_("Error while processing domain %s: %s"), 
							$domainname, $e->getMessage()));
						throw $ErrorList;
					}
					
					// Fuck! Add a singleton here, you dick!
					$Manifest = $Registries[$extension]->GetManifest();
					
					if ($Manifest->GetRegistryOptions()->ability->preregistration != 1)
					{
						$ErrorList->AddMessage(sprintf(_("Error while processing domain %s: Module %s doesn't support domain pre-registration."), 
							$domainname, $Registries[$extension]->GetModuleName()));
						throw $ErrorList;
					}
				}
				
				$Domain = $Registries[$extension]->NewDomainInstance();
				$Domain->Name = $domain_name;
				$Domain->UserID = $_SESSION['userid'];
				
				
				try
				{
					$res = $Registries[$extension]->DomainCanBeRegistered($Domain)->Result;
				}
				catch(Exception $e)
				{
					Log::Log(sprintf(_("Cannot check domain availability while processing line %s: %s"), $k+1, $e->getMessage()));
				}
				
    			if ($res == true)
				{
					$ErrorList->AddMessage(sprintf(_("Error while processing line %s: Domain name not registered"), $k+1));
					throw $ErrorList;
				}
				
				$Domain->CreateDate = strtotime($expiredate);
								
				if (Client::Load($_SESSION['userid'])->GetSettingValue('bill_for_domain_preorder') != 1)
				{
					$Domain->Status = DOMAIN_STATUS::AWAITING_PREREGISTRATION;
					DBDomain::GetInstance()->Save($Domain);
				}
				else
				{
					$Domain->Status = DOMAIN_STATUS::AWAITING_PAYMENT;
					$Domain = DBDomain::GetInstance()->Save($Domain);
					
					$Invoice = new Invoice(INVOICE_PURPOSE::PREREGISTRATION_DROPCATCHING, $Domain->ID, $_SESSION['userid']);
					$Invoice->Description = sprintf(_("Domain name %s pre-registration"), $Domain->GetHostName());
					$invoices[] = $Invoice->Save()->ID;
				}
    		}
	    	catch(ErrorList $e)
	    	{
	    		$err = array_merge($e->GetAllMessages(), $err);
	    	}
	    	catch(Exception $e)
	    	{
	    		Log::Log(sprintf(_("Exception thrown for domain %s while adding domains to bulk preregistration: %s"), $domainname, $e->getMessage()));
	    		$err[] = sprintf(_("Error occured for domain %s: %s"), $domainname, $e->getMessage());	
	    	}
    	}
    	
    	if (count($err) == 0)
    	{
    		if (count($invoices) != 0)
    			CoreUtils::Redirect("checkout.php?string_invoices=".implode(",", $invoices));
    		else
    		{
    			$okmsg = _("Domains successfully added to pre-registration queue");
    			CoreUtils::Redirect("domains_view.php");
    		}
    	}
    }
    
	
	include_once("src/append.inc.php");
?>