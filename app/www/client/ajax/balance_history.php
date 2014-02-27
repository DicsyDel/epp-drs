<?php
	$enable_json = true;
	include("../src/prepend.inc.php");
	
	$response = array();
	
	try
	{
		$DBBalance = DBBalance::GetInstance();
		$Balance = $DBBalance->LoadClientBalance($_SESSION["userid"]);
		
		$sql = "SELECT * FROM balance_history WHERE balanceid = '{$Balance->ID}'";

		$sort = $req_sort ? mysql_escape_string($req_sort) : "operation_date";
		$dir = $req_dir ? mysql_escape_string($req_dir) : "DESC";
		$sql .= " ORDER BY $sort $dir";
		
		$response["total"] = $db->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 100;
		$sql .= " LIMIT $start, $limit";		
		
		$response["data"] = array();
		foreach ($DBBalance->LoadBalanceHistory($sql) as $row)
		{
			if ($row->InvoiceID)
			{
				$row->InvoiceDescription = $db->GetOne("SELECT description from invoices WHERE id = ?", array($row->InvoiceID));
			}			
			
			$response["data"][] = array
			(
				"type" => $row->Type,
				"amount" => number_format($row->Amount, 2),
				"operation_date" => date("M j, Y H:i:s", $row->Date),
				"description" => $row->InvoiceID ? 
					$row->Type == "Withdraw" ? 
						sprintf(_("Payment for Invoice #%s (%s)"), $row->InvoiceID, $row->InvoiceDescription) :
						sprintf(_("Balance refill by Invoice #%s"), $row->InvoiceID)
					:
					nl2br($row->Description)
			);
		}
		
	}
	catch (Exception $e)
	{
		$response["error"] = $e->getMessage();
	}
	
	print json_encode($response);
?>
