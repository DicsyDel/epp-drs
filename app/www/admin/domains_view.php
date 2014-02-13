<?php 

	require_once('src/prepend.inc.php');
	
	// Delete domain
	if (isset($get_task))
	{
		try {
			$Domain = DBDomain::GetInstance()->Load($get_domainid);
		}
		catch(Exception $e) {
			$errmsg = $e->getMessage();
		}
		
		if ($Domain)
		{
			try {
				$Registry = $RegistryModuleFactory->GetRegistryByExtension($Domain->Extension);
		    }
		    catch(Exception $e) {
		    	$errmsg = sprintf(_("Cannot delete domain name. Reason: %s"),$e->getMessage());
		    }
		}
		
		if ($Domain->Name && $Registry)
		{
			switch($get_task)
			{
				case "delete":
					
		    		try
		    		{
			    		$res = $Registry->DeleteDomain($Domain);
		    		}
		    		catch(Exception $e)
		    		{
		    			$errmsg = sprintf(_("Cannot delete domain name. Reason: %s"),$e->getMessage());
		    		}
		    			
				    if ($res)
				    {
					    $okmsg = _("Domain successfully deleted.");
			    		CoreUtils::Redirect("domains_view.php");
				    }
					
					break;
									
				case "send":
					
					$invoiceid = $db->GetOne("SELECT id FROM invoices 
						WHERE userid=? AND itemid=? AND purpose=? AND status=?", 
						array($Domain->UserID, $Domain->ID, INVOICE_PURPOSE::DOMAIN_RENEW, INVOICE_STATUS::PENDING)
					);
					if (!$invoiceid)
					{	
						$period = $Registry->GetManifest()->GetSectionConfig()->domain->renewal->min_period;
						$Domain->Period = $period;
						DBDomain::GetInstance()->Save($Domain);
						 
						try
						{
							$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_RENEW, $Domain->ID, $Domain->UserID);
							$Invoice->Description = sprintf(_("%s domain name renewal for %s years"), $Domain->GetHostName(), $period);
							$Invoice->Save();
							
							$invoiceid = $Invoice->ID;			
						}
						catch(Exception $e)
						{
							$errmsg = $e->getMessage();
						}
					}// if check inf
					
					if ($invoiceid)
					{
						$userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($Domain->UserID));
						$args = array(
							"domain_name"	=> $Domain->Name, 
							"extension"		=> $Domain->Extension,
							"Invoice"		=> Invoice::Load($invoiceid),
							"expDays"		=> $Domain->DaysBeforeExpiration,
							"client"		=> $userinfo
						);
						mailer_send("renew_notice.eml", $args, $userinfo["email"], $userinfo["name"]);
						$okmsg = _("Notification successfully sent");
						CoreUtils::Redirect("domains_view.php");
					}
					
					break;
			}
		}
	}	


	$display["title"] = _("Domains &nbsp;&raquo;&nbsp; View");
	$display["help"] = _("&bull;&nbsp; You can learn about possible values of status field <a target='blank' href='http://webta.net/docs/wiki/domain.status.codes'>here</a>.<br/> &bull;&nbsp;When domain is locked, domain cannot be transferred.<br/> &bull;&nbsp;Domain password is commonly used in transfer procedure.");
	
	// Ajax view
	$display["load_extjs"] = true;
	
	require_once ("src/append.inc.php");
?>
