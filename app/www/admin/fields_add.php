<? 
	require_once('src/prepend.inc.php');
	
    $display["title"] = _("Settings &nbsp;&raquo;&nbsp; Client profile Additional fields&nbsp;&raquo;&nbsp;Add / Edit");
	
	if ($_POST) 
	{
		if (count($err) == 0)
		{			
			if ($post_type == 'SELECT')
				$elements = serialize(array($post_select_values, $post_select_text));
			else
				$elements = "";
			
			if ($post_id)
		    {
		       
		       $db->Execute("UPDATE client_fields SET type = ?, name = ?, defval = ?, title = ?, required=?, elements=? WHERE id = ?", array(
		                              $post_type,
		                              $post_name,
		                              $post_defval,
		                              $post_title,
		                              (($post_isrequired == 1) ? 1 : 0),
		                              $elements,
		                              $post_id
		                            )
		                    );

		        $okmsg = _("Field successfully updated");
		        CoreUtils::Redirect("fields_view.php");
		    }
		    else 
		    {
		         
		        $db->Execute("INSERT INTO client_fields SET type = ?, name = ?, defval = ?, required=?, title = ?, elements=?", array(
		                              $post_type,
		                              $post_name,
		                              $post_defval,
		                              (($post_isrequired == 1) ? 1 : 0),
		                              $post_title,
		                              $elements
		                            )
		                    );
	                             
		        $okmsg = _("New field create successfully. It will appear on registration form immediately.");
		        CoreUtils::Redirect("fields_view.php");
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
	    $info = $db->GetRow("SELECT *, title as ftitle FROM client_fields WHERE id = ?", array($id));
	    $display = array_merge($display, $info);
	    
	    $display["SelectItems"] = unserialize($info["elements"]);
	}
	
	if ($display["type"] == 'SELECT')
	   $display["ftable_display"] = '';
	else 
	   $display["ftable_display"] = 'none';
	
	$display["noheader"] = true;
	$display["help"] = _("You can add your custom fields on client registration form. Only text input and checkbox types supported for now");   
	require_once('src/append.inc.php');
?>