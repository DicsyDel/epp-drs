<?
	require_once('src/prepend.inc.php');	

	$cfg = array();		
	$rows = $db->GetAll(
		"SELECT * FROM user_settings WHERE userid = ? AND `key` LIKE 'api_%'", 
		array($_SESSION["userid"]));
		
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
			$db->Execute("REPLACE INTO user_settings SET value=?, `key`=?, userid=?", array("1", "api_enabled", $_SESSION["userid"]));
			if (!$cfg["api_key_id"])
			{
				$key_tool = new EppDrs_Api_KeyTool();
				$keys = $key_tool->GenerateKeyPair();
				$db->Execute("REPLACE INTO user_settings SET value=?, `key`=?, userid = ?", array($keys["key-id"], "api_key_id", $_SESSION["userid"]));
				$db->Execute("REPLACE INTO user_settings SET value=?, `key`=?, userid = ?", array($keys["key"], "api_key", $_SESSION["userid"]));
			}
		}
		
	    if (count($err) == 0)
	    {
    	    foreach($_POST as $k=>$v)
    	    {
                $db->Execute("REPLACE INTO user_settings SET `value`=?, `key`=?, userid=?", array($v, $k, $_SESSION["userid"]));
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
	
	require_once ("src/append.inc.php");
?>