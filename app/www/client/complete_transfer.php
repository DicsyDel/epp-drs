<?
	require_once('src/prepend.inc.php');
		
	if (isset($req_id))
	{
		$req_domainid = $req_id;
		require_once("src/set_managed_domain.php");
	}
	else
		require_once("src/get_managed_domain_object.php");
		
	// Check domain status
	if (($Domain->Status == DOMAIN_STATUS::PENDING && $Domain->IncompleteOrderOperation == INCOMPLETE_OPERATION::DOMAIN_TRANSFER) || 
		$Domain->Status == DOMAIN_STATUS::TRANSFER_FAILED ||
		$Domain->Status == DOMAIN_STATUS::AWAITING_TRANSFER_AUTHORIZATION)
	{
		// Check invoice
		$invoice = $db->GetRow("SELECT * FROM invoices WHERE itemid=? AND status='1' AND purpose=?", array($Domain->ID, INVOICE_PURPOSE::DOMAIN_TRANSFER));				
		if (!$invoice)
		{
			$errmsg = _("No corresponding invoice found for this domain");
			CoreUtils::Redirect("domains_view.php");
		}
	}
	else 
	{
		$errmsg = _("Domain status not allow complete transfer");
		CoreUtils::Redirect("domains_view.php");
	}

	$display["fields"] = UI::GetTransferExtraFieldsForSmarty($Manifest->GetSectionConfig());
	$RegistryContacts = UI::GetContactsListForSmarty($Manifest->GetSectionConfig(), true);
	$transfer_contacts = UI::GetTransferContactsForSmarty($Manifest->GetSectionConfig());
	$section_shared_contacts = (bool)$Manifest->GetRegistryOptions()->ability->section_shared_contacts;	
	$DbContact = DBContact::GetInstance();
	
	
	if ($transfer_contacts)
	{
		$display["contacts"] = array();
		foreach ($transfer_contacts as $v)
		{
			foreach ($RegistryContacts as $kk=>$vv)
			{
				if ($vv["type"] == $v)
				{
					$smarty_contact = $vv;
		        	$smarty_contact["groupname"] = $Manifest->GetGroupNameByContactType($vv["type"]);
		        	
					if (!$section_shared_contacts)
		        	{
		        		$section_name = $Manifest->GetSectionName();
		        		$target_index = $Manifest->GetContactTargetIndex($Domain->Extension, $smarty_contact["groupname"]); 
		        		$smarty_contact['target_title'] =  $Manifest->GetContactTargetTitle($Domain->Extension, $smarty_contact["groupname"]);        		
		        	}
		        	else
		        	{
		        		$section_name = "";
		        		$target_index = 0;
		        		$smarty_contact['target_title'] = $Registry->GetModuleName();
		        	}		        	
		        	
		        	
		        	$contact_count = $db->GetOne("
		        		SELECT COUNT(clid) FROM contacts 
		        		WHERE userid=? AND
		        		(TLD = ? OR (module_name=? AND section_name=? AND target_index=?)) AND
		        		groupname=?
		        	", array(
						$_SESSION['userid'],
						// 
						$Domain->Extension, 
						$Registry->GetModuleName(), 
						$section_name, 
						$target_index,
						//
						$smarty_contact["groupname"]
		        	));
		        	if ($contact_count < 25)
		        	{
			        	$smarty_contact["exists"] = $db->GetAll("
			        		SELECT clid FROM contacts
			        		WHERE userid=? AND
			        		(TLD = ? OR (module_name=? AND section_name=? AND target_index=?)) AND
			        		groupname=? ORDER BY (SELECT value FROM contacts_data WHERE contacts_data.contactid = contacts.clid AND contacts_data.field = 'name') ASC, (SELECT value FROM contacts_data WHERE contacts_data.contactid = contacts.clid AND contacts_data.field = 'organization') ASC, clid ASC
			        	",	array (	
							$_SESSION['userid'], 
							// 
							$Domain->Extension, 
							$Registry->GetModuleName(), 
							$section_name, 
							$target_index,
							//
							$smarty_contact["groupname"]
						));
						
						foreach ($smarty_contact["exists"] as &$c)
			            {
			                try
			                {
			            		$Contact = $DbContact->LoadByCLID($c["clid"]);
			            		$c['title'] = $Contact->GetTitle();
			            		
				                if ($Domain)
				                	$DC = $Domain->GetContact($vv["type"]); 
				
				                if ($Domain && $DC && $DC->CLID == $c["clid"])
				                	$c["selected"] = true;
			                }
			                catch(Exception $e)
			                {
			                	unset($c);
			                	continue;
			                }
			           		catch(ErrorList $e)
			                {
			                	unset($c);
			                	continue;
			                }
			            }
			            
			            $smarty_contact['list'] = array();
			            foreach ($smarty_contact['exists'] as $ex)
			            {
							$smarty_contact['list'][$ex['clid']] = $ex['title'];
			            }
		        	}
		        	else
		        	{
		        		$smarty_contact['too_many_items'] = true;
		        	}
		            
		            $display["contacts"][] = $smarty_contact;
				}
			}
		}
	}
	
	if ($_POST)
	{
		if ($post_add_data)
		{
			//Parse url encoded extra fields
			mparse_str($post_add_data, $extra_data);
			$post_add_data = $extra_data;
		}
		else
		{
			// Empty extra
			$extra_data = array();
		}
		
		// Merge extra fields with std POST fields
		$extra_data = array_merge($extra_data, $_POST);
		$extra_data = array_map('trim', $extra_data);
		
		$XmlConfig = $Registry->GetManifest()->GetSectionConfig();
		$trnFields = $XmlConfig->domain->transfer->fields;
		if ($trnFields->children())
		{
			$DataForm = new DataForm();
			$DataForm->AddXMLValidator($trnFields);
			$errors = $DataForm->Validate($extra_data);
			if ($errors)
				foreach ($errors as $_errmsg) $err[] = $_errmsg;
		}
		
		//var_dump($err);
		//var_dump($post_add_data);
		//die();
		
		if (!$err)
		{
			if ($transfer_contacts)
			{
				foreach($transfer_contacts as $v)
				{
					if ($_POST[$v])
					{
						$c = DBContact::GetInstance()->LoadByCLID($_POST[$v]);
						if ($c->HasPendingOperation(Registry::OP_CREATE_APPROVE)
	        				|| $c->HasPendingOperation(Registry::OP_UPDATE_APPROVE))
	        			{
	        				$err[] = sprintf(_("Contact <%s> is not yet approved by administrator"), $c->GetTitle()); 
	        			}
	        			else
	        			{
							$Domain->SetContact($c, $v);
	        			}						
					}			
				}
			}
	
			if (!$err)
			{
				try
				{
					$Registry->TransferRequest($Domain, $extra_data);
					$okmsg = _("Domain transfer request successfully submitted to registry");
					CoreUtils::Redirect("domains_view.php");
				}
				catch(Exception $e)
				{
					$errmsg = $e->getMessage();
				}
				
			}
		}
		
		$display["post_add_data"] = $post_add_data;		
	}
	
	$display["id"] = $req_id;
	$display["TLD"] = $Domain->Extension;
	$display["domain"] = $Domain->GetHostName();
	
	$display["load_extjs"] = true;
	$display["load_extjs_nocss"] = true;
	
	require_once('src/append.inc.php');
?>
