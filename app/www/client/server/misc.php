<?
$enable_json = true;
include("../src/prepend.inc.php");

switch ($get_task)
{
	case "disablehelp":
			
		if (!CONFIG::$DEV_DEMOMODE)
		$db->Execute("REPLACE INTO user_settings SET value=?, `key`=?, userid=?",
		array('0', 'inline_help', $_SESSION['userid']));
			
		break;

	case "set_contact":

		$Registry = $RegistryModuleFactory->GetRegistryByExtension($req_TLD);		
		$Manifest = $Registry->GetManifest();
		$RegistryContacts = UI::GetContactsListForSmarty($Manifest->GetSectionConfig());

		foreach($RegistryContacts as $k=>$v)
			$display["types"][] = $v;		
		
		try
		{
			$Domain = DBDomain::GetInstance()->Load($req_domainid);
			$contact_type = $req_type;
			$NewContact = $req_newContact ? DBContact::GetInstance()->LoadByCLID($req_newContact) : null;
			
			$Action = new UpdateDomainContactAction($Domain, $contact_type, $NewContact);
			$result = $Action->Run($_SESSION['userid']);
			if ($result == UpdateDomainContactAction_Result::OK)
			{
				$_SESSION["okmsg"] = _("Contact successfully changed");
				$data = array("domainid" => $req_domainid);
			}
			else if ($result == UpdateDomainContactAction_Result::PENDING)
			{
				$_SESSION["okmsg"] = _("Contact change request has been sent to registry. ") . 
					_("You will be notified by email as soon as this operation will be completed.");
				$data = array("domainid" => $req_domainid);				
			}
			else if ($result == UpdateDomainContactAction_Result::INVOICE_GENERATED)
			{
				$Invoice = $Action->GetInvoice();
				$data = array
				(
					"domainid" => $req_domainid, 
					"checkout_redir" => true, 
					"invoiceid" => $Invoice->ID, 
					"invoice_status" => $Invoice->Status
				);
			}
			$res = true;
		}
		catch (UpdateDomainContactAction_Exception $e)
		{
			$res = false;			
			$data = $e->getMessage();
		}
		break;
			
	case "edit":
			
		$res = false;
		$Registry = $RegistryModuleFactory->GetRegistryByExtension($req_TLD);
		if ($Registry)
		{
			$Manifest = $Registry->GetManifest();
			$RegistryContacts = UI::GetContactsListForSmarty($Manifest->GetSectionConfig());

			foreach($RegistryContacts as $k=>$v)
			$display["types"][] = $v;

			$fields = $_REQUEST[$req_type];

			$groupname = $Manifest->GetGroupNameByContactType($req_type);
			 
			$contactsconfig = $Manifest->GetSectionConfig()->xpath("//contact[@type = '{$req_type}']");
			$contactsconfig = $contactsconfig[0];
			 
			$Domain = DBDomain::GetInstance()->Load($req_domainid);
			if ($Domain->UserID != $_SESSION["userid"])
			$err = _("You don't have permissions for manage this domain");
			 
			$Contact = $Domain->GetContact($req_type);

			if ($Contact->UserID != $_SESSION["userid"])
			$err = _("You don't have permissions for manage this contact");
			 
			if (!empty($get_parentID))
			$Contact->ParentCLID = $get_parentID;
			 
			try
			{
				$Contact->SetFieldList($fields);
				$Contact->ExtraData = array("domainname" => $Domain->Name, "type" => $req_type);
				foreach (array_keys($Contact->GetDiscloseList()) as $opt)
				{
					$Contact->SetDiscloseValue($opt, $_GET['disclose'][$opt]);
				}
			}
			catch(ErrorList $e)
			{
				$err = $e->getAllMessages();
			}
			 
			if (!$err)
			{
				try
				{
					$Contact = $Registry->UpdateContact($Contact);
				}
				catch(Exception $e)
				{
					$err[] = $e->getMessage();
				}

				if ($Contact && !$err)
				{
					try
					{
						$res = true;
						$data = array("domainid" => $req_domainid,
        			   	 		"id" => $Contact->CLID, 
        			   	 		"groupname" => $groupname
						);
						$_SESSION["okmsg"] = _("Contact successfully updated");
					}
					catch(Exception $e)
					{
						$err[] = $e->getMessage();
					}
				}
			}
			 
			if (!$res)
			$data = $err[0];
		}
		else
		$data = _("Bad request");
			
		break;
			
	case "create":
			
		$Registry = $RegistryModuleFactory->GetRegistryByExtension($req_TLD);
		if ($Registry)
		{
			$Manifest = $Registry->GetManifest();
			$RegistryContacts = UI::GetContactsListForSmarty($Manifest->GetSectionConfig());

			foreach($RegistryContacts as $k=>$v)
				$display["types"][] = $v;

			$fields = $_REQUEST[$req_type];
			$groupname = $Manifest->GetGroupNameByContactType($req_type);
			$contactsconfig = $Manifest->GetSectionConfig()->xpath("//contact[@type = '{$req_type}']");
			$contactsconfig = $contactsconfig[0];

			$Contact = $Registry->NewContactInstance($req_type);
			if (!empty($get_parentID))
				$Contact->ParentCLID = $get_parentID;

			try
			{
				$Contact->SetFieldList($fields);	
			}
			catch (ErrorList $e)
			{
				$err = $e->GetAllMessages();
			}	
				
			if (!$err)
			{
				$Contact->UserID = $_SESSION['userid'];				
				
				try
				{
					$ContactConfig = $Contact->GetConfig();
					$need_approval = $ContactConfig->policy->approveChangesPolicy 
							&& $ContactConfig->policy->approveChangesPolicy->getName();
					if ($need_approval)
					{
						DBContact::GetInstance()->Save($Contact);						
						$Registry->AddPendingOperation($Contact, Registry::OP_CREATE_APPROVE);
						EmailToRegistrantObserver::OnNewChangeContactRequest($Contact, Registry::OP_CREATE_APPROVE);
					}
					else
					{
						$Contact = $Registry->CreateContact($Contact);					
					}

					
					$res = true;
					$data = array(
        		   	 		'title' => $Contact->GetTitle(),
        		   	 		"id" => $Contact->CLID, 
        		   	 		"groupname" => $groupname
					);
				}
				catch (Exception $e)
				{
					$err[] = $e->getMessage();					
				}
			}
			 
			if (!$res)
				$data = $err[0];
		}
		else
			$data = _("Bad request");
			
		break;

	case "get_childs_list":
			
		$contacts = $db->Execute("
				SELECT * FROM contacts WHERE 
				parent_clid=? AND TLD=? AND groupname=? AND userid=?",
		array($get_parentID, $get_TLD, $req_groupname, $_SESSION["userid"])
		);
			
		$data = array();
		while ($contact = $contacts->FetchRow())
		{
			$data[] = array("clid" => $contact["clid"],
					"name" => DBContact::GetInstance()->LoadByCLID($contact["clid"])->GetFullName());
		}
		$res = true;
			
		break;
			
	case "get_edit_contact_form":
			
		$Registry = $RegistryModuleFactory->GetRegistryByExtension($req_TLD);
		if ($Registry)
		{
			$Manifest = $Registry->GetManifest();
			 
			$RegistryContacts = UI::GetContactsListForSmarty($Manifest->GetSectionConfig());
			 
			foreach($RegistryContacts as $k=>$v)
			{
				$display["types"][] = $v;
				 
				if ($v["type"] == $req_type)
				$contact_name = $v["name"];
			}
			 
			if (!$req_type)
			$req_type = $display["types"][0]["type"];
			 
			$display["fields"] = $Manifest->GetContactFormSchema($req_type);
			 
			$display["contactinfo"] = $_POST;
			$display["contact_name"] = $contact_name;
			$display["TLD"] = $req_TLD;
			$display["type"] = $req_type;
			$display["classname"] = "text";
			$display["padding"] = "0";
			$display["add_td"] = "0";
			$display["intablepadding"] = "1";
			$display["field_name_start"] = "{$req_type}[";
			$display["field_name_end"] = "]";
			 
			$Domain = DBDomain::GetInstance()->Load($req_domainid);
			 
			if ($Domain->UserID != $_SESSION["userid"])
			$err = _("Error: You don't have permissions for manage this domain");
			 
			$Contact = $Domain->GetContact($req_type);
			 
			$contactinfo = $Contact->GetFieldList();
			$phone_fields = $Manifest->GetSectionConfig()->xpath("//contact[@type='{$req_type}']/fields/field[@type='phone']");
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


			$discloses = $Contact->GetDiscloseList();

			foreach($discloses as $dname=>$dvalue)
			{
				$descr =  $Contact->GetConfig()->xpath("disclose/option[@name='{$dname}']/@description");
				$display["disclose"][(string)$descr[0]->description] = array("name" => $dname, "value" => $dvalue);
			}

			$smarty->assign($display);
			print $smarty->fetch("client/inc/edit_contact_form.tpl");
			exit();
		}
		else
		{
			print _("Error: Invalid domain extension");
			exit();
		}
			
		break;
			
	case "get_contact_form":
			
		$Registry = $RegistryModuleFactory->GetRegistryByExtension($req_TLD);
		if ($Registry)
		{
			$Manifest = $Registry->GetManifest();
			 
			$RegistryContacts = UI::GetContactsListForSmarty($Manifest->GetSectionConfig());
			 
			foreach($RegistryContacts as $k=>$v)
			{
				$display["types"][] = $v;
				 
				if ($v["type"] == $req_type)
				$contact_name = $v["name"];
			}
			 
			if (!$req_type)
			$req_type = $display["types"][0]["type"];
			 
			try
			{
				$display["fields"] = $Manifest->GetContactFormSchema($req_type);
				foreach ($display["fields"] as &$field)
				$field["iseditable"] = 1;

				$display["contactinfo"] = $_POST;
				$display["contact_name"] = $contact_name;
				$display["TLD"] = $req_TLD;
				$display["type"] = $req_type;
				$display["classname"] = "text";
				$display["padding"] = "0";
				$display["add_td"] = "0";
				$display["intablepadding"] = "1";
				$display["field_name_start"] = "{$req_type}[";
				$display["field_name_end"] = "]";

				$no_one = $db->GetOne("
					SELECT COUNT(*) FROM contacts 
					WHERE userid=? AND groupname=? AND 
					(TLD=? OR (module_name=? AND section_name=? AND target_index=?))
				", array(
					$_SESSION['userid'], 
					$req_groupname, 
					$req_TLD,
					$Registry->GetModuleName(),
					$Manifest->GetSectionName(),
					$Manifest->GetContactTargetIndex($req_TLD, $req_groupname)
				)) == 0;  
				if ($no_one && (int)$Client->GetSettingValue(ClientSettings::PREFILL_CONTACT))
				{
					$display["contactinfo"] = $db->GetRow("SELECT * FROM users WHERE id=?", array($_SESSION['userid']));
					$display["contactinfo"]["street1"] = $display["contactinfo"]["address"];
					$display["contactinfo"]["cc"] = $display["contactinfo"]["country"];
					$display["contactinfo"]["sp"] = $display["contactinfo"]["state"];
					$display["contactinfo"]["pc"] = $display["contactinfo"]["zipcode"];
					$display["contactinfo"]["street2"] = $display["contactinfo"]["address2"];
					$display["contactinfo"]["voice"] = $display["contactinfo"]["phone"];
					$display["contactinfo"]["password"] = "";
				}

				$smarty->assign($display);
				print $smarty->fetch("client/inc/contact_form.tpl");
				exit();
			}
			catch(Exception $e)
			{
				print sprintf(_("Error: %s"), $e->getMessage());
				exit();
			}
		}
		else
		{
			print _("Error: Invalid domain extension");
			exit();
		}
			
		break;
			
	case "get_contact_list":
			
		$Registry = $RegistryModuleFactory->GetRegistryByExtension($req_TLD);
		$Manifest = $Registry->GetManifest();
		$RegistryContacts = UI::GetContactsListForSmarty($Manifest->GetSectionConfig());
		
		$section_shared_contacts = (bool)$Manifest->GetRegistryOptions()->ability->section_shared_contacts;
		if (!$section_shared_contacts)
        {
        	$section_name = $Manifest->GetSectionName();
        	$target_index = $Manifest->GetContactTargetIndex($get_TLD, $get_groupname); 
        }
        else
        {
        	$section_name = "";
        	$target_index = 0;
        }		
		
			

		foreach($RegistryContacts as $k=>$v)
		{
			$display["types"][] = $v;

			if ($v["type"] == $req_type)
			$contact_name = $v["name"];
		}
			
			
		$sql = "
			SELECT clid
			FROM contacts
			WHERE
			userid='{$_SESSION['userid']}' AND
			(TLD = '{$get_TLD}' OR (
				module_name='{$Registry->GetModuleName()}' AND 
				section_name='{$section_name}' AND 
				target_index='{$target_index}'
			)) AND
			groupname='{$get_groupname}'
		";
		$paging = new SQLPaging($sql, isset($req_pn) ? $req_pn : 1, 10);
		$paging->ApplyFilter(
			$req_filter_q,
			array(),
			"(clid IN (SELECT DISTINCT contactid FROM contacts_data WHERE value LIKE '%[FILTER]%')) or clid LIKE '%[FILTER]%'"
		);
		$paging->SetURLFormat("javascript:ContactList_ShowPage('{$req_type}', %d)");
		$paging->TrainLength = 5;
		$sql = $paging->ApplySQLPaging();
		$paging->ParseHTML();
			
		$clids = $db->GetAll($sql);
		$rows = array();
		foreach ($clids as $clid)
		{
			try
			{
				$c = DBContact::GetInstance()->LoadByCLID($clid['clid']);
				$contact_fields = $c->GetFieldList();
				$rows[] = array
				(
					'clid' => $c->CLID,
					'title' => $c->GetTitle()
				);
			}
			catch (Exception $e)
			{}
		}

		$display['rows'] = $rows;
		$display['paging'] = $paging->GetPagerHTML("client/inc/paging.tpl");
		$display['filter'] = $paging->GetFilterHTML('client/inc/contact_table_filter.tpl');
		$display['type'] = $req_type;
		$display['TLD'] = $req_TLD;
		$display['groupname'] = $req_groupname;
		$display['contact_name'] = $contact_name;
			
			
		$smarty->assign($display);
		print $smarty->fetch("client/inc/contact_list.tpl");
		exit();
			
			
		break;
}

$retval = array("result" => $res, "data" => $data);
print json_encode($retval);
?>
