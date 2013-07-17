<?php
define("NO_SESSIONS", 1);
define("NO_TEMPLATES", 1);
require_once (dirname(__FILE__) . "/../src/prepend.inc.php");

function _die($message) {
	die(
		"{$message}.\n" . 
		"You can execute migration script again by executing in shell command:\n" .
		"\$ php -q ".__FILE__ . "\n"
	);
}

try {
	print "Starting migration...\n";
	
	$row = $db->GetRow("SELECT * FROM tlds WHERE TLD = ? AND modulename = ?", array("nl", "DotNL"));
	if (!$row || $row["isactive"] == 0) {
		die("DotNL module not used\n");
	}
	
	$row = $db->GetRow("SELECT * FROM tlds WHERE TLD = ? AND modulename = ?", array("nl*", "EPPNL"));
	if (!$row || $row["isactive"] == 0) {
		_die("EPPNL module is not enabled. Please enable it and configure to use live EPP server");
	}
	
	
	print "Checking that EPPNL is properly configured\n";
	$Registry = $RegistryModuleFactory->GetRegistryByExtension("nl*");
	$Domain = $Registry->NewDomainInstance();
	$Domain->Name = "epp-drs-" . rand(1000, 9999);
	$Response = $Registry->DomainCanBeRegistered($Domain);
	if (!$Response->Result) {
		_die("EPPNL module is not properly configured. Cannot domain:check '{$Domain->Name}.nl'");
	}
	print "EPPNL is ready for use\n";
	
	
	print "Switching DotNL -> EPPNL for .nl domain extension\n";
	$sql = "UPDATE tlds SET isactive = ?, modulename = ? WHERE TLD = ?";
	$db->Execute($sql, array(1, "EPPNL", "nl"));
	print "Switched\n";
	
	
	print "Removing DotNL contacts data from database\n";
	$db->Execute("DELETE c, cd FROM contacts c 
		LEFT JOIN contacts_data cd ON (cd.contactid = c.clid) WHERE c.module_name = ?",
		array("DotNL"));
	print "Removed\n";
		
	
	print "Importing EPPNL contacts data from registry\n";
	$DbContact = DBContact::GetInstance();
	$clids = array();
	foreach (array("c_registrant", "c_admin", "c_tech") as $ctype) {
		$rows = $db->GetAll("SELECT DISTINCT $ctype FROM domains WHERE TLD = ?", array("nl"));
		foreach ($rows as $row) {
			if (!is_numeric($row[$ctype])) {
				$clids[] = $row[$ctype];
			}
		}
	}
	$clids = array_unique(array_filter($clids));

	$Registry = $RegistryModuleFactory->GetRegistryByExtension("nl");	
	foreach ($clids as $clid) {
		$Contact = $Registry->NewContactInstanceByGroup("generic");
		$Contact->CLID = $clid;
		try {
			printf("Synchronize '%s'\n", $clid);
			$Contact = $Registry->GetRemoteContact($Contact);
		} catch (Exception $e) {
			printf("error: Cannot synchronize '%s'. %s\n", $clid, $e->getMessage());
			$Contact->FullName = $clid;
		}
		$DbContact->Save($Contact);
		sleep(2);		
	}
	print "Sync complete\n";
	
	
	print "Update domains -> contacts relations\n";
	$db->Execute("UPDATE contacts c, domains d SET c.userid = d.userid 
			WHERE c.clid = d.c_registrant OR c.clid = d.c_admin OR c.clid = d.c_tech AND d.TLD = 'nl'");
	print "Updated\n";
	
	
	
	/*
	$DbDomain = DBDomain::GetInstance();
	$Registry = $RegistryModuleFactory->GetRegistryByExtension("nl");
	$rows = $db->GetAll("SELECT * FROM domains WHERE TLD = ?", array("nl"));
	foreach ($rows as $row) {
		if ($row["status"] == "Delegated") {
			printf("Synchronize '%s'\n", "{$row["name"]}.{$row["TLD"]}");
			$Domain = $DbDomain->Load($row["id"]);
			try {
				$Domain = $Registry->GetRemoteDomain($Domain);
				$DbDomain->Save($Domain);
			} catch (Exception $e) {
				printf("error: Cannot synchronize '%s'. Try manual sync after update\n", $Domain->GetHostName());
				$db->Execute("UPDATE domains 
					SET c_registrant=?, c_admin=?, c_tech=?, c_billing=? WHERE id = ?",
					array('', '', '', '', $Domain->ID));
			}
		} else {
			printf("Remove contacts from '%s' (status: %s)\n", "{$row["name"]}.{$row["TLD"]}", $row["status"]); 
			$db->Execute("UPDATE domains 
				SET c_registrant=?, c_admin=?, c_tech=?, c_billing=? WHERE id = ?",
				array('', '', '', '', $row["id"])
			);
		}
	}
	*/

	// Finalization
	print "Domains created in OTE registry remains in the database:\n";
	$rows = $db->GetAll("SELECT name FROM domains WHERE TLD = ?", array("nl*"));
	foreach ((array)$rows as $row) {
		printf("\t%s\n", "{$row["name"]}.nl");
	}
	$db->Execute("DELETE FROM tlds WHERE TLD = ?", array("nl*"));
	$db->Execute("UPDATE domains SET TLD = ? WHERE TLD = ?", array("nl", "nl*"));
	
	print "Done\n";
	
} catch (Exception $e) {
	$db->Execute("UPDATE tlds SET modulename = ? WHERE TLD = ?", array("DotNL", "nl"));	
	$db->Execute("UPDATE tlds SET isactive = ? WHERE modulename = ?", array(1, "EPPNL"));
	_die("caught: {$e->getMessage()}");
}