<?

	require_once('src/prepend.inc.php');

	if ($_POST)
	{
		// Get user information from database
		$user = $db->GetRow("SELECT * 
							FROM users 
							WHERE id=? AND password = ?
						", array($_SESSION['userid'], $Crypto->Hash($post_o_pass)));
		// if user exists
		if ($user)
		{
			// Compare two passwords
			if ($post_n_pass != $post_n_pass_c)
				$err["new"] = _("Two password do not match");
			
			if (strlen($post_n_pass) < 6)
				$err[] = _("Password must be at least 7 symbols long");
				
			if (count($err) == 0)
			{
				// update password in database
				$db->Execute("UPDATE users SET password=? WHERE id=?", array($Crypto->Hash($post_n_pass), $_SESSION['userid']));
				$okmsg = _("Password successfully changed");
				CoreUtils::Redirect ("login.php");
			}
		}
		else
			$err["old"] = _("Old password incorrect");
	}
	
	
	require_once ("src/append.inc.php");
?>