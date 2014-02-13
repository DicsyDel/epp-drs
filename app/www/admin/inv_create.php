<?php 
	require_once('src/prepend.inc.php');
	
    if ($_POST) 
	{
		if (count($err) == 0)
		{			
			try
			{
				$Invoice = new Invoice(INVOICE_PURPOSE::CUSTOM, 0, $post_userid);
				$Invoice->SetTotal($post_total);
				$Invoice->Description = $post_description;
				$Invoice->Save();
			}
			catch(Exception $e)
			{
				$errmsg = $e->getMessage();
			}
			
			if (!$errmsg)
			{
				$okmsg = _("Invoice successfully created");
				CoreUtils::Redirect("inv_view.php");
			}
		}
	}
	
	$display["users"] = $db->GetAll("SELECT * FROM users");
	foreach ($display["users"] as &$user)
	{
		if ((float)$user["vat"] > 0)
			$user["client_vat"] = (float)$userinfo["vat"];
		else
			$user["client_vat"] = (float)$db->GetOne("SELECT vat FROM countries WHERE code=?", array($user["country"]));
	}
	
	$display["selected_user"] = $req_userid;
	$display["help"] = "";   
	require_once('src/append.inc.php');
?>