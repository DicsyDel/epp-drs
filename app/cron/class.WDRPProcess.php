<?php
    class WDRPProcess implements IProcess
    {
		public $ThreadArgs;
		public $ProcessDescription = "Whois Data Reminder Policy (Daily)";
		
		public function __construct()
		{
		}
		
		
		public function OnStartForking()
		{
			// Initialization
			$Db = Core::GetDBInstance();
			$DbDomain = DBDomain::GetInstance();
			$Whois = JWhois::GetInstance();

			// Grep TLDs  
			$data = $Db->GetAll(
				"SELECT TLD FROM tlds WHERE modulename = 'Verisign' AND isactive = 1"
			);
			foreach ($data as $row)
			{
				$tlds[] = "'{$row['TLD']}'";
			}
			$tlds = join(',', $tlds);
			// Grep domains
			$domain_data = $Db->GetAll("
				SELECT name, TLD FROM domains 
				WHERE
				-- TLD in matching list
				TLD IN ($tlds)
				-- Today is anniversary of registration
				AND ((MONTH(NOW()) = MONTH(start_date) AND DAY(NOW()) = DAY(start_date))
				-- Today is 28/02 and domain was registered 29/02 at leap year 
				OR (MONTH(NOW()) = 2 AND DAY(NOW()) = 28 AND MONTH(start_date) = 2 AND DAY(start_date) = 29))
			");
			foreach ($domain_data as $row)
			{
				try
				{
					$Domain = $DbDomain->LoadByName($row['name'], $row['TLD']);
					$Client = Client::Load($Domain->UserID);
					
					// Send notice
					$emlvars = array(
						'whois' => $Whois->Whois($Domain->GetHostName()),
						'Client' => $Client
					);
		        	mailer_send("wdrp_notice.eml", $emlvars, $Client->Email, $Client->Name);
				}
				catch (Exception $e)
				{
					Log::Log(sprintf("Failed to sent notice about %s. %s", 
						"{$row['name']}.{$row['TLD']}", $e->getMessage()), E_ERROR);
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