<? 
    require_once('src/prepend.inc.php');

    $display["help"] = "";
	
	if ($req_action)
	{
		if ($req_ext)
		{
			$extinfo = $db->GetRow("SELECT * FROM extensions WHERE `key`=?", array($req_ext));
			if (!$extinfo)
			{
				
			}
			
			switch($req_action)
			{
				case "enable":
					
					if (License::IsExtensionLicensed($extinfo['license_flag']))
					{
						$db->Execute("UPDATE extensions SET enabled='1' WHERE `key`=?", array($req_ext));
						$okmsg = _("EPP-DRS extension successfully enabled");
					}
					else
						$errmsg = _("Your license doesn't allow this extension");
						
					break;
					
				case "disable":
					
					$db->Execute("UPDATE extensions SET enabled='0' WHERE `key`=?", array($req_ext));
					$okmsg = _("EPP-DRS extension successfully disabled");
					
					break;
			}
		}
	}
	
	$sql = "SELECT * FROM `extensions` WHERE 1 > 0";
	
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
    {
    	$row["licensed"] = License::IsExtensionLicensed($row['license_flag']);    	
    	$display["rows"][] = $row;
    }
	   										
	require_once('src/append.inc.php');
?>