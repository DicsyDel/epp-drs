<?
	
	if ($_SESSION['wizard']['check_user']['reg_type'] == 'newclient')
	{
		$_POST = array_map("trim", $_POST);
		
		// Validate new client fields
		$Validator = new Validator();
							
		if (!$Validator->IsEmail($_POST["email"]))	
			$err[] = _("Invalid E-mail address");
			
	    //
	    if (!$Validator->IsNotEmpty($_POST["login"]))
            $err[] = _("Login required");
	      
	    if (!$Validator->IsNotEmpty($_POST["name"]))
	        $err[] = _("Name required");
	        
	    if (!$Validator->IsNotEmpty($_POST["address"]))
	        $err[] = _("Address 1 required");
	        
	    if (!$Validator->IsNotEmpty($_POST["city"]))
	        $err[] = _("Town/City required");
	        
	    if (!$Validator->IsNotEmpty($_POST["state"]))
	        $err[] = _("State required");
	        
	    if (!$Validator->IsNotEmpty($_POST["country"]))
	        $err[] = _("Country required");
	        
	    if (!$Validator->IsNotEmpty($_POST["zipcode"]))
	        $err[] = _("Postal code required");
	        
	        
	    if ($Validator->IsNotEmpty($_POST['phone']))
	    {
	    	if (!Phone::GetInstance()->IsPhone($_POST["phone"]))
	        	$err[] = _("Phone invalid");
	    }
		else
			$err[] = _('Phone required');	        
	        
		if ($Validator->IsNotEmpty($_POST['fax']))
		{
			if (!Phone::GetInstance()->IsPhone($_POST['fax']))
				$err[] = _('Fax invalid');
		}
	
			
		$DBEmailCheck = $db->GetOne("SELECT COUNT(*) FROM users WHERE email=?", array($post_email));
		if ($DBEmailCheck > 0)
			$err[] = _("E-mail already exists in database");
			
	    $DBLoginCheck = $db->GetOne("SELECT COUNT(*) FROM users WHERE login=?", array($post_login));
	    if ($DBLoginCheck > 0)
			$err[] = _("Login already exists in database");
			
		if (count($err) != 0)
			$step = "newclient";
	}
?>