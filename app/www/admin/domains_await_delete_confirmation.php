<?php

	require_once ("src/prepend.inc.php");

	$DbDomain = DBDomain::GetInstance();
	$RegFactory = RegistryModuleFactory::GetInstance();
	
	if ($post_action == "appr")
	{
		$proceed = 0;
		foreach ((array)$post_id as $id)
		{
			try
			{
				$Domain = $DbDomain->Load($id);
				$Registry = $RegFactory->GetRegistryByExtension($Domain->Extension);

				$Registry->DeleteDomain($Domain);
				$db->Execute("UPDATE domains SET delete_status = ? WHERE id = ?", 
						array(DOMAIN_DELETE_STATUS::APPROVED, $Domain->ID));
				
				$userinfo = $db->GetRow("SELECT * FROM users WHERE id = ?", array($Domain->UserID));
				// Send domain expired notice
				$args = array
				(
					"login"				=>	$userinfo["login"], 
				  	"domain_name"		=>	$Domain->Name, 
				  	"extension"			=>	$Domain->Extension, 
				  	"client"			=>  $userinfo
				);
				mailer_send("expired_notice.eml", $args, $userinfo["email"], $userinfo["name"]);				

				$proceed++;
			}
			catch (Exception $e)
			{
				$hostname = $Domain ? $Domain->GetHostName() 
						: $db->GetOne("SELECT CONCAT(name, '.', TLD) FROM domains WHERE id = ?", array($post_id));
				$msg = sprintf(_("Cannot delete domain '%s'. %s"), $hostname, $e->getMessage());
				
				Log::Log($msg, E_USER_ERROR);
				$err[] = $msg;
			}
		}
		if ($proceed)
			$okmsg = sprintf(_("%s domain(s) deleted"), $proceed);
		CoreUtils::Redirect("domains_await_delete_confirmation.php");
	}
	
	
	$display["title"] = _("Domains") . "&nbsp;&raquo;&nbsp;". _("Awaiting delete confirmation");
	$display["help"] = _("These domains were registered in auto renew registry and their renewal was unpaid by customers.");
	
	// Ajax view
	$display["load_extjs"] = true;
	
	require_once ("src/append.inc.php");