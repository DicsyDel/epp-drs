<?php
    class LogRotateProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Rotate EPP-DRS logs. (Daily)";
        
        public function OnStartForking()
        {            
            Log::Log("Starting 'LogRotate' cronjob...", E_USER_NOTICE);
            
            $db = Core::GetDBInstance();
            
            $this->ThreadArgs = array();
    
			if (CONFIG::$ROTATE_LOG_EVERY > 0)
				$db->Execute("DELETE FROM syslog WHERE TO_DAYS(NOW())-TO_DAYS(dtadded) > ?", array(CONFIG::$ROTATE_LOG_EVERY));
        }
        
        public function OnEndForking()
        {
                        
        }
        
        public function StartThread($serverinfo)
        {   
        
        }
    }
?>
