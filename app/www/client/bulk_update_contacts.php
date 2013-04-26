<?php

	require_once('src/prepend.inc.php');

	$stepno = $post_stepno;
	if (!$stepno)
	{
		$stepno = 1;
	}
	
	
	if ($_POST)
	{
		$RegFactory = RegistryModuleFactory::GetInstance();
		
		if ($stepno == 1)
		{
			// Accept selected TLD 
            try
			{
				$Registry = $RegFactory->GetRegistryByExtension($post_TLD);
				$_SESSION['BU_TLD'] = $post_TLD;
				$stepno = 2;
            }
			catch(Exception $e)
			{
				$errmsg = sprintf(_("Registry module not defined for %s domain extension"), $post_TLD);
			}
		}
		else if ($stepno == 2)
		{
			// Accept selected domains
			if (!empty($post_domains))
			{
				$_SESSION['BU_DOMAINS'] = $post_domains;
				$stepno = 3;
			}
			else
			{
				$errmsg = sprintf(_("No domains were selected"));
			}
		}
		else if ($stepno == 3)
		{
			// Accept selected contacts and create pending update operation
			foreach (array($post_registrant, $post_admin, $post_billing, $post_tech) as $clid)
			{
				if ($clid)
				{
	        		try
	        		{
	        			$Contact = DBContact::GetInstance()->LoadByCLID($clid);
	        			if ($Contact->HasPendingOperation(Registry::OP_CREATE_APPROVE)
	        				|| $Contact->HasPendingOperation(Registry::OP_UPDATE_APPROVE))
	        			{
	        				$err[] = sprintf(_("Contact <%s> is not yet approved by administrator"), 
	        						$Contact->GetTitle()); 
	        			}
	        		}
	        		catch (Exception $e)
	        		{
	        			$err[] = $e->getMessage();
	        		}
				}
			}
			if (!$err)
			{
				if (!($post_registrant || $post_admin || $post_billing || $post_tech))
				{
					$errmsg = sprintf(_("No contacts were selected"));
				}
				else if (!$_SESSION['BU_TLD'])
				{
					$errmsg = sprintf(_("No domain extension was selected"));
				}
				else if (!$_SESSION['BU_DOMAINS'])
				{
					$errmsg = sprintf(_("No domains were selected"));
				}
				else
				{
					// Create bulk update task abd put in into tasks queue
					$Queue = TaskQueue::GetInstance();
					try
					{
						// Define contact clids list
						$clids = array(
							CONTACT_TYPE::REGISTRANT => $post_registrant,
							CONTACT_TYPE::ADMIN => $post_admin,
							CONTACT_TYPE::TECH => $post_tech,
							CONTACT_TYPE::BILLING => $post_billing
						);
					
						// Construct task
						$Task = new Task(
							$_SESSION['userid'],
							new BulkUpdateContactJob($_SESSION['BU_TLD'], $clids),
							$_SESSION['BU_DOMAINS']
						);
						$Queue->Put($Task);
						CoreUtils::Redirect("bulk_update_complete.php");
					}
					catch (Exception $e)
					{
						$errmsg = $e->getMessage();
					}
				}
			}
			
		}
	}
	
	
	if ($stepno == 1)
	{
		// Get available TLDs
    	$display["tlds"] = array();
//		$TLDs = $RegistryModuleFactory->GetExtensionList();    	
    	foreach ($TLDs as $k=>$v)
    	{
    		try
			{
            	$Registry = $RegistryModuleFactory->GetRegistryByExtension($v);
            	$display['tlds'][] = $v;
            }
			catch(Exception $e)
			{
				$errmsg = $e->getMessage();
			}
    	}
    	$display["num_tlds"] = count($display["tlds"]);
	}
	else if ($stepno == 2)
	{
		$rows = $db->GetAll("SELECT name FROM domains WHERE TLD = ? AND userid = ? AND status = ? ORDER BY name", 
			array($post_TLD, $_SESSION['userid'], DOMAIN_STATUS::DELEGATED)
		);
		$items = array();
		foreach ($rows as $row)
		{
			$items[$row['name']] = "{$row['name']}.{$post_TLD}";
		}
		$display['checklist'] = array(
			'source_title' => "Select domains",
			'items' => $items,
			'name' => 'domains[]'
		);
	}
	else if ($stepno == 3)
	{
		$CForm = new DomainAllContactsForm(array(
			'userid' => $_SESSION['userid'],
			'tld' => $_SESSION['BU_TLD'],
			'form_title' => sprintf(_("Bulk contacts update - Step %d (Set Contacts)"), $stepno),
			'button_text' => _("Next step"),
			'form_fields' => array('stepno' => $stepno)
		));
		$display['all_contacts_form'] = $CForm->GetRenderedData();		
	}
	
	$display['stepno'] = $stepno;
	$template_name = "client/bulk_update_contacts_step{$stepno}";
	
	include_once("src/append.inc.php");	
?>