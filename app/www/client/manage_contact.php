<? 
	require_once('src/prepend.inc.php');
	
	// Get Domain object
	if (isset($req_domainid) && $req_domainid > 0)
		require_once("src/set_managed_domain.php");
	else
    	$Domain = DBDomain::GetInstance()->Load($_SESSION["selected_domain"]);
   
    // Check UserID for Domain
	if ($Domain->UserID != $_SESSION["userid"])
    	$errmsg = _("You don't have permissions to manage this contact");
	
    // Check domain status (We can manage only Delegated domains)
    if ($Domain->Status != DOMAIN_STATUS::DELEGATED)
		$errmsg = _("Domain status prohibits operation");
		
    // if error Redirect to domains_view page
	if ($errmsg)
		CoreUtils::Redirect ("domains_view.php");
	
	// Get User info
	$user = $db->GetRow("SELECT * FROM users WHERE id=?", array($Domain->UserID));	
	
	// Get contact information from database
	
	$Contact = $Domain->GetContact($req_c);
		
	// Create Module object
	try
	{
		$Registry = $RegistryModuleFactory->GetRegistryByExtension($Domain->Extension);
		if ($Registry)
			$Manifest = $Registry->GetManifest(); 
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();		
	}
		
	$RegistryContacts = UI::GetContactsListForSmarty($Registry->GetManifest()->GetSectionConfig());
		
	foreach($RegistryContacts as $k=>$v)
		if ($v["type"] == $req_c)
		{
			$cinfo = $v;
			break;
		}

	$display["title"] = sprintf(_("Edit %s contact for %s"), strtolower($cinfo["name"]), $Domain->GetHostName());
	
	$section_config = $Registry->GetManifest()->GetSectionConfig();
	$res = $section_config->xpath("contacts/contact[@type='{$req_c}']");
	
	$display["is_optional"] = !(bool)(int)$res[0]->attributes()->required;
	
	$display["change_allowed"] = (bool)(int)$Manifest->GetRegistryOptions()->ability->change_domain_contact;
	$display["edit_allowed"] = (bool)(int)$Manifest->GetRegistryOptions()->ability->update_contact 
			&& $_SESSION["userid"] == $Contact->UserID;

	$display["Extension"] = $Domain->Extension;
	$display["groupname"] = $Manifest->GetGroupNameByContactType($req_c);
	$display["type_name"] = $cinfo["name"];
	$display["type"] = $req_c;
	$display["Domain"] = $Domain;
	$display["Contact"] = $Contact;
	
	if ($Contact && $Contact->UserID != $_SESSION['userid'])
		$display["warn"] = "You cannot modify this contact because it is being controlled by another registrant.";
	
	
	if ($Contact)
		$curr_clid = $Contact->CLID;
	else
		$curr_clid = "";
	
	foreach ($Domain->GetContactList() as $dc)
	{
		$c[] = $dc->CLID;
	}
		

	
	
	// Contact change policy
	if ($Contact)
	{
		$Conf = $Contact->GetConfig();
		if ((int)$Conf->attributes()->change_approval)
		{
			$policy_id = $Conf->attributes()->policy;
			$RegOptions = $Manifest->GetRegistryOptions();
			$Policy = $RegOptions->xpath("policy[@id='$policy_id']");
			$display["contact_policy"] = count($Policy) ? (string)$Policy[0] : null;			
		}
	}

	$group_name = $Manifest->GetGroupNameByContactType($req_c);
		
	$section_shared_contacts = (bool)$Manifest->GetRegistryOptions()->ability->section_shared_contacts;	
    if (!$section_shared_contacts)
    {
        $section_name = $Manifest->GetSectionName();
		$target_index = $Manifest->GetContactTargetIndex($Domain->Extension, $group_name);
    }
    else
    {
        $section_name = "";
        $target_index = 0;
    }
	
	$contact_count = $db->GetOne("
		SELECT COUNT(*) FROM contacts 
		WHERE 
			(userid=? OR (clid='{$c[0]}' OR clid='{$c[1]}' OR clid='{$c[2]}' OR clid='{$c[3]}')) AND 
			groupname=?	AND 
			(TLD = ? OR (module_name=? AND section_name=? AND target_index=?))  
		ORDER BY IF(clid = ?, 1, 0) DESC",
		array(
			$_SESSION['userid'], $group_name, 
			$Domain->Extension, $Registry->GetModuleName(), $section_name, $target_index,
			$curr_clid
		)	
	);
	if ($contact_count < 25)
	{
		$contacts = $db->GetAll("
			SELECT * FROM contacts 
			WHERE 
				(userid=? OR (clid='{$c[0]}' OR clid='{$c[1]}' OR clid='{$c[2]}' OR clid='{$c[3]}')) AND 
				groupname=? AND 
				(TLD = ? OR (module_name=? AND section_name=? AND target_index=?)) 
			ORDER BY IF(clid = ?, 1, 0) DESC",
			array(
				$_SESSION['userid'], $group_name, 
				$Domain->Extension, $Registry->GetModuleName(), $section_name, $target_index, 
				$curr_clid
			)
		);
		
		foreach ($contacts as &$c)
		{
			try
			{
				$Cont = DBContact::GetInstance()->LoadByCLID($c["clid"], $Manifest);
				$c['title'] = $Cont->GetTitle();
				
				unset($Cont);
				$display['contacts'][] = $c;
			}
			catch (Exception $e) 
			{
			}
		}
	}
	else
	{
		$display['too_many_items'] = true;
	}
	
	if ($Domain->HasPendingOperation(Registry::OP_TRADE) || 
		$Domain->HasPendingOperation(Registry::OP_UPDATE) || 
		$Domain->HasPendingOperation(Registry::OP_UPDATE_APPROVE))
	{
		$display["disable_change"] = true;
		$display["warn"] = sprintf(_("This contact cannot be edited now, because there is a pending operation on %s. You will be notified by email once it has been processed."), 
			$Domain->GetHostName());
	}	
	
	require("src/append.inc.php");
?>