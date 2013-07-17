<?

	require_once('src/prepend.inc.php'); 

	if ($req_id)
	{
	    try
	    {
	    	$Contact = DBContact::GetInstance()->Load($req_id);
	    	if ($Contact->UserID != $_SESSION['userid'])
	    	{
	    		CoreUtils::Redirect("contacts_view.php");
	    	}
	    		
			$Registry = $Contact->Extension ? 
					$RegistryModuleFactory->GetRegistryByExtension($Contact->Extension) :
					$RegistryModuleFactory->GetRegistryByName($Contact->ModuleName);
	    }
	    catch(Exception $e)
	    {
	    	$errmsg = $e->getMessage();
			CoreUtils::Redirect("contacts_view.php");
	    }
	}
		
	
	if ($req_task == "delete" && $Contact)
	{
		$chk = $db->GetOne("SELECT COUNT(*) FROM domains 
            WHERE `c_registrant` = ? OR 
            `c_admin` = ? OR 
            `c_tech` = ? OR 
            `c_billing` = ?", 
           	array($Contact->CLID, $Contact->CLID, $Contact->CLID, $Contact->CLID)
		);
		if ($chk > 0)
		{
			$errmsg = _("Cannot delete contact. There are domains associated with this contact")."[<a href='domains_view.php?clid={$Contact->CLID}'>"._("View domains")."</a>].";
		}
		else 
		{
			if ((int)$Registry->GetManifest()->GetRegistryOptions()->ability->contact_removal == 1)
			{
				try
				{                        	
					//Send Delete Request
					$res = $Registry->DeleteContact($Contact);
				}
				catch (ObjectNotExistsException $e)
				{
					// Delete non existed contacts
					DBContact::GetInstance()->Delete($Contact);
				}
				catch(Exception $e2)
				{
					$errmsg = $e2->getMessage();
				}
    				  
				if ($res)
				{
					$okmsg = _("Contact successfully deleted");
					CoreUtils::Redirect("contacts_view.php");
				}
			}
			else
			{ 
				$errmsg = _("Cannot remove this contact. Registry does not allow contact removal for this domain extension.");
			}
        }
	}
	else if ($get_task == "reject_changes" && $Contact)
	{
		$operations = $Registry->GetPendingOperationList(Registry::OBJ_CONTACT, $Contact->ID);
		foreach ($operations as $PendingOperation)
		{
			if (in_array($PendingOperation->Type, array(Registry::OP_CREATE_APPROVE, Registry::OP_UPDATE_APPROVE)))
			{
				$Registry->RemovePendingOperation($PendingOperation->ID);
				if ($PendingOperation->Type == Registry::OP_UPDATE_APPROVE)
				{
					$fields = array();
					foreach ($Contact->GetEditableNames() as $n)
					{
						$fields[$n] = $PendingOperation->ObjectBefore->GetField($n);
					}
					$Contact->SetFieldList($fields);	
					$Contact->SetDiscloseList($PendingOperation->ObjectBefore->GetDiscloseList());
					DBContact::GetInstance()->Save($Contact);	
					$okmsg = _("Contact update rejected");
				}
				else
				{
					DBContact::GetInstance()->Delete($Contact);
					$okmsg = _("Contact create rejected");					
				}
				
				CoreUtils::Redirect("contacts_view.php");
				break;
			}
		}
	}
	
   
    $display["help"] = _("This page contains all your contacts for all domain extensions that you have registered. Contacts are unique per domain extension. There are different types of contacts. Most common are Registrant, Billing and Technical. Some domain extension require other contact types to register domain.");
    $display["load_extjs"] = true;
    
	require_once ("src/append.inc.php")
?>
