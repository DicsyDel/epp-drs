<?
	require_once('src/prepend.inc.php');

	if (CONFIG::$UPDATE_STATUS == UPDATE_STATUS::SCHEDULED && $get_task != 'info')
		CoreUtils::Redirect("index.php");
	
	if ($get_task == "info")
	{
		$updateinfo = $db->GetRow("SELECT * FROM updatelog WHERE id=?", array($get_id));
		if (!$updateinfo)
			CoreUtils::Redirect("index.php");
			
		$display["isinfo"] = true;
		
		$display["title"] = "Information on installed update";
	}
	else 
	{
		$display["title"] = "Autoupdate";
	}

	$display["curr_revision"] = CONFIG::$APP_REVISION;
	
	if ($_POST)
	{
		if ($post_agree == 1 && $post_agree2 == 1)
		{
			$db->Execute("UPDATE config SET value='?' WHERE `key`='update_status'", array(UPDATE_STATUS::SCHEDULED));
			
			$okmsg = "Update successfully scheduled.";
			CoreUtils::Redirect("index.php");
		}
		else 
			$err[] = "You must agree to backup responsibility assignment.";
	}
	
	$AutoUpdateClient = new AutoUpdateClient();

	//
	// Prepare
	//
	foreach ((array)$AUTOUP_SERVICES as $svc)
		$AutoUpdateClient->AddService($svc);
		
	$AutoUpdateClient->SetProductID(CONFIG::$PRODUCT_ID);
	$AutoUpdateClient->SetLocalRevision(CONFIG::$APP_REVISION);
	
	// Bind event listener
	$AutoUpdateClient->SetEventHandler(new AutoupEventHandler());
	
	$latest_rev = $AutoUpdateClient->GetLatestRevision();
	$display["latest_rev"] = $latest_rev;

	if ($get_task == "info")
		$hops = $AutoUpdateClient->ListHops($updateinfo["from_revision"], $updateinfo["to_revision"]);
	else
		$hops = $AutoUpdateClient->ListHops(CONFIG::$APP_REVISION, $latest_rev);
	
	$display["notes"] = array();
	$display["FilesToUpdate"] = array();
	$display["FoldersToAdd"] = array();
	$display["FilesToAdd"] = array();
	$display["FilesToDelete"] = array();
	$display["FoldersToDelete"] = array();
	$display["Chmods"] = array();
	$display["Commands"] = array();
	$display["Scripts"] = array();
	$display["SQLQueries"] = array();
	$display["ChangelogFixed"] = array();
	$display["ChangelogAdded"] = array();
	
	if (count($hops) == 0)
	{
		$okmsg = _("No updates available. Your EPP-DRS is up to date.");
		CoreUtils::Redirect("index.php");
	}
	$display["r_errors"] = array();
	foreach ($hops as $hop)
	{
		$update_object = $AutoUpdateClient->GetUpdateObject($hop);
		$display["hops"][$hop] = $update_object;
		
		$display["notes"][] = $update_object->Notes;
		$display["FilesToUpdate"] = array_merge($display["FilesToUpdate"], array_flip((array)$update_object->FilesToUpdate));
		$display["FoldersToAdd"] = array_merge($display["FoldersToAdd"], array_flip((array)$update_object->FoldersToAdd));
		$display["FilesToAdd"] = array_merge($display["FilesToAdd"], array_flip((array)$update_object->FilesToAdd));
		$display["FilesToDelete"] = array_merge($display["FilesToDelete"], array_flip((array)$update_object->FilesToDelete));
		$display["FoldersToDelete"] = array_merge($display["FoldersToDelete"], array_flip((array)$update_object->FoldersToDelete));
		$display["Chmods"] = array_merge($display["Chmods"], (array)$update_object->Chmods);	
		$display["Commands"] = array_merge($display["Commands"], array_flip((array)$update_object->Commands));
		$display["Scripts"] = array_merge($display["Scripts"], array_flip((array)$update_object->Scripts));
		$display["SQLQueries"] = array_merge($display["SQLQueries"], array_flip((array)$update_object->SQLQueries));
		$display["ChangelogFixed"] = array_merge($display["ChangelogFixed"], (array)$update_object->ChangelogFixed);
		$display["ChangelogAdded"] = array_merge($display["ChangelogAdded"], (array)$update_object->ChangelogAdded);
		
		if ($update_object->Requirements['phpversion'] != '')
		{
			$ver = (int)preg_replace("/[^0-9]+/", "", substr(PHP_VERSION, 0, 5));
			$req_ver = (int)preg_replace("/[^0-9]+/", "", $update_object->Requirements['phpversion']);
			if ($ver < $req_ver)
				$display["r_errors"][$hop][] = array(sprintf(_("This version requires PHP %s or higher."), $update_object->Requirements['phpversion']));
			
		}
		
		if (count($update_object->Requirements['class_exists']) > 0)
		{
			foreach ($update_object->Requirements['class_exists'] as $req)
			{
				if (!class_exists($req['name']))
				{
					$display["r_errors"][$hop][] = array($req['message'], $req['uri']);
					
					if ($req['mandatory'] == 1)
						$display['mandatory_requirement_missing'] = true;
				}
			}
		}
		
		if (count($update_object->Requirements['function_exists']) > 0)
		{
			foreach ($update_object->Requirements['function_exists'] as $req)
			{
				if (!function_exists($req['name']))
				{
					$display["r_errors"][$hop][] = array($req['message'], $req['uri']);
					
					if ($req['mandatory'] == 1)
						$display['mandatory_requirement_missing'] = true;
				}
			}
		}
		
		if (count($update_object->Requirements['expressions']) > 0)
		{
			foreach ($update_object->Requirements['expressions'] as $req)
			{
				eval("\$result = {$req['expression']};");
				if (!$result)
				{
					$display["r_errors"][$hop][] = array($req['message'], $req['uri']);
					
					if ($req['mandatory'] == 1)
						$display['mandatory_requirement_missing'] = true;
				}
			}
		}
	}
	
	$display["FilesToUpdate"] = array_flip($display["FilesToUpdate"]);
	$display["FoldersToAdd"] = array_flip($display["FoldersToAdd"]);
	$display["FilesToAdd"] = array_flip($display["FilesToAdd"]);
	$display["FilesToDelete"] = array_flip($display["FilesToDelete"]);
	$display["FoldersToDelete"] = array_flip($display["FoldersToDelete"]);
	$display["Commands"] = array_flip($display["Commands"]);
	$display["Scripts"] = array_flip($display["Scripts"]);
	
	$display["SQLQueries"] = array_flip($display["SQLQueries"]);
		
	
	require_once ("src/append.inc.php");
?>