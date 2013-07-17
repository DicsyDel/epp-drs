<? 
	require_once('src/prepend.inc.php');
	
	//FIXME:
	CoreUtils::Redirect("ipurposes_view.php");
	
    if ($_POST) 
	{
		if (count($err) == 0)
		{			
			if ($post_id)
		    {
		       
		       $db->Execute("UPDATE invoice_purposes SET name = ?, description = ? WHERE id = ?", array(
		                              $post_name,
		                              $post_description,
		                              $post_id
		                            )
		                    );

		        $okmsg = _("Invoice purpose successfully updated");
		        CoreUtils::Redirect("ipurposes_view.php");
		    }
		    else 
		    {
		         
		        $db->Execute("INSERT INTO invoice_purposes SET name = ?, description = ?, `key`=?, issystem='0'", array(
		                              $post_name,
		                              $post_description,
		                              preg_replace("/[^A-Za-z0-9]+/", "_", $post_name)
		                            )
		                    );
	                             
		        $okmsg = _("Invoice purpose create successfully");
		        CoreUtils::Redirect("ipurposes_view.php");
		    }
		}
	}
	
	if (!$req_id)
	{
	    $display = array_merge($display, $_POST);
	}
	else 
	{
	    $id = (int)$req_id;
	    $info = $db->GetRow("SELECT * FROM invoice_purposes WHERE id = ?", array($id));
	    $display = array_merge($display, $info);
	}

	$display["help"] = "";   
	require_once('src/append.inc.php');
?>