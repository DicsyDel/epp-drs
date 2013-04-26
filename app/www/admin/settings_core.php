<?php require_once('src/prepend.inc.php'); ?>
<?
	
	if (CONFIG::$DEV_DEMOMODE != 1)
	{
		if ($_POST)
		{
		    unset($_POST["Submit"]);
		    unset($_POST["id"]);
		    unset($_POST["page"]);
		    unset($_POST["f"]);
			
			$_POST['email_copy'] = ($_POST['email_copy']) ? '1' : '0';
		    $_POST['enable_managed_dns'] = ($_POST['enable_managed_dns']) ? '1' : '0';
		    $_POST['prepaid_mode'] = ($_POST['prepaid_mode']) ? '1' : '0';
		    $_POST['mail_poll_messages'] = ($_POST['mail_poll_messages']) ? '1' : '0';
	    	$_POST['auto_delete'] = ($_POST['auto_delete']) ? '1' : '0';
	    	$_POST['client_manual_approval'] = ($_POST['client_manual_approval']) ? '1' : '0';
		    
		    
		    
		    $_POST['allow_A_record'] = ($_POST['allow_A_record']) ? '1' : '0';
		    $_POST['allow_MX_record'] = ($_POST['allow_MX_record']) ? '1' : '0';
		    $_POST['allow_NS_record'] = ($_POST['allow_NS_record']) ? '1' : '0';
		    $_POST['allow_CNAME_record'] = ($_POST['allow_CNAME_record']) ? '1' : '0';
		    $_POST['inline_help'] = ($_POST['inline_help']) ? '1' : '0';
		    $_POST['site_url'] = $_POST['site_url_scheme'] . "://" . $_POST['site_url'];
		    unset($_POST['site_url_scheme']);

		    	        
		    if ($_POST["ns1"] == "")
		       $err[] = _("Default nameserver #1 must be specified");
		       
		    if ($_POST["ns2"] == "")
		       $err[] = _("Default nameserver #2 must be specified");
		       
		    if (!Phone::IsValidFormat($_POST['phone_format']))
		    	$err[] = _("Incorrect phone format. Please follow the pattern.");
		    	
		    if ($_FILES["logo"] && $_FILES["logo"]["size"] 
		    	&& !in_array($_FILES["logo"]["type"], array("image/gif")))
		    {
		    	$err[] = _("Invalid company logo file format. Only gif allowed");
		    }
		    	
		    
		    if (count($err) == 0)
		    {
	    	    foreach($_POST as $k=>$v)
	    	    {
	    	        if ($k != 'pass' && $k != 'pass2')
	    	            $db->Execute("REPLACE INTO config SET value=?, `key`=?", array($v, $k));
	    	    }
	    	    
	    		if ($post_pass != "******")
	    		{
	    			if ($post_pass == $post_pass2)
	    				$db->Execute("UPDATE config SET `value`=? WHERE `key` = 'pass'", array($Crypto->Hash($post_pass)));
	    			else
	    				$errmsg = _("Two passwords do not match");
	    		}
	    
	    		if ($_FILES["logo"] &&  $_FILES["logo"]["size"])
	    		{
	    			if (move_uploaded_file($_FILES["logo"]["tmp_name"], CACHE_PATH."/logo.gif")) 
	    			{
	    				chmod(CACHE_PATH."/logo.gif", 0777);
	    			}
	    			else
	    				$errmsg = _("Cannot upload file");
	    		}
	    		
	    		if (!$errmsg)
	    		{
	    			$okmsg = _("Settings saved");
	    			CoreUtils::Redirect("settings_core.php");
	    		}
		    }
		}
		
		$cfg = $db->GetAll("SELECT * FROM config");
		$countries = $db->GetAll("SELECT * FROM countries ORDER BY name");
	
		foreach($cfg as $k=>$v)
	   		$display[$v["key"]] = $v["value"];   
	   		
	   	$display["pass"] = "******";
	   	foreach ($countries as $row)
	   		$display["country_list"][$row["code"]] = $row["name"];
	   	$display["site_url_https"] = "https" == substr($display["site_url"], 0, 5);
	   	$display["site_url"] =  preg_replace('/^http(s)?\:\/\//', '', $display["site_url"]);
	}
	else 
	{
		$errmsg = _("Configuration is disabled in demo mode. No data being displayed or submitted.");
	}
	
	$display['currency'] = htmlspecialchars($display['currency']);
		
	require_once ("src/append.inc.php");
?>