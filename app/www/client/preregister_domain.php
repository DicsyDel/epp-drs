<?php 
    require_once('src/prepend.inc.php');
    @set_time_limit(999999);
    
    if (Client::Load($_SESSION['userid'])->GetSettingValue('domain_preorder') != 1)
    	CoreUtils::Redirect("index.php");
    
    if ($_POST)
    {
    	$Validator = new Validator();
    	$lines = explode("\n", $post_domains);
    	
    	
    	$post_domainname = FQDN::Sanitize($post_domainname);
    	list ($domain_name, $extension) = FQDN::Parse($post_domainname);
    	$expiredate = trim($post_dt);
    	
    	// Validate date. Sucks.
    	if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $expiredate) || !strtotime($expiredate))
    	{
    		throw new ApplicationException(sprintf(_("Incorrect expiration date.")));
    	}
    	
    	// Validate domain name
    	if (!$Validator->IsDomain($post_domainname))
    	{
    		throw new Exception(sprintf(_("Incorrect domain name: %s"), $post_domainname));
    	}
    		
		if (!$Registries[$extension])
		{
			try {
				$Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($extension);
			} catch(Exception $e)
			{
				throw new ApplicationException($e->getMessage());
			}
			
			$Manifest = $Registry->GetManifest();
			
			if ($Manifest->GetRegistryOptions()->ability->preregistration != 1)
			{
				throw new ApplicationException(sprintf(_("Module %s does not support domain pre-registration."), $Registry->GetModuleName()));
			}
		}
		
		$Domain = $Registry->NewDomainInstance();
		$Domain->Name = $domain_name;
		$Domain->UserID = $_SESSION['userid'];
		
		try {
			$res = $Registry->DomainCanBeRegistered($Domain)->Result;
		}
		catch(Exception $e) {
			throw new ApplicationException(sprintf(_("Cannot check availability of %s. %s")));
		}
		
    	if ($res == true)
			throw New ApplicationException(sprintf(_("Domain %s not registered"), $Domain->GetHostName()));
		
		// Save expiration date
		$Domain->CreateDate = strtotime($expiredate);

		// See if we have to issue invoice for this client
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
    	
    	if (count($err) == 0)
    	{
    		if (count($invoices) != 0)
    		{
    			$okmsg = _("Please pay the generated invoices below to add domains to pre-registration queue");
    			CoreUtils::Redirect("checkout.php?string_invoices=".implode(",", $invoices));
    		}
    		else
    		{
    			$okmsg = _("Domain successfully added to pre-registration queue");
    			CoreUtils::Redirect("domains_view.php");
    		}
    	}
    }
    
	
	include_once("src/append.inc.php");
?>