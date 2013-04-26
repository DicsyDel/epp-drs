<?php
	require_once('src/prepend.inc.php');
	
	if ($req_id)
	{
		try
	    {
	    	$Contact = DBContact::GetInstance()->Load($req_id);
			$Registry = $Contact->Extension ? 
					$RegistryModuleFactory->GetRegistryByExtension($Contact->Extension) :
					$RegistryModuleFactory->GetRegistryByName($Contact->ModuleName);
	    }
	    catch(Exception $e)
	    {
	    	$errmsg = $e->getMessage();
			CoreUtils::Redirect("contacts_change_requests.php");
	    }		
		
		
	    if ($Contact)
	    {
			$operations = $Registry->GetPendingOperationList(Registry::OBJ_CONTACT, $Contact->ID);
			foreach ($operations as $PendingOperation)
			{
				if (in_array($PendingOperation->Type, array(Registry::OP_CREATE_APPROVE, Registry::OP_UPDATE_APPROVE)))
				{
			    	try
		    		{						
						$Registry->RemovePendingOperation($PendingOperation->ID);					
						if ($req_approve)
						{
							
							if ($PendingOperation->Type == Registry::OP_UPDATE_APPROVE)
							{
								$Registry->UpdateContact($Contact);
								$okmsg = _("Contact update approved");
							}
							else if ($PendingOperation->Type == Registry::OP_CREATE_APPROVE)
							{
								$Registry->CreateContact($Contact);
								$okmsg = _("Contact creation approved");
							}
						}
						else
						{
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
							else if ($PendingOperation->Type == Registry::OP_CREATE_APPROVE)
							{
								DBContact::GetInstance()->Delete($Contact);
								$okmsg = _("Contact create rejected");					
							}
						}
						EmailToRegistrantObserver::OnCompleteChangeContactRequest($Contact, $PendingOperation->Type, $req_approve, true);
						
						CoreUtils::Redirect("contacts_change_requests.php");
						break;
		    		}
			    	catch (Exception $e)
			    	{
		    			$err[] = $e->getMessage();
			    		EmailToRegistrantObserver::OnCompleteChangeContactRequest($Contact, $PendingOperation->Type, $req_approve, false, $e->getMessage());
			    		
			    		// Restore data to previous state
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
						}
						else if ($PendingOperation->Type == Registry::OP_CREATE_APPROVE)
						{
							DBContact::GetInstance()->Delete($Contact);
						}
			    	}
				}
	    	}
	    }
	}
	
	$sql = "SELECT * FROM pending_operations 
		WHERE operation = '".Registry::OP_CREATE_APPROVE."' 
			OR operation = '".Registry::OP_UPDATE_APPROVE."' 
		ORDER BY dtbegin DESC";
	
	$paging = new SQLPaging();
	$paging->SetSQLQuery($sql);
	$paging->ApplySQLPaging();
	$paging->ParseHTML();

	$display["paging"] = $paging->GetPagerHTML("admin/inc/paging.tpl");

	$Factory = RegistryModuleFactory::GetInstance();
	$rows = $db->GetAll($paging->SQL);
	foreach ($rows as &$row)
	{
		$Contact = unserialize($row["object_after"]);
		$ContactBefore = unserialize($row["object_before"]);
		unset($row["object_after"], $row["object_before"]);
		
		$row["clid"] = $Contact->CLID;
		$row["contactid"] = $Contact->ID;
		
		
		$Registry = $Contact->TLD ? 
				$Factory->GetRegistryByExtension($Contact->TLD) : 
				$Factory->GetRegistryByName($Contact->ModuleName);
		$fields = $Registry->GetManifest()->GetContactFormSchema(null, $Contact->GroupName);
		
		if ($row["operation"] == Registry::OP_UPDATE_APPROVE)
		{
			$row["operation"] = _("Update");
		}
		else if ($row["operation"] == Registry::OP_CREATE_APPROVE)
		{
			$row["operation"] = _("Create");
		}
	}
	
	$display["rows"] = $rows;
	
	require("src/append.inc.php");	
?>