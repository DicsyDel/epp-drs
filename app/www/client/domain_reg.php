<?
	require_once('src/prepend.inc.php');
	
	$display["TLDs"] = $TLDs;
	$Client = Client::Load($_SESSION["userid"]);	
	
	// Complete domain registration (postpaid)
	if ($req_action == "complete")
	{
		Log::Log("domain_reg.php?action=complete", E_USER_NOTICE);
		// Get domain info
		try
		{
			$Domain = DBDomain::GetInstance()->Load($req_id);
		}
		catch(Exception $e)
		{
			Log::Log("Cannot get domain object (id={$req_id}), error: {$e->getMessage()}", E_USER_WARNING);
			$errmsg = $e->getMessage();	
		}
		
		Log::Log("DomainName = {$Domain->GetHostName()}", E_USER_NOTICE);
		
		if (!$errmsg && $Domain->UserID != $_SESSION['userid'])
		{
			Log::Log("UserID != Domain->UserID", E_USER_WARNING);
			$errmsg = _("You don't have permissions to manage this domain");
		}
		
		// Check domain status
		if (!$errmsg && ($Domain->Status != DOMAIN_STATUS::PENDING || $Domain->IncompleteOrderOperation != INCOMPLETE_OPERATION::DOMAIN_CREATE))
		{
			Log::Log("Status={$Domain->Status}, IncompleteOperation={$Domain->IncompleteOrderOperation}", E_USER_WARNING);
			$errmsg = _("There is no incomplete registration order for this domain");
		}
		
		// Check invoice
		$invoice = $db->GetRow("SELECT * FROM invoices WHERE itemid=? AND status='1' AND purpose=?", array($Domain->ID, INVOICE_PURPOSE::DOMAIN_CREATE));				
		if (!$errmsg && !$invoice)
		{
			Log::Log("No paid invoice for thi domain name", E_USER_WARNING);
			$errmsg = _("Cannot find corresponding invoice for this domain");
		}
			
		if (!$errmsg)
		{
			// If all test passed, Set Session variables and show 3 step (Select contacts)
			
			$_SESSION["domaininfo"] = false;
			$_SESSION["domaininfo"]["name"] = $Domain->Name;
			$_SESSION["domaininfo"]["extension"] = $Domain->Extension;
			$_SESSION["domaininfo"]["period"] = $Domain->Period;
			$_SESSION["domaininfo"]["id"] = $Domain->ID;
			
			$_SESSION["wiz_dname"] = true;
	    	$_SESSION["wiz_period"] = true;
	    	$_SESSION["wiz_contacts"] = false;
	    	$_SESSION["wiz_nameservers"] = false;
	    	$_SESSION["wiz_checkout"] = true;
	    	$post_step = 3;
	    	
	    	Log::Log("Display step 3. SESSION = ".serialize($_SESSION["domaininfo"]), E_USER_NOTICE);
		}
		else 
			CoreUtils::Redirect("domains_view.php");
	}
	
	
	// If postpaid, create domain else show avaiable payment modules for checkout
	if ($post_step == 5)
	{
		try
		{
			$exception = new ErrorList();
			
			Log::Log("Begin step 5. SESSION = ".serialize($_SESSION["domaininfo"]), E_USER_NOTICE);
			
			if (!$_SESSION['domaininfo']['extension'])
			{
				Log::Log('$_SESSION[domaininfo][extension] == false', E_USER_WARNING);
				$exception->AddMessage(_('Your session has been expired'));
				throw $exception;
			}
			
			try 
			{
		    	$Registry = $RegistryModuleFactory->GetRegistryByExtension($_SESSION["domaininfo"]["extension"]);
			}
			catch (Exception $e)
			{
				$exception->AddMessage(_("Invalid extension"));
			}
			
		    $registry_config = $Registry->GetManifest()->GetSectionConfig();
	    
		    $Validator = new Validator();
		    
		    // Validate additional domain information
		    if (count($registry_config->domain->registration->extra_fields->field) > 0)
		    {
				$display["add_fields"] = UI::GetRegExtraFieldsForSmarty($registry_config);
				
				$DataForm = new DataForm();
				$DataForm->AddXMLValidator($registry_config->domain->registration->extra_fields);
				$errors = $DataForm->Validate($_POST["add_fields"]);
				if ($errors)
					foreach ($errors as $_errmsg) $exception->AddMessage($_errmsg);
		    }
			else
				$display["add_fields"] = false;
				
			
			if ($exception->hasMessages())
				throw $exception;
				
			$full_dmn_name = "{$_SESSION["domaininfo"]["name"]}.{$_SESSION["domaininfo"]["extension"]}";
				
	        if ($_POST["enable_managed_dns"])
			{
				$_POST["ns1"] = CONFIG::$NS1;
				$_POST["ns2"] = CONFIG::$NS2;
			}
			else 
			{
				foreach (array("ns1", "ns2") as $k)
				{
					if (!$Validator->IsDomain($_POST[$k]))
						$exception->AddMessage(sprintf(_("%s is not a valid host"), $_POST[$k]));
					else
					{
						$isglue = FQDN::IsSubdomain($_POST[$k], $full_dmn_name);
						if ($isglue)
							$exception->AddMessage(sprintf(_("%s cannot be used as nameserver because %s is not registered yet."), $_POST[$k], $full_dmn_name));
					}
				}				
					
				if ($_POST["ns1"] == $_POST["ns2"])
					$exception->AddMessage(_("You cannot use the same nameserver twice."));
			}
			
			if ($exception->hasMessages())
				throw $exception;

			if ($_SESSION["domaininfo"]["id"])
				$Domain = DBDomain::GetInstance()->LoadByName($_SESSION["domaininfo"]["name"], $_SESSION["domaininfo"]["extension"]);
			else
				$Domain = $Registry->NewDomainInstance();
				
			$nslist[] = new Nameserver($_POST["ns1"]);
        	$nslist[] = new Nameserver($_POST["ns2"]);
        	$Domain->SetNameserverList($nslist);
        	$Domain->IsManagedDNSEnabled = $_POST["enable_managed_dns"];
			
        	$DbContact = DBContact::GetInstance();
        	foreach (array("registrant", "admin", "billing", "tech") as $contact_type)
        	{
        		if (($clid = $_SESSION["domaininfo"][$contact_type]))
        		{
        			try
        			{
        				$Domain->SetContact($DbContact->LoadByCLID($clid), $contact_type);
        			}
        			catch (Exception $e) { }
        		}
        	}
        	
  	
    		try
    		{
				if (is_array($_POST["add_fields"]) && count($_POST["add_fields"]) > 0)
			    {
			    	foreach ($_POST["add_fields"] as $fieldname=>$fieldvalue)
			    	{
				    	if ($fieldvalue)
				    	{
			    			$Domain->{$fieldname} = $fieldvalue;				    		
				    	}
			    	}
			    }
    		}
    		catch(Exception $e)
    		{
    			$errmsg = $e->getMessage();
        		$exception->AddMessage($errmsg);
        		throw $exception;
    		}
        	
	    	if ($_SESSION["domaininfo"]["id"])
    	    {
    	    	Log::Log("Trying to register domain from registrant CP. (Postpaid)", E_USER_NOTICE);
    	    	try
    	    	{
    	    		$cr = $Registry->CreateDomain($Domain, $Domain->Period, $_POST["add_fields"]);
    	    	}
    	    	catch(Exception $e)
    	    	{
    	    		$errmsg = $e->getMessage();
    	    		$exception->AddMessage($errmsg);
    	    		throw $exception; 
    	    	}
				
				if (!$errmsg)
				{
					$okmsg = _("Domain name successfully registered");
					CoreUtils::Redirect("domains_view.php");
				}
    	    }
	    	else 
	    	{
    	    	
	    		$Domain->Name = $_SESSION["domaininfo"]["name"];
	    		$Domain->UserID = $_SESSION["userid"];
	    		$Domain->Period = $_SESSION["domaininfo"]["period"];
	    		$Domain->Status = DOMAIN_STATUS::AWAITING_PAYMENT;
	    		try
	    		{
	    			$Domain = DBDomain::GetInstance()->Save($Domain);
	    		} 
	    		catch (Exception $e)
	    		{
	    			Log::Log($e->getMessage(), E_USER_ERROR);
	    			$exception->AddMessage(_('Cannot save domain'));
	    			throw $exception;
	    		}
	    		
	    		try
	    		{			    			    
                	$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_CREATE,$Domain->ID,$_SESSION['userid']);
                	$Invoice->Description = sprintf(_("%s domain name registration for %s year(s)"), 
                		$Domain->GetHostName(), $Domain->Period
                	);
                	$Invoice->Save();
	    		}
	    		catch(Exception $e)
	    		{
	    			$exception->AddMessage(sprintf(_("Cannot create invoice: %s"), $e->getMessage()));
    				throw $exception;	
	    		}
				
				$_SESSION["domaininfo"]["extension"] = false;
				$_SESSION["regdomain"] = false;
				$_SESSION["dInfo"] = false;
				$_SESSION["invTotal"] = false;
				$_SESSION["invID"] = false;
				$_SESSION["invDescription"] = false;
				$_SESSION["gate"] = false;
				$_SESSION["success_payment_redirect_url"] = "domains_view.php";
				
				if ($Invoice->Status == INVOICE_STATUS::PENDING)
				{
					CoreUtils::Redirect("checkout.php?invoices[]={$Invoice->ID}");
				}
				else
				{
					CoreUtils::Redirect("order_info.php?invoiceid={$Invoice->ID}");
				}
	    	}   
		    //if (count($err) == 0 && !$errmsg)
	        $template_name = "client/domain_reg_step_5";
		}
		catch (ErrorList $e)
		{
			$err = array_merge((array)$err, $e->GetAllMessages());
			
			if ($_SESSION["domaininfo"]["id"])
			{
        		$domaininfo = $db->GetRow("SELECT * FROM domains WHERE id=?", array($_SESSION["domaininfo"]["id"]));
        		$display["ns1"] = ($_POST["ns1"]) ? $_POST["ns1"] : $domaininfo["ns1"];
        		$display["ns2"] = ($_POST["ns2"]) ? $_POST["ns2"] : $domaininfo["ns2"];
			}
			else 
			{
				$display["ns1"] = $_POST["ns1"];
				$display["ns2"] = $_POST["ns2"];
			}
			
			if ($_SESSION['domaininfo']['extension'])
			{
				try
				{
					$Registry = $RegistryModuleFactory->GetRegistryByExtension($_SESSION["domaininfo"]["extension"]);
				    $registry_config = $Registry->GetManifest()->GetSectionConfig();
					$fields = $registry_config->domain->registration->extra_fields->xpath("field"); 
		        	if (count($fields) > 0)
						$display["add_fields"] = UI::GetRegExtraFieldsForSmarty($registry_config);
					else
						$display["add_fields"] = false;
				}
				catch(Exception $e)
				{
					$template_name = "client/domain_reg_step_1";
				}
				
				$display["enable_managed_dns"]	= ENABLE_EXTENSION::$MANAGED_DNS;
				$display["add_fields_post"] = $_POST["add_fields"];
			
				if (!$template_name)
	    			$template_name = "client/domain_reg_step_4";
			}
			else
			{
				$template_name = "client/domain_reg_step_1";
			}
		}
		
	}
	
	// Validate contacts and show form with nameservers and additional fields
	elseif ($post_step == 4)
	{
    	try
    	{
			$exception = new ErrorList();
			
			$Registry = $RegistryModuleFactory->GetRegistryByExtension($_SESSION["domaininfo"]["extension"]);
		    $registry_config = $Registry->GetManifest()->GetSectionConfig();
			
			$RegistryContacts = UI::GetContactsListForSmarty($registry_config);		
			
	        $display["TLD"] = $_SESSION["domaininfo"]["extension"];
	
	        foreach ($RegistryContacts as $k=>$v)
	        {
	        	if (!$_POST[$v["type"]] && $v["isrequired"] == 1)
	        	{
	        		$message = sprintf(_("%s contact not specified"), $v["name"]);
	        		$exception->AddMessage(sprintf(_("%s contact not specified"), $v["name"]));
	        	}
	        	else if ($_POST[$v["type"]])
	        	{
	        		try
	        		{
	        			$Contact = DBContact::GetInstance()->LoadByCLID($_POST[$v["type"]]);
	        			if ($Contact->HasPendingOperation(Registry::OP_CREATE_APPROVE)
	        				|| $Contact->HasPendingOperation(Registry::OP_UPDATE_APPROVE))
	        			{
	        				$exception->AddMessage(
	        					sprintf(_("Contact <%s> is not yet approved by administrator"), $Contact->GetTitle()));
	        			}
	        			else
	        			{
	        				// Accept contact
		        			$_SESSION["domaininfo"][$v["type"]] = $_POST[$v["type"]];	        				
	        			}
	        		}
	        		catch (Exception $e)
	        		{
	        			$exception->AddMessage($e->getMessage());
	        		}
	        	}
	        }
	        
	        if ($exception->HasMessages())
	        	throw $exception;
	        
	        $fields = $registry_config->domain->registration->extra_fields->xpath("field"); 
        	if (count($fields) > 0)
				$display["add_fields"] = UI::GetRegExtraFieldsForSmarty($registry_config);
			else
				$display["add_fields"] = false;
				
			$_SESSION["wiz_contacts"] = true;
			
			if ($_SESSION["domaininfo"]["id"])
			{        		
				$domaininfo = $db->GetRow("SELECT * FROM domains WHERE id=?", array($_SESSION["domaininfo"]["id"]));
        		$display["ns1"] = $domaininfo["ns1"];
        		$display["ns2"] = $domaininfo["ns2"];
        		
        		
				$additional_data = $db->Execute("SELECT * FROM domains_data WHERE domainid=?", array($_SESSION["domaininfo"]["id"]));
				while($dt = $additional_data->FetchRow())
					$display["add_fields_post"][$dt["key"]] = $dt["value"];
			}
        	
			$display["enable_managed_dns"]	= ENABLE_EXTENSION::$MANAGED_DNS;
				
			$template_name = "client/domain_reg_step_4";
			
    	}
    	catch (ErrorList $e)
    	{
    		$err = array_merge((array)$err, $e->GetAllMessages());
			$template_name = "client/domain_reg_step_3";
			$set_contacts = true;			    		
    	}
		catch (Exception $e)
    	{
    		Log::Log("Step4 thrown exception: {$e->getMessage()}", E_USER_WARNING);
    		$errmsg = _("Your session has been expired");
			$template_name = "client/domain_reg_step_1";	    		
    	}
		
	}
	
	// Show form with contacts
	elseif ($post_step == 3)
	{		
		$Registry = $RegistryModuleFactory->GetRegistryByExtension($_SESSION["domaininfo"]["extension"]);
	    $registry_config = $Registry->GetManifest()->GetSectionConfig();
		
		$RegistryContacts = UI::GetContactsListForSmarty($registry_config);
		
		$period = ($post_period) ? (int)$post_period : $_SESSION["domaininfo"]["period"];
		if ($period > 0)
		{
			$_SESSION["domaininfo"]["period"] = $period;
	        $display["TLD"] = $_SESSION["domaininfo"]["extension"];
	               
	        $set_contacts = true;
			$_SESSION["wiz_period"] = true;			
			$template_name = "client/domain_reg_step_3";
		}
		else 
		{
			$display["periods"] = array();
    	    $min_period = (int)$registry_config->domain->registration->min_period;
    	    $max_period = (int)$registry_config->domain->registration->max_period;
		    for($i = $min_period; $i <= $max_period; $i++)
                $display["periods"][] = $i;
			
			$err[] = _("Invalid registration period for this extension");
			$template_name = "client/domain_reg_step_2";
		}
	}
	
	// Select period
	elseif ($post_step == 2)
	{		
		try
	    {
	    	$display = array_merge($_POST, $display);	    	
	    	$Registry = $RegistryModuleFactory->GetRegistryByExtension($post_TLD);
	    	$registry_config = $Registry->GetManifest()->GetSectionConfig();

	    	$post_domain = String::Fly($post_domain)->ToLower();
	    	
	    	if ($registry_config->idn)
	    	{
	    		$allowed_utf8_chars = $registry_config->idn->xpath("//allowed-utf8-chars");
			    $disallowed_utf8_chars = $registry_config->idn->xpath("//disallowed-utf8-chars");
			    
			    if ($allowed_utf8_chars[0] || $disallowed_utf8_chars[0])
			    {
				    $Validator = new Validator();
				    if (!$Validator->IsDomain("{$post_domain}.{$post_TLD}", null, null, (string)$allowed_utf8_chars[0], (string)$disallowed_utf8_chars[0]))
				    	throw new Exception(_("Domain name contains non-supported characters"));
			    }
	    	}
	    	else
	    	{
			    $Validator = new Validator();
			    if (!$Validator->IsDomain("{$post_domain}.{$post_TLD}"))
			    	throw new Exception(_("Domain name contains non-supported characters"));
	    	}	
		    
		    // Create domain object
		    $Domain = $Registry->NewDomainInstance();
		    $Domain->Name = $post_domain;
		    
			try
			{ 
				// Be sure that it's free 
				$domain_avaiable = $Registry->DomainCanBeRegistered($Domain);
			}
			catch (Exception $e)
			{
				throw new Exception(_("Cannot check domain name availability. Make sure that you spelled domain name correctly"));
			}
			
			if (!$domain_avaiable->Result || DBDomain::GetInstance()->ActiveDomainExists($Domain))
			{
				throw new Exception(_('Domain cannot be registered') . ($domain_avaiable->Reason ? ". ". _("Reason: {$domain_avaiable->Reason}") : ""));
			}
			else if (DBDomain::GetInstance()->FindByName($Domain->Name, $Domain->Extension))
			{
				throw new Exception(_('Domain name already exists in EPP-DRS'));
			}			

			// Make prise list for various registration periods
    	    $display["periods"] = array();
    	    $min_period = (int)$Domain->GetConfig()->registration->min_period;
    	    $max_period = (int)$Domain->GetConfig()->registration->max_period;
    	    
    	    $discount_pc = $Client->PackageID ? (float)$db->GetOne(
    	    		"SELECT discount FROM discounts WHERE TLD=? AND purpose=? AND packageid=?", 
    	    		array($post_TLD, INVOICE_PURPOSE::DOMAIN_CREATE, $Client->PackageID)) : 0;
    	    for($i = $min_period; $i <= $max_period; $i++)
    	    {
				$price = $db->GetOne(
					"SELECT cost FROM prices WHERE purpose=? AND TLD=? AND period=?", 
					array(INVOICE_PURPOSE::DOMAIN_CREATE, $post_TLD, $i)
				);
				$discount = round($price/100*$discount_pc, 2);
				
    	    	$display["periods"][] = array("period" => $i, "price" => $price - $discount);
    	    }
    	    
    	    $_SESSION["domaininfo"]["name"] = $post_domain;
			$_SESSION["domaininfo"]["extension"] = $post_TLD;
    	    
            $_SESSION["wiz_dname"] = true;
    		$template_name = "client/domain_reg_step_2";
	    }
	    catch (Exception $e)
	    {
	    	$err[] = $e->getMessage();
			$template_name = "client/domain_reg_step_1";	    	
	    }
	}
	
	// Choose domain name
	else
	{
	    $template_name = "client/domain_reg_step_1";
	    $_SESSION["domaininfo"] = false;
    	$_SESSION["invID"] = false;
    	$_SESSION["gate"] = false;
    	$_SESSION["wiz_dname"] = false;
    	$_SESSION["wiz_period"] = false;
    	$_SESSION["wiz_contacts"] = false;
    	$_SESSION["wiz_nameservers"] = false;
    	$_SESSION["wiz_checkout"] = false;
	}
	
	if ($set_contacts)
	{
		$CForm = new DomainAllContactsForm(array(
			'tld' => $_SESSION["domaininfo"]["extension"],
			'userid' => $_SESSION['userid'],
			'form_action' => 'domain_reg.php',
			'form_fields' => array('step' => 4),
			'Domain' => $Domain
		));
		$display['all_contacts_form'] = $CForm->GetRenderedData();
	}

	if (!$display["ns1"])
	{
		$ns = $Client->GetSettingValue(ClientSettings::NS1);	
		$display["ns1"] =  $ns ? $ns : CONFIG::$NS1;
	}
	if (!$display["ns2"])
	{
		$display["ns2"] = $Client->GetSettingValue(ClientSettings::NS1) ? // Set client ns2 if ns1 was defined 
			$Client->GetSettingValue(ClientSettings::NS2) : CONFIG::$NS2;
	}
		
	$display["wiz"] = array(	"domainname" => ($_SESSION["wiz_dname"]) ? "complete" : "incomplete", 
								"period" => ($_SESSION["wiz_period"]) ? "complete" : "incomplete", 
								"contacts" => ($_SESSION["wiz_contacts"]) ? "complete" : "incomplete", 
								"nameservers" => ($_SESSION["wiz_nameservers"]) ? "complete" : "incomplete", 
								"checkout" => ($_SESSION["wiz_checkout"]) ? "complete" : "incomplete", 
						   );
	$smarty->assign($display);
	$display["filter"] = $smarty->fetch("client/inc/domain_wizard_progress.tpl");
	
	require_once('src/append.inc.php');
?>