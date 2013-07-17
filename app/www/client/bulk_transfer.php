<?php 
    require_once('src/prepend.inc.php');
    set_time_limit(0);
    
    if ($_POST)
    {
    	// Select TLD to transfer
        if ($post_step == 1)
        {
        	// So we have submitted TLD name
            if ($post_TLD)
            {
            	// Try to load registry for TLD 
                try
                {
            		$Registry = $RegistryModuleFactory->GetRegistryByExtension($post_TLD);
            		if (!$Registry)
            			$errmsg = sprintf(_("Registry module not defined for %s domain extension"), $post_TLD);
                }
                catch(Exception $e)
                {
                	$errmsg = $e->getMessage();
                }
                
                // Check that transfer allowed for TLD
                if (!$errmsg && $Registry)
                {
	    		    $allow = (int)$Registry->GetManifest()->GetSectionConfig()->domain->transfer->allow_bulk_transfer;
	    		    if ($allow)
	    		    {
	    		        $_SESSION["BT_TLD"] = $post_TLD;
	                    $stepno = 2;
	    		    }
	    		    else 
	    		    {
	    		        $err[] = _("Invalid extension");
	    		        $stepno = 1;
	    		    }
                }
            }
            else
                $err[] = _("Invalid extension");
        }
        
        // Process domains specified for transfer
        elseif ($post_step == 2)
        {
        	$domain_names = array_filter(array_map('trim', explode("\n", (string)$post_domains)));
        	if ($domain_names)
        	{
        		$stepno = 3;
        	}
        	else
        	{
        		$errmsg = _("There are no domains to transfer");
        	}
        }
        
        
        elseif ($post_step == 3)
        {
        	// Try to load registry for TLD
        	try
			{
            	$Registry = $RegistryModuleFactory->GetRegistryByExtension($_SESSION["BT_TLD"]);
            	if (!$Registry)
            		$errmsg = sprintf(_("Registry module not defined for %s domain extension"), $_SESSION["BT_TLD"]);
            }
			catch(Exception $e)
			{
				$errmsg = $e->getMessage();
			}

        	// Accept checked domain names
			if ($post_domains)
			{
				$domain_names = array();
				foreach ($post_domains as $d)
				{
					if ($d['avail'])
					{
						list($name, $tld) = explode(".", $d['name'], 2);
						$domainnames[] = $name;
					}
				}
			}
			if (!$domainnames)
			{
				$errmsg = _("There are no domains to transfer");
			}
			
			$stepno = 4;
			
			// Can be transferred domains
			// domain=>true 
			$res_ok = array();
			// Transfer not available 
			// domain=>reason 
			$res_fail = array();
			
			$Order = new Order($_SESSION['userid']);
			
			// For each entered domain name check tranfer posibility
			if (!$errmsg && $Registry)
			{
		    	foreach ($domainnames as $domain_name)
		    	{
			        Log::Log("Process {$domain_name}", E_USER_NOTICE);
		    		
		    		if ($Registry->GetManifest()->GetSectionConfig()->idn)
			        {
			        	$allowed_utf8_chars = $Registry->GetManifest()->GetSectionConfig()->idn->xpath("//allowed-utf8-chars");
				    	$disallowed_utf8_chars = $Registry->GetManifest()->GetSectionConfig()->idn->xpath("//disallowed-utf8-chars");
				    	
				    	$allowed_chars = (string)$allowed_utf8_chars[0];
				    	$disallowed_chars = (string)$disallowed_utf8_chars[0];
			        }
			    
					$Validator = new Validator();
			    	if (!$Validator->IsDomain("{$domain_name}.{$_SESSION["BT_TLD"]}", null, null, $allowed_chars, $disallowed_chars))
			    	{
			    		$res_fail[$domain_name] = _("Domain name contains non-supported characters");
			    	}
		        	else 
		        	{
		        		$DbDomain = DBDomain::GetInstance();
		        		
		        		// Inst domain object
						$Domain = $Registry->NewDomainInstance();
						$Domain->Name = $domain_name;
		        		
						// Check that domain can be transferred
		        		if ($DbDomain->ActiveDomainExists($Domain))
		        		{
							$res_fail[$domain_name] = _("Sorry, this domain can not be transferred");
		        		}

           				// If no errors - start transfer process		        		
            			if ($res_fail[$domain_name] === null)
            			{
            				Log::Log("Update domain status to awaitingPayment", E_USER_NOTICE);
            				
            				// Configure and save domain in Db
            				$Domain->UserID = $Order->UserID;
            				$Domain->Status = DOMAIN_STATUS::AWAITING_PAYMENT;
            				$Domain->IncompleteOrderOperation = INCOMPLETE_OPERATION::DOMAIN_TRANSFER;
            				$DbDomain->Save($Domain);

            				// Checkout
            				Log::Log("Issue invoice", E_USER_NOTICE);
            				$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_TRANSFER, $Domain->ID, $Order->UserID);
            				$Invoice->Description = sprintf(_("%s domain name transfer"), $Domain->GetHostName());
            				
            				// Add invoice to order
            				$Order->AddInvoice($Invoice);            				
            				
            				// Mmm okay
            				$res_ok[$domain_name] = true;
						}
					}
				}
			}
		
			// If order has invoices, save it and issue invoices
			if (count($Order->GetInvoiceList()))
			{
				Log::Log("Save order", E_USER_NOTICE);
				$Order->Save();
				
				$invoices_paid = true;
				foreach ($Order->GetInvoiceList() as $_Invoice)
					$invoices_paid &= $_Invoice->Status == INVOICE_STATUS::PAID;
				
				if ($Order->GetTotal() == 0 || $invoices_paid)
				{
					UI::Redirect("order_info.php?orderid={$Order->ID}");
				}
				else
				{
					$_SESSION['orderid'] = $Order->ID;
					UI::Redirect("checkout.php");
				}
			}
        }
    }
	
	$display["attr"] = $_POST;
	
	if (!$stepno)
	   $stepno = 1;
		   
	//
	// get TLD Prices
	//
	if ($stepno == 1)
	{
    	$display["transferTLDs"] = array();
    	foreach ($TLDs as $k=>$v)
    	{
    		try
			{
            	$Registry = $RegistryModuleFactory->GetRegistryByExtension($v);
            	if (!$Registry)
            		$errmsg = sprintf(_("Registry module not defined for %s domain extension"), $v);
                }
			catch(Exception $e)
			{
				$errmsg = $e->getMessage();
			}
			if ($Registry)
    			$allow = (int)$Registry->GetManifest()->GetSectionConfig()->domain->transfer->allow_bulk_transfer;
    			
    		if ($allow)
    		  array_push($display["transferTLDs"], $v);
    	}
    	
    	$display["num_TLDs"] = count($display["transferTLDs"]);
	}
    elseif ($stepno == 2) 
    {
        
    }
    elseif ($stepno == 3)
    {
		$_SESSION["JS_SESSIONID"] = $display["JS_SESSIONID"] = verifyCode();
		require_once SRC_PATH . "/LibWebta/library/Data/JSON/JSON.php";
		$json = new Services_JSON();
		
		$tld = $_SESSION["BT_TLD"];
		$domains = array();
		foreach ($domain_names as $domainname)
		{
			$domains[] = "$domainname.$tld";
		}
		$display['domains'] = $json->encode($domains);
    }
	elseif ($stepno == 4) 
    {
        $display["res_ok"] = $res_ok;    	
        $display["res_fail"] = $res_fail;
        $display["BT_TLD"] = $_SESSION["BT_TLD"];
    }
    
    $display["stepno"] = $stepno;
    $template_name = "client/bulk_transfer_step{$stepno}";
	
	include_once("src/append.inc.php");
	
	function verifyCode ()
	{
		return md5(mt_rand().microtime(true));
	}	
?>