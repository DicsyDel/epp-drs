<?php require_once('src/prepend.inc.php'); ?>
<?
	// Add
	if ($_POST)
	{
		$_POST['min_domains'] = (int)$post_min_domains;
		$_POST['min_balance'] = (float)str_replace(",", ".", $post_min_balance);
		
		$set = "name = ?";
		$bind = array($post_name);
		
		foreach(array("min_domains", "min_balance") as $k)
		{
			if ($_POST[$k])
			{
				$set .= ", $k=?";
				$bind[] = $_POST[$k];
			}
			else
				$set .= ", $k=NULL";	
		}
		
		if (!$post_id)
		{
    		if (sizeof($err) == 0)
    		{
    			$db->Execute("INSERT INTO packages SET $set", $bind);
    		}
    			
    		if (sizeof($err) == 0)
    		{
    			$okmsg = _("Discount package created succesfully");
    			CoreUtils::Redirect("packages_view.php");
    		}
		}
		else 
		{
		    if (sizeof($err) == 0)
    		{
    			$bind[] = $post_id;
    			$db->Execute("UPDATE packages SET $set WHERE id = ?", $bind);
    		}
    			
    		if (sizeof($err) == 0)
    		{
    			$okmsg = _("Discount package updated succesfully");
    			CoreUtils::Redirect("packages_view.php");
    		}   
		}
	}

	if ($req_id)
	{
	    $packageinfo = $db->GetRow("SELECT * FROM packages WHERE id=?", array($req_id));
	    if (!$packageinfo)
	       CoreUtils::Redirect("packages_view.php");
	       
	    $display = array_merge($display, $packageinfo);
	}
 
	$display["title"] = _("Setings&nbsp;&raquo;&nbsp;Discount packages&nbsp;&raquo;&nbsp;Add/Edit");
	$display["help"] = _("Discount packages are the way to give discount to specific users. You can set discount percentage for each domain extension on <a href='tld_view.php' target='_blank'>Settings&nbsp;&raquo;&nbsp;Registry modules&nbsp;&raquo;&nbsp;Configure domain extensions</a>.");
	
	require_once("src/append.inc.php");
?>