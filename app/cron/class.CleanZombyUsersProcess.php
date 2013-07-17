<?
    class CleanZombyUsersProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Deletes users that dont have active domains or paid invoices. (Daily)";
        
        public function OnStartForking()
        {            
            Log::Log("Starting 'CleanZombyUsers' cronjob...", E_USER_NOTICE);
            
            $db = Core::GetDBInstance();
            
            $this->ThreadArgs = array();
    
			foreach((array)$db->GetAll("SELECT * FROM users") as $user)
			{ 
				$domains = $db->GetOne("SELECT COUNT(*) FROM domains WHERE status='".DOMAIN_STATUS::DELEGATED."' AND userid='{$user['id']}'");
				$invoices = $db->GetOne("SELECT COUNT(*) FROM invoices WHERE (status='1' OR (status = '0' 
										AND TO_DAYS(NOW())-TO_DAYS(dtcreated)<15)) AND userid='{$user['id']}'
									   ");
				
				if ($domains == 0 && $invoices == 0)
				{
					Log::Log("Found inactive user: {$user['login']} (id = {$user['id']})", E_USER_NOTICE);
					
					$db->Execute("DELETE FROM users WHERE id='{$user['id']}'");	
					$db->Execute("DELETE FROM invoices WHERE userid='{$user['id']}'");
					$db->Execute("DELETE FROM domains WHERE userid='{$user['id']}'");
					$db->Execute("DELETE FROM contacts WHERE userid='{$user['id']}'");
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
?>
