<?
    class AssignDiscountProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Assigns discount packages to users. (Daily)";
        
        public function OnStartForking()
        {            
            $DbBalance = DBBalance::GetInstance();
            $db = Core::GetDBInstance();
            			
			// Get packages. Order by their commercial value.
            $sql = "SELECT SUM(discount)/COUNT(packageid) AS cv, packageid AS id, min_domains, min_balance  
				FROM discounts d 
				INNER JOIN packages p ON p.id = d.packageid
				WHERE p.min_domains IS NOT NULL OR p.min_balance IS NOT NULL
				GROUP BY packageid ORDER BY cv";
            $packages = (array)$db->GetAll($sql);
            foreach ($packages as &$pkg)
            {
            	$pkg["min_balance"] = (float)$pkg["min_balance"];
            	$pkg["min_domains"] = (int)$pkg["min_domains"];
            } 
            
            if ($packages)
            {
				foreach((array)$db->GetAll("SELECT id FROM users WHERE package_fixed != 1") as $user)
				{ 
					$Balance = $DbBalance->LoadClientBalance($user["id"]);
					$num_domains = (int)$db->GetOne("
						SELECT COUNT(*) 
						FROM domains 
						WHERE userid = ? AND status NOT IN (
							'".DOMAIN_STATUS::EXPIRED."', 
							'".DOMAIN_STATUS::TRANSFER_FAILED."', 
							'".DOMAIN_STATUS::REJECTED."', 
							'".DOMAIN_STATUS::REGISTRATION_FAILED."',
							'".DOMAIN_STATUS::APPLICATION_RECALLED."')
					", array($user["id"]));
					
					$newpackageid = 0;
					foreach ($packages as $package)
					{
						if (($package["min_domains"] && $num_domains > $package["min_domains"])
							|| ($package["min_balance"] && $Balance->Total > $package["min_balance"]))
						{
							$newpackageid = $package["id"];
						}
					}
					
					$db->Execute("UPDATE users SET packageid=? WHERE id=?", array($newpackageid, $user["id"]));
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
