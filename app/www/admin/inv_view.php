<?php
	require_once('src/prepend.inc.php'); 
	set_time_limit(0);
	
	// Delete
	if ($_POST)
	{			
		if (($post_action == "appr"))
		{
			//
			// Mark as paid
			//
			$i = 0;
			foreach ((array)$post_id as $k=>$v)
			{
				try
				{
					$Invoice = Invoice::Load($v);
					// if invoice not proccessed and paid
					if ($Invoice)
					{
						if ($Invoice->Status == INVOICE_STATUS::PENDING || $Invoice->Status == INVOICE_STATUS::FAILED)
						{
							$Invoice->MarkAsPaid(null);
							$i++;
						}
					}
				}
				catch(Exception $e)
				{
					$err[] = $e->getMessage();
				}
			}
			
			if (!$err)
			{
				// Redirect
				$okmsg = sprintf(_("%s invoices proccessed"), $i);
				CoreUtils::Redirect($_SERVER["HTTP_REFERER"]);
			}
		}
		elseif ($post_action == "unappr")
		{
			//
			// Reject ivoices
			//
			$i = 0;
			foreach ((array)$post_id as $k=>$invoiceid)
			{
				$i++;
				
				try
				{
					$Invoice = Invoice::Load($invoiceid);
					if ($Invoice)
					{
						if ($Invoice->Status == INVOICE_STATUS::PENDING)
						{
							$Invoice->MarkAsFailed(null);
							$i++;
						}
						else 
							$err[] = sprintf(_("Invoice #%s already processed and cannot be rejected."), $invoiceid);
					}
				}
				catch(Exception $e)
				{
					$err[] = $e->getMessage();
				}
			}
			
			if (count($err) == 0)
			{
				$okmsg = sprintf(_("%d invoices rejected"), $i);
				CoreUtils::Redirect($_SERVER["HTTP_REFERER"]);
			}
		}
		elseif ($post_action == "del")
		{
			//
			// Delete invoices
			//
			$i = 0;
			foreach ((array)$post_id as $k=>$invoiceid)
			{
				try
				{
					Invoice::Load($invoiceid)->Delete();
					$i++;	
				}
				catch(Exception $e)
				{
					if (preg_match('/Domain ID=\d+ not found in database/i', $e->getMessage()))
					{
						$db->Execute('DELETE FROM invoices WHERE id=?', array($invoiceid));
						$i++;
					}
				}
			}
			
			$okmsg = sprintf(_("%d invoices deleted."), $i);
			CoreUtils::Redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	
	$display["title"] = _("Invoices &nbsp;&raquo;&nbsp; View");
	$display["help"] = _("Invoice status can be one of the following: <br>&nbsp;&nbsp;&bull;&nbsp;Pending - Invoice was not paid yet; <br>&nbsp;&nbsp;&bull;&nbsp;Paid - Invoice has been paid; <br>&nbsp;&nbsp;&bull;&nbsp;Rejected - Invoice rejected. Possible reasons: payment failed, invoice manually cancelled by Registrar.");
	$display["load_extjs"] = true;	

										
	require_once("src/append.inc.php");
?>