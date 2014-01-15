<?php
	$enable_json = true;
	include("../src/prepend.inc.php");
	
	switch ($get_task)
	{
		case "disablehelp":
			
			$db->Execute("REPLACE INTO config SET value='0', `key`='inline_help'");
			
			break;
	}
?>