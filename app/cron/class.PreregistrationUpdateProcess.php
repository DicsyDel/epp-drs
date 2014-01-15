<?php
    class PreregistrationUpdateProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Updates database for domains that were pre-registered and succesfully delegated by DomainCatch daemon. (Hourly)";
        
        public function OnStartForking()
        {            
            Log::Log("Starting 'PreregistrationUpdate' cronjob...", E_USER_NOTICE);
            
            $db = Core::GetDBInstance();
            
            $this->ThreadArgs = array();
    		
            //
            // Update registered domains
            //
            $domains = $db->Execute("SELECT * FROM domains WHERE status=?", array(DOMAIN_STATUS::PREREGISTRATION_DELEGATED));
            while ($domain = $domains->FetchRow())
            {
	            ////
				try
				{
					$Domain = DBDomain::GetInstance()->Load($domain['id']);
				}
				catch(Exception $e)
				{
					Log::Log(__CLASS__.": thrown exception: '{$e->getMessage()}' File: {$e->getFile()}:{$e->getLine()}", E_ERROR);
					exit();
				}
				
				if ($Domain->Status == DOMAIN_STATUS::AWAITING_PREREGISTRATION)
				{
					try
					{
						$Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($Domain->Extension);
					}
					catch(Exception $e)
					{
						Log::Log(__CLASS__.": thrown exception: '{$e->getMessage()}' File: {$e->getFile()}:{$e->getLine()}", E_ERROR);
						exit();
					}
		
					try
					{
						$Domain = $Registry->GetRemoteDomain($Domain);
					}
					catch(Exception $e)
					{
						Log::Log(__CLASS__.": Cannot get remote domain: '{$e->getMessage()}' File: {$e->getFile()}:{$e->getLine()}", E_ERROR);
						exit();
					}
					
					if (($Domain->RemoteCLID == $Registry->GetRegistrarID()))
					{
						$Domain->Status = DOMAIN_STATUS::DELEGATED;
						DBDomain::GetInstance()->Save($Domain);
					}
					else
					{
						Log::Log(__CLASS__.": Remote domain not owned by us. Domain RegistrarID: {$Domain->RemoteCLID}", E_ERROR);
						exit();
					}
				}
				///
            }
            
            //
            // Update failed domains
            //
            $domains = $db->Execute("SELECT * FROM domains WHERE status=? AND TO_DAYS(NOW()) > TO_DAYS(start_date)", array(DOMAIN_STATUS::AWAITING_PREREGISTRATION));
            while($domain = $domains->FetchRow())
            	$db->Execute("UPDATE domains SET status=? WHERE id=?", array(DOMAIN_STATUS::REGISTRATION_FAILED, $domain['id']));
        }
        
        public function OnEndForking()
        {
                        
        }
        
        public function StartThread($serverinfo)
        {   
        
        }
    }
?>
