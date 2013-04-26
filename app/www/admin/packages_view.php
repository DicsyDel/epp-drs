<?

	require_once('src/prepend.inc.php');

	// Post actions
	if ($_POST && $post_actionsubmit)
	{
		if ($post_action == "del")
		{
			// Delete users
			$i = 0;			
			foreach ((array)$post_delete as $k=>$v)
			{
				$i++;
				$db->Execute("DELETE FROM packages WHERE id='{$v}'");	
				$db->Execute("UPDATE users SET packageid='0' WHERE packageid='{$v}'");
			}
			
			$okmsg = sprintf(_("%d packages deleted", $i));
			CoreUtils::Redirect("packages_view.php");
		}
	}


	$sql = "SELECT * from packages WHERE id>0";
			
	if ($get_id)
		$sql .= " AND id='{$get_id}'";
	
	//
	//Paging
	//
	$paging = new SQLPaging($sql);
	$paging->AdditionalSQL = "ORDER BY name ASC";
	$paging->ApplyFilter($_POST["filter_q"], array("name"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("admin/inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("admin/inc/paging.tpl");


	//
	// Rows
	//
	foreach ((array)$db->GetAll($paging->SQL) as $k=>$row)
	{
		$display["rows"][$k] = $row;
		
		$display["rows"][$k]["num_users"]  = $db->GetOne("SELECT COUNT(*) FROM users WHERE packageid='{$row['id']}'");
	}
	
	$display["hash"] = $_SESSION["admin_hash"];
	$display["help"] = _("Discount packages are the way to give discount to specific users. You can set discount percentage for each domain extension on <a href='tld_view.php' target='_blank'>Settings&nbsp;&raquo;&nbsp;Registry modules&nbsp;&raquo;&nbsp;Configure domain extensions</a>.");
	
	$display["page_data_options"] = array(
											array(	"name" => "Delete", 
													"action" => "del"
												 )
										);  
	$display["page_data_options_add"] = true;
	
	require_once ("src/append.inc.php");
?>