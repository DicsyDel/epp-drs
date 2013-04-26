<? 
	require("src/prepend.inc.php"); 
	
	Core::Load("NET/DNS/class.DNSZoneParser.php");
	
	$display["title"] = "DNS zone{$crumb}Edit";
	
	if ($req_zonename)
	{
		$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=? AND isdeleted='0'", array($req_zonename));
		if ($zoneinfo)
		{
			$dominainfo = $db->GetRow("SELECT * FROM domains WHERE CONCAT(name,'.',TLD) = ?", array($req_zonename));
			
			if ($dominainfo && $dominainfo["userid"] == $_SESSION["userid"])
			{
				$records = $db->GetAll("SELECT * FROM records WHERE zoneid='{$zoneinfo["id"]}'");
			
				$display["zone"] = $zoneinfo;
				$display["zone"]["records"] = $records;
				$display["domainname"] = $display["zone"]["zone"];
			}
			else 
			{
				$errmsg = _("DNS zone not found");
				CoreUtils::Redirect("domains_view.php");
			}
		}
		else 
		{
			$errmsg = _("DNS zone not found");
			CoreUtils::Redirect("domains_view.php");
		}
	}
	else 
		CoreUtils::Redirect("domains_view.php");
	
	if ($_POST) 
	{
		if ($post_zonename)	
		{
			$db->BeginTrans();
			
			try
			{
				Core::ClearWarnings();
				$Zone = new DNSZone();
    			$SOA = new SOADNSRecord($post_zonename, $post_zone["soa_parent"], $post_zone["soa_owner"]);
    			if (!$SOA->__toString())				        
    			    $error = true;
    			else 
    			{
                    $Zone->AddRecord($SOA);
                    $CNAMERecords = array();
                    
                    foreach ((array)$post_zone["records"] as $k=>$record)
					{
						if ($record["rvalue"] != '')
    					{
        					foreach ($record as $kk=>$vv)
        					{
        						$record[$kk] = str_replace('{$hostname}', $post_zonename, $record[$kk]);
        						$record[$kk] = str_replace('{$ipaddr}', $post_ip, $record[$kk]);
        					}
        					
        					switch($record["rtype"])
            				{
            					case "A":
            							$record = new ADNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
            							$Zone->AddRecord($record);
            						break;
            						
            					case "NS":
            							$record = new NSDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
            							$Zone->AddRecord($record);
            						break;
            						
            					case "CNAME":
            							$record = new CNAMEDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
            							$Zone->AddRecord($record);
            							array_push($CNAMERecords, $record->Name);
            						break;
            						
            					case "MX":
            							$record = new MXDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"], $record["rpriority"]);
            							$Zone->AddRecord($record);
            						break;
            				}
    					}
					}
					
					foreach ((array)$post_add as $k=>$record)
					{
						if ($record["rkey"] != '' && $record["rvalue"] != '')
    					{
        					foreach ($record as $kk=>$vv)
        					{
        						$record[$kk] = str_replace('{$hostname}', $post_zonename, $record[$kk]);
        						$record[$kk] = str_replace('{$ipaddr}', $post_ip, $record[$kk]);
        					}
        					
        					switch($record["rtype"])
            				{
            					case "A":
            							$record = new ADNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
            							$Zone->AddRecord($record);
            						break;
            						
            					case "NS":
            							$record = new NSDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
            							$Zone->AddRecord($record);
            						break;
            						
            					case "CNAME":
            							$record = new CNAMEDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
            							$Zone->AddRecord($record);
            							array_push($CNAMERecords, $record->Name);
            						break;
            						
            					case "MX":
            							$record = new MXDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"], $record["rpriority"]);
            							$Zone->AddRecord($record);
            						break;
            				}
    					}	
					}
                    
					foreach ($Zone->Records as $record)
					{
						if (!($record instanceof CNAMEDNSRecord))
						{
							if (in_array($record->Name, $CNAMERecords))
							{
								Core::RaiseWarning(sprintf(_("CNAME RRs '%s' cannot have any other RRs with the same name."), $record->Name));
								$error = true;
							}
						}
					}
										
					$zonecontent = $Zone->__toString();
    				if (Core::HasWarnings())  				        
    				    $error = true;
    			}
				
				if ($error)
    			{
    			    $db->RollbackTrans();
    			    
    			    Log::Log(sprintf(_("Failed to generate DNS zone for '%s'"), $post_zonename), E_ERROR);
    			    foreach ($GLOBALS["warnings"] as $warn)
    			    {
    			        Log::Log("[Error]{$warn}", E_USER_NOTICE);
    			        $err[] = $warn;
    			    }

    			    Core::ClearWarnings();
    			}
    			else 
    			{
    			    $db->Execute("UPDATE zones SET `soa_owner`=?, `soa_ttl`=?, `soa_parent`=?, `soa_refresh`=?, `soa_retry`=?, `soa_expire`=?, `min_ttl`=? WHERE zone=?", 
					array($post_zone["soa_owner"], $post_zone["soa_ttl"], $post_zone["soa_parent"], $post_zone["soa_refresh"], $post_zone["soa_retry"], $post_zone["soa_expire"], $post_zone["min_ttl"], $post_zonename));
					
					$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($post_zonename));
					
					foreach ((array)$post_zone["records"] as $k=>$v)
					{
						if ($v["rkey"] != '' && $v["rvalue"] != '')
						{					
							$db->Execute("UPDATE 
													records
												SET 
													rtype		= ?, 
													ttl			= ?, 
													rpriority	= ?, 
													rvalue		= ?, 
													rkey		= ? 
											   WHERE 
											   		id = ? AND zoneid=?"
									, array($v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"], $k, $zoneinfo["id"]));
						}
						else
						{
							$db->Execute("DELETE FROM records WHERE id=? AND zoneid=?", array($k, $zoneinfo["id"]));
						}
					}
					
					foreach ((array)$post_add as $k=>$v)
					{
						if ($v["rkey"] != '' && $v["rvalue"] != '')
							$db->Execute("INSERT INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?", array($zoneinfo["id"], $v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"]));
					}
    				
					
    				Log::Log(sprintf(_("Succesfully generated DNS zone for '%s'"), $post_zonename), E_USER_NOTICE);    
    			    $db->Execute("UPDATE zones SET isupdated='0', isdeleted='0' WHERE id=?", array($zoneinfo["id"]));
    			    
    			    $db->CompleteTrans();
    			    
    			    $okmsg = _("Zone successfully updated");
    			    CoreUtils::Redirect("dnszone_edit.php?zonename={$post_zonename}");
    			}
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				$errmsg = sprintf(_("Failed to update DNS zone for '%s'. Please contact service administrator"), $post_zonename);
				Log::Log(sprintf(_("Failed to update DNS zone for '%s'"), $post_zonename), E_USER_ERROR);
				Log::Log($e->getMessage(), E_USER_ERROR);
			}
		}
	}
		
	$display["add"] = array(1, 2, 3, 4, 5);
	$display["def_sn"] = date("Ymd")."01";
	$display["zonename"] = $req_zonename;
		
	if ($zoneinfo["isupdated"] == 0)
	{
		$display["warn"] = sprintf(_("Your current DNS zone were not yet saved on %s nameservers. It will be saved within one hour or less."), CONFIG::$COMPANY_NAME);
	}
	
	if (CONFIG::$DEV_DEMOMODE == 1)
	{
		$errmsg = "In demo mode, zone save cronjob is disabled. DNS zones changes will not be commited to NS servers.";
	}
	
	require("src/append.inc.php"); 
?>