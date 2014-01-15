<?php
	$enable_json = true;
	require_once('../src/prepend.inc.php');
	
	if ($req_unused)
	{
		$sql = "SELECT DISTINCT c.* from contacts c 
				left join domains d on c.clid = d.c_registrant 
				left join domains d2 on c.clid = d2.c_admin 
				left join domains d3 on c.clid = d3.c_tech 
				left join domains d4 on c.clid = d4.c_billing";
		$where = array("d.id is null", "d2.id is null", "d3.id is null", "d4.id is null");
	}
	else
	{
		$sql = "SELECT DISTINCT c.* from contacts AS c";
		$where = array();
	
		// Show contacts only for specified user
		if ($req_userid)
		{
			$where[] = "userid = '".(int)$req_userid."'";
		}
	
		// Show contacts uses by specified domain
		if ($req_domainid)
		{
			$domain = $db->GetRow("SELECT * FROM domains WHERE id=?", array($req_domainid));
			$where[] = "(" . join(" OR ", array(
					"clid = '{$domain['c_registrant']}'",
					"clid = '{$domain['c_admin']}'",
					"clid = '{$domain['c_tech']}'",
					"clid = '{$domain['c_billing']}'"))
				. ")";
		}
	}

	
	// Apply filter
	if ($req_query)
	{
		$query = mysql_escape_string($req_query);
		$sql .= " LEFT JOIN contacts_data as cd ON c.clid = cd.contactid";
		$where[] = "(c.clid LIKE '%{$query}%' OR cd.`value` LIKE '%{$query}%')";
	}
	$sql .= $where ? " WHERE ".join(" AND ", $where) : "";
	
	$sort = $req_sort ? mysql_escape_string($req_sort) : "clid";
	$dir = $req_dir ? mysql_escape_string($req_dir) : "ASC";
	$sql .= " ORDER BY $sort $dir";


	$response["total"] = $db->GetOne(preg_replace('/DISTINCT c\.\*/', 'COUNT(DISTINCT c.id)', $sql, 1));

	$start = $req_start ? (int) $req_start : 0;
	$limit = $req_limit ? (int) $req_limit : 20;
	$sql .= " LIMIT $start, $limit";
	
	
	$response["data"] = array();
	$DbContact = DBContact::GetInstance();	
	foreach ($db->GetAll($sql) as $row)
	{
		$tld = '';
		try
		{
			$Contact = $DbContact->LoadByCLID($row["clid"]);
			$tld = $Contact->GetTargetTitle();
		}
		catch (Exception $e) {}
		$userlogin =  $db->GetOne("SELECT login FROM users WHERE id = ?", array($row["userid"]));
		
		$response["data"][] = array
		(
			"id" => $row["id"],
			"clid" => $row["clid"],
			"userid" => $row["userid"],
			"userlogin" => "$userlogin",
			"tld" => $tld
		);	
	}
	
	print json_encode($response);
?>
