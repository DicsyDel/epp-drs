<?php
	require_once('src/prepend.inc.php');	
	$display["title"] = "Settings &nbsp;&raquo;&nbsp; Languages &nbsp;&raquo;&nbsp; List";
	
	$modules = glob(LANGS_DIR."/*", GLOB_ONLYDIR);
	
	$display["langs"] = array();
	
	if ($get_action == 'setdefault')
	{
	    $db->Execute("UPDATE languages SET isdefault='0'");
	    $db->Execute("UPDATE languages SET isdefault='1' WHERE name=?", array($get_name));
	    
	    CoreUtils::Redirect("langs.php");
	}
	
	if ($get_action == 'enable')
	{
	    $db->Execute("REPLACE INTO languages SET name=?, isinstalled='1'", array($get_name));
	    
	    CoreUtils::Redirect("langs.php");
	}
	
	if ($get_action == 'disable')
	{
	    $db->Execute("UPDATE languages SET isinstalled='0', isdefault='0' WHERE name=?",array($get_name));
	    
	    CoreUtils::Redirect("langs.php");
	}
	
	foreach($modules as $module)
	{
	    $name = basename($module);
	    if (preg_match("/^([a-z]{2})_([A-Z]{2})$/si", $name))
	    {
    	    $lang = array(
    	                                   "mo"    => @file_exists("{$module}/LC_MESSAGES/default.mo"),
    	                                   "img"   => @file_exists(LANGS_DIR."/../www/images/lang/{$name}.gif"),
    	                                   "name"  => $name
    	                               );
    	   $isinstalled = $db->GetOne("SELECT isinstalled FROM languages WHERE name=?", array($name));
    	   $lang["isdefault"] = $db->GetOne("SELECT isdefault FROM languages WHERE name=?", array($name));
           if ($isinstalled)
                $lang["isinstalled"] = '1';
           else 
           {
               if ($lang["mo"] && $lang["img"])
                $lang["isinstalled"] = '0';
               else 
                $lang["isinstalled"] = '2';
           }
                      
    	   $display["langs"][] = $lang;  
	    }
	}
	$display["help"] = "EPP-DRS is fully translatable. This page shows all language packs installed in your EPP-DRS copy. For information on translating EPP-DRS, please consult <a target='blank' href='http://webta.net/docs/epp-drs/#loc'>EPP-DRS docs</a>.";
	require ("src/append.inc.php");
?>