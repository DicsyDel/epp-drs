<? 
	require("src/prepend.inc.php");
		
	if (!$template_name)
	{
		Core::Load("Data/Formater");
		
		$display["title"] = _("Objects history");
		//$display["help"] = _("1");
		
	    $display["load_calendar"] = 1;
	
	    $paging = new SQLPaging();
		$sql = "SELECT * FROM objects_history WHERE 1=1";
		
		if ($req_search && $req_search != '')
		{
			$search = mysql_escape_string($req_search);
			$sql .= " AND object LIKE '%{$search}%'";
			$paging->AddURLFilter("search", $req_search);
			$display["search"] = $req_search;
		}
		
		if ($req_obj_filter && $req_obj_filter !="")
		{
			$obj = mysql_escape_string($req_obj_filter);
			$sql .= " AND type = '{$obj}'";
			$paging->AddURLFilter("obj_filter", $req_obj_filter);
			$display["obj_filter"] = $req_obj_filter;
		}

		if ($req_op_filter && $req_op_filter !="")
		{
			$op = mysql_escape_string($req_op_filter);
			$sql .= " AND operation = '{$op}'";
			$paging->AddURLFilter("op_filter", $req_op_filter);
			$display["op_filter"] = $req_op_filter;
		}
		
		if ($req_dt)
		{
			$date = strtotime($req_dt);
			$sql .= " AND TO_DAYS(dtadded) = TO_DAYS(FROM_UNIXTIME('{$date}'))";
			$paging->AddURLFilter("dt", $req_dt);
			$display["dt"] = $req_dt;
		}
		else
			$display["dt"] = date("m/d/Y");
		
		//Paging
		$paging->SetSQLQuery($sql);
		$paging->AdditionalSQL = "ORDER BY dtadded DESC";
		$paging->ApplyFilter($_POST["filter_q"], array("object"));
		$paging->ApplySQLPaging();
		$paging->ParseHTML();
		$display["filter"] = $paging->GetFilterHTML("admin/inc/table_filter.tpl");
		$display["paging"] = $paging->GetPagerHTML("admin/inc/paging.tpl");
	
		
		// Rows
		$rows = $db->Execute($paging->SQL);
		
	    $added = array();  	
		while ($row = $rows->FetchRow())
		{
		    $row["type"] = ucfirst(strtolower($row["type"]));
		    $row["operation"] = ucfirst(strtolower($row["operation"]));
			$display["rows"][] = $row;
		}
	}
	
	require("src/append.inc.php");
?>