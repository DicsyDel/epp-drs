<?php 
	$enable_json = true;
	include("../src/prepend.inc.php");
	
	
	$sql = "SELECT clid from contacts WHERE userid='{$_SESSION['userid']}'";
	
	if ($req_id)
	{
	    $id = (int)$req_id;
	    $sql .= " AND id = '{$id}'";		
	}
	
	if ($req_unused)
	{
		$sql .= " AND (" . join(" AND ", array(
			"clid NOT IN (SELECT DISTINCT c_registrant FROM domains)",
			"clid NOT IN (SELECT DISTINCT c_admin FROM domains)",
			"clid NOT IN (SELECT DISTINCT c_tech FROM domains)",
			"clid NOT IN (SELECT DISTINCT c_billing FROM domains)",	
		)) . ")"; 
		
	}	

	if ($req_query)
	{
		$filter = mysql_escape_string($req_query);
		$sql .= " AND (clid IN (SELECT DISTINCT contactid FROM contacts_data WHERE value LIKE '%$filter%') or clid LIKE '%$filter%')";
	}
	
	
//	$sort = $req_sort ? mysql_escape_string($req_sort) : "module_name";
//	$dir = $req_dir ? mysql_escape_string($req_dir) : "ASC";
	$sql .= " ORDER BY tld ASC, module_name ASC";
	
	
	
	$response["total"] = $db->GetOne(preg_replace('/clid/', 'COUNT(*)', $sql, 1));

	$start = $req_start ? (int) $req_start : 0;
	$limit = $req_limit ? (int) $req_limit : 20;
	$sql .= " LIMIT $start, $limit";
	
	$response["data"] = array();
    foreach ($db->GetAll($sql) as $row)
    {
    	try
    	{
    		$Contact = DBContact::GetInstance()->LoadByCLID($row["clid"]);
    	}
    	catch(ErrorList $e)
    	{
    		Log::Log(join('; ', $e->GetAllMessages()), E_USER_ERROR);
    		continue;
    	}
    	catch(Exception $e)
    	{    		
    		Log::Log($e->getMessage(), E_USER_ERROR);
    		continue;
    	}
    	
    	$status = null;
    	if ($Contact->HasPendingOperation(Registry::OP_UPDATE_APPROVE))
    		$status = "Await update approve";
    	if ($Contact->HasPendingOperation(Registry::OP_CREATE_APPROVE))
    		$status = "Await create approve";
    		
    	$allows = array();
    	if ((bool)(int)$Contact->Registry->GetManifest()
    			->GetRegistryOptions()->ability->update_contact) 
    	{
    		$allows[] = "edit";
    	}
    	
    	$response["data"][] = array
    	(
    		"id" => $Contact->ID,
    		"clid" => $row["clid"],
    		"name" => $Contact->GetFullName(),
    		"email" => $Contact->GetEmail(),
    		"tld" => $Contact->GetTargetTitle(),
    		"status" => $status,
    		"allows" => join(",", $allows)
    	);
    }
	
	print json_encode($response);
?>