<?
	if ($_POST["reg_type"] == "newclient")
		$step = "newclient";
	else
	{
		$chk = $db->GetRow("SELECT * FROM users WHERE login = ? AND password = ?", array($post_login, $Crypto->Hash($post_password)));
		if (!$chk)		
			$err[] = "Invalid login or password";
		
		if (count($err) == 0)
		{
			$_SESSION['userid'] = $chk['id'];
			$_SESSION['c_login'] = $post_login;
			$_SESSION['c_password'] = $Crypto->Hash($post_password);
			$step = "placeorder";
		}
		else 
			$step = "check_user";
			
		$user_checked = true;
	}
?>