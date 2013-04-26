<?php
	require_once('src/prepend.inc.php');
	
	
	// Load contact from DB
	$DbContact = DBContact::GetInstance();
	try
	{
		$Contact = $DbContact->Load($req_contactid);
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
	}	
	
	
	if ($Contact)
	{
		// Get contact registry module		
		try
		{
			$Registry = $Contact->ModuleName ? 
					$RegistryModuleFactory->GetRegistryByName($Contact->ModuleName) :
					$RegistryModuleFactory->GetRegistryByExtension($Contact->Extension);
		}
		catch (Exception $e)
		{
			$errmsg = $e->getMessage();
		}

		$ContactConfig = $Contact->GetConfig();
		
		$policy = $ContactConfig->policy->children();
		$policy = $policy[0];
		$policy_id = $policy->attributes()->id;
		
		$RegistryOptions = $Registry->GetManifest()->GetRegistryOptions();
		$Policy = $RegistryOptions->xpath("policy[@id='$policy_id']");
		$display["policy_text"] = count($Policy) ? (string)$Policy[0] : null;
	} 
	
	$display["button_js_action"] = "location.href='$req_next_url'";
	
	require("src/append.inc.php");	
?>