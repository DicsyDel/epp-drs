<?php
	require_once 'src/prepend.inc.php';
	
	if ($_POST)
	{
		if (!$post_userid)
			$err[] = sprintf(_("%s is required"), "Client");
		if (!$post_description)
			$err[] = sprintf(_("%s is required"), "Description");
		$post_amount = floatval(str_replace(",", ".", $post_amount));
		if (!$post_amount)
			$err[] = sprintf(_("%s is required"), "Amount");
			
		if (!$err)
		{
			try
			{
				$Client = Client::Load($post_userid);
				$Balance = DBBalance::GetInstance()->LoadClientBalance($Client->ID);
				$Operation = $Balance->CreateOperation($post_type, $post_amount);
				$Operation->Description = $post_description;
				
				$Balance->ApplyOperation($Operation);
			}
			catch (Exception $e)
			{
				$err[] = $e->getMessage();
			}
		}
		
		if (!$err)
		{
			$okmsg = "Balance operation apply successfully";
			CoreUtils::Redirect("balance_operation.php");
		}
	}
	
	$display["attr"] = array_merge($_GET, $_POST);
	$display["users"] = $db->GetAll("SELECT * FROM users ORDER BY login");
	$display["currency"] = CONFIG::$CURRENCY;

	require_once 'src/append.inc.php';
?>