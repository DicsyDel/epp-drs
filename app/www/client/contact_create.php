<?php
	require_once('src/prepend.inc.php');
    		
	$display["TLDs"] = $TLDs;

	if ($req_TLD)
	{
		try
	    {
			$Registry = $RegistryModuleFactory->GetRegistryByExtension($req_TLD);
			if ($Registry)
				$Manifest = $Registry->GetManifest();
	    }
	    catch(Exception $e)
	    {
	    	$err[] = $e->getMessage();
	    }
	    
		if ($Registry)
	    {	
            // Get contact type groups from module manifest
            $groups = array();
            $XmlGroups = $Manifest->GetSectionConfig()->xpath('contact_groups/group');
            foreach ($XmlGroups as $XmlGroup)
            {
            	$groups[(string)$XmlGroup->attributes()->name] = _($XmlGroup->attributes()->title);
            }
            $display['groups'] = $groups;
            
            // Choose contact group 
            if (!$req_group)
            	$req_group = key($groups);
            
            // Get contact fields
            $display["fields"] = $Manifest->GetContactFormSchema(null, $req_group);
            
            // Make all fields editable
            foreach ($display["fields"] as &$field)
            {
	           	$field["iseditable"] = 1;
            }
	           
    	
	        if ($_POST["step"] == 3)
	        {
            	$display["contactinfo"] = $_POST;
	        }
	        else
	        {
				$no_one = $db->GetOne("
					SELECT COUNT(*) FROM contacts 
					WHERE userid=? AND groupname=? AND 
					(TLD=? OR (module_name=? AND section_name=? AND target_index=?))
				", array(
					$_SESSION['userid'], 
					$req_group, 
					$req_TLD,
					$Registry->GetModuleName(),
					$Manifest->GetSectionName(),
					$Manifest->GetContactTargetIndex($req_TLD, $req_group)
				)) == 0;
				
				
	        	
	        	if ($no_one && (int)$Client->GetSettingValue(ClientSettings::PREFILL_CONTACT))
	            {
		            $display["contactinfo"] = $db->GetRow("SELECT * FROM users WHERE id='{$_SESSION['userid']}'");
		            $display["contactinfo"]["street1"] = $display["contactinfo"]["address"];
		            $display["contactinfo"]["cc"] = $display["contactinfo"]["country"];
		            $display["contactinfo"]["sp"] = $display["contactinfo"]["state"];
		            $display["contactinfo"]["pc"] = $display["contactinfo"]["zipcode"];
		            $display["contactinfo"]["street2"] = $display["contactinfo"]["address2"];
		            $display["contactinfo"]["voice_display"] = $display["contactinfo"]["phone"];
		            $display["contactinfo"]["fax_display"] = $display["contactinfo"]["fax"];		            
		            $display["contactinfo"]["password"] = "";
	            }     
	        }
    	    	
            $template_name = "client/contact_create_step2";
            
            $display["TLD"] = $req_TLD;
            $display["group"] = $req_group;
	    }
	}
	
	if ($post_step == 3)
	{
		try
	    {
			$Registry = $RegistryModuleFactory->GetRegistryByExtension($req_TLD);
	    }
	    catch(Exception $e)
	    {
	    	$err[] = $e->getMessage();
	    }
	    
	    if ($Registry)
	    {
//            $Manifest = $Registry->GetManifest();
//	    	$RegistryContacts = UI::GetContactsListForSmarty($Manifest->GetSectionConfig());
            	        
	        $fields = $_POST;
            		
//			$contactsconfig = $Manifest->GetSectionConfig()->xpath("//contact[@type = '{$req_type}']");
//			$contactsconfig = $contactsconfig[0];
	            
            $Contact = $Registry->NewContactInstanceByGroup($req_group);
            $Contact->ParentCLID = $req_parentCLID;
            
	    	try
            {
            	$Contact->SetFieldList($fields);
            }
            catch(ErrorList $e)
            {
            	$err = $e->getAllMessages();
            }
            
            if (!$err)
            {
				$Contact->UserID = $_SESSION['userid'];

				try
				{
	   				// Get contact change policy
					$ContactConfig = $Contact->GetConfig();
					$need_approval = $ContactConfig->policy->approveChangesPolicy 
							&& $ContactConfig->policy->approveChangesPolicy->getName(); 
					if ($need_approval)
					{
						DBContact::GetInstance()->Save($Contact);						
						$Registry->AddPendingOperation($Contact, Registry::OP_CREATE_APPROVE);
						EmailToRegistrantObserver::OnNewChangeContactRequest($Contact, Registry::OP_CREATE_APPROVE);
						
						$okmsg = _("Contact will be created after administrator approval");
						CoreUtils::Redirect("change_contact_policy.php?contactid=".$Contact->ID."&next_url=".urlencode("contacts_view.php"));
					}
					else
					{
						$Registry->CreateContact($Contact);
		            	$okmsg = _("Contact successfully created.");
						CoreUtils::Redirect("contacts_view.php");
					}
				}
				catch (Exception $e)
				{
                	$err[] = $e->getMessage();					
				}
            }
            
            $template_name = "client/contact_create_step2";
            $display["contactinfo"] = $_POST;
	    }
	    
	    $display["TLD"] = $post_TLD;
	}
	
	if (!$template_name)
		$template_name = "client/contact_create_step1";
	   
	$display["help"] = _("This page lets you create a new contact for particular domain extension. Contacts are unique per domain extension. There are different types of contacts. Most common are Registrant, Billing and Technical. Some domain extension require other contact types to register domain.");
	   
	require("src/append.inc.php");
?>
