<?php

	include ('src/prepend.inc.php');
	
	$invoices = array();
	
	if (isset($req_orderid))
	{
		$Order = Order::Load($req_orderid);
		$invoices = $Order->GetInvoiceList();
	}
	else if (isset($req_invoiceid))
	{
		$invoices[] = Invoice::Load($req_invoiceid);
	}
	
	
	$table = array();
	foreach ($invoices as $Invoice)
	{
		$table[] = array(
			'id' 				=> $Invoice->ID,
			'description' 		=> $Invoice->Description,
			'inv_status' 		=> $Invoice->Status,
			'action_status' 	=> $Invoice->ActionStatus,
			'action_fail_reason'=> $Invoice->ActionFailReason
		);
	}
	$display['table'] = $table;
	
	/*
	$display['table'] = array(
		array(
			'id' 				=> '23',
			'purpose' 			=> 'dicsydel01x0154.gr domain name registration for 2 year(s)',
			'inv_status' 		=> '1',
			'action_status' 	=> '2',
			'action_fail_reason'=> 'Registry returns mailformed XML'
		),
		array(
			'id' 				=> '5545',
			'purpose' 			=> '1983.ch domain name transfer.',
			'inv_status' 		=> '1',
			'action_status' 	=> '1',
			'action_fail_reason'=> ''
		)
	);
	*/

	include ('src/append.inc.php');

?>