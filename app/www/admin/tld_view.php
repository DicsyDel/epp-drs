<?php
	require_once('src/prepend.inc.php');
	
	// Delete	
	$paging = new SQLPaging(null, $req_pn, CONFIG::$PAGING_ITEMS);
	
	$sql = "SELECT * from tlds WHERE id!=''";
	
	if ($req_modulename)
	{
		$sql .= " AND modulename='{$req_modulename}'";
		$paging->AddURLFilter('modulename', $req_modulename);
	}
	
	//
	//Paging
	//
	if ($req_pf && !$post_filter_q)
		$_SESSION["filter"] = null;
	
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = " ORDER BY modulename ASC, TLD ASC";
	$paging->ApplyFilter($post_filter_q ? $post_filter_q : $req_pf, array("TLD", "modulename"));
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
			if (isset($post_isactive[$row["TLD"]]) && $row['isactive'] == 0)
			{
				$db->Execute("UPDATE tlds SET isactive='1' WHERE TLD=?", array($row["TLD"]));
				$row["isactive"] = 1;
			}
			elseif (!isset($post_isactive[$row["TLD"]]) && $row['isactive'] == 1)
			{
				$db->Execute("UPDATE tlds SET isactive='0' WHERE TLD=?", array($row["TLD"]));
				$row["isactive"] = 0;
			}
			
			$mess = _("Domain extensions settings successfully saved");
		}
		
		if ($row["TLD"] != '')
		{
			try
			{
				$Registry = $RegistryModuleFactory->GetRegistryByExtension($row["TLD"], false);
			}
			catch(Exception $e)
			{
				$err[] = $e->getMessage();
			}
    		
			if ($Registry)
			{
				$periods = 0;
				$section_config = $Registry->GetManifest()->GetSectionConfig();
				for ($i = (int)$section_config->domain->registration->min_period; 
					 $i<=(int)$section_config->domain->registration->max_period;
					 $i++
					)
						$periods++;
				
				// Transfer, renew for each period and register for each period
				$expected_num_prices = $periods*2+1;
				$real_num_prices = $db->GetOne("SELECT COUNT(*) FROM prices WHERE TLD=?", array($row["TLD"]));
				
				if ($real_num_prices < $expected_num_prices)
					$row["disabled"] = true;
					
				$display["rows"][] = $row;
			}
		}
	}
	
	$display["pn"] = $req_pn;
	$display["pt"] = $req_pt;
	$display["pf"] = $post_filter_q ? $post_filter_q : $req_pf;
	
	$display["help"] = "You cannot enable domain extension until prices for it are fully configured. In this case, \"Enabled\" checkbox will be greyed out.";
	
	require_once ("src/append.inc.php");
?>