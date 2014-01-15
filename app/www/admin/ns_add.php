<?php 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Settings&nbsp;&raquo;&nbsp;Managed DNS&nbsp;&raquo;&nbsp;Nameservers&nbsp;&raquo;&nbsp;Add";
	$display["help"] = "EPP-DRS communicates with nameserver via SSH2 protocol. 'System user' must be able to remotely SSH to 'Nameserver IP address', and have enough permissions to execute rndc binary, create file in 'Path to zone files folder' and modify named.conf file.
	<br>After you add a nameserver, make sure to add appropriate NS record in <a target='_blank' href='dnsdef_manage.php'>Settings&nbsp;&raquo;&nbsp;Managed DNS&nbsp;&raquo;&nbsp;Set default zone records</a>.";
	
	if ($_POST) 
	{
		$Validator = new Validator();
		
		// 
		if (!$Validator->IsAlpha($post_username))
			$err[] = _("Username must consist of letters and numbers only");
		
		//
		if (!$Validator->IsNumeric($post_port))
			$err[] = _("Server port must be a number");
		
		// Check hostname
		if (!$Validator->IsIPAddress($post_host))
			$err[] = sprintf(_("'%s' is not valid IP address"), $post_host);
	
	    if (count($err) == 0)
	    {
    		if (!$post_id)
    		{
    			$db->Execute("INSERT INTO nameservers (host, port, username, password, rndc_path, named_path, namedconf_path, isnew) values (?,?,?,?,?,?,?, 1)",
                    			array(   $post_host, 
                    			         $post_port, 
                    			         $post_username, 
                    			         $Crypto->Encrypt($post_password, LICENSE_FLAGS::REGISTERED_TO), 
                    			         $post_rndc_path, 
                    			         $post_named_path, 
                    			         $post_namedconf_path
                    			     )
    			     );    			     
    			$okmsg = _("Nameserver created successfully");
    		
    			CoreUtils::Redirect("ns_view.php");
    			
    		}
    		else
    		{
    			$password = ($post_password != '******') ? "password='".$Crypto->Encrypt($post_password, LICENSE_FLAGS::REGISTERED_TO)."'," : "";
    			$info = $db->GetRow("SELECT * FROM nameservers WHERE id='{$post_id}'");
    			unset($info["id"]);
    			
    			$db->Execute("UPDATE nameservers SET host=?, port=?, username=?, $password rndc_path=?, named_path=?, namedconf_path=?
    							WHERE id='{$post_id}'",
    							array($post_host, $post_port, $post_username, $post_rndc_path, $post_named_path, $post_namedconf_path));
    
    			$uinfo = $db->GetRow("SELECT * FROM nameservers WHERE id='{$post_id}'");
    			unset($uinfo["id"]);
    			    							
    			$okmsg = _("Nameserver updated succesfully");
    			CoreUtils::Redirect("ns_view.php");
    		}
	    }
	}
	
	if ($req_id)
	{
		$id = (int)$req_id;
		$display["ns"] = $db->GetRow("SELECT * FROM nameservers WHERE id='{$id}'");
		$display["id"] = $id;
	}
	else
		$display = array_merge($display, $_POST);
	
	if (CONFIG::$DEV_DEMOMODE == 1)
	{
		$errmsg = "In demo mode, zone save cronjob is disabled. DNS zones changes will not be commited to NS servers.";
	}
		
	require("src/append.inc.php"); 	
?>