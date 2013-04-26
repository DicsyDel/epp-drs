<?php 
    
    require_once('src/prepend.inc.php');
    
	// Add
	if ($_POST)
	{
	    $domains = explode(PHP_EOL, $_POST["domains"]);
		
		foreach((array)$domains as $k=>$domain)
		{
			$parts = explode(",", trim($domain));
			
			list($domain_name, $TLD) = explode(".", $parts[0], 2);
			
			$domain_params = array();
			for ($i=1; $i<count($parts); $i++)
			{
				list($k,$v) = explode("=", $parts[$i]);
				$domain_params[$k] = $v;
			}
			
			// Use utf8 compatible string object 
			$domain_name = String::Fly($domain_name)->ToLower();
			$TLD = String::Fly($TLD)->ToLower();
			$num_errors_old = count($err); 			

			if (strlen($domain_name) < 2 || !$TLD)
			{
				$err[] = sprintf(_("'%s' is not valid domain name"), $domain);
				continue;
			}
				
			try
			{
				$Registry = $RegistryModuleFactory->GetRegistryByExtension($TLD);
			}
			catch(Exception $e)
			{
				$err[] = $e->getMessage();
				continue;
			}
			
			if ($Registry)
			{
				$Domain = $Registry->NewDomainInstance();
				$Domain->Name = $domain_name;

				foreach ($domain_params as $k => $v)
				{
					if ($k == "pw")
						$Domain->AuthCode = $v;
					else
						$Domain->SetExtraField($k, $v);
				}
			
				if (!DBDomain::ActiveDomainExists($Domain))
				{						
					try
					{
						$Domain = $Registry->GetRemoteDomain($Domain);
						$RegistryContacts = UI::GetContactsListForSmarty($Registry->GetManifest()->GetSectionConfig());
					}
					catch(Exception $e)
					{
						$err[] = sprintf("%s: %s", $Domain->GetHostName(), $e->getMessage());
					}
					
					if ($Domain->RemoteCLID)
					{
					    if ($Domain->RemoteCLID == $Registry->GetRegistrarID() || $Domain->AuthCode != '')
    					{
    						$contacts_list = $Domain->GetContactList();
    						// Apply contacts to domain owner
    						foreach ($contacts_list as $Contact)
							{
								$Contact->UserID = $post_userid;
							}    						
    						
    						if (count($err) == $num_errors_old)
    						{
    							$period = date("Y", $Domain->ExpireDate)-date("Y", $Domain->CreateDate);
    							$Domain->Status = DOMAIN_STATUS::DELEGATED;
    							$Domain->UserID = $post_userid;
    							
    							try
    							{
    								DBDomain::GetInstance()->Save($Domain);
    								
    								
									// http://bugzilla.webta.net/show_bug.cgi?id=391
									// Prevent domain expiration
									$Config = $Registry->GetManifest()->GetSectionConfig();
									$days = $Config->domain->xpath("//renewal/notifications/period");
									$last_notification_period = (int)end($days);
									
									$last_renewal_date = $Domain->RenewalDate ? $Domain->RenewalDate : $Domain->ExpireDate;
									$days_before_expire = (int)ceil(($last_renewal_date - time())/86400);
									Log::Log("Last notification period: " . $last_notification_period, E_USER_NOTICE);
									if ($days_before_expire <= $last_notification_period) {
										if ($days_before_expire < 0) {
											// Today will expire... OMG!
											$days_before_expire = 0;
										}
										
										// Set renew period
										$period = (int)$Config->domain->renewal->min_period;
										$Domain->Period = $period;
										
										// Copypasta from class.RenewProcess.php
										// Issue invoice for renew and send notification to client.
										$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_RENEW, $Domain, $Domain->UserID);
										$Invoice->Description = sprintf(_("%s domain name renewal for %s years"), $Domain->GetHostName(), $period);
										$Invoice->Save();
										
										if ($Invoice->Status == INVOICE_STATUS::PENDING)
										{
											$userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($Domain->UserID));
						
											$args = array(
												"domain_name"	=> $Domain->Name, 
												"extension"		=> $Domain->Extension,
												"invoice"		=> $Invoice,
												"expDays"		=> $days_before_expire,
												"client"		=> $userinfo,
												"renewal_date"  => $Domain->RenewalDate
											);
											mailer_send("renew_notice.eml", $args, $userinfo["email"], $userinfo["name"]);
						
											Application::FireEvent('DomainAboutToExpire', $Domain, $days_before_expire);
										}
									}    								
    								
    							}
    							catch(Exception $e)
    							{
    								$err[] = sprintf("%s: %s", $Domain->GetHostName(), $e->getMessage());
    							}
    						}
    					}
    					else
    						$err[] = sprintf(_("'%s' cannot be imported because it does not belong to the current registar."), $Domain->GetHostName());	
					}
					else
						$err[] = sprintf(_("'%s' has no RemoteCLID, we cannot check that domain belongs to current registrar."), $Domain->GetHostName());
				}
				else
					$err[] = sprintf(_("Domain '%s' already exists in our database."), $Domain->GetHostName());
			}
		}
		
		if (sizeof($err) == 0)
		{
			$okmsg = "All domains imported successfully";
			CoreUtils::Redirect("domains_view.php");
		}
	}

	$display["attr"] = $_POST;
	$display["users"] = $db->GetAll("SELECT * FROM users ORDER BY login");
	
	
	$display["help"] = sprintf(_("This form will import existing domains from registry that were not created through %s. It will try to add all domains, one-by-one to %s database, assigning them to selected client."), CONFIG::$COMPANY_NAME, CONFIG::$COMPANY_NAME);
	
	include_once("src/append.inc.php");
?>
