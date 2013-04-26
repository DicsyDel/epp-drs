<?
	require_once('src/prepend.inc.php');	

	if (CONFIG::$DEV_DEMOMODE != 1)
	{
		$cfg = array();		
		$rows = $db->GetAll("SELECT * FROM config WHERE `key` LIKE 'api_%'");
		foreach($rows as $row)
			$cfg[$row["key"]] = $row["value"];
		
		if ($_POST)
		{
		    unset($_POST["Submit"]);
		    unset($_POST["id"]);
		    unset($_POST["page"]);
		    unset($_POST["f"]);
			
			$_POST['api_enabled'] = ($_POST['api_enabled']) ? '1' : '0';
			if (array_key_exists("api_allowed_ips", $_POST))
				$_POST['api_allowed_ips'] = join(",", array_filter($_POST['api_allowed_ips'])); 

			if ($_POST["api_enabled"] && !$cfg["api_enabled"])
			{
				$db->Execute("REPLACE INTO config SET value=?, `key`=?", array("1", "api_enabled"));
				if (!$cfg["api_key_id"])
				{
					$key_tool = new EppDrs_Api_KeyTool();
					$keys = $key_tool->GenerateKeyPair();
					$db->Execute("REPLACE INTO config SET value=?, `key`=?", array($keys["key-id"], "api_key_id"));
					$db->Execute("REPLACE INTO config SET value=?, `key`=?", array($keys["key"], "api_key"));
				}
			}
			
		    if (count($err) == 0)
		    {
	    	    foreach($_POST as $k=>$v)
	    	    {
    	            $db->Execute("REPLACE INTO config SET `value`=?, `key`=?", array($v, $k));
	    	    }
	    		
	    		if (!$err)
	    		{
	    			$okmsg = _("Settings saved");
	    			CoreUtils::Redirect("settings_api.php");
	    		}
		    }
		}
		
		
		foreach ($cfg as $k => $v)	
			$display[$k] = $v;
	   	$allowed_ips = explode(",", $display["api_allowed_ips"]);
	   	$display["api_allowed_ips"] = array();
	   	foreach (range(0, 4) as $i) {
	   		$display["api_allowed_ips"][$i] = $allowed_ips[$i]; 
	   	}
	}
	else 
	{
		$errmsg = _("Configuration is disabled in demo mode. No data being displayed or submitted.");
	}
	
	require_once ("src/append.inc.php");
?>