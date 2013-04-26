<?php

require_once (dirname(__FILE__) . '/../src/prepend.inc.php');
define('NO_TEMPLATES', true);

$db = Core::GetDBInstance();
$DbDomain = DBDomain::GetInstance();
$RegistryFactory = RegistryModuleFactory::GetInstance();
$registriesPool = array();

$sql = "select d.id, d.name, d.TLD 
	from domains as d 
	left join whois_domain as wd on wd.domain = CONCAT(d.name, '.', d.TLD) 
	where wd.domain is null AND d.TLD IN('com', 'net') AND d.status = 'Delegated'";
foreach ($db->GetAll($sql) as $row)
{
	if ($registriesPool[$row['TLD']] == null)
		$registriesPool[$row['TLD']] = $RegistryFactory->GetRegistryByExtension($row['TLD']);
	$Registry = $registriesPool[$row['TLD']];
	
	$Domain = $DbDomain->Load($row['id']);
	
	// invoke sync
	$Registry->GetModule()->OnDomainUpdated($Domain);
	
	print "Synced {$Domain->GetHostName()}\n";
	$total++;
}

print "\nFinished.\nTotal processed: {$total}\n";
?>