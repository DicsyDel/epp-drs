<?php

	include (dirname(__FILE__) . '/../src/prepend.inc.php');
	
	$Db = Core::GetDBInstance();
	
	$operation_purpose_map = array(
		'Create'	=> INVOICE_PURPOSE::DOMAIN_CREATE,
		'Transfer' 	=> INVOICE_PURPOSE::DOMAIN_TRANSFER,
		'Renew'		=> INVOICE_PURPOSE::DOMAIN_RENEW,
		'Trade'		=> INVOICE_PURPOSE::DOMAIN_TRADE
	);
	
	foreach ($operation_purpose_map as $operation => $purpose)
	{
		$Db->Execute(
			'UPDATE prices SET purpose = ? WHERE purpose = ?', 
			array($purpose, $operation)
		);
	}

?>