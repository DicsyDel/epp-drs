<?php
	$enable_json = true;
	include("../src/prepend.inc.php");
	

	$sql = "SELECT * FROM domains 
		WHERE userid='{$_SESSION['userid']}'";
		//AND status != '".DOMAIN_STATUS::PENDING_DELETE."'";	

	if ($req_act == "expsoon")
	{
		$sql .= " AND (TO_DAYS(end_date)-TO_DAYS(NOW()) <= 90 AND TO_DAYS(end_date)-TO_DAYS(NOW()) >= 0)";	
	}
	elseif ($req_act == "pending")
	{
		$sql .= " AND status != '".DOMAIN_STATUS::DELEGATED."'";
	}
	if ($req_id)
	{
	    $sql .= " AND id = '".(int)$req_id."'";		
	}
	if ($req_clid)
	{
	    $clid = preg_replace("/[^A-Za-z0-9_\-\{\}]+/", "", $req_clid);
	    $sql .= " AND (`c_registrant` = '{$clid}' OR `c_admin` = '{$clid}' OR `c_tech` = '{$clid}' OR `c_billing` = '{$clid}')";
	}
	if ($req_status)
	{
		$sstatus = preg_replace("/[^A-Za-z]+/", "", $req_status);
		$sql .= " AND status='{$status}'";
	}
	
	if ($req_query)
	{
		$filter = mysql_escape_string($req_query);
		foreach(array("name", "CONCAT(name,'.',TLD)") as $field)
		{
			$likes[] = "$field LIKE '%{$filter}%'";
		}
		$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND ";
		$sql .= "(" . join(" OR ", $likes) . ")";
	}
	
	$sort_names = array
	(
		"date_expire" => "end_date",
		"date_create" => "start_date"
	);
	
	$sort = $req_sort ? key_exists($req_sort, $sort_names) ? $sort_names[$req_sort] : mysql_escape_string($req_sort) : $sort_names["name"];	
	$dir = $req_dir ? mysql_escape_string($req_dir) : "ASC";
	$sql .= " ORDER BY $sort $dir";

	$response["total"] = $db->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));	
	
	$start = $req_start ? (int) $req_start : 0;
	$limit = $req_limit ? (int) $req_limit : 100;
	$sql .= " LIMIT $start, $limit";

	$response["data"] = array();
	foreach ($db->GetAll($sql) as $i => $row)
	{
		try
        {
    		$Registry = $RegistryModuleFactory->GetRegistryByExtension($row["TLD"]);
    		$RegistryOptions = $Registry->GetManifest()->GetRegistryOptions();

    		$allow = array();
    		if ($RegistryOptions->ability->domain_lock == 1)
    			$allow[] = "lock";
    		if ((bool)$RegistryOptions->allowed_domain_flags->flag[0])
    			$allow[] = "manage_flags";
    		if ($RegistryOptions->ability->change_domain_authcode == 1)
    			$allow[] = "change_authcode";
    		if ($row["managed_dns"])
    			$allow[] = "managed_dns";
    		if (!(bool)$RegistryOptions->ability->hostattr)
    			$allow[] = "ns_hosts"; 
    		
			$response["data"][] = array
			(
				"id" => $row["id"],
				"name" => "{$row["name"]}.{$row["TLD"]}",
				"status" => $row["status"],
				"date_create" => ($t1 = strtotime($row["start_date"])) ? date("M j, Y H:i:s", $t1) : null,
				"date_expire" => ($t2 = strtotime($row["end_date"])) ? date("M j, Y H:i:s", $t2) : null,
				"is_locked" => (bool)$row["islocked"],
				"renew_disabled" => (bool)$row["renew_disabled"],
				"pw" => $row["pw"],
				"allow" => join(",", $allow),
				"incomplete_operation" => $row["incomplete_operation"]
			);
    		
        }
        catch(Exception $e)
        {
        	$response["error"][] = $e->getMessage(); 
        }
	}
	
	print json_encode($response);
?>
