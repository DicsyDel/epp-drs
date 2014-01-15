<?php
	require_once(dirname(__FILE__)."/../src/prepend.inc.php");
	$db = Core::GetDBInstance();
	$url_scheme = $_SERVER['HTTPS'] == "on" || $_SERVER['SERVER_PORT'] == 443 ? "https" : "http"; 
	$db->Execute("UPDATE config SET `value` = CONCAT('$url_scheme', '://', `value`) WHERE `key` = 'site_url'");
?>