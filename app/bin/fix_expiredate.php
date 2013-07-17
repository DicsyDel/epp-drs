<?php

	include (dirname(__FILE__) . "/../src/prepend.inc.php");
	
	$DbDomain = DBDomain::GetInstance();
	$RegistryFactory = RegistryModuleFactory::GetInstance();
	$domain_arr = $db->GetAll('SELECT id FROM domains');
	
	foreach ($domain_arr as $domain_row)
	{
		try
		{
			$Domain = $DbDomain->Load($domain_row['id']);
			if (!$Domain->IsActive())
			{
				continue;
			}
		
			$Registry = $RegistryFactory->GetRegistryByExtension($Domain->Extension);
			$RDomain = $Registry->NewDomainInstance();
			$RDomain->Name = $Domain->Name;
		
			$RDomain = $Registry->GetRemoteDomain($RDomain);
			$Domain->ExpireDate = $RDomain->ExpireDate;
			$DbDomain->Save($Domain);
			
			Log::Log("{$Domain->GetHostName()} fixed expiration date", E_USER_NOTICE);
		}
		catch (Exception $e)
		{
			Log::Log($e->getMessage(), E_USER_WARNING);
			continue;
		}
	}

?>