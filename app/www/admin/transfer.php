<?

	require_once('src/prepend.inc.php'); 

	if ($post_action == "transfer")
	{
		if (sizeof($post_domains) > 0)
		{
			// Get user information
			$userinfo = $db->GetRow("SELECT * FROM users WHERE id='{$post_n_userid}'");
			if ($userinfo)
			{
				foreach ($post_domains as $k=>$id)
				{
					// Get domain info
					$d_info = $db->GetRow("SELECT * FROM domains WHERE id='{$id}'");
					
					$purposes = implode(',', array($db->qstr(INVOICE_PURPOSE::DOMAIN_CREATE), 
						$db->qstr(INVOICE_PURPOSE::DOMAIN_RENEW),
						$db->qstr(INVOICE_PURPOSE::DOMAIN_TRANSFER),
						$db->qstr(INVOICE_PURPOSE::DOMAIN_TRADE)
					));
					
					// update domain
					$db->Execute("UPDATE domains SET userid='{$userinfo['id']}' WHERE id='{$d_info['id']}'");
					$db->Execute("UPDATE invoices SET userid='{$userinfo['id']}' WHERE itemid='{$d_info['id']}' AND purpose IN ({$purposes})");
					
					// Update registrant contact
					if ($d_info["c_registrant"] != '')
						$db->Execute("UPDATE contacts 
										SET 
											userid='{$userinfo['id']}' 
										WHERE clid='{$d_info['c_registrant']}'
									");
					
					// Update admin contact
					if ($d_info["c_admin"] != '')
						$db->Execute("UPDATE contacts 
										SET userid='{$userinfo['id']}' 
										WHERE clid='{$d_info['c_admin']}'
									");
					
					// update billing contact
					if ($d_info["c_billing"] != '')
						$db->Execute("UPDATE contacts 
										SET userid='{$userinfo['id']}' 
										WHERE clid='{$d_info['c_billing']}'
									");
									
					// Update technical conatct
					if ($d_info["c_tech"] != '')
						$db->Execute("UPDATE contacts 
										SET userid='{$userinfo['id']}' 
										WHERE clid='{$d_info['c_tech']}'
									");
				}
				
				// redirect
				$okmsg = "Domains successfully transferred to another client";
				CoreUtils::Redirect("domains_view.php");
			}
			else
				$err[] = "Invalid client!";
		}
		else
		{
			$okmsg = _("Please select one or more domains");
			CoreUtils::Redirect("domains_view.php");
		}
	}

	if (sizeof($post_id) == 0 && $post_action != 'transfer')
	{
		$okmsg = _("Please select one or more domains");
		CoreUtils::Redirect("domains_view.php");
	}

	$display["title"] = "Domains view &nbsp &raquo; &nbsp; Change owner";
	
	foreach ($post_id as $domain)
	{
		$domaininfo = $db->GetRow("SELECT * FROM domains WHERE id='{$domain}'");
		$display["domains"][] = array(
			"id"	=> $domain, 
			"name"	=> $domaininfo["name"].".".$domaininfo["TLD"]
		);
	}
	
	$display["users"] = $db->GetAll("SELECT * FROM users ORDER BY login");
	
	require_once ("src/append.inc.php");
?>
