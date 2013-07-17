<?php require_once('src/prepend.inc.php'); ?>
<?
	// Add
	if ($_POST)
	{
		$DBEmailCheck = $db->GetOne("SELECT COUNT(*) FROM users WHERE email=?", array($post_email));
		if ($DBEmailCheck > 0)
			$err[] = "This E-mail already exists in database";
		
		$login = CONFIG::$USER_PREFIX.rand(10000000, 99999999);
		$password = $Crypto->Sault(10);
		
		if (sizeof($err) == 0)
		{
			$Client = new Client($login, $Crypto->Hash($password), $post_email);
			$Client->Status = 1;
			$Client->SetSettingValue('inline_help', 1);
			try
			{
				$userid = $Client->Save()->ID;					
			}
			catch(Exception $e)
			{
				$errmsg = $e->getMessage();
			}
			
			if (!$errmsg)
			{
				$info = $db->GetRow("SELECT * FROM users WHERE id='{$userid}'");
				
				Application::FireEvent('ClientCreated', $Client);
				
				$args = array("client" => $info, "login"=>$Client->Login, "password"=>$password);
				mailer_send("root_newclient.eml", $args, $Client->Email, $Client->Login);
			}
		}
			
		if (sizeof($err) == 0 && !$errmsg)
		{
			$okmsg = "New client created";
			CoreUtils::Redirect("users_view.php");
		}
	}

	$display["email"] = $_POST["email"];

	require_once("src/append.inc.php");
?>