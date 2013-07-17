<?php
    class TaskQueueProcess implements IProcess
    {
		public $ThreadArgs;
		public $ProcessDescription = "Retrives operations from EPP-DRS system queue and executes appropriate handlers. (Every 15 minutes)";
        
		private $handlers;
		
		public function __construct()
		{
			$this->handlers = array(
				new BulkUpdateContactJobHandler(),
				new BulkUpdateNSJobHandler(),
				new BulkRegisterDomainJobHandler()
			);
		}
		
		
		public function OnStartForking()
		{
			$TaskQueue = TaskQueue::GetInstance();
         	$offset = 0;
         	while ($Task = $TaskQueue->Peek($offset))
         	{
         		// Find handler that would accept the task
				$handledAndRemoved = false;         		
				foreach ($this->handlers as $Handler)
				{
					if ($Handler->Accept($Task))
					{
						// This handler is responsible to make task job
						try
						{
							// Do it dear
							$Handler->Handle($Task);
						}
						catch (Exception $e)
						{
							Log::Log(sprintf(_("Task handler fails. %s"), $e->getMessage()), E_USER_ERROR);
						}
						
						// When task has no more targets it's complete
		         		if (!$Task->HasActiveTargets())
		         		{
		         			// Send report about success/fails
		         			try
		         			{
		         				$Handler->Report($Task);
		         			}
		         			catch (Exception $e)
		         			{
		         				Log::Log(sprintf(_("Task report failed. %s"), $e->getMessage()), E_USER_ERROR);
		         			}
		         			// Remove task from queue
		         			$TaskQueue->Remove($Task);
		         			$handledAndRemoved = true;
		         		}
					}
				}
				
				if (!$handledAndRemoved)
				{
					$offset++;					
				}
         	}
		}
		
        public function OnEndForking()
        {
                        
        }
        
        public function StartThread($serverinfo)
        {   
        
        }
    }
    
    interface ITaskHandler
    {
    	function Accept (Task $Task);
    	
    	function Handle (Task $Task);
    	
    	function Report (Task $Task);
    }

    class BulkUpdateContactJobHandler implements ITaskHandler 
    {
    	/**
    	 * @var Task
    	 */
    	private $Task;
    	
    	/**
    	 * @var BulkUpdateContactJob
    	 */
    	private $Job;
    	
    	function Accept (Task $Task)
    	{
    		return $Task->JobObject instanceof BulkUpdateContactJob; 
    	}
    	
    	function Handle (Task $Task)
    	{
    		$Job = $Task->JobObject;
    		
    		// Load registry for TLD
        	$RegFactory = RegistryModuleFactory::GetInstance();
        	$Registry = $RegFactory->GetRegistryByExtension($Job->TLD);

        	// Load contacts        	
        	$contacts = array();
        	$DbContact = DBContact::GetInstance();        	
        	foreach ($Registry->GetManifest()->GetContactTypes() as $ctype)
        	{
        		$clid = $Job->clids[$ctype];
        		$contacts[$ctype] = $clid ? $DbContact->LoadByCLID($clid) : null;
        	}

        	// For each target load domain and update each contact        	
        	$DbDomain = DBDomain::GetInstance();
        	foreach ($Task->GetActiveTargets() as $Target)
        	{
        		try
        		{
        			$Domain = $DbDomain->LoadByName($Target->target, $Job->TLD);
        			foreach ($contacts as $ctype => $Contact)
        			{
        				$OldContact = $Domain->GetContact($ctype);
        				if ($OldContact != $Contact)
        				{
        					$Action = new UpdateDomainContactAction($Domain, $ctype, $Contact);
        					$Action->Run($Task->userid);
        				}
        			}
					$Task->TargetCompleted($Target);        			
        		}
        		catch (Exception $e)
        		{
        			Log::Log(sprintf(_("Update failed for %s. %s"), "{$Target->target}.{$Job->TLD}", $e->getMessage()));
        			$Target->fail_reason = $e->getMessage();
        			$Task->TargetFailed($Target);
        		}
        	}
    	}
    	
    	function Report (Task $Task)
    	{
    		$Job = $Task->JobObject;
    		
        	$DbContact = DBContact::GetInstance();
        	if ($Job->clids[CONTACT_TYPE::REGISTRANT])
        	{
        		$emlvars['Registrant'] = $DbContact->LoadByCLID($Job->clids[CONTACT_TYPE::REGISTRANT]);
        	}
        	if ($Job->clids[CONTACT_TYPE::ADMIN])
        	{
        		$emlvars['Admin'] = $DbContact->LoadByCLID($Job->clids[CONTACT_TYPE::ADMIN]);
        	}
        	if ($Job->clids[CONTACT_TYPE::TECH])
        	{
        		$emlvars['Tech'] = $DbContact->LoadByCLID($Job->clids[CONTACT_TYPE::TECH]);
        	}
        	if ($Job->clids[CONTACT_TYPE::BILLING])
        	{
        		$emlvars['Billing'] = $DbContact->LoadByCLID($Job->clids[CONTACT_TYPE::BILLING]);
        	}
    		
        	$emlvars['report'] = array();
        	foreach ($Task->GetAllTargets() as $Target)
        	{
        		$emlvars['report'][] = array(
        			'domain' => "{$Target->target}.{$Job->TLD}",
        			'status' => $Target->status == TargetStatus::OK ? "ok" : "failed",
        			'fail_reason' => $Target->fail_reason
        		);
        	}
        	
			$Client = Client::Load($Task->userid);

        	$emlvars['Client'] = $Client;
        	mailer_send("bulk_update_contact.eml", $emlvars, $Client->Email, $Client->Name);
    	}
    }
    
    class BulkUpdateNSJobHandler implements ITaskHandler
    {
    	function Accept (Task $Task)
    	{
    		return $Task->JobObject instanceof BulkUpdateNSJob;
    	}
    	
    	function Handle (Task $Task)
    	{
			$Job = $Task->JobObject;
    		
    		// Load registry for TLD
        	$RegFactory = RegistryModuleFactory::GetInstance();
        	$Registry = $RegFactory->GetRegistryByExtension($Job->TLD);
        	
        	// For each target load domain and update nameservers        	
        	$DbDomain = DBDomain::GetInstance();
        	foreach ($Task->GetActiveTargets() as $Target)
        	{
        		try
        		{
        			$Domain = $DbDomain->LoadByName($Target->target, $Job->TLD);
        			$Action = new UpdateDomainNameserversAction($Domain, $Job->nslist);
        			$Action->Run($Task->userid);
        			$Task->TargetCompleted($Target);
        		}
        		catch (Exception $e)
        		{
        			$Target->fail_reason = $e->getMessage();
        			Log::Log(sprintf(_("Update failed for %s. %s"), "{$Target->target}.{$Job->TLD}", $e->getMessage()));
        			$Task->TargetFailed($Target);
        		}
        	}
    	}
    	
    	function Report (Task $Task)
    	{
    		$Job = $Task->JobObject;
    		
        	$emlvars['nslist'] = $Job->nslist;
    		
        	$emlvars['report'] = array();
        	foreach ($Task->GetAllTargets() as $Target)
        	{
        		$emlvars['report'][] = array(
        			'domain' => "{$Target->target}.{$Job->TLD}",
        			'status' => $Target->status == TargetStatus::OK ? "ok" : "failed",
        			'fail_reason' => $Target->fail_reason
        		);
        	}
        	
			$Client = Client::Load($Task->userid);

        	$emlvars['Client'] = $Client;
        	mailer_send("bulk_update_ns.eml", $emlvars, $Client->Email, $Client->Name);
    	}
    }

    class BulkRegisterDomainJobHandler implements ITaskHandler 
    {
    	function Accept (Task $Task)
    	{
    		return $Task->JobObject instanceof BulkRegisterDomainJob;
    	}
    	
    	function Handle (Task $Task)
    	{
    	    $Job = $Task->JobObject;
			Log::Log("This is job: " . var_export($Job, true), E_USER_NOTICE);
    	    
    	    $Order = new Order($Task->userid);    	    
    	    foreach ($Job->tlds as $tld)
    	    {
    	    	// Get all domains of $tld
    	    	$targets = $this->GetTargetsForTld($Task->GetActiveTargets(), $tld);
    	    	Log::Log("$tld has ".count($targets)." targets", E_USER_NOTICE);
    	    	$RegAction = null;
	    	    try
	    	    {
		    	    Log::Log("Init $tld registry");
	    	    	$Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($tld);
	    	    	Log::Log("Init $tld reg action");
		    	    $RegAction = new RegisterDomainAction(array(
		    	    	'tld' => $tld,
		    	    	'Registry' => $Registry,
		    	    	'Order' => $Order,
		    	    	'clid_list' => $Job->contact_list[$tld],
		    	    	'ns_list' => $Job->ns_list,
		    	    	'managed_dns_enabled' => false,
		    	    	'extra_data' => $Job->extra[$tld]
		    	    ));
	    	    }
	    	    catch (RegisterDomainAction_Exception $e)
	    	    {
	    	    	$msg = $e->getMessage();
	    	    	if ($e->ErrorList)
	    	    		$msg .= ". ".join(" ", $e->ErrorList->GetAllMessages());
	    	    	
	    	    	Log::Log(sprintf("Failed to init RegisterDomainAction. %s. File:%s Line:%s \n%s", 
	    	    		$msg, $e->getFile(), $e->getLine(), $e->getTraceAsString()), E_USER_NOTICE);
	    	    	foreach ($targets as $Target)
	    	    	{
	    	    		$Target->fail_reason = "RegisterDomainAction initialization failed. $msg";
	    	    		$Task->TargetFailed($Target);
	    	    	}
	    	    	continue;
	    	    }
	    	    
				// Register each domain
	    	    foreach ($targets as $Target)
	    	    {
	    	    	try
	    	    	{
	    	    		list($domain_name, ) = explode(".", $Target->target, 2); 
		    	    	$Domain = $Registry->NewDomainInstance();
		    	    	$Domain->Name = $domain_name;
		    	    	$Domain->Period = $Job->periods[$tld];
		    	    	$Domain->UserID = $Task->userid;
		    	    	Log::Log("Register {$Domain->GetHostName()}", E_USER_NOTICE);
	    	    		
						$result = $RegAction->Run($Domain);
						$Target->action_result = $result; 
						
	    	    		$Task->TargetCompleted($Target);				
	    	    	}
	    	    	catch (RegisterDomainAction_Exception $e)
	    	    	{
		    	    	$msg = $e->getMessage();
		    	    	if ($e->ErrorList)
		    	    		$msg .= ". ".join(" ", $e->ErrorList->GetAllMessages());	    	    		
	    	    		
						Log::Log(sprintf("Registration failed for %s. %s. File:%s Line:%s \n%s", 
		    	    		$Domain->GetHostName(), $msg, $e->getFile(), $e->getLine(), $e->getTraceAsString()), E_USER_NOTICE);		    	    		
		    	    		
	        			$Target->fail_reason = $msg;
	        			$Task->TargetFailed($Target);
	    	    	}
	    	    }
    	    }
    	    Log::Log("Order has ".count($Order->GetInvoiceList())." invoices", E_USER_NOTICE);
    	    $Order->Save();
    	}
    	
    	function Report (Task $Task)
    	{
    		$Job = $Task->JobObject;
    		
    		// Generate CSV report
    		$report_fname = tempnam(sys_get_temp_dir(), "");
        	$fp = fopen($report_fname, "w+");
        	
        	// Put headers
        	fputcsv($fp, array("status", "domain", "period", 
        		"registrant", "admin", "tech", "billing", 
        		"ns1", "ns2", "extra", "error"));
        	
        	// For each tld take domains and generate report
        	foreach ($Job->tlds as $tld)
        	{
    	    	// Get all domains in task of $tld
    	    	$targets = $this->GetTargetsForTld($Task->GetAllTargets(), $tld);
    	    	foreach ($targets as $Target)
    	    	{
    	    		// Format extra data
    	    		if ($Job->extra[$tld]) 
    	    		{
  	    				$extra = array();
    	    			foreach ($Job->extra[$tld] as $k => $v)
    	    				$extra[] = "$k: $v";
    	    			$extra = join("\n", $extra);
    	    		}
    	    		else
    	    			$extra = "";
    	    			
    	    		// Make status
    	    		if ($Target->status == TargetStatus::OK)
    	    		{
    	    			if ($Target->action_result == RegisterDomainAction_Result::INVOICE_GENERATED)
    	    				$status = "invoice issued";
    	    			else if ($Target->action_result == RegisterDomainAction_Result::PENDING)
    	    				$status = "pending";
    	    			else
    	    				$status = "ok";
    	    		} 
    	    		else
    	    			$status = "fail";

    	    		// Add row to report
    	    		$row = array(
    	    			/*status*/ 	$status,
    	    			/*domain*/	$Target->target,
    	    			/*period*/	$Job->periods[$tld],
    	    			/*registrant*/	$Job->contact_list[$tld]["registrant"],
	    	    		/*admin*/	$Job->contact_list[$tld]["admin"],
	    	    		/*tech*/	$Job->contact_list[$tld]["tech"],
	    	    		/*billing*/	$Job->contact_list[$tld]["billing"],
    	    			/*ns1*/		$Job->ns_list[0],
    	    			/*ns2*/ 	$Job->ns_list[1],
    	    			/*extra*/	$extra,
    	    			/*error*/	$Target->fail_reason	
    	    		);
    	    		fputcsv($fp, $row, ',', '"');
    	    	}
        	}
        	fclose($fp);
        	
			$Mailer = Core::GetPHPSmartyMailerInstance();
			
			// Attach report file 
			$Mailer->ClearAttachments();
			$Mailer->AddAttachment($report_fname, "report.csv");
			
			// Send email
			$Client = Client::Load($Task->userid);			
        	mailer_send("bulk_registration.eml", array("Client" => $Client), $Client->Email, $Client->Name);
        	
        	// Clear attachments for future send
        	$Mailer->ClearAttachments();
        	unlink($report_fname);
    	}

    	function GetTargetsForTld ($targets, $tld)
    	{
	    	$ret = array();
    		foreach ($targets as $T)
	    	{
	    		list($_name, $_tld) = explode(".", $T->target, 2);
	    		if ($_tld == $tld)
	    			$ret[] = $T;
	    	}
    		return $ret;
    	}
    }
?>