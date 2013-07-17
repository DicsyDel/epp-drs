<?

	require_once('src/prepend.inc.php');

	if ($_POST)
	{
		// get user information from database
		$user = $db->GetRow("SELECT * 
								FROM users 
							WHERE id=? AND password = ?", array($_SESSION['userid'], $Crypto->Hash($post_pass)));
							
		$Validator = new Validator();
		if (!$Validator->IsEmail($post_email))
			$err[] = sprintf(_("'%s' is not a valid email address"), $post_email);
		
		if (!$user)
			$err[] = _("Incorrect password");
	
		if ($post_email == $user["email"])
			$err[] = _("New email address cannot be the same as the old one");
			
		// if user password is correct update info
		if (count($err) == 0)
		{
			$db->Execute("UPDATE users SET nemail=? WHERE id=?", array($post_email, $_SESSION['userid']));
			
			$code = base64_encode($Crypto->Hash("{$user['id']}::{$user['password']}::{$post_email}"));
			
			$link = CONFIG::$SITE_URL."/client/login.php?id={$user['id']}&code={$code}&action=confirmeml";
				
			$args = array("link" => $link, "client" => $user);
			mailer_send("email_change_confirm.eml", $args, $user["email"], $user["name"]);
							
			$okmsg = _("Confirmation email was sent on the new email address that you entered. Please click on a link inside email to confirm email change.");
			CoreUtils::Redirect ("index.php");	
		}
	}
	
	$display["info"] = $db->GetRow("SELECT email FROM users WHERE id=?", array($_SESSION['userid']));
	$display["help"] = sprintf(_("This page allows you to change your account email address. %s sends sensitive information on this address, thus you must enter your current account password, and then click a link inside email message that will be sent to you, to confirm email change."), CONFIG::$COMPANY_NAME);

	require_once ("src/append.inc.php");
?>