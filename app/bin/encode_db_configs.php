<?php
	
	if (!file_exists(CONFIG::$PATH."/etc/.dbconfigs_encoded"))
	{
		$fields = $db->GetAll("SELECT * FROM modules_config");
		foreach($fields as $f)
		{
			$db->Execute("UPDATE modules_config SET value=? WHERE id=?", array($Crypto->Encrypt($f["value"], LICENSE_FLAGS::REGISTERED_TO), $f["id"]));
		}
		
		$fields = $db->GetAll("SELECT * FROM pmodules_config");
		foreach($fields as $f)
		{
			$db->Execute("UPDATE pmodules_config SET value=? WHERE id=?", array($Crypto->Encrypt($f["value"], LICENSE_FLAGS::REGISTERED_TO), $f["id"]));
		}
		
		file_put_contents(CONFIG::$PATH."/etc/.dbconfigs_encoded", "1");
	}
?>