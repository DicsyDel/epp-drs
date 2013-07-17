<?php
	
	$db = Core::GetDBInstance();
	
	if (!$get_searchpage && $get__cmd != 'search')
		$XMLNav = new XMLNavigation();
	else
	{
		$string = ($get__cmd == 'search') ? $req_search_string : $get_searchpage;
		$XMLNav = new XMLNavigation($string);
	}
		
	$XMLNav->LoadXMLFile(dirname(__FILE__)."/../../../etc/client_nav.xml");
	
    //
    if ($_SESSION["selected_domain"])
    {
		try
		{
    		$Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($_SESSION["TLD"]);
    		$registry_exists = true;
		}
		catch (Exception $e)
		{
			$registry_exists = false;
		}	    
    }
		
	if ($_SESSION["selected_domain"] && $registry_exists)
	{
		
		$node = new DOMDocument("1.0", "UTF-8");
	    $node->loadXML(@file_get_contents(dirname(__FILE__)."/../../../etc/client_domain_nav.xml"));
	    
	    $XMLNav->AddNode($node->documentElement, $XMLNav->XML->documentElement);
	    
	    $XPath = new DOMXPath($XMLNav->XML);
	    
	    $entries = $XPath->query('//node[@type = "tasks"]', $XMLNav->XML->documentElement);
	    if ($entries && $entries->item(0))
    	    foreach ($entries as $node)
    	       if ($node instanceof DOMElement)
    	           $node->setAttribute("title", sprintf(_("Tasks for %s"), "{$_SESSION['domain']}.{$_SESSION['TLD']}"));
    	           
    	$entries = $XPath->query('//item[@type != ""]', $XMLNav->XML->documentElement);

	    if ($entries && $entries->item(0))
	    {
    	    foreach ($entries as $node)
    	    {
    	       if ($node instanceof DOMElement)
    	       {
    	           $exists = false;
    	           $type = $node->getAttribute("type");
    			   foreach (UI::GetContactsListForSmarty($Registry->Manifest->GetSectionConfig()) as $ck=>$cv)
    			   {
    			       if ($cv["type"] == $type)
    	                   $exists = true;
    			   }
    			   
    			   if (!$exists)
    			     $node->parentNode->removeChild($node);
    	       }
    	    }
	    }
   	}
				
	// Language Node
	if (count($display["languages"]) > 1)
	{
		//
		// Add languages to menu
		$DOMLang = new DOMDocument();
		$DOMLang->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?><menu></menu>");
		$LangRoot = $DOMLang->documentElement;
		
		// Settings Node
		$node_Settings = $DOMLang->createElement("node");
		$node_Settings->setAttribute("title", _("Settings"));
		$node_Settings->setAttribute("type", "settings");
		$LangRoot->appendChild($node_Settings);
		
		$node = $DOMLang->createElement("node");
		$node->setAttribute("title", _("Select interface language"));
		$node_Settings->appendChild($node);
		
		foreach ($display["languages"] as $k=>$lng)
		{
		    $item = $DOMLang->createElement("item");
		    $item->setAttribute("href", "index.php?lang={$lng["name"]}");
		    
		    $off = ($lng["name"] == LOCALE) ? "" : "-off";
	    	$item->nodeValue = "<img src='/images/menu-radio{$off}.gif'>&nbsp;".$lng["language"];
		    
		    $node->appendChild($item);
		}	
		$XMLNav->AddNode($LangRoot, $XMLNav->XML->documentElement);
	}
	
	if (ENABLE_EXTENSION::$PREREGISTRATION && Client::Load($_SESSION['userid'])->GetSettingValue("domain_preorder"))
	{
		//
		// Add languages to menu
		$DOMLang = new DOMDocument();
		$DOMLang->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?><menu></menu>");
		$LangRoot = $DOMLang->documentElement;
		
		// Settings Node
		$node_Settings = $DOMLang->createElement("node");
		$node_Settings->setAttribute("title", _("My domains"));
		$node_Settings->setAttribute("type", "domains");
		$LangRoot->appendChild($node_Settings);
		
		$sep = $DOMLang->createElement("separator");
		$node_Settings->appendChild($sep);
		
		$node = $DOMLang->createElement("item");
		$node->setAttribute("href", "preregister_domain.php");
		$node->nodeValue = _("Preregister domain");
		$node_Settings->appendChild($node);
		
		$node = $DOMLang->createElement("item");
		$node->setAttribute("href", "bulk_preregistration.php");
		$node->nodeValue = _("Bulk domains preregistration");
		$node_Settings->appendChild($node);
		
		$XMLNav->AddNode($LangRoot, $XMLNav->XML->documentElement);
	}
	
	//
	// Add incomplete orders to menu
	//
	//
	$display["reg_incomplete"] = $db->GetOne("SELECT COUNT(*) FROM domains WHERE status=? AND incomplete_operation=? AND userid=?", array(DOMAIN_STATUS::PENDING, INCOMPLETE_OPERATION::DOMAIN_CREATE, $_SESSION['userid']));
	$display["trans_incomplete"] = $db->GetOne("SELECT COUNT(*) FROM domains WHERE status=? AND incomplete_operation=? AND userid=?", array(DOMAIN_STATUS::PENDING, INCOMPLETE_OPERATION::DOMAIN_TRANSFER, $_SESSION['userid']));
	$display["transout_incomplete"] = $db->GetOne("SELECT COUNT(*) FROM domains WHERE status=? AND outgoing_transfer_status=? AND userid=?", array(DOMAIN_STATUS::DELEGATED, OUTGOING_TRANSFER_STATUS::REQUESTED, $_SESSION['userid']));
	$display["trade_incomplete"] = $db->GetOne("SELECT COUNT(*) FROM domains WHERE status=? AND incomplete_operation=? AND userid=?", array(DOMAIN_STATUS::DELEGATED, INCOMPLETE_OPERATION::DOMAIN_TRADE, $_SESSION['userid']));
	
	
	if ($display["reg_incomplete"] > 0 || $display["trans_incomplete"] > 0 || $display["trade_incomplete"] > 0)
	{
		// Add languages to menu
		$DOMLang = new DOMDocument();
		$DOMLang->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?><menu></menu>");
		$BillingRoot = $DOMLang->documentElement;
		
		// Settings Node
		$node_Billing = $DOMLang->createElement("node");
		$node_Billing->setAttribute("title", _("My domains"));
		$node_Billing->setAttribute("type", "domains");
		$BillingRoot->appendChild($node_Billing);
		
		$sep = $DOMLang->createElement("separator");
		$node_Billing->appendChild($sep);
		
		if ($display["reg_incomplete"] > 0)
		{
			$item = $DOMLang->createElement("item");
		    $item->setAttribute("href", sprintf("incomplete_orders.php?op=%s", INCOMPLETE_OPERATION::DOMAIN_CREATE));
		    $item->nodeValue = _("Incomplete registrations");			    
		    $node_Billing->appendChild($item);
		}
		
		if ($display["trans_incomplete"] > 0)
		{
			$item = $DOMLang->createElement("item");
		    $item->setAttribute("href", sprintf("incomplete_orders.php?op=%s", INCOMPLETE_OPERATION::DOMAIN_TRANSFER));
		    $item->nodeValue = _("Incomplete transfers");			    
		    $node_Billing->appendChild($item);
		}
		
		if ($display["trade_incomplete"] > 0)
		{
			$item = $DOMLang->createElement("item");
		    $item->setAttribute("href", sprintf("incomplete_orders.php?op=%s", INCOMPLETE_OPERATION::DOMAIN_TRADE));
		    $item->nodeValue = _("Incomplete transfers");			    
		    $node_Billing->appendChild($item);
		}
				
		$XMLNav->AddNode($BillingRoot, $XMLNav->XML->documentElement);
	}
	
	// Generate menu
	$XMLNav->Generate();
?>