<?
	foreach (glob(dirname(__FILE__) . "/../src/licserver-common/*.php") as $filename)
		require_once($filename); 

    class AutoupdateProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Checks for available updates. If update is approved, installs it. (Hourly)";
        
        public function OnStartForking()
        {            
            global $AUTOUP_SERVICES;
            
        	if (!CONFIG::$TAR_PATH)
				CONFIG::$TAR_PATH = "tar";

			$db = Core::GetDBInstance();

			
			if (CONTEXTS::$SECURITY_CONTEXT == SECURITY_CONTEXT::ZENDED)
			{
				// Detect license version
				$lic_info = zend_loader_file_licensed();
				$license_uuid = $lic_info[ZendLicense::X_LICENSE_ID];
				if ($license_uuid) // New type. Check license
				{
					Log::Log("Check license", E_USER_NOTICE);
					$license_service = new RestClient(LICENSE_SERVICE_URL);
					$license_service->SetInterface("PublicLicenseService");
				
					$chk_result = $license_service->CheckLicense($license_uuid);
					Log::Log(var_export($chk_result, true));
					Log::Log("License check result: " 
						. ($chk_result->valid ? "VALID" : "NOT-VALID") 
						. ". {$chk_result->message}", E_USER_NOTICE);
						
					if (!$chk_result->valid)
					{
						Log::Log("License check detected that license not valid. Future autoupdates will be disabled");
					}
				}
			}
			
			$AutoUpdateClient = new AutoUpdateClient();
			$AutoUpdateClient->TarCmd = CONFIG::$TAR_PATH;
			$AutoUpdateClient->PhpCmd = CONFIG::$PHP_PATH;
	
			//
			// Prepare
			//
			foreach ((array)$AUTOUP_SERVICES as $svc)
				$AutoUpdateClient->AddService($svc);
				
			if (CONTEXTS::$SECURITY_CONTEXT == SECURITY_CONTEXT::ZENDED)
			{
				$AutoUpdateClient->SetLicense($license_uuid);
			}

			try 
			{
				$AutoUpdateClient->SetProductID(CONFIG::$PRODUCT_ID);
				$AutoUpdateClient->SetLocalRevision(CONFIG::$APP_REVISION);
				// Bind event listener
				$AutoUpdateClient->SetEventHandler(new AutoupEventHandler());		

				// Get most recent revision
				$latest_rev = $AutoUpdateClient->GetLatestRevision();
			}
			catch (Exception $e)
			{
				if (preg_match('/services failed while trying/', $e->getMessage())) {
					Log::Log("Autoupdate server is down for maintenance", E_USER_WARNING);
					return;
				} else {
					throw $e;	
				}
			}
			
			// Check if we're running outdated copy. 
			if ($latest_rev > CONFIG::$APP_REVISION)
			{
				if (CONFIG::$UPDATE_STATUS != UPDATE_STATUS::AVAILABLE_AND_EMAIL_SENT &&
					CONFIG::$UPDATE_STATUS != UPDATE_STATUS::SCHEDULED)
				{
					$args = array("current_revision" => CONFIG::$APP_REVISION, "latest_revision" => $latest_rev);
					mailer_send("root_updates_available.eml", $args, CONFIG::$EMAIL_ADMIN, CONFIG::$EMAIL_ADMINNAME);
					$db->Execute("UPDATE config SET value=? WHERE `key`='update_status'", array(UPDATE_STATUS::AVAILABLE_AND_EMAIL_SENT));
				}
			}
			
			
			
			/*
			 * Select approval flag from database
			 */
			if (CONFIG::$UPDATE_STATUS == UPDATE_STATUS::SCHEDULED)
			{
				$status = UPDATE_STATUS::NO_UPDATES;
				
				if ($AutoUpdateClient->LocalRevision < $AutoUpdateClient->GetLatestRevision())
				{
					$db->Execute("UPDATE config SET value=? WHERE `key`='update_status'", array(UPDATE_STATUS::RUNNING));
					
					try
					{		
						$AutoUpdateClient->TarCmd = CONFIG::$TAR_PATH;
						$AutoUpdateClient->SetTempDir(CACHE_PATH);
						$AutoUpdateClient->SetAppPath(CONFIG::$PATH);
						$AutoUpdateClient->XAgent = "AUTOUPDATE-EPPDRS";
					
						// Update to latest
						$AutoUpdateClient->UpdateToLatest();
					}
					catch (Exception $e)
					{
						if (preg_match('/services failed while trying/', $e->getMessage())) {
							Log::Log("Autoupdate server is down for maintenance", E_USER_WARNING);
						} else {
							Log::Log("Update failed: {$e->getMessage()}", E_ERROR);	
						}
						$failed = true;
					}
	
					file_put_contents(dirname(__FILE__)."/../etc/version", $AutoUpdateClient->LocalRevision);
					
					// Email Report
					$report = $AutoUpdateClient->BuildReport();
					$status = ($failed) ? "Failed" : "Success";
					
					if (!$AutoUpdateClient->SendReportLater)
					{
						$args = array("report" => $report, "status" => $status);
						mailer_send("root_update_complete.eml", $args, CONFIG::$EMAIL_ADMIN, CONFIG::$EMAIL_ADMINNAME);			
						$email_status = "Send";
					}
					else
						$email_status = "Await";
					
					$db->Execute("INSERT INTO updatelog SET dtupdate=NOW(), status=?, report=?, transactionid=?, from_revision=?, to_revision=?, email_status=?",
								array($status, $report, TRANSACTION_ID, CONFIG::$APP_REVISION, $AutoUpdateClient->LocalRevision, $email_status));
								
					if ($AutoUpdateClient->TargetUpdate->AutoScheduleNextUpdate == 1)
					{
						if ($AutoUpdateClient->LocalRevision != $AutoUpdateClient->GetLatestRevision())
						{
							Log::Log("This update requires application reload. The next update will be installed on next cronjob run automatically.", E_USER_WARNING);
							$status = UPDATE_STATUS::SCHEDULED;
						}
					}
				}
								
				$db->Execute("UPDATE config SET value=? WHERE `key`='update_status'", array($status));
			}
			
			if (!$AutoUpdateClient->SendReportLater)
			{
				// Send all awaiting emails
				$update_logs = $db->GetAll("SELECT * FROM updatelog WHERE email_status = 'Await'");
				if ($update_logs)
					foreach ($update_logs as $update_log)
					{
						$args = array("report" => $update_log['report'], "status" => $update_log['status']);
						mailer_send("root_update_complete.eml", $args, CONFIG::$EMAIL_ADMIN, CONFIG::$EMAIL_ADMINNAME);			
						$db->Execute("UPDATE updatelog SET email_status = ? WHERE id = ?", array("Send", $update_log["id"]));
					}
			}
			
			//xdebug_stop_trace();
        }
        
        public function OnEndForking()
        {
                        
        }
        
        public function StartThread($serverinfo)
        {   
        
        }
    }
?>