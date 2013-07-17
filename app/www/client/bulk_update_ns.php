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
			// Accept entered nameservers and create pending update operation
			$hostnames = array_filter(array_map('trim', explode("\n", (string)$post_hostnames)));
			if (empty($hostnames))
			{
				$errmsg = sprintf(_("No nameservers were entered"));
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
					$nslist = array();
					$Validator = new Validator();
					foreach ($hostnames as $hostname)
					{
						if ($Validator->IsDomain($hostname))
						{
							$nslist[] = new Nameserver($hostname);
						}
						else
						{
							throw new Exception(sprintf(_("%s is not a valid host"), $hostname));
						}
					}
					
					// Construct task
					$Task = new Task(
						$_SESSION['userid'],
						new BulkUpdateNSJob($_SESSION['BU_TLD'], $nslist),
						$_SESSION['BU_DOMAINS']
					);
					$Queue->Put($Task);
					CoreUtils::Redirect("bulk_update_complete.php");
				}
				catch (Exception $e)
				{
					$errmsg = $e->getMessage();
					$display['hostnames'] = join("\n", $hostnames);
				}
			}
		}
	}
	
	
	if ($stepno == 1)
	{
		// Get available TLDs
    	$display["tlds"] = array();
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
		//
	}
	
	$display['stepno'] = $stepno;	
	$template_name = "client/bulk_update_ns_step{$stepno}";
	include_once("src/append.inc.php");	

?>