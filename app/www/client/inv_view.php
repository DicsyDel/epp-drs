<?
	require_once('src/prepend.inc.php');
	include_once("src/class.activecalendar.php");
	set_time_limit(99999999);
	
	if ($get_task == "delete" && $get_id)
	{
		try
		{
			$Invoice = Invoice::Load($get_id);
			if ($Invoice->UserID == $_SESSION["userid"] 
				&& $Invoice->Cancellable 
				&& $Invoice->Status == INVOICE_STATUS::PENDING)
			{
				$Invoice->Delete();
				$okmsg = _("Invoice rejected");
				CoreUtils::Redirect("inv_view.php");
			}
			else
				throw new Exception("invoice cannot be deleted");
		}
		catch (Exception $e)
		{
			$errmsg = $e->getMessage();
		}
	}
	
	// Delete
	if ($_POST)
	{	
		if ($post_action == "pay")
		{
			$invoices = array();
			foreach ($post_id as $invoiceid)
			{
				$invoiceinfo = $db->GetRow("SELECT * FROM invoices WHERE id=? AND userid=?", array($invoiceid, $_SESSION["userid"]));
				if ($invoiceinfo)
					array_push($invoices, (int)$invoiceid);
			}
			
			$invoices_str = implode(",", $invoices);
			CoreUtils::Redirect("checkout.php?string_invoices={$invoices_str}");
		}
		elseif (isset($post_dfilter) && !$post_actionsubmit)
		{
			if ($_SESSION["dt"] == $post_dt && 
				$_SESSION["dt2"] == $post_dt2 && 
				$_SESSION["date_type"] == $post_date_type &&
				$_SESSION["quick_date"] == $post_quick_date &&
				$_SESSION["purpose"] == $post_purpose)
			{
				$_SESSION["dfilter"] = false;
				$_SESSION["dt"] = "";
				$_SESSION["dt2"] = "";
				$_SESSION["date_type"] = "";
				$_SESSION["quick_date"] = "";
				$_SESSION["purpose"] = "";
			}
			else
			{
				$_SESSION["dt"] = $post_dt;
				$_SESSION["dt2"] = $post_dt2;
				$_SESSION["date_type"] = $post_date_type;
				$_SESSION["quick_date"] = $post_quick_date;
				$_SESSION["purpose"] = $post_purpose;
				$_SESSION["dfilter"] = true;
			}
		}
	}
	
	if (!$template_name)
	{
		$paging = new SQLPaging();
		// 
		// Start compile SQL query
		//
		$sql = "SELECT * from invoices WHERE id!='' AND userid='{$_SESSION['userid']}' AND hidden=0";
		
		//
		// Show invoices with specified status
		//
				
		if (isset($req_status))
		{
			$get_status = (int)$get_status;
			$sql .= " AND status='{$req_status}'";
			
			$paging->AddURLFilter("status", $req_status);
		}
			
		//
		// Show invoices for specified domain name
		//
		if (isset($get_domainid))
		{
			$domainid = (int)$get_domainid;
		
			$purposes = implode(',', array($db->qstr(INVOICE_PURPOSE::DOMAIN_CREATE), 
				$db->qstr(INVOICE_PURPOSE::DOMAIN_RENEW),
				$db->qstr(INVOICE_PURPOSE::DOMAIN_TRANSFER),
				$db->qstr(INVOICE_PURPOSE::DOMAIN_TRADE)
			));
			
			$sql .= " AND itemid='{$domainid}' AND purpose IN ({$purposes})";
			
			$paging->AddURLFilter("domainid", $get_domainid);
		}
		
		//
		// Use date filter
		//	
		if ($_SESSION["dfilter"])
		{
			$display["date_type"] = $_SESSION["date_type"];
			$display["quick_date"] = $_SESSION["quick_date"];
			
			switch($_SESSION["date_type"])
			{
				case "Q":
					
						switch($_SESSION["quick_date"])
						{
							case "today":
								
								$date_sql = "TO_DAYS(dtcreated) = TO_DAYS(NOW())";
								
								break;
							case "yesterday":
	
								$date_sql = "TO_DAYS(dtcreated) = TO_DAYS(NOW())-1";
								
								break;
							case "last7days":
	
								$date_sql = "TO_DAYS(dtcreated) > TO_DAYS(NOW())-7";
								
								break;
							case "lastweek":
	
								if (date("D") != 'Mon')
									$monday = date("Y-m-d 00:00:00", strtotime("last Monday"));
								else
									$monday = date("Y-m-d 00:00:00");
								
								$date_sql = "(TO_DAYS(dtcreated) >= TO_DAYS('{$monday}') AND TO_DAYS(dtcreated) <= DATE_ADD('{$monday}', INTERVAL 6 DAY))";
								
								break;
							case "lastbusinessweek":
	
								if (date("D") != 'Mon')
									$monday = date("Y-m-d 00:00:00", strtotime("last Monday"));
								else
									$monday = date("Y-m-d 00:00:00");
									
								$date_sql = "(TO_DAYS(dtcreated) >= TO_DAYS('{$monday}') AND TO_DAYS(dtcreated) <= DATE_ADD('{$monday}', INTERVAL 4 DAY))";
								
								break;
							case "lastmonth":
								
								$first_day = date("Y-m-d 00:00:00", mktime(0,0,0, date("m")-1, 1, date("Y")));
								$last_day = date("Y-m-d 00:00:00", mktime(0,0,0, date("m")-1, date("t", strtotime($first_day)), date("Y")));
								
								$date_sql = "(TO_DAYS(dtcreated) >= TO_DAYS('{$first_day}') AND TO_DAYS(dtcreated) <= TO_DAYS('{$last_day}'))";
								
								break;
							case "thismonth":
	
								$first_day = date("Y-m-d 00:00:00", mktime(0,0,0, date("m"), 1, date("Y")));
															
								$date_sql = "(TO_DAYS(dtcreated) >= TO_DAYS('{$first_day}'))";
								
								break;
						}
					
					break;
				
				case "E":
						
						$tmp = explode("/", $_SESSION["dt"]);
						$date_from = $tmp[2]."-".$tmp[0]."-".$tmp[1];
						$tmp = explode("/", $_SESSION["dt2"]);
						$date_to = $tmp[2]."-".$tmp[0]."-".$tmp[1];
						
						$date_sql .= "(TO_DAYS(dtcreated)>=TO_DAYS('{$date_from}') AND TO_DAYS(dtcreated)<=TO_DAYS('{$date_to}'))";
					
					break;
					
				case "PURPOSE":
					
					$date_sql .= "purpose = '{$_SESSION['purpose']}'";
					
					break;
			}
			
			$sql .= " AND ({$date_sql})";
					
			$display["dfilter"] = true;
		}
		
		
		//
		//Paging
		//
		$paging->SetSQLQuery($sql);
		$paging->AdditionalSQL = "ORDER BY UNIX_TIMESTAMP(dtcreated) DESC";
		$paging->ApplyFilter($_POST["filter_q"], array("description"));
		$paging->ApplySQLPaging();
		$paging->ParseHTML();
		$display["filter"] = $paging->GetFilterHTML("client/inc/table_filter.tpl");
		$display["paging"] = $paging->GetPagerHTML("client/inc/paging.tpl");
		
		//
		// Rows
		//
		foreach ((array)$db->GetAll($paging->SQL) as $k=>$row)
		{
			$display["rows"][$k] = $row;
			$display["rows"][$k]["userinfo"] = $db->GetRow("SELECT * FROM users WHERE id='{$row['userid']}'");
	
			$display["rows"][$k]["gatename"] = ucfirst($row["gate"]);
		}
		
		$display["dt"] = (!$_POST["dt"]) ? date("m/d/Y") : $_POST["dt"];
		$display["dt2"] = (!$_POST["dt2"]) ? date("m/d/Y") : $_POST["dt2"];
		$display["dfilter"] = ($_SESSION["dfilter"]) ? "i" : "";
		$display["purpose"] = $_SESSION["purpose"];
		$display["err"] = $err;
		
		$display["page_data_options"] = array(array("name" => "Make payment for selected invoices", "action" => "pay"));
		$display["help"] = _("Invoice status can be one of the following: Pending - Invoice was not paid yet; Paid - Invoice has been paid; Rejected - Invoice rejected. Possible reasons: payment failed, invoice manually cancelled by Registrar.");
	}
	
	$display["purposes"] = $db->GetAll("SELECT * FROM invoice_purposes");
	
	$display["load_extjs"] = true;
	require_once("src/append.inc.php");
?>