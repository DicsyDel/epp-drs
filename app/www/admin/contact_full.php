<?php
	
	require_once('src/prepend.inc.php');
	
	if ($get_clid)
	{
		$display["row"] = $db->GetRow("SELECT * FROM contacts WHERE clid=?", array($get_clid));
		
		try
		{
			$Registry = $display["row"]["module_name"] ? 
				$RegistryModuleFactory->GetRegistryByName($display["row"]["module_name"]) :
				$RegistryModuleFactory->GetRegistryByExtension($display["row"]["TLD"]);
			$Contact = DbContact::GetInstance()->LoadByCLID($display["row"]["clid"]);
			$display["row"]["TLD"] = $Contact->GetTargetTitle();
		}
		catch(Exception $e)
		{
			$errmsg = $e->getMessage();
		}
		
		if ($Registry)
		{
			$fields = $Registry->GetManifest()->GetContactFormSchema(null, $display["row"]["groupname"]);
			
			$display["fields"] = array();
	        foreach ($fields as $k=>$field)
	        {
	            $display["fields"][] = array
	            (
	            	"description" => $k,
	                "value"       => $db->GetOne("SELECT value FROM contacts_data WHERE contactid=? AND field=?", array($display["row"]["clid"], $field["name"]))
				);
	        }
		}
		else
		{
			$errmsg = sprintf(_("Registry not defined for %s domain extension"), $display["row"]["TLD"]);
			CoreUtils::Redirect ("contacts_view.php");
		}
	}
	else
		CoreUtils::Redirect ("contacts_view.php");

	$display["title"] = _("Contact &nbsp;&raquo;&nbsp; full info");
	
	require_once ("src/append.inc.php");
?>