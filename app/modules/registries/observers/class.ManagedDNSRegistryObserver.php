<?php
	
	class ManagedDNSRegistryObserver extends RegistryObserverAdapter
	{
		private $DB;
		
		public function __construct ()
		{
			$this->DB = Core::GetDBInstance();	
		}
		
		public function OnDomainCreated (Domain $domain)
		{
			if ($domain->IsManagedDNSEnabled)
			{
				$userinfo = $this->DB->GetRow("SELECT * FROM users WHERE id=?", array($domain->UserID));
				$SOA_owner = str_replace("@", ".", $userinfo["email"]);
				
				$this->DB->Execute("INSERT INTO zones (	`zone`, 
														`soa_owner`, 
														`soa_ttl`, 
														`soa_parent`, 
														`soa_serial`, 
														`soa_refresh`, 
														`soa_retry`, 
														`soa_expire`, 
														`min_ttl`, 
														`isupdated`
													   )
	    								 VALUES (?,?,'14400',?,?,'14400','7200','3600000','86400',0)", 
						array(	$domain->GetHostName(), 
								$SOA_owner, 
								CONFIG::$NS1, 
								date("Ymd")."01")
							 );
							 
				$zoneid = $this->DB->Insert_ID();
				$this->DB->Execute("INSERT INTO records (id, zoneid, rtype, ttl, rpriority, rvalue, rkey) 
							  SELECT null, '{$zoneid}', rtype, ttl, rpriority, rvalue, rkey FROM records 
							  WHERE zoneid='0'
							 ");
			}
		}
	}
?>