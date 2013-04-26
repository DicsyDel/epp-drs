<?
    class RenewProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Sends notifications to registrants about expiration of their domains. Generates invoices for domain renew. (Daily)";
        
        public function OnStartForking()
        {            
            global $TLDs, $modules_config;
            
        	Log::Log("Starting 'Renew' cronjob...", E_USER_NOTICE);
            
            $db = Core::GetDBInstance();
            $RegFactory = RegistryModuleFactory::GetInstance();
            $DbDomain = DBDomain::GetInstance();
            
            $this->ThreadArgs = array();
            
            // For each client send notice about expiring domains
            $sql = "SELECT id FROM users";
            foreach ($db->GetAll($sql) as $client_data)
            {
            	try
            	{
	            	$Client = Client::Load($client_data["id"]);
	            	
	            	$sql = "
						SELECT 
							d.id, d.name, d.TLD, d.end_date, 
							IF(dd.`key` IS NOT NULL, FROM_UNIXTIME(dd.`value`), DATE_SUB(end_date, INTERVAL 1 DAY)) AS last_renew_date 
						FROM domains d 
						LEFT JOIN domains_data dd ON (dd.domainid = d.id AND dd.`key` = 'RenewalDate') 
						WHERE d.status = '".DOMAIN_STATUS::DELEGATED."' AND d.userid = ? AND (TO_DAYS(end_date) - TO_DAYS(NOW()) BETWEEN 0 AND ?) AND renew_disabled != 1
						ORDER BY d.end_date ASC";
					$start_days = $Client->GetSettingValue(ClientSettings::EXPIRE_NOTIFY_START_DAYS);
	            	$domains_data = $db->GetAll($sql, array($Client->ID, $start_days ? $start_days : 60));
	            	
	            	// Send email to client
	            	if ($domains_data) 
	            	{
	            		$eml_args = array(
	            			"client_name" => $Client->Name,
	            			"domains" => array()
	            		);
	            		foreach ($domains_data as $domain_data)
	            		{
	            			$eml_args["domains"][] = array(
	            				"name" => "{$domain_data["name"]}.{$domain_data["TLD"]}",
	            				"expire_date" => date("Y-m-d", strtotime($domain_data["end_date"])),
	            				"last_renew_date" => date("Y-m-d", strtotime($domain_data["last_renew_date"]))
	            			);
	            		}
	            		mailer_send("bulk_renew_notice.eml", $eml_args, $Client->Email, $Client->Name);
	            	}
	            	
	            	foreach ($domains_data as $domain_data)
	            	{
	            		$Domain = $DbDomain->Load($domain_data['id']);
	            		
	            		 // Find more then 60 days invoice
	            		 // Legacy from old notification system.
	            		 // FIXME: Need to create a better solution to finding unpaid invoices for upgoing renew 
						$dtNearest = date("Y-m-d", $Domain->ExpireDate - 60*24*60*60);
						$invoiceid = $db->GetOne("SELECT id FROM invoices 
							WHERE userid=? AND itemid=? AND purpose=? AND dtcreated >= ?", 
							array($Domain->UserID, $Domain->ID, INVOICE_PURPOSE::DOMAIN_RENEW, $dtNearest)
						);

							
						// Generate invoice
						if (!$invoiceid && !$Domain->RenewDisabled)
						{				
							$Registry = $RegFactory->GetRegistryByExtension($Domain->Extension);
							$config = $Registry->GetManifest()->GetSectionConfig();
							$period = (int)$config->domain->renewal->min_period;
							$Domain->Period = $period;
							$DbDomain->Save($Domain);

							try
							{
								$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_RENEW, $Domain->ID, $Domain->UserID);
								$Invoice->Description = sprintf(_("%s domain name renewal for %s years"), $Domain->GetHostName(), $period);
								$Invoice->Cancellable = 1;
								$Invoice->Save();
								
								if ($Invoice->Status == INVOICE_STATUS::PENDING)
								{
									$diff_days = ceil(($Domain->ExpireDate - time())/(60*60*24));
									Application::FireEvent('DomainAboutToExpire', $Domain, $diff_days);
								}
							}
							catch(Exception $e)
							{
								Log::Log("Cannot create renew invoice. Caught: {$e->getMessage()}", E_USER_ERROR);
							}
						}
	            	}
            	}
            	catch (Exception $e)
            	{
            		Log::Log("Caught: {$e->getMessage()}", E_USER_ERROR);
            	}
            	
            }
            
            /*
			$supported_extensions = $RegFactory->GetExtensionList();
			// For all supported TLDs
            foreach ($supported_extensions as $ext)
            {
            	$Registry = $RegFactory->GetRegistryByExtension($ext);
            	
            	
            	$config = $Registry->GetManifest()->GetSectionConfig();
				$days = $config->domain->renewal->notifications->period;
				$biggest_period = (int)$days[0];
				
				
				
				
				// For each notification 
				foreach($days as $expireDays)
				{
					$expireDays = (int)$expireDays;
					
					$domains = $db->GetAll("
						SELECT dom.* FROM domains AS dom 
						LEFT JOIN domains_data AS ext 
						ON dom.id = ext.domainid AND ext.`key` = 'RenewalDate'
						WHERE dom.TLD = ? 
						AND dom.status = ? 
						AND TO_DAYS(IF 
						(
							ext.`key` IS NOT NULL AND TO_DAYS(FROM_UNIXTIME(ext.`value`)) < TO_DAYS(dom.end_date),
							FROM_UNIXTIME(ext.`value`),
							dom.end_date 
						)) - TO_DAYS(NOW()) = ?
					", array(
						$ext, DOMAIN_STATUS::DELEGATED, $expireDays
					));

					// For each domain
					foreach($domains as $domain_info)
					{
						try
						{
							$Domain = DBDomain::GetInstance()->Load($domain_info['id']);
							
							$dtNearest = date("Y-m-d", strtotime($domain_info["end_date"]) - $biggest_period*24*60*60);
							$invoiceid = $db->GetOne("SELECT id FROM invoices 
								WHERE userid=? AND itemid=? AND purpose=? AND status=? AND dtcreated >= ?", 
								array($Domain->UserID, $Domain->ID, INVOICE_PURPOSE::DOMAIN_RENEW, INVOICE_STATUS::PENDING, $dtNearest)
							);
							
							// Generate invoice
							if (!$invoiceid && !$Domain->RenewDisabled)
							{				
								
								$period = (int)$config->domain->renewal->min_period;
								$Domain->Period = $period;
								DBDomain::GetInstance()->Save($Domain);
								 
								try
								{
									$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_RENEW, $Domain->ID, $Domain->UserID);
									$Invoice->Description = sprintf(_("%s domain name renewal for %s years"), $Domain->GetHostName(), $period);
									$Invoice->Cancellable = 1;
									$Invoice->Save();
									
									if ($Invoice->Status == INVOICE_STATUS::PENDING)
									{
										$userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($Domain->UserID));
										
										//print "send notification and invoice about {$Domain->GetHostName()} to {$userinfo['email']}\n";									
										
										$args = array(
											"domain_name"	=> $Domain->Name, 
											"extension"		=> $Domain->Extension,
											"invoice"		=> $Invoice,
											"expDays"		=> $expireDays,
											"client"		=> $userinfo,
											"renewal_date"  => $Domain->RenewalDate
										);
										mailer_send("renew_notice.eml", $args, $userinfo["email"], $userinfo["name"]);
	
										Application::FireEvent('DomainAboutToExpire', $Domain, $expireDays);
									}
								}
								catch(Exception $e)
								{
									$errmsg = $e->getMessage();
								}
							}
							else
							{
								$userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($Domain->UserID));
								//print "send notification about {$Domain->GetHostName()} to {$userinfo['email']}\n";
								$args = array(
									"domain_name"	=> $Domain->Name, 
									"extension"		=> $Domain->Extension,
									"expDays"		=> $expireDays,
									'client' 		=> $userinfo,
									"renewal_date"  => $Domain->RenewalDate
								);
								mailer_send("renew_notice.eml", $args, $userinfo["email"], $userinfo["name"]);
							}
						} 
						catch (Exception $e)
						{
							Log::Log("First domains loop. Caught: ".$e->getMessage(), E_USER_ERROR);
						}
					}
				}
            }
            */

            
			// For auto-renew registries the day before expiration date 
			// send to unpaid domains delete command and mark them as expired
			Log::Log("Going to process expiring tomorrow domains in 'auto-renew' registries", E_USER_NOTICE);			
			$days_before = 1;
			$del_date = strtotime("+$days_before day");
			$delete_report = array();
			$domains = $db->GetAll("SELECT dom.id, dom.name, dom.TLD FROM domains AS dom 
				LEFT JOIN domains_data AS ext ON dom.id = ext.domainid AND ext.`key` = 'RenewalDate'
				LEFT JOIN invoices AS i ON (dom.id = i.itemid AND i.purpose = 'Domain_Renew')
				WHERE dom.status = ? 
				AND TO_DAYS(IF
				(
					ext.`key` IS NOT NULL AND TO_DAYS(FROM_UNIXTIME(ext.`value`)) < TO_DAYS(dom.end_date),
					FROM_UNIXTIME(ext.`value`),
					dom.end_date 
				)) - TO_DAYS(NOW()) = ?	AND ((i.status = ? AND TO_DAYS(NOW()) - TO_DAYS(i.dtcreated) <= 60) OR i.status IS NULL)",
				array(DOMAIN_STATUS::DELEGATED, $days_before, INVOICE_STATUS::PENDING));
				
			foreach ($domains as $domain_info)
			{
				try
				{
					$Domain = $DbDomain->Load($domain_info["id"]);
					$Registry = $RegFactory->GetRegistryByExtension($domain_info["TLD"]);
					$RegistryOptions = $Registry->GetManifest()->GetRegistryOptions();
					$auto_renewal = (int)$RegistryOptions->ability->auto_renewal;
					$scheduled_delete = (int)$RegistryOptions->ability->scheduled_delete;
					
					if ($auto_renewal)
					{
						if (CONFIG::$AUTO_DELETE)
						{
							try
							{
								// For 'auto-renew + scheduled delete' send scheduled delete
								if ($scheduled_delete)
								{
									Log::Log(sprintf("Send scheduled delete to domain '%s' at '%s'", 
											$Domain->GetHostName(), date("Y-m-d", $del_date)), E_USER_NOTICE);
									
									$Registry->DeleteDomain($Domain, $del_date);
								}
								// For 'auto-renew' only send direct delete
								else
								{
									Log::Log(sprintf("Send direct delete to domain '%s'", 
											$Domain->GetHostName()), E_USER_NOTICE);
									$Registry->DeleteDomain($Domain);
								}
								
								$this->MarkAsExpired($Domain);
							}
							catch (Exception $e)
							{
								Log::Log(sprintf("Cannot delete expiring domain '%s'. %s", 
										$Domain->GetHostName(), $e->getMessage()), E_USER_ERROR);
							}								
						}
						else
						{
							Log::Log(sprintf("Domain %s need to be deleted. Send it to admin court", 
									$Domain->GetHostName()), E_USER_NOTICE);
							
							// Send to the administrator court
							$db->Execute("UPDATE domains SET delete_status = ? WHERE id = ?",
									array(DOMAIN_DELETE_STATUS::AWAIT, $Domain->ID));
							$userinfo = $db->GetRow("SELECT * FROM users WHERE id = ?", array($Domain->UserID));
							$delete_report[] = array
							(
								"domain" => $Domain->GetHostName(),
								"user" => "{$userinfo["name"]}({$userinfo["email"]})"
							);
						}
					}
				}
				catch (Exception $e)
				{
					Log::Log(sprintf("Cannot load expiring domain '%s'. %s", 
							"{$domain_info["name"]}.{$domain_info["TLD"]}", $e->getMessage()), E_USER_ERROR);
				}
			}
			// Notify admin about expiring domains need to be deleted
			if ($delete_report)
			{
				$args = array(
					"date" => date("Y-m-d", $del_date), 
					"report" => $delete_report, 
					"confirm_url" => CONFIG::$SITE_URL . "/admin/domains_await_delete_confirmation.php"
				);
				
				mailer_send("root_domains_await_delete.eml", $args, CONFIG::$EMAIL_ADMIN, CONFIG::$EMAIL_ADMINNAME);
			}
			
			
			// For all registries mark domain as expired at expiration date
			Log::Log("Going to process expiring today domains", E_USER_NOTICE);
			$domains = $db->GetAll("SELECT id, name, TLD FROM domains 
					WHERE TO_DAYS(end_date) = TO_DAYS(NOW()) AND status = ? AND delete_status != ?",
					array(DOMAIN_STATUS::DELEGATED, DOMAIN_DELETE_STATUS::AWAIT));
			
			foreach ($domains as $domain_info)
			{
				try
				{
					$Domain = $DbDomain->Load($domain_info["id"]);
					
					$this->MarkAsExpired($Domain);
				}
				catch (Exception $e)
				{
					Log::Log(sprintf("Cannot load expired domain '%s'. %s", 
							"{$domain_info["name"]}.{$domain_info["TLD"]}", $e->getMessage()), E_USER_ERROR);
				}
			}

			
			// Cleanup database from expired and transferred domains (more then 60 days)
			Log::Log("Going to cleanup database from transferred and expired domains (more then 60 days)", E_USER_NOTICE);
			$domains = $db->GetAll("
					SELECT * FROM domains 
					WHERE (status=? OR status=?) AND 
					((TO_DAYS(NOW()-TO_DAYS(end_date)) >= 60) OR (TO_DAYS(NOW()-TO_DAYS(dtTransfer)) >= 60))", 
					array(DOMAIN_STATUS::TRANSFERRED, DOMAIN_STATUS::EXPIRED));
					
			foreach ($domains as $domain_info)
			{
				try
				{
					Log::Log("Delete {$domain_info["name"]}.{$domain_info["TLD"]} from database. "
						. "(start_date={$domain_info["start_date"]}, end_date={$domain_info["end_date"]}, status={$domain_info["status"]})", 
						E_USER_NOTICE);
					$Domain = $DbDomain->Load($domain_info['id']);
					$DbDomain->Delete($Domain);
				}
				catch (Exception $ignore) 
				{
					Log::Log("Catch ignored exception. {$ignore->getMessage()}", E_USER_NOTICE);
				}
			}
			
			
			// Notify customers about low balance
			Log::Log("Going to notify customers about their low balance", E_USER_NOTICE);
			$user_ids = $db->GetAll("SELECT userid FROM user_settings 
					WHERE `key` = '".ClientSettings::LOW_BALANCE_NOTIFY."' AND `value` = '1'");
			foreach ($user_ids as $id) 
			{
				try 
				{
					$id = $id["userid"];
					$Client = Client::Load($id);
					$amount = (float)$db->GetOne("SELECT `total` FROM balance WHERE clientid = ?", array($id));
					$min_amount = (float)$Client->GetSettingValue(ClientSettings::LOW_BALANCE_VALUE);
					if ($amount < $min_amount)
					{
						mailer_send("low_balance_notice.eml", array(
							"client" => array("name" => $Client->Name),
							"amount" => number_format($min_amount, 2),
							"currency" => CONFIG::$CURRENCYISO
						), $Client->Email, $Client->Name);
					}
				}
				catch (Exception $e)
				{
					Log::Log("Cannot notify customer about his low balance. Error: {$e->getMessage()}", E_USER_ERROR);
					
				}
			}
        }
        
        public function OnEndForking()
        {
                        
        }
        
        public function StartThread($serverinfo)
        {   
        
        }
        
        private function MarkAsExpired (Domain $Domain)
        {
        	$db = Core::GetDBInstance();
        	
        	//Set to domain 'Expired' 
			Log::Log(sprintf("Mark domain '%s' as expired", $Domain->GetHostName()), E_USER_NOTICE);
			$db->Execute("UPDATE domains SET status = ? WHERE id = ?", array(DOMAIN_STATUS::EXPIRED, $Domain->ID));
					
			// Mark invoice as 'Failed'
			$db->Execute("UPDATE invoices SET status = ? WHERE itemid = ? AND status = ? AND purpose = ?", 
					array(INVOICE_STATUS::FAILED, $Domain->ID, INVOICE_STATUS::PENDING, INVOICE_PURPOSE::DOMAIN_RENEW));
			$userinfo = $db->GetRow("SELECT * FROM users WHERE id = ?", array($Domain->UserID));
					
			// Send domain expired notice
			$args = array
			(
				"login"				=>	$userinfo["login"], 
			  	"domain_name"		=>	$Domain->Name, 
			  	"extension"			=>	$Domain->Extension, 
			  	"client"			=>  $userinfo
			);
			mailer_send("expired_notice.eml", $args, $userinfo["email"], $userinfo["name"]);
        }
    }
?>
