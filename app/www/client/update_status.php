<? 

	include ("src/prepend.inc.php");
	
	$display["title"] = _("Domains &nbsp;&raquo;&nbsp; Update status");
	$display["help"] = sprintf(_("This routine will retrieve domain status from registry and update it in %s database"), CONFIG::$COMPANY_NAME);
	
	$DbDomain = DBDomain::GetInstance();
	
	if (isset($req_id))
	{
		$req_domainid = $req_id;
		require_once("src/set_managed_domain.php");
	}
	else
		require_once("src/get_managed_domain_object.php");
	
	if ($Domain->Status != DOMAIN_STATUS::DELEGATED && $Domain->Status != DOMAIN_STATUS::TRANSFER_REQUESTED)
	{
		$okmsg = _("Domain status prohibits operation");
		CoreUtils::Redirect ("domains_view.php");
	}

	if ($_POST)
	{
		if (isset($post_submit1))
		{
			try
			{
				$Registry->TransferApprove($Domain);
				$Domain->Status = DOMAIN_STATUS::TRANSFERRED;
				$Domain->OutgoingTransferStatus = OUTGOING_TRANSFER_STATUS::APPROVED;
				$DbDomain->Save($Domain);
				$okmsg = _("Domain successfully transferred");
				CoreUtils::Redirect("domains_view.php");
			}
			catch(Exception $e)
			{
				$errmsg = $e->getMessage();
			}
		}
		elseif (isset($post_submit2))
		{
			try
			{
				$Registry->TransferReject($Domain);
				$Domain->Status = DOMAIN_STATUS::DELEGATED;
				$Domain->OutgoingTransferStatus = OUTGOING_TRANSFER_STATUS::REJECTED;
				$DbDomain->Save($Domain);
				$okmsg = _("Domain transfer successfully rejected");
				CoreUtils::Redirect("domains_view.php");	
			}
			catch (Exception $e)
			{
				$errmsg = $e->getMessage();
			}
		}
	}
	
	try
	{
		$Domain = $Registry->GetRemoteDomain($Domain);
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();	
	}

	if ($errmsg)
		CoreUtils::Redirect("domains_view.php");
	else
	{
		if ($Domain->Status == DOMAIN_STATUS::TRANSFER_REQUESTED)
		{
			$display['ShowContent'] = true;
			$display["id"] = $get_id;			
		}
		else
		{
			$Manifest = $Registry->GetManifest();
			$pendingTransferStatus = (string)$Manifest->GetSectionConfig()->domain->transfer->pending_status;
			if (in_array($pendingTransferStatus, $Domain->GetFlagList()) 
				|| $Domain->RegistryStatus == $pendingTransferStatus)
			{
				$Domain->Status = DOMAIN_STATUS::TRANSFER_REQUESTED;
				$display["id"] = $get_id;
				$display["ShowContent"] = true;
			}
			elseif ($Domain->RemoteCLID && ($Domain->RemoteCLID != $Registry->GetRegistrarID()))
			{
				$Domain->Status = DOMAIN_STATUS::TRANSFERRED;
			}
		}
		DBDomain::GetInstance()->Save($Domain);		
		
		if (!$display["ShowContent"])
		{
			$okmsg = _("Status updated");
    		CoreUtils::Redirect("domains_view.php");
		}
	}
	
	require_once ("src/append.inc.php");
?>