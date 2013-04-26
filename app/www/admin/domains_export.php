<?php
    require_once('src/prepend.inc.php');
    
    if ($_POST) {
   	
    	header('Content-Type: application/vnd.ms-excel charset=utf-8');
    	header('Content-Disposition: attachment; filename="domains-'.date('YmdHis').'.csv"');
    	
    	$fp = fopen("php://output", "w");

    	fputcsv($fp, array("Domain", "Create date", "Expire date", "Status", 
    			"Registrant contact", "Admin contact", "Billing contact", "Tech contact", 
    			"Nameservers"));
    	
    	$sql = "SELECT name, TLD, c_registrant, c_admin, c_tech, c_billing, 
    			ns1, ns2, ns_n, status, start_date, end_date 
    			FROM domains ORDER BY name";
    	foreach ($db->GetAll($sql) as $row) {
    		$line = array("{$row["name"]}.{$row["TLD"]}");
    		
    		$t = strtotime($row["start_date"]);
    		$line[] = $t ? date("Y-m-d- H:i:s", $t) : "";
    		
    		$t = strtotime($row["end_date"]);
    		$line[] = $t ? date("Y-m-d- H:i:s", $t) : "";
    		
    		$line[] = $row["status"];
    		$line[] = $row["c_registrant"];
    		$line[] = $row["c_admin"];
    		$line[] = $row["c_billing"];
    		$line[] = $row["c_tech"];
    		
    		$ns = array();
    		if ($row["ns1"]) $ns[] = $row["ns1"];
    		if ($row["ns2"]) $ns[] = $row["ns2"];
    		if ($row["ns_n"]) {
    			foreach (array_map('trim', explode(';', $row['ns_n'])) as $_ns) {
    				$ns[] = $_ns;
    			}
    		}
    		$line[] = join(", ", $ns);
    		
    		fputcsv($fp, $line);
    	}
    	die();
    }
    
    require_once('src/append.inc.php');