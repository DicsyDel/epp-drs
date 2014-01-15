<?php
	require_once('src/prepend.inc.php');
	
	if ((int)$req_id > 0)
        $contactid = (int)$req_id;

	// Get User info
	$user = $db->GetRow("SELECT * FROM users WHERE id=?", array($_SESSION['userid']));	

	// Load contact from DB
	$DbContact = DBContact::GetInstance();
	try
	{
		$Contact = $DbContact->Load($contactid);
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
	}
    if (!$Contact)
        CoreUtils::Redirect("contacts_view.php");

    
	// Get contact registry module 
	try
	{
		$Registry = $Contact->ModuleName ? 
				$RegistryModuleFactory->GetRegistryByName($Contact->ModuleName) :
				$RegistryModuleFactory->GetRegistryByExtension($Contact->Extension);
		$Manifest = $Registry->GetManifest();
	}
	catch (Exception $e)
	{
		$errmsg = $e->getMessage();
		CoreUtils::Redirect ("contacts_view.php");
	}

   
	if ($_POST)
	{
		if ($Contact->UserID != $_SESSION["userid"])
		{
			$errmsg = _("You don't have permissions to manage this contact");
			CoreUtils::Redirect ("contacts_view.php");
		}
			
		// if contact found
		if ($Contact)
		{
			// Set fields
	        try
	        {
				$Contact->SetFieldList($_POST);
	        }
	        catch(ErrorList $e)
	        {
	        	$err = $e->getAllMessages();
	        }
			
	        // Set discloses
	        try
	        {
	        	foreach ($Contact->GetDiscloseList() as $dname=>$dvalue)
	        		$Contact->SetDiscloseValue($dname, $_POST["disclose"][$dname]);
	        }
	        catch(Exception $e)
	        {
	        	$err[] = $e->getMessage();
	        }
	        	        
	        
			if (!$err)
			{
    			if ($Domain)
					$Contact->ExtraData = array("domainname" => $Domain->Name);
					
				try
				{
					// Get contact change policy
					$ContactConfig = $Contact->GetConfig();
					$need_approval = $ContactConfig->policy->approveChangesPolicy 
							&& $ContactConfig->policy->approveChangesPolicy->getName();
					if ($need_approval)
					{
						$DbContact->Save($Contact);						
						$Registry->AddPendingOperation($Contact, Registry::OP_UPDATE_APPROVE);
						EmailToRegistrantObserver::OnNewChangeContactRequest($Contact, Registry::OP_UPDATE_APPROVE);

						$okmsg = _("Contact will be changed after administrator approval");
						CoreUtils::Redirect("change_contact_policy.php?contactid=".$Contact->ID."&next_url=".urlencode("contacts_view.php"));
					}
					else
					{
						$Registry->UpdateContact($Contact);
						$okmsg = _("Contact successfully updated");
		    			CoreUtils::Redirect("contacts_view.php");
					}
				}
				catch (Exception $e)
				{
					$err[] = $e->getMessage();
				}
			}
		}
	}
	
	$display["dsb"] = ($Contact->UserID != $_SESSION['userid']) ? "disabled" : "";
	if ($display["dsb"])
		$display["warn"] = "You cannot modify this contact because it is being controlled by another registrant.";
	if ($Contact->HasPendingOperation(Registry::OP_CREATE_APPROVE) 
		|| $Contact->HasPendingOperation(Registry::OP_UPDATE_APPROVE))
	{
		$display["disable_change"] = true;
		$display["warn"] = _("This contact cannot be edited now, because there is a pending operation on it. You will be notified by email once it has been processed."); 
	}
		
	
	$group = $Manifest->GetSectionConfig()->xpath("//contact_groups/group[@name='{$Contact->GroupName}']");
	if ((int)$group[0]->attributes()->assoc_with_domain == 1)
	    $errmsg = sprintf(_("Cannot directly edit '.%s' contact. Please edit contact using domain options."), $Contact->Extension);
	
	if ($errmsg)
		CoreUtils::Redirect("domains_view.php");
	
	$display["title"] = _("Edit contact");
	$contactinfo = $Contact->GetFieldList();
	$phone_fields = $Manifest->GetSectionConfig()->xpath("//contact_groups/group[@name='{$Contact->GroupName}']/fields/field[@type='phone']");
	if (count($phone_fields) > 0)
	{
		foreach($phone_fields as $pfield)
		{
			settype($pfield, "array");
			$finfo = $pfield["@attributes"];
			if ($contactinfo[$finfo["name"]])
				$contactinfo[$finfo["name"]] = $Contact->E164ToPhone($contactinfo[$finfo["name"]]);
		}
	}
	
	$display["contactinfo"] = $contactinfo;
	$display["contactinfo"]["type"] = $Contact->Type;
	$display["contactinfo"]["clid"] = $Contact->CLID;
	$display["contactinfo"]["extension"] = $Contact->Extension;
	
	$discloses = $Contact->GetDiscloseList();
	
	foreach($discloses as $dname=>$dvalue)
	{
		$descr =  $Contact->GetConfig()->xpath("disclose/option[@name='{$dname}']/@description");
		$display["disclose"][(string)$descr[0]->description] = array("name" => $dname, "value" => $dvalue);
	}	
	
	$display["fields"] = $Registry->GetManifest()->GetContactFormSchema(null, $Contact->GroupName);
	
	if ($_POST)
		$display["contactinfo"] = array_merge($_POST, $display["contactinfo"]);
	
	$display["id"] = $contactid;
	
	require("src/append.inc.php");
?>
