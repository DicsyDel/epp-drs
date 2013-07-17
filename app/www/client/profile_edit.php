<? 
	require_once('src/prepend.inc.php');

	if ($_POST)
	{
		$Validator = Core::GetValidatorInstance();
		
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
	        		
		if (count($err) == 0)
		{
			$Client = Client::Load($_SESSION['userid']);
			$oldClient = clone $Client;
						
		    $Client->Name = $post_name;
		    $Client->Organization = $post_org;
		    $Client->Business = $post_business;
		    $Client->Address = $post_address;
		    $Client->Address2 = $post_address2;
		    $Client->City = $post_city;
		    $Client->State = $post_state;
		    $Client->Country = $post_country;
		    $Client->ZipCode = $post_zipcode;
		    $Client->Phone = $post_phone;
		    $Client->Fax = $post_fax;
			
			foreach ((array)$_POST["add"] as $k=>$v)
				$Client->{$k} = $v;
			
			try
			{
				$Client->Save();
			}
			catch(Exception $e)
			{
				$errmsg = $e->getMessage();
			}

			if (!$errmsg)
			{
				Application::FireEvent('ClientUpdated', $oldClient, $Client);
				
				// CoreUtils::Redirect to users view page
				$okmsg = _("Profile successfully updated");
				CoreUtils::Redirect("index.php");
			}
		}
	}

	if (!$_POST)
		$display["attr"] = $db->GetRow("SELECT * FROM users WHERE id = ?", array($_SESSION['userid']));
	else
		$display["attr"] = $_POST;
		
	$display["countries"] = $db->GetAll("SELECT * FROM countries");
	
	foreach($db->GetAll("SELECT * FROM client_fields") as $v)
	{
	    $display["additional_fields"][$v["title"]] = $v;
		if ($display["additional_fields"][$v["title"]]["type"] == "SELECT")
		{
			$values = unserialize($v["elements"]);
			foreach($values[0] as $kk=>$vv)
            	$display["additional_fields"][$v["title"]]["values"][$vv] = $values[1][$kk];
		}
		$display["contactinfo"][$v["name"]] = $db->GetOne("SELECT value FROM client_info WHERE clientid=? AND fieldid=?", array($_SESSION['userid'], $v["id"]));
	}

	$display["help"] = sprintf(_("This page allows you to edit your %s account profile. This profile is not related to your contacts."), CONFIG::$COMPANY_NAME);
	$display['phone_widget'] = Phone::GetInstance()->GetWidget();
	
	require_once ("src/append.inc.php");
?>