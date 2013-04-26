<?php

	require_once dirname(__FILE__) . '/../src/prepend.inc.php';
	
	$row = $db->GetRow("SELECT * FROM tlds WHERE TLD = ? AND isactive = ? AND modulename = ?", 
			array("be", "1", "DotBE"));
	if ($row)
	{
		$RegFactory = RegistryModuleFactory::GetInstance();
		$Module = $RegFactory->GetRegistryByExtension("be")->GetModule();
		$DbDomain = DBDomain::GetInstance();
		
		$rows = $db->GetAll("SELECT id, name FROM domains WHERE TLD = 'be'");
		foreach ($rows as $row)
		{
			try 
			{
				$Domain = $DbDomain->Load($row["id"]);
				$Grd = $Module->GetRemoteDomain($Domain);
				
				Log::Log(sprintf("Set '%s' tech contact to '%s'", "{$row["name"]}.be", $Grd->TechContact), E_USER_NOTICE);
				$db->Execute("UPDATE domains SET c_tech = ? WHERE id = ?", 
						array($Grd->TechContact, $row["id"]));
			}
			catch (Exception $e)
			{
				Log::Log($e->getMessage(), E_USER_ERROR);
			}
		}
			
	}
			
	
