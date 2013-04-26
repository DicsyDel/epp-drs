<?
    Core::Load("NET/API/BIND/class.BIND.php");
	Core::Load("NET/API/BIND/class.RemoteBIND.php");
	Core::Load("NET/DNS/class.DNSZoneParser.php");
	
	class DNSPushProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Sends updated zones to nameservers (Every hour)";
        
        public function OnStartForking()
        {            
            Log::Reload("EPPDRSLogger");
        	Log::Log("Starting 'DNSPush' cronjob...", E_USER_NOTICE);
            
            $db = Core::GetDBInstance(null, true);
            
            $this->ThreadArgs = $db->GetAll("SELECT * FROM nameservers WHERE 1=1");
            
            Log::Log("Found ".count($this->ThreadArgs)." nameservers.", E_USER_NOTICE);
        }
        
        public function OnEndForking()
        {
			Log::Reload("EPPDRSLogger");
            Log::Log("Finishing 'DNSPush' cronjob... Updating database...", E_USER_NOTICE);
            
            $db = Core::GetDBInstance(null, true);            
            //Get zones for update
            $zones = $db->Execute("SELECT * FROM zones WHERE isupdated='0' OR (isdeleted='1' AND isupdated!='2')");
            while ($zone = $zones->FetchRow()) 
            {
            	if ($zone["isdeleted"] == 0)
                {
                	$db->Execute("UPDATE zones SET isupdated='1' WHERE id = ?", array($zone['id']));
                }
                else 
                {
                	$db->Execute("DELETE FROM zones WHERE id='{$zone['id']}'");
                }
            }
            
            $db->Execute("UPDATE zones SET isupdated='0' WHERE isupdated='2'");
            
            Log::Log("'DNSPush' cronjob succeffully finished.", E_USER_NOTICE);
        }
        
        public function StartThread($serverinfo)
        {   
            $db = Core::GetDBInstance();
            $Crypto = Core::GetInstance("Crypto", LICENSE_FLAGS::REGISTERED_TO);
	
            $db->Execute("UPDATE nameservers SET isnew='0' WHERE id='{$serverinfo['id']}'");
            
            //Get zones for update
            if ($serverinfo["isnew"] == 0)
            	$zones = $db->Execute("SELECT * FROM zones WHERE isupdated='0' OR isdeleted='1'");
            else 
            	$zones = $db->Execute("SELECT * FROM zones");
            	
            $count = $zones->RecordCount();
            Log::Log("[PID: ".posix_getpid()."] Found {$count} zones for update on {$serverinfo['host']}", E_USER_NOTICE);
            
            if ($count == 0)
            {
                Log::Log("[PID: ".posix_getpid()."] Nothing to update on {$serverinfo['host']}. Exiting...", E_USER_NOTICE);
                exit();
            }
            
            // Set credentials for connecting to NS server
        	$authinfo = array(
    							"type" 			=> "password",
    							"login"			=> $serverinfo["username"],
    							"password"		=> $Crypto->Decrypt($serverinfo["password"])
    						);
        	
        	// Connect to server
        	Log::Log("[PID: ".posix_getpid()."] Connecting to {$serverinfo['host']}...", E_USER_NOTICE);
        	
        	$zonetemplate = @file_get_contents(dirname(__FILE__)."/../templates/DNS/zone.tpl");
        	
            $remoteBIND = new RemoteBIND(	
                                        $serverinfo["host"], 
                                        $serverinfo["port"], 
                                        $authinfo,
                                        $serverinfo["rndc_path"],
                                        $serverinfo["namedconf_path"],
                                        $serverinfo["named_path"],
                                        $zonetemplate,
                                        false
            				            );
            if (!$remoteBIND->SetTransport("ssh"))
            {
            	Log::Log("[PID: ".posix_getpid()."] Connection to {$serverinfo['host']} failed.", E_USER_NOTICE);
            	$remoteBIND = false;
            }
            				              
            while ($zone = $zones->FetchRow()) 
            {
                $zone_updated = false;
            	if ($remoteBIND)
                {
	            	Log::Log("[PID: ".posix_getpid()."] Processing zone '{$zone['zone']}'...", E_USER_NOTICE);
	                
	                if ($zone["isdeleted"] == '0')
	                {
	                	$Zone = new DNSZone();
		        		$new_serial = SOADNSRecord::RaiseSerial($zone["soa_serial"]);
	                	
		        	    Core::ClearWarnings();
		        		$SOA = new SOADNSRecord(	
		        									$zone["zone"], 
		        									$zone["soa_parent"], 
		        									$zone["soa_owner"],
		        									$zone["soa_ttl"],
		        									$new_serial,
		        									$zone["soa_refresh"],
		        									$zone["soa_retry"],
		        									$zone["soa_expire"],
		        									$zone["soa_min"]
		        								);
		        								
		                if ($SOA->__toString() != "")
		                {
		                    $Zone->AddRecord($SOA);
		                    
		                    $records = $db->Execute("SELECT * FROM records WHERE zoneid = ? ORDER BY rtype", array($zone['id']));
		                    
		                    while($record = $records->FetchRow())
		        			{
		        			    if ($record["rkey"] || $record["rvalue"])
		        			    {
		            				switch($record["rtype"])
		            				{
		            					case "A":
		            							$record = new ADNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
		            							$Zone->AddRecord($record);
		            						break;
		            						
		            					case "NS":
		            					        $record = new NSDNSRecord($record["rkey"], trim($record["rvalue"]), $record["ttl"]);
		            							$Zone->AddRecord($record);
		            						break;
		            						
		            					case "CNAME":
		            							$record = new CNAMEDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
		            							$Zone->AddRecord($record);
		            						break;
		            						
		            					case "MX":
		            							$record = new MXDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"], $record["rpriority"]);
		            							$Zone->AddRecord($record);
		            						break;
		            				    
		            					case "TXT":
		                                        $record = new TXTDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
		                                        $Zone->AddRecord($record);
		                                    break;
		            				}
		        			    }
		        			}
		        			
		        			if (!Core::HasWarnings())
					        {
					            Log::Log("[PID: ".posix_getpid()."] Sending '{$zone['zone']}' zone config to server...", E_USER_NOTICE);
		                        $content = $Zone->__toString();
		                        if ($remoteBIND->SaveZone($zone["zone"], $content, false))
		                        {
			                        $db->Execute("UPDATE zones SET soa_serial=? WHERE id=?", array($new_serial, $zone["id"]));
			                        
		                        	Log::Log("[PID: ".posix_getpid()."] '{$zone['zone']}' zone successfully updated", E_USER_NOTICE);
			                        $zone_updated = true;
		                        }
			                    else 
			                    {
			                    	$mess = "There are warnings for zone '{$zone['zone']}':\n";
		                            foreach ($GLOBALS['warnings'] as $warn)
		                                $mess .= "              - {$warn}\n";
		            		          
		            		        Log::Log("[PID: ".posix_getpid()."] {$mess}", E_USER_ERROR);
			                    }
					        }
					        else 
					        {
					            if (Core::HasWarnings())
		            		    {
		                            $mess = "There are warnings for zone '{$zone['zone']}':\n";
		                            foreach ($GLOBALS['warnings'] as $warn)
		                                $mess .= "              - {$warn}\n";
		            		          
		            		        Log::Log("[PID: ".posix_getpid()."] {$mess}", E_USER_ERROR);
		            		    }
					        }
		                }
		                else 
		                {	                    
		                	Log::Log(sprintf("[PID: %s] Broken SOA record for zone '%s' (%s)", posix_getpid(), $zone['zone'], Core::GetLastWarning()), E_USER_ERROR);
		                }
	                }
	                elseif ($serverinfo["isnew"] == '0')
	                {
	                	$remoteBIND->DeleteZone($zone["zone"]);
	                }
                }
                
                if (!$zone_updated)
                {
		        	Log::Log("[PID: ".posix_getpid()."] Zone '{$zone['zone']}' update failed", E_USER_ERROR);
                	$db->Execute("UPDATE zones SET isupdated='2' WHERE id='{$zone['id']}'");
                }
            }
            
            if ($remoteBIND)
            	$remoteBIND->ReloadRndc();
        }
    }
?>