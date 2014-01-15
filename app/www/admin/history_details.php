<?php 
	require("src/prepend.inc.php");
	
	$display["title"] = "Object update details";
	
    if (!$get_id)
	   CoreUtils::Redirect("history.php");

	$info = $db->GetRow("SELECT * FROM objects_history WHERE id=?", array($get_id));
		
	$obj_before_update = @unserialize($info["before_update"]);
	$obj_after_update = @unserialize($info["after_update"]);
			
	switch($info["type"])
	{
		case "CONTACT":
			
			$oContact = $obj_before_update;
			$nContact = $obj_after_update;
			
			foreach($oContact->GetFieldList() as $fn=>$fv)
			{				
				if ($fv != $nContact->GetField($fn))
					$changes[] = sprintf("%s contact field changed from '%s' to '%s'", $fn, $fv, $nContact->GetField($fn));
			}
			
			break;
			
		case "DOMAIN":
			
			$oDomain = $obj_before_update;
			$nDomain = $obj_after_update;
			
			$contacts = $oDomain->GetContactList();
			foreach ($contacts as $type=>$contact)
			{
				if ($contact->CLID != $nDomain->GetContact($type)->CLID)
					$changes[] = sprintf("%s contact changed from %s to %s", ucfirst($type), $contact->CLID, $nDomain->GetContact($type)->CLID);
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
			
			break;
	}
	
	$display["changes"] = $changes;
	
	require("src/append.inc.php");
?>