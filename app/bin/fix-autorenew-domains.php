<?php
require_once (dirname(__FILE__) . "/../src/prepend.inc.php");

$db = Core::GetDBInstance();

// Fix RRPProxy renewal mode
$Registry = RegistryModuleFactory::GetInstance()->GetRegistryByName("RRPProxy");
$Registry->GetModule()->GetTransport()->SetDumpTraffic(true);

$autorenewTlds = array();	
$manifest = $Registry->GetManifest();
foreach ($manifest->GetExtensionList() as $tld) {
	$manifest->SetExtension($tld);
	if ((int)$manifest->GetRegistryOptions()->ability->auto_renewal) {
		$autorenewTlds[] = $tld;
	}
}
foreach ($autorenewTlds as &$tld) {
	$tld = "'$tld'";
}

$sql = "SELECT concat(d.name, '.', d.TLD) AS domain  
	FROM domains AS d INNER JOIN tlds AS t ON d.TLD = t.TLD 
	WHERE d.TLD in (".join(",", $autorenewTlds).") AND t.modulename = 'RRPProxy' AND isactive = 1";

$rows = $db->GetAll($sql);
$domains = array();
foreach ($rows as $row) {
	$domains[] = $row["domain"];
}

Log::Log("here", E_USER_NOTICE);

foreach ($domains as $domain) {
	$Registry->GetModule()->Request('SetDomainRenewalMode', array(
		'domain' => $domain,  
		'renewalmode' => "AUTORENEW"
	));
	sleep(1);
}

// Fix expire date for domains that were renewed in EPP-DRS and then synced with registry
$autorenewTlds[] = "'lu'";
$sql = "update invoices as i, domains as d
		set d.end_date = DATE_ADD(d.end_date, INTERVAL 1 YEAR)
		where d.id = i.itemid and i.purpose = 'Domain_Renew' and d.TLD in (".join(",", $autorenewTlds).") 
		and i.status = 1 and TO_DAYS(d.end_date) - TO_DAYS(i.dtcreated) <= 60";
$db->Execute($sql);
?>