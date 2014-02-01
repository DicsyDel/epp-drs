<?php
define('NO_TEMPLATES', 1);
require_once(dirname(__FILE__)."/../src/prepend.inc.php");


if ($_SERVER["argc"] > 1)
{
	if ($_SERVER["argv"][1] == "--all")
	{
		$rows = $db->GetAll("SELECT name, TLD FROM domains WHERE status = 'Delegated'");		
	}
	else
	{
		for ($i=1; $i<$_SERVER["argc"]; $i++)
		{
			list($name, $tld) = explode(".", $_SERVER["argv"][$i], 2);
			$rows[] = array("name" => $name, "TLD" => $tld);
		}
	}
}
else
{
	print "Usage sync.php [options] [domain1, domain2 ...]\n";
	print "Options:\n";
	print "  --all Sync all 'Delegated' domains\n";
	die();
}

print "Starting...\n";

foreach ($rows as $row)
{
	try
	{
		print "Syncing {$row["name"]}.{$row["TLD"]}";		
		
		$Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($row["TLD"]);
		$Domain = DBDomain::GetInstance()->LoadByName($row["name"], $row["TLD"]);
	
		$Domain = $Registry->GetRemoteDomain($Domain);
		if ($Domain->RemoteCLID && ($Domain->RemoteCLID != $Registry->GetRegistrarID()))
		{
			$Domain->Status = DOMAIN_STATUS::TRANSFERRED;
		}
		
		DBDomain::GetInstance()->Save($Domain);
		
		print " OK\n";
		
		sleep(2);		
	}
	catch (Exception $e)
	{
		/*
		if ($e instanceof ObjectNotExistsException)
		{
			Log::Log("Delete domain {$Domain->GetHostName()}", E_USER_NOTICE);
			print " Deleted\n";
			DBDomain::GetInstance()->Delete($Domain);
		}
		else
		{
		*/
		Log::Log("Sync failed. Domain {$row["name"]}.{$row["TLD"]}. Error: <".get_class($e)."> {$e->getMessage()}", E_USER_NOTICE);
		print " Failed. {$e->getMessage()}\n";
		sleep(2);
	}	
}

?>
