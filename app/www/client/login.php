<? 
	require_once('src/prepend.inc.php');
	
	$display["title"] = _("Login");

	
	if (isset($get_logout))
	{
		Application::FireEvent('Logout', $_SESSION["login"]);
		
		session_destroy();
		$mess = _("Succesfully logged out");
		CoreUtils::Redirect("login.php");
	}
	
	if ($get_action == "confirmeml" && $get_code && $get_id)
	{
		$user = $db->GetRow("SELECT * FROM users WHERE id=?", array($get_id));
		if (!$user)
		{
			$mess = _("Invalid confirmation link");
			CoreUtils::Redirect ("login.php");
		}
		
		// Generate new hash
		$hash = base64_encode($Crypto->Hash("{$user['id']}::{$user['password']}::{$user['nemail']}"));
		if ($hash == $get_code)
		{
			$db->Execute("UPDATE users SET email=nemail WHERE id=?", array($user['id']));
			
			$mess = _("Email address was succesfully confirmed and changed");
			CoreUtils::Redirect ("login.php");
		}
		else
		{
			$mess = _("Invalid confirmation link");
			CoreUtils::Redirect ("login.php");
		}
	}
	
	if ($get_action=="sendpwd" && $get_code && $get_id)
	{
		$user = $db->GetRow("SELECT * FROM users WHERE id=?", array($get_id));
		if (!$user)
		{
			$mess = _("Invalid confirmation link");
			CoreUtils::Redirect ("login.php");
		}
		
		// Generate new hash
		$hash = base64_encode($Crypto->Hash("{$user['id']}::{$user['password']}::{$user['email']}"));
		if ($hash == $get_code)
		{
			$new_pass = $Crypto->Sault(8);
			
			$args = array(	"login"		=> $user["login"], 
							"password"	=> $new_pass,
							"client"	=> $user
					);
			mailer_send("newpassword.eml", $args, $user["email"], $user["name"]);
		
			$db->Execute("UPDATE users SET password=? WHERE id=?", array($Crypto->Hash($new_pass), $user['id']));
			
			$mess = _("Your password has been reset and emailed to you");
			CoreUtils::Redirect ("login.php?p=1");
		}
		else
		{
			$mess = _("Invalid confirmation link");
			CoreUtils::Redirect ("login.php");
		}
	}
	
	if ($get_action == "lostpwd")
	{
		if ($_POST)
		{
			$user = $db->GetRow("SELECT * FROM users WHERE email=?", array($post_email));
			if (!$user)
			{
				$mess = _("No such email in database");
				CoreUtils::Redirect ("login.php");
			}
			
			$code = base64_encode($Crypto->Hash("{$user['id']}::{$user['password']}::{$user['email']}"));
			
			$link = sprintf("%s/client/login.php?id=%s&code=%s&action=sendpwd", CONFIG::$SITE_URL, $user['id'], $code);
				
			$args = array( "link" => $link, "client" => $user);
			mailer_send("password_change_confirm.eml", $args, $user["email"], $user["name"]);
			
			$mess = _("Please confirm password change. E-mail has been send to you.");
			CoreUtils::Redirect ("login.php");
		}
	}
	
	if ($_POST || $get_hash)
	{
		if (!$get_hash)
		{
			Application::FireEvent('LoginAttempt', $post_login, $post_pass);
			
			try
			{
				$Client = Client::LoadByLogin($post_login);
			}
			catch (Exception $e)
			{
				$mess = _("Client with specified login not found in database");
				CoreUtils::Redirect("login.php");
			}
			
			if ($Crypto->Hash($post_pass) == $Client->Password)
			{
				if ($Client->Status == 1)
				{
					$sault = $Crypto->Sault();
					$_SESSION["sault"] = $sault;
					$_SESSION["userid"] = $Client->ID;
					$_SESSION["login"] = $Client->Login;
					
					$_SESSION["hash"] = $Crypto->Hash("{$Client->Login}:{$Client->Password}:{$sault}");
					
					Application::FireEvent('LoginSuccess', $post_login, $post_pass);
					
					if (!$_SESSION["REQUEST_URI"])
						CoreUtils::Redirect ("/client/domains_view.php");
					else 
						CoreUtils::Redirect($_SESSION["REQUEST_URI"]);
				}
				else
				{
					$mess = _("Client account is inactive");
					CoreUtils::Redirect("login.php");
				}
				
			}
			else
			{
				$mess = _("Invalid password");
				CoreUtils::Redirect("login.php");
			}
		}
		else
		{
			$newhash = $Crypto->Hash(CONFIG::$LOGIN.":".CONFIG::$PASS.":".$_SESSION["sault"]);
			
			if (trim($newhash) == trim($get_hash))
			{
				$user = $db->GetRow("select * from users where login=?", array($get_u));
				//$_SESSION["sault"] = "$sault";
				$_SESSION["userid"] = $user["id"];
				$_SESSION["login"] = $user["login"];
				$_SESSION["registrant_id"] = $user["registrant_id"];
				
				// XXX: Old client cleanup
				unset($_SESSION["domain"], $_SESSION["selected_domain"], $_SESSION["TLD"]);
				
				$_SESSION["hash"] = $Crypto->Hash("{$user["login"]}:{$user["password"]}:{$_SESSION["sault"]}");
				
				CoreUtils::Redirect ("/client/domains_view.php");
			}
			else
				CoreUtils::Redirect ("/admin/index.php");
		}
			
	}
	
	$display["action"] = $get_action;
	
	require_once ("src/append.inc.php");
?>