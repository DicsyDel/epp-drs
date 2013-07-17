<?
	require_once('src/prepend.inc.php');
	
	// Delete	
	$paging = new SQLPaging(null, $req_pn, CONFIG::$PAGING_ITEMS);
	
	$sql = "SELECT * from countries WHERE id!=''";
		
	//
	//Paging
	//
	if ($req_pf && !$post_filter_q)
		$_SESSION["filter"] = null;
	
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = " ORDER BY name ASC";
	$paging->ApplyFilter($post_filter_q ? $post_filter_q : $req_pf, array("name", "code"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("admin/inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("admin/inc/paging.tpl");
		
	
	//
	// Rows
	//
	foreach ((array)$db->GetAll($paging->SQL) as $k=>$row)
	{
		if ($_POST && $post_actionsubmit)
		{
			if (isset($post_enabled[$row["id"]]) && $row['enabled'] == 0)
			{
				$db->Execute("UPDATE countries SET enabled='1' WHERE id=?", array($row["id"]));
				$row["enabled"] = 1;
			}
			elseif (!isset($post_enabled[$row["id"]]) && $row['enabled'] == 1)
			{
				$db->Execute("UPDATE countries SET enabled='0' WHERE id=?", array($row["id"]));
				$row["enabled"] = 0;
			}
			
			$db->Execute("UPDATE countries SET vat=? WHERE id=?", array(round($post_vat[$row["id"]], 2), $row["id"]));
			
			$row["vat"] = $post_vat[$row["id"]];
			
			$mess = _("Countries successfully saved");
		}
		
		$display["rows"][] = $row;
	}
	
	if ($_POST && $post_actionsubmit)
		UI::Redirect("countries.php");
	
	$display["pn"] = $req_pn;
	$display["pt"] = $req_pt;
	$display["pf"] = $post_filter_q ? $post_filter_q : $req_pf;
	
	$display["help"] = "";
	
	require_once ("src/append.inc.php");
?>