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
	print "Usage reset-pw.php [options] [domain1, domain2 ...]\n";
	print "Options:\n";
	print "  --all Update all 'Delegated' domains\n";
	die();
}

print "Starting...\n";

foreach ($rows as $row)
{
	try
	{
		$Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($row["TLD"]);
		if ($Registry->GetManifest()->GetRegistryOptions()->ability->change_domain_authcode == 1) 
		{
			$pw = AbstractRegistryModule::GeneratePassword();			
			print "Updating {$row["name"]}.{$row["TLD"]} (pw: {$pw})\n";

			$Domain = DBDomain::GetInstance()->LoadByName($row["name"], $row["TLD"]);
			$Registry->UpdateDomainAuthCode($Domain, $pw);
			print "Updated\n";
		}
		else
		{
			print "Not supported for {$row["name"]}.{$row["TLD"]}\n";
		}		
	}
	catch (Exception $e)
	{
		print "error: {$e->getMessage()}\n";
	}
}

print "Done\n";