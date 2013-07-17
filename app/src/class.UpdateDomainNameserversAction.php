<?php

class UpdateDomainNameserversAction_Result
{
	const OK = 1;
	const PENDING = 2;
}

class UpdateDomainNameserversAction
{
	/**
	 * @var Domain
	 */
	private $Domain;
	
	private $nslist;
	
	private $Db;
	
	public function __construct(Domain $Domain, $nslist)
	{
		$this->Domain = $Domain;
		$this->nslist = $nslist;
		$this->Db = Core::GetDBInstance();
	}
	
	public function Run ($userid)
	{
		try
		{
			$Factory = RegistryModuleFactory::GetInstance();
			$Registry = $Factory->GetRegistryByExtension($this->Domain->Extension);
			$host_as_attr = (bool)$Registry->GetManifest()->GetRegistryOptions()->ability->hostattr;
		}
		catch (Exception $e)
		{
			throw new UpdateDomainNameserversAction_Exception(
				sprintf(_("Cannot change nameservers. Reason: %s"), $e->getMessage())
			);
		}
	
		// Check that all nameserver hosts are registered		
		foreach ($this->nslist as &$NS)
		{
			$glue_record = preg_match("/^(.*)\.{$this->Domain->GetHostName()}$/", $NS->HostName, $matches);
			if ($glue_record)
			{
				$known_ns = $this->Db->GetRow(
					"SELECT * FROM nhosts WHERE hostname=? AND domainid=?",
					array($matches[1], $this->Domain->ID)
				);
				if (!$known_ns)
				{
					if ($host_as_attr)
					{
						// No need to register nameserver hosts
						$this->Db->Execute(
							"INSERT INTO nhosts SET domainid = ?, hostname = ?, ipaddr = ?",
							array($this->Domain->ID, $matches[1], $NS->IPAddr)
						);
					}
					else
					{
						throw new UpdateDomainNameserversAction_Exception(
							sprintf(_("Nameserver %s does not exist"), $NS->HostName),
							UpdateDomainNameserversAction_Exception::NAMESERVERHOST_NOT_REGISTERED
						);
					}
				}
				else
				{
					$NS = new NameserverHost($NS->HostName, $host_as_attr ? $NS->IPAddr : $known_ns['ipaddr']);
					$NS->ID = $known_ns['id'];
				}
			}
		}
		
		// Perform nameservers update
		try
		{
			$Changes = new Changelist($this->Domain->GetNameserverList(), $this->nslist);
			
			$Registry->UpdateDomainNameservers($this->Domain, $Changes);
			
			$DBNSHost = DBNameserverHost::GetInstance();
			$DBNSHost->SaveList($this->Domain->GetNameserverHostList(), $this->Domain->ID);
			
			return $this->Domain->HasPendingOperation(Registry::OP_UPDATE) ?
				UpdateDomainNameserversAction_Result::PENDING :
				UpdateDomainNameserversAction_Result::OK;
		}
		catch (Exception $e)
		{
			throw new UpdateDomainNameserversAction_Exception(
				sprintf(_("Cannot change nameservers. Reason: %s"), $e->getMessage())
			);
		} 
	}
}

class UpdateDomainNameserversAction_Exception extends Exception 
{
	const NAMESERVERHOST_NOT_REGISTERED = -1;
}

?>
