<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Settings&nbsp;&raquo;&nbsp;Managed DNS&nbsp;&raquo;&nbsp;Nameservers&nbsp;&raquo;&nbsp;View all";
	$display["help"] = _("This page lists all your nameservers. EPP-DRS will save all new zones and modified zones on all nameservers.");

	if ($_POST && $post_actionsubmit)
	{
		if ($_POST["action"] == "delete")
		{
			foreach ((array)$_POST["delete"] as $dd)
			{	
				$info = $db->GetRow("SELECT * FROM nameservers WHERE id='{$dd}'");
			    if ($info)
			    {
    			    $db->Execute("DELETE FROM nameservers WHERE id='{$dd}'");
    				$i++;
			    }
			}
			
			$okmsg = sprintf(_("%s nameservers deleted"), $i);
			CoreUtils::Redirect("ns_view.php");
		}
	};
	
	$sql = "SELECT * FROM nameservers";


	//Paging
	$paging = new SQLPaging($sql);
	$paging->ApplyFilter($_POST["filter_q"], array("host"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("admin/inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("admin/inc/paging.tpl");

	
	// Rows
	$display["rows"] = $db->GetAll($paging->SQL);
	
	$display["page_data_options"] = array(array("name" => "Delete", "action" => "delete"));
	
	if (CONFIG::$DEV_DEMOMODE == 1)
	{
		$errmsg = "In demo mode, zone save cronjob is disabled. DNS zones changes will not be commited to NS servers.";
	}
	
	require("src/append.inc.php"); 
	
?>