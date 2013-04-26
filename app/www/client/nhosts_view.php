<?
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
		
	// Get User info
	$user = $db->GetRow("SELECT * FROM users WHERE id=?", array($Domain->UserID));
	$DBNSHost = DBNameserverHost::GetInstance();

	if ($_POST)
	{
		$Validator = new Validator();
		$nshost_list = $DBNSHost->LoadList($Domain->ID);		
		
		
		// if add new host
		if ($post_add)
		{
			// Check host in database
			$chk = $db->GetRow("
				SELECT * 
				FROM nhosts 
				WHERE hostname=? AND domainid=?", 
				array($_POST["ns_add"], $_SESSION["selected_domain"])
			);
			
			if (!$chk)
			{
				if (!$Validator->IsIPAddress($post_ip_add))
					$err[] = _("IP address incorrect");
				else 
				{
					$nshost = new NameserverHost("{$post_ns_add}.{$Domain->GetHostName()}", $post_ip_add);
					
					try
					{
						$add = (bool)$Registry->CreateNameserverHost($nshost);
					}
					catch (ObjectExistsException $e)
					{
						// do nothing
						$add = true;
					}
					catch (Exception $e)
					{
						$errmsg = $e->getMessage();
					}
					
    				if ($add)
    				{
    					$nshost_list[] = $nshost;
    					
    					$DBNSHost->SaveList($nshost_list, $Domain->ID);
    					
    					// redirect
    					$okmsg = _("Nameserver host created successfully.");
    					CoreUtils::Redirect("nhosts_view.php");
    				}
				}
			}
			else
				$errmsg = _("Nameserver host already exists.");
		}
		
		// delete host
		elseif($post_delete)
		{
			foreach ($nshost_list as $index => $nshost)
				if ($nshost->ID == $post_nid)
					break;
			if ($nshost->ID == $post_nid)
			{
				try
				{
					$del = $Registry->DeleteNameserverHost($nshost);
				}
				catch(Exception $e)
				{
					$errmsg = $e->getMessage();
				}

				if ($del)
				{
					unset($nshost_list[$index]);
					
					
					$DBNSHost->SaveList($nshost_list, $Domain->ID);
					
					// redirect
					$okmsg = _("Nameserver host deleted successfully");
					CoreUtils::Redirect("nhosts_view.php");
				}
			}
		}
		
		// modify host
		elseif($post_modify)
		{
			foreach ($nshost_list as $index => $nshost)
				if ($nshost->ID == $post_nid)
					break;
					
			if ($nshost->ID == $post_nid)
			{
				try
				{
					$nshost->IPAddr = $post_ip;
					$upd = $Registry->UpdateNameserverHost($nshost);
				}
				catch(Exception $e)
				{
					$errmsg = $e->getMessage();
				}
				    					
				if ($upd)
				{
					$nshost_list[$index] = $nshost;
					$DBNSHost->SaveList($nshost_list, $Domain->ID);
					
					// redirect
					$okmsg = _("Nameserver host updated successfully");
					CoreUtils::Redirect("nhosts_view.php");
				}
			}
		}
	}
	
	
	// Get hosts from database
	$display["nhosts"] = $db->GetAll("SELECT * FROM nhosts WHERE domainid=?", array($_SESSION['selected_domain']));
	
	foreach ((array)$display["nhosts"] as $k=>$v)
	{	
		$nshost = "{$v['hostname']}.{$Domain->Name}.{$Domain->Extension}";
		
		$chk = $db->GetOne("SELECT * FROM domains WHERE 
						ns1='{$nshost}' OR 
						ns2='{$nshost}' OR 
						ns_n LIKE '{$nshost}' OR
						ns_n LIKE '%;{$nshost};%' OR
						ns_n LIKE '{$nshost};%' OR
						ns_n LIKE '%;{$nshost}'
						");
		
		if($chk > 0)
			$display["nhosts"][$k]["isused"] = true;
	}
	
	$display["title"] = "<a href=\"domains_view.php?id={$Domain->ID}\">{$_SESSION['domain']}.{$_SESSION['TLD']}</a> &nbsp;&raquo;&nbsp;"._("Manage nameserver hosts");
	$display["dsb"] = ($Domain->Status != DOMAIN_STATUS::DELEGATED) ? "disabled" : "";
	$display["dsb2"] = ((int)$Registry->GetManifest()->GetRegistryOptions()->host_objects->is_managed == 0) ? "disabled" : "";
	$display["help"] = _("This page allows you to create new name servers based on your domain");
	$display["Domain"] = $Domain;
	$display["noheader"] = true;
	
	require_once ("src/append.inc.php");
?>