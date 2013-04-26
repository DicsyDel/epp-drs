<?php
	include (dirname(__FILE__) . '/../src/prepend.inc.php');
	
	$sql = "SELECT c.clid FROM contacts AS c 
		LEFT JOIN contacts_data AS cd ON cd.contactid = c.clid 
		WHERE c.module_name = 'DotSE' 
			AND	cd.`field` = 'orgno'
			AND (cd.`value` IS NULL OR cd.`value` = '')";
	
	$registry = RegistryModuleFactory::GetInstance()->GetRegistryByName("DotSE");
	$regModule = $registry->GetModule(); 
	$contactFly = $registry->NewContactInstanceByGroup("generic");
	foreach ($db->GetAll($sql) as $row)
	{
		$contactFly->CLID = $row["clid"];
		$resp = $regModule->GetRemoteContact($contactFly);
		foreach (array("orgno", "vatno") as $field)
		{
			$db->Execute(
				"UPDATE contacts_data SET `value` = ? WHERE contactid = ? AND `field` = '$field'", 
				array($resp->{$field}, $row["clid"])
			);
		}
	}
	
?>
