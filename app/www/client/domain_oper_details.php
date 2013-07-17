<? 
	require("src/prepend.inc.php");
	
	$display["title"] = "Pending operation details";
	
    if (!$get_id)
	   CoreUtils::Redirect("domains_view.php");

	$info = $db->GetRow("SELECT * FROM pending_operations WHERE objectid=? AND operation=?", array($get_id, $get_op));
		
	if (!$info)
	{
		$errmsg = _("Information about pending operation not found in database");
		CoreUtils::Redirect("domains_view.php");
	}
			
	$oDomain = @unserialize($info["object_before"]);
	$nDomain = @unserialize($info["object_after"]);
			
	if ($oDomain->UserID != $_SESSION['userid'] || $nDomain->UserID != $_SESSION['userid'])
		CoreUtils::Redirect("domains_view.php");
		
	switch($info["operation"])
	{			
		case "TRADE":
			
			$attrs = array(
							"NewContact" => DBContact::GetInstance()->LoadByCLID($nDomain->GetContact("registrant")->CLID),
							"OldContact" => DBContact::GetInstance()->LoadByCLID($oDomain->GetContact("registrant")->CLID),
						  );
			
			$smarty->assign($attrs);
			$display["details"] = $smarty->fetch("inc/pending_operations_details/trade.tpl"); 
						  
			break;
						
		case "UPDATE":
			
			$contacts = $oDomain->GetContactList();
			foreach ($contacts as $type=>$contact)
			{
				if ($contact->CLID != $nDomain->GetContact($type)->CLID)
					$changes[] = sprintf("Change %s contact from %s to %s", ucfirst($type), $contact->GetFullName(), $nDomain->GetContact($type)->GetFullName());
			}
			
			foreach($oDomain->GetNameserverList() as $ns)
				$o_ns[] = $ns->HostName;
			
			foreach($nDomain->GetNameserverList() as $ns)
				$n_ns[] = $ns->HostName;
				
			$changeList = new Changelist($o_ns, $n_ns);
			
			if (count($changeList->GetAdded()) != 0 || count($changeList->GetRemoved()) != 0)
			{
				foreach ($changeList->GetAdded() as $added)
					$changes[] = sprintf("Added new nameserver %s", $added);
					
				foreach ($changeList->GetRemoved() as $rem)
					$changes[] = sprintf("Nameserver %s removed", $rem);
			}
			
			$oFlags = $oDomain->GetFlagList();
			$nFlags = $nDomain->GetFlagList();
			
			$changeList = new Changelist($oFlags, $nFlags);
			if (count($changeList->GetAdded()) != 0 || count($changeList->GetRemoved()) != 0)
			{
				foreach ($changeList->GetAdded() as $added)
					$changes[] = sprintf("Added new flag %s", $added);
					
				foreach ($changeList->GetRemoved() as $rem)
					$changes[] = sprintf("Flag %s removed", $rem);
			}
			
			$attrs = array("changes" => $changes);
			
			$smarty->assign($attrs);
			$display["details"] = $smarty->fetch("inc/pending_operations_details/update.tpl");
			
			break;
	}
	
	$display["info"] = $info;
	
	require("src/append.inc.php");
?>