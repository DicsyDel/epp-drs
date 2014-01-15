<?php 
	require_once('src/prepend.inc.php');

	if ($_POST)
	{
		if ($post_id)
		{
			
			// check new login in database
			$chk = $db->GetOne("SELECT * FROM users WHERE login=? AND id!=?", array($post_login, $post_id));
			
			if (!$chk)
			{
				$Client = Client::Load($post_id);
				$oldClient = clone $Client;
				
				// if we update password
				if ($post_password!='******')
					$Client->Password = $Crypto->Hash($post_password);
					
				$Client->PackageID = (int)$post_packageid;
				$Client->PackageFixed = (int)$post_package_fixed;
			    $Client->Login = $post_login;
			    $Client->Email = $post_email;			
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
			    $Client->VAT = strlen($post_vat) && (float)$post_vat > -1 ? (float)$post_vat : -1;
			    			    
				foreach ((array)$_POST["add"] as $k=>$v)
				    $Client->{$k} = $v;
				           
				if (ENABLE_EXTENSION::$PREREGISTRATION)
				{
					$Client->SetSettingValue('domain_preorder', (int)$post_settings['domain_preorder']);
					$Client->SetSettingValue('bill_for_domain_preorder', (int)$post_settings['bill_for_domain_preorder']);
				}
				
				try
				{
					$Client->Save();
				}
				catch (Exception $e)
				{
					$errmsg = $e->getMessage();
				}
				
			    if (!$errmsg)
			    {
			    	Application::FireEvent('ClientUpdated', $oldClient, $Client);
			    	
			    	// CoreUtils::Redirect to users view page
					$okmsg = "User edited";
					CoreUtils::Redirect("users_view.php");
			    }
			}
			else
				$err[] = "Username with same login already exists in database! Please select another and try again.";
		}
	}

	if (!$_POST)
		$display["attr"] = $db->GetRow("SELECT * FROM users WHERE id = ?", array($get_id));
	else
		$display["attr"] = $_POST;
		
	$display["id"] = $req_id;
	$display["Client"] = Client::Load($req_id);
	$display["attr"]["vat"] = (int)$display["attr"]["vat"] > -1 ? $display["attr"]["vat"] : '';  
	$display["title"] = "Client&nbsp;&raquo;&nbsp;Edit";
	$display["packages"] = $db->GetAll("SELECT * FROM packages");
	$display["countries"] = $db->GetAll("SELECT * FROM countries");
	$display['phone_widget'] = Phone::GetInstance()->GetWidget();
	
	$list = $db->GetAll("SELECT * FROM client_fields");
	foreach((array)$list as $v)
	{
	    $display["additional_fields"][$v["title"]] = $v;
		if ($display["additional_fields"][$v["title"]]["type"] == "SELECT")
		{
			$values = unserialize($v["elements"]);
			foreach($values[0] as $kk=>$vv)
            	$display["additional_fields"][$v["title"]]["values"][$vv] = $values[1][$kk];
		}
		$display["contactinfo"][$v["name"]] = $db->GetOne("SELECT value FROM client_info WHERE clientid=? AND fieldid=?", array($get_id, $v["id"]));
	}
	
	require_once ("src/append.inc.php");
?>