<?php
	require_once('src/prepend.inc.php');
    
	if (isset($req_domainid))
		require_once("src/set_managed_domain.php");
	else
		require_once("src/get_managed_domain_object.php");
	
	if ($Domain->Status != DOMAIN_STATUS::DELEGATED)
	{
		$errmsg = _("Domain status prohibits operation");
		CoreUtils::Redirect("domains_view.php");
	}
		
	if ($_POST)
	{
		if ($post_task == "mdns")
		{
			if ($post_enable_managed_dns == 1 && $Domain->IsManagedDNSEnabled == 0)
			{
				$list = $Domain->GetNameserverList();
				$chlist = new Changelist($list);
				
				// Set new Nameserver
				$chlist->Add(new Nameserver(CONFIG::$NS1));
				$chlist->Add(new Nameserver(CONFIG::$NS2));
				
				$deletens = array();
				foreach ($list as $ns)
					if ($ns->HostName != CONFIG::$NS1 && $ns->HostName != CONFIG::$NS2)
						$chlist->Remove($ns);

				if (!$chlist->GetAdded() && !$chlist->GetRemoved())
					$upd = true;
				else
				{
					try
					{
						$upd = $Registry->UpdateDomainNameservers($Domain, $chlist);
					}
					catch(Exception $e)
					{
						$errmsg = $e->GetMessage();
					}
				}
				
				if ($upd)
				{
					$userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($_SESSION['userid']));
					$SOA_owner = str_replace("@", ".", $userinfo["email"]);
					
					$db->Execute("REPLACE INTO zones (	`zone`, 
														`soa_owner`, 
														`soa_ttl`, 
														`soa_parent`, 
														`soa_serial`, 
														`soa_refresh`, 
														`soa_retry`, 
														`soa_expire`, 
														`min_ttl`, 
														`isupdated`
													)
		    								 VALUES (?,?,'14400',?,?,'14400','7200','3600000','86400',0)", 
							array(	$Domain->GetHostName(), 
									$SOA_owner, 
									CONFIG::$NS1, 
									date("Ymd")."01")
								 );
								 
					$zoneid = $db->Insert_ID();
					$db->Execute("INSERT INTO records (id, zoneid, rtype, ttl, rpriority, rvalue, rkey) 
								  SELECT null, '{$zoneid}', rtype, ttl, rpriority, rvalue, rkey FROM records 
								  WHERE zoneid='0'
								 ");
					
					$db->Execute("UPDATE domains SET managed_dns='1' WHERE id=?", array($Domain->ID));
				}
			
				if (!$errmsg)
				{
					$okmsg = sprintf(_("Managed DNS enabled successfully for %s"), $Domain->GetHostName());
					CoreUtils::Redirect("ns.php");
				}
			}
			else if ($post_enable_managed_dns == 0 && $Domain->IsManagedDNSEnabled == 1)
			{
				// Disable managed DNS
				$db->Execute("UPDATE domains SET managed_dns='0' WHERE id=?", array($Domain->ID));
				$zone = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($Domain->GetHostName()));
				$db->Execute("UPDATE zones SET isdeleted='1' WHERE id=?", array($zone['id']));
				$db->Execute("DELETE FROM records WHERE zoneid=?", array($zone['id']));
				
				$okmsg = sprintf(_("Managed DNS disabled successfully for %s"), $Domain->GetHostName());
				CoreUtils::Redirect("ns.php");
			}
		}
		elseif ($post_task == "modify")
		{	
			$registryOptions = $Registry->GetManifest()->GetRegistryOptions();
			$host_as_attr = (bool)$registryOptions->ability->hostattr;
			
			$nslist = array();
			foreach ($post_ns as $k => $hostname)
			{
				if ($hostname && !in_array($hostname, (array)$post_delete))
				{
					if ($host_as_attr && FQDN::IsSubdomain($hostname, $Domain->GetHostName()))
					{
						$nslist[] = new NameserverHost($hostname, $post_ns_ip[$k]);
					}
					else
					{
						$nslist[] = new Nameserver($hostname);						
					}
				}
			}
			
			try
			{
				$Action = new UpdateDomainNameserversAction($Domain, $nslist);
				$result = $Action->Run($_SESSION['userid']);
				if ($result == UpdateDomainNameserversAction_Result::OK)
				{
					$okmsg = _("Nameserver list successfully updated");
				}
				else if ($result == UpdateDomainNameserversAction_Result::PENDING)
				{
					$okmsg = _("Nameservers change request has been sent to registry. You will be notified by email as soon as this operation will be completed.");					
				}
			}
			catch (UpdateDomainNameserversAction_Exception  $e)
			{
				if ($e->getCode() == UpdateDomainNameserversAction_Exception::NAMESERVERHOST_NOT_REGISTERED)
				{
					$errmsg = $e->getMessage() . " " . _("You can create it <a href='nhosts_view.php'>here</a>.");
				}
				else
				{
					$errmsg = $e->getMessage();
				}
			}
		}
	}

	$nameservers = $Domain->GetNameserverList();
	
	// Get ns IP's
	foreach ((array)$nameservers as $ns)
	{
		$isglue = preg_match("/^(.*)\.{$Domain->GetHostName()}$/", $ns->HostName, $matches);
		if ($isglue)
		{
			$nshostinfo = $db->GetRow(
				"SELECT * FROM nhosts WHERE hostname=? AND domainid=?", 
				array($matches[1], $Domain->ID)
			);
			$ip = $nshostinfo["ipaddr"]; 
		}
		else
			$ip = false;
		
		$display["nameservers"][] = array("name"=>$ns->HostName, "ip"=>$ip, "isglue" => $isglue);
	}
	
	$display["title"] = sprintf(_("<a href=\"domains_view.php?id=%s\">%s.%s</a> &nbsp;&raquo;&nbsp; Manage nameservers"), $_SESSION['selected_domain'], $_SESSION['domain'], $_SESSION['TLD']);
	
	if ($Domain->Status != DOMAIN_STATUS::DELEGATED || $Domain->HasPendingOperation(Registry::OP_UPDATE))
	{
		$display["dsb"] = "disabled";
		$display["disable_save_button"] = true;
		$display["warn"] = _("There are unapplied nameservers changes pending. You cannot edit nameservers until previous changes will be approved by registry.");
	}
	
	$display["Domain"] = $Domain;
	
	$display["host_as_attr"] = (int)$Manifest->GetRegistryOptions()->ability->hostattr;
	$display["max_ns"] = (int)$Manifest->GetRegistryOptions()->host_objects->max_ns;
	if ($display["max_ns"] == 0)
	   $display["max_ns"] = 99;
	 
	$display["help"] = _("This page allows you to set nameservers for your domain. Contact your hosting provider if you are not sure how to fill this form.");
	
	$display["enable_managed_dns"]	= ENABLE_EXTENSION::$MANAGED_DNS;
	
	if (ENABLE_EXTENSION::$MANAGED_DNS)
		$display["help"] .= sprintf(_("<br/>Managed DNS allows you to control your domain DNS zone in your registrant Control Panel. If you enable Managed DNS for this domain, it will use name servers of %s"), CONFIG::$COMPANY_NAME);

	require_once ("src/append.inc.php");
?>
