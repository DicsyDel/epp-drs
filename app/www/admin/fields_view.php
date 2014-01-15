<?php 
    require_once('src/prepend.inc.php');

    $display["help"] = _("On this page you can edit additional form fields for client registration form.");
	
	if ($_POST && $post_actionsubmit)
	{
		if ($post_action == "del")
		{
			foreach ((array)$_POST["delete"] as $dd)
			{	
			    $db->Execute("DELETE FROM client_fields WHERE id = ?", array($dd));
   			    $db->Execute("DELETE FROM client_info WHERE fieldid=?", array($dd));
			}
			CoreUtils::Redirect("?mess=$i "._("fields deleted."));
		}
	};
	
	$sql = "SELECT * FROM `client_fields` WHERE 1 > 0";
	
	if ($get_id)
	{
	    $id = (int)$get_id;
	    $sql .= " AND id = '{$id}'";
	}
	
	//Paging
	$paging = new SQLPaging($sql);
	$paging->ApplyFilter($_POST["filter_q"], array("name"));
	//$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("admin/inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("admin/inc/paging.tpl");

	
	// Rows
	$rows = $db->SelectLimit($paging->SQL, $paging->ItemsOnPage, $paging->GetOffset());
    while ($row = $rows->FetchRow())
	   $display["rows"][] = $row;
	   
	$display["page_data_options"] = array(
											array(	"name" => "Delete", 
													"action" => "del"
												 )
										);  
	$display["page_data_options_add"] = true;
										
	require_once('src/append.inc.php');
?>