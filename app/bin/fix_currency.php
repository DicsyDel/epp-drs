<?php

	include (dirname(__FILE__) . "/../src/prepend.inc.php");
	
	$sql = "REPLACE INTO config SET `key` = ?, `value` = ?";
	
	$currencyISO = $db->GetRow("SELECT value FROM config WHERE `key` = 'currencyISO'");
	$currencyISO = $currencyISO['value'];
	$db->Execute($sql, array('billing_currencyISO', $currencyISO));
	$db->Execute($sql, array('currency_rate', 1));

?>