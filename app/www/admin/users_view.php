<?php

	require_once('src/prepend.inc.php');
	
	// Get actions
	if ($_GET["action"])
	{
		if ($get_action == "activate")
		{
			$Client = Client::Load($get_id);
			$db->Execute("UPDATE users SET status = 1 WHERE id = ?", array($Client->ID));
			
			$welcome_send = $Client->GetSettingValue("welcome_send");
			$pwd = $Client->GetSettingValue("pwd");
			if (!$welcome_send)
			{
				$args = array(
					"login" => $Client->Login, 
					"password" => $pwd, 
					"client" => array("name" => $Client->Name)				
				);
				mailer_send("signup.eml", $args, $Client->Email, $Client->Name);
					
				$Client->ClearSettings("pwd");
				$Client->SetSettingValue("welcome_send", 1);
			}
			
			$okmsg = "1 user activated";
			CoreUtils::Redirect("users_view.php");			
		}
		
		if ($get_action == "deactivate")
		{
			$db->Execute("UPDATE users SET status = 0 WHERE id = ?", array($get_id));			
			$okmsg = "1 user deactivated";
			CoreUtils::Redirect("users_view.php");			
		}
	}
	
	// Post actions
	if ($_POST)
	{
		if ($post_action == "del")
		{
			// Delete users
			$i = 0;			
			foreach ((array)$post_id as $k=>$v)
			{
				$i++;
				try
				{
					$Client = Client::Load($v);
					$Client->Delete();
				}
				catch(Exception $e)
				{
					$err[] = $e;
				}
				
				Application::FireEvent('ClientDeleted', $Client);
			}
			
			if (!$err)
			{
				$okmsg = "{$i} users deleted";
				CoreUtils::Redirect("users_view.php");
			}
		}
		elseif($post_action == "mail")
		{
			// Resend registration email
			$i = 0;
			foreach ((array)$post_id as $k=>$v)
			{
				$info = $db->GetRow("SELECT * FROM users WHERE id='{$v}'");
				$password = $Crypto->Sault(10);
				
				$db->Execute("UPDATE users SET password='".$Crypto->Hash($password)."' WHERE id='{$v}'");
				
				$args = array("client" => $info, "login"=>$info["login"], "password"=>$password);
				mailer_send("signup.eml", $args, $info["email"], $info["name"]);
				
				$i++;
			}
			
			$okmsg = "{$i} email messages sent";
			CoreUtils::Redirect("users_view.php");
		}
	}


	$display["authHash"] = $_SESSION["admin_hash"];
	$display["load_extjs"] = true;
	
	require_once ("src/append.inc.php");
?>
