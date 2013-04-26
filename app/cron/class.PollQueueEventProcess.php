<?
	class PollQueueEventProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Retrieves messages from poll message queue on EPP server and calls appropriate handlers. (Every 30 minutes)";
        
        public function OnStartForking()
        {            
        	$db = Core::GetDBInstance();
        	$this->ThreadArgs = $db->GetAll("SELECT * FROM modules WHERE status='1'");
        }
        
        public function OnEndForking()
        {
   	
        }
        
        public function StartThread($module_info)
        {   
        	// Reopen database connection in child process
        	$db = Core::GetDBInstance(null, true);
        	
        	// Attach mail notifications on registy events
			Registry::AttachClassObserver(new EmailToRegistrantObserver());
        	Registry::AttachClassObserver(new OperationHistory());
        	Registry::AttachClassObserver(new ManagedDNSRegistryObserver());
			
        	$RegFactory = RegistryModuleFactory::GetInstance();        	
        	$Registry = $RegFactory->GetRegistryByName($module_info["name"], false);
        	$Extensions = $Registry->GetManifest()->GetExtensionList();
        	
            foreach ($Extensions as $ext)
            {
            	try
            	{
            		$r = false;
            		$r = $RegFactory->GetRegistryByExtension($ext, true, true);
            	}
            	catch(Exception $e)
            	{
            		$r = false;
            	}
            	
            	if ($r && $r->GetModuleName() == $Registry->GetModuleName())
            	{
	            	Log::Log(sprintf("Processing %s extension with module %s", $ext, $r->GetModuleName()), E_USER_NOTICE);
    	        	$r->DispatchPendingOperations();
            	}
            }
        }
    }    
?>