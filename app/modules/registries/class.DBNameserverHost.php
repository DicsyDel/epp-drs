<?php

class DBNameserverHost
{
	private $FieldPropertyMap = array(
		'id' 		=> 'ID',
		'hostname' 	=> 'HostName',
		'ipaddr' 	=> 'IPAddr'
	);
	
	/**
	 * Database instance
	 */
	private $DB;
	
	public function __construct ()
	{
		$this->DB = Core::GetDBInstance();
	}

	private static $Instance;
	
	private $LoadedObjects = array();

	
	/**
	 * @return DBNameserverHost
	 */
	public static function GetInstance ()
	{
		if (self::$Instance === null)
			self::$Instance = new DBNameserverHost();
		return self::$Instance;
	}

	/**
	 * Return domain in initial state, when it was just loaded
	 *
	 * @param NameserverHost $domain
	 * @return NameserverHost Clone of the domain
	 */
	public function GetInitialState (NameserverHost $nshost)
	{
		if ($nshost->ID === null || !array_key_exists($nshost->ID, $this->LoadedObjects))
			throw new Exception(_('Namserver host was not loaded through DBNameserverHost, or was deleted'));
			
		return clone $this->LoadedObjects[$nshost->ID];
	}

	/**
	 * Enter description here...
	 *
	 * @param array $row
	 * @return NameserverHost
	 */
	private function CreateObject ($row)
	{
		$nshost = new NameserverHost(null, null);
		
		// Map fields to properties
		foreach ($this->FieldPropertyMap as $field => $property)
			$nshost->{$property} = $row[$field];
			
		// Add nameserver host to loaded objects storage
		$this->LoadedObjects[$nshost->ID] = clone $nshost;		
			
		return $nshost;
	}
	
	/**
	 * Load list of domain nameserver hosts from database
	 *
	 * @param int $domainId
	 * @return NameserverHost
	 */
	public function LoadList ($domainId)
	{
		$rows = $this->DB->GetAll("
			SELECT ns.*, CONCAT(hostname, '.', d.name, '.', d.TLD) AS hostname 
			FROM nhosts AS ns
			INNER JOIN domains AS d ON ns.domainid = d.id
			WHERE domainid = ?
		", array($domainId));
		$ret = array();
		foreach ($rows as $row)
			$ret[] = $this->CreateObject($row);
		return $ret;
	}
	
	public function SaveList ($nshostList, $domainId)
	{
		$this->DB->BeginTrans();		
		
		try
		{
			$ids = array();
			
			foreach ($nshostList as $nshost)
			{
				$row = array('domainid' => $domainId);				
				
				// Properties data
				foreach ($this->FieldPropertyMap as $field => $property)
					$row[$field] = ($nshost->{$property} != null) ? $nshost->{$property} : '';	

				// Normalize hostname
				$tmp = explode('.', $row['hostname']);
				$row['hostname'] = $tmp[0];
				unset($row['id']);
					
				// Prepare SQL statement
				$set = array();
				$bind = array();
				foreach ($row as $field => $value) 
				{
					$set[] = "`$field` = ?";
					$bind[] = $value;
				}
				$set = join(', ', $set);
				
				if ($nshost->ID)
				{
					// Perform Update
					$bind[] = $nshost->ID;
					$this->DB->Execute("UPDATE nhosts SET $set WHERE id = ?", $bind);
				}
				else
				{
					// Perform Insert
					$this->DB->Execute("INSERT INTO nhosts SET $set", $bind);
					$nshost->ID = $this->DB->Insert_ID();
				}
				
				$ids[] = $nshost->ID;
				
				$this->LoadedObjects[$nshost->ID] = clone $nshost;
			}
			
			$ids = join(',', $ids);
			$not_in_list = $this->DB->GetAll(
				$ids ? 
				"SELECT * FROM nhosts WHERE domainid = ? AND id NOT IN ({$ids})" : 
				"SELECT * FROM nhosts WHERE domainid = ?", 
				array($domainId)
			);
			foreach ($not_in_list as $row)
			{
				unset ($this->LoadedObjects[$row['id']]);
				$this->DB->Execute('DELETE FROM nhosts WHERE id = ?', array($row['id']));				
			}
		}
		catch (Exception $e)
		{
			$this->DB->RollbackTrans();
			throw new ApplicationException ($e->getMessage(), $e->getCode());			
		}
		
		$this->DB->CompleteTrans();		
		
	}
	
	/**
	 * Delete list of domain nameserver hosts
	 *
	 * @param int $domainid
	 */
	public function DeleteList ($domainId)
	{
		$data = $this->DB->GetAll("SELECT * FROM nhosts WHERE domainid = ?", array($domainId));
		foreach ($data as $row)
			unset ($this->LoadedObjects[$row['id']]);
		
		$this->DB->Execute('DELETE FROM nhosts WHERE domainid = ?', array($domainId));
	}
}

?>