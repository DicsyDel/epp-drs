<?php

	if (!$get_searchpage && $get__cmd != 'search')
		$XMLNav = new XMLNavigation();
	else
	{
		$string = ($get__cmd == 'search') ? $req_search_string : $get_searchpage;
		$XMLNav = new XMLNavigation($string);
	}

	$XMLNav->LoadXMLFile(dirname(__FILE__)."/../../../etc/admin_nav.xml");
	
	//
	// Add languages to menu
	$DOMLang = new DOMDocument();
	$DOMLang->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?><menu></menu>");
	$LangRoot = $DOMLang->documentElement;
		
	// Settings Node
	$node_Settings = $DOMLang->createElement("node");
	$node_Settings->setAttribute("title", "Settings");
	$node_Settings->setAttribute("type", "settings");
	$LangRoot->appendChild($node_Settings);
			
	// Product info Node
	$item = $DOMLang->createElement("separator");
    $node_Settings->appendChild($item);		
	
	$item = $DOMLang->createElement("item");
    $item->setAttribute("href", "product_info.php");
    $item->nodeValue = _("Product info");
    $node_Settings->appendChild($item);
		
	$XMLNav->AddNode($LangRoot, $XMLNav->XML->documentElement);
	
	if (!ENABLE_EXTENSION::$MANAGED_DNS)
	{
		$xpath = new DOMXPath($XMLNav->XML);
		$node = $xpath->query("//node[@type='manageddns']/following-sibling::separator[position()=1]")->item(0);
		$node->parentNode->removeChild($node);
		$node = $xpath->query("//node[@type='manageddns']")->item(0);
		$node->parentNode->removeChild($node);
	}
	
	$XMLNav->Generate();
?>