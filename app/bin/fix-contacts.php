<?php

	require_once dirname(__FILE__) . '/../src/prepend.inc.php';
	
	$db = Core::GetDBInstance();
	
	// Fix contacts  which throws errors on load.
	$DbContact = DBContact::GetInstance();
	$rows = $db->GetAll("SELECT DISTINCT clid AS clid FROM contacts");
	foreach ($rows as $row)
	{
		try
		{
			$Contact = $DbContact->LoadByCLID($row["clid"]);
		}
		catch (Exception $e)
		{
			Log::Log("{$row["clid"]}. Set non-strict", E_USER_NOTICE);
			$db->Execute("UPDATE contacts SET strict_fields = 0 WHERE clid = ?", array($row["clid"]));
		}
	}
	
	// Set fullname for contacts that has no data
	$rows = $db->GetAll("SELECT DISTINCT c.clid AS clid
			FROM contacts AS c 
			LEFT JOIN contacts_data AS cd ON cd.contactid = c.clid 
			WHERE cd.contactid IS NULL AND c.fullname = ''");
	foreach ($rows as $row)
	{
		Log::Log("{$row["clid"]}. Set fullname", E_USER_NOTICE);
		$db->Execute("UPDATE contacts SET fullname = clid WHERE clid = ?", array($row["clid"]));
	}
	
	// Создать записи для контактов присутствующих в таблице domains, для которых нет записей в таблице contacts
	$RegFactory = RegistryModuleFactory::GetInstance();
	$DbContact = DBContact::GetInstance();  
	foreach (array("c_registrant", "c_admin", "c_tech", "c_billing") as $ctype)
	{
		$rows = $db->GetAll("SELECT DISTINCT d.{$ctype} AS clid, d.TLD, d.userid 
				FROM domains AS d 
				LEFT JOIN contacts AS c ON c.clid = d.{$ctype} 
				WHERE d.{$ctype} != '' AND c.clid IS NULL");
		$ctype = substr($ctype, 2);		
		foreach ($rows as $row)
		{
			Log::Log("{$row["clid"]}. Create $ctype contact", E_USER_NOTICE);
			
			$Registry = $RegFactory->GetRegistryByExtension($row["TLD"]);
			$Contact = $Registry->NewContactInstance($ctype);
			$Contact->UserID = $row["userid"];			
			$Contact->CLID = $row["clid"];
			$Contact->FullName = $row["clid"];
			
			$DbContact->Save($Contact);
		}
	} 
	
	