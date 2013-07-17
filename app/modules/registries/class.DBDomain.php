<?php

/**
 * @name DBDomain
 * @category   EPP-DRS
 * @package    Modules
 * @subpackage RegistryModules
 * @author Marat Komarov <http://webta.net/company.html> 
 * @author Igor Savchenko <http://webta.net/company.html>
 */

/**
 * This class provides Domain persistence in database
 */
class DBDomain
{
	private $FieldPropertyMap = array(
		'id' 		=> 'ID',
		'name' 		=> 'Name',
		'TLD' 		=> 'Extension',
		'pw' 		=> 'AuthCode',
		'protocol' 	=> 'Protocol',
		'status'	=> 'Status',
		'sys_status'=> 'RegistryStatus',
		'delete_status' => 'DeleteStatus',
		'userid'	=> 'UserID',
		'start_date'=> 'CreateDate',
		'end_date'	=> 'ExpireDate',
		'dtTransfer'=> 'TransferDate',
		'period'	=> 'Period',
		'error_msg' => 'ErrorMsg',
		'islocked'	=> 'IsLocked',
		'comment'	=> 'Comment',
		'managed_dns' => 'IsManagedDNSEnabled',
		'incomplete_operation' => 'IncompleteOrderOperation',
		'renew_disabled' => 'RenewDisabled',
		'outgoing_transfer_status' => 'OutgoingTransferStatus'
	);
	
	private $ContactFieldTypeMap = array
	(
		'c_registrant' 	=> CONTACT_TYPE::REGISTRANT,
		'c_admin' 		=> CONTACT_TYPE::ADMIN,
		'c_billing' 	=> CONTACT_TYPE::BILLING,
		'c_tech' 		=> CONTACT_TYPE::TECH	
	);
	
	/**
	 * Database instance
	 */
	private $DB;
	
	/**
	 * @var DBContact
	 */
	private $DBContact;
	
	/**
	 * @var DBNameserverHost
	 */
	private $DBNameserverHost;
	
	public function __construct ()
	{
		$this->DB = Core::GetDBInstance();
		$this->DBContact = DBContact::GetInstance(); 
		$this->DBNameserverHost = DBNameserverHost::GetInstance();
	}
	
	private static $Instance;
	
	private $LoadedObjects = array();
	
	/**
	 * @return DBDomain
	 */
	public static function GetInstance ()
	{
		if (self::$Instance === null)
			self::$Instance = new DBDomain();
		return self::$Instance;
	}

	/**
	 * Return domain in initial state, when it was just loaded
	 *
	 * @param Domain $domain
	 * @return Domain Clone of the domain
	 */
	public function GetInitialState (Domain $domain)
	{
		if ($domain->ID === null || !array_key_exists($domain->ID, $this->LoadedObjects))
			throw new Exception(_('Domain was not loaded through DBDomain, or was deleted'));
			
		return clone $this->LoadedObjects[$domain->ID];
	}
	
	/**
	 * Find domain
	 * 
	 * @return int Primary key of domain
	 */
	public function Find ($id)
	{
		return $this->DB->GetOne('SELECT id FROM domains WHERE id = ?', array($id));
	}
	
	/**
	 * Find domain by name, extension 
	 *
	 * @return int Primary key of domain
	 */
	public function FindByName ($name, $extension)
	{
		return $this->DB->GetOne('SELECT id FROM domains WHERE name = ? AND TLD = ?', array($name, $extension));
	}
	
	/**
	 * Load domain from DB
	 *
	 * @param int $id Primary key
	 * @return Domain
	 */
	public function Load ($id, RegistryManifest $registry_manifest = null)
	{
		$row = $this->DB->GetRow("SELECT * FROM domains WHERE id = ?", array($id));
		if (!$row) 
			throw new Exception(sprintf(_('Domain ID=%s not found in database'), $id));
		
		return $this->CreateObject($row, $registry_manifest);
	}
	
	/**
	 * Load domain by it's Name and Extension
	 *
	 * @param string $name
	 * @param string $extension
	 * @return Domain
	 */
	public function LoadByName ($name, $extension, RegistryManifest $registry_manifest = null)
	{
		$row = $this->DB->GetRow("SELECT * FROM domains WHERE name = ? AND TLD = ?", array($name, $extension));
		if (!$row) 
			throw new Exception(sprintf(_("Domain named '%s.%s' not found in database"), $name, $extension));
		
		return $this->CreateObject($row, $registry_manifest);
	}
	
	private function CreateObject ($row, RegistryManifest $registry_manifest = null)
	{
		/*
		if ($registry_manifest == null)
		{
			$registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($row['TLD']);
			$registry_manifest = $registry->GetManifest();
			unset($registry);
		}
		*/
		
		// TODO
		// ���������� ���������� �� ������������ ������, ���� ������� ���� �������� 
		// ��������� ������� Domain
		// 
		
		$registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($row['TLD']);
		if (!$registry)
			throw new Exception(sprintf(_("Registry module not defined for '%s' domain extension"), $row['TLD']));
		
		$domain = $registry->NewDomainInstance();
		
		$registry_manifest = $registry->GetManifest();
		//unset($registry);
		
		
		// Normalize DB data
		$row['start_date'] 	= strtotime($row['start_date']);
		$row['end_date'] 	= strtotime($row['end_date']);
		$row['dtTransfer'] 	= strtotime($row['dtTransfer']);
		$row["islocked"] 	= (bool)$row["islocked"];
		$row['managed_dns'] = (bool)$row['managed_dns'];

		// Normalize nameservers data
		$ns_arr = array();
		if ($row['ns1'])
			$ns_arr[] = $row['ns1'];

		if ($row['ns2'])
			$ns_arr[] = $row['ns2'];
		
		if ($row['ns_n'])
		{
			$ns_n = array_map('trim', explode(';', $row['ns_n']));
			foreach ($ns_n as $hostname)
				$ns_arr[] = $hostname;
		}
		
		// Load glue records
		$arr = $this->DB->GetAll('SELECT * FROM nhosts WHERE domainid = ?', array($row['id']));
		$glue_records = array();
		foreach ($arr as $tmp)
			$glue_records["{$tmp['hostname']}.{$row['name']}.{$row['TLD']}"] = $tmp;
			
		// Create nameservers list
		$nslist = array();
		foreach ($ns_arr as $hostname)
		{
			// Check that nameserver is glue record.
			if (FQDN::IsSubdomain($hostname, "{$row["name"]}.{$row["TLD"]}") && 
				array_key_exists($hostname, $glue_records))
			{
				$nshost = new NameserverHost($hostname, $glue_records[$hostname]['ipaddr']);
				$nshost->ID = $glue_records[$hostname]['id'];
				$nslist[] = $nshost;
			}
			else
			{
				$nslist[] = new Nameserver($hostname);
			}
		} 
		
		// Map fields to properties
		foreach ($this->FieldPropertyMap as $field => $property)
			$domain->{$property} = $row[$field];

		// Set nameservers
		$domain->SetNameserverList($nslist);

		// Load extra fields
		$extra_fields = $this->DB->GetAll('SELECT `key`, `value` FROM domains_data WHERE domainid = ?', array($domain->ID));
		foreach ($extra_fields as $ext_row)
		{
			$domain->{$ext_row['key']} = $ext_row['value'];
			$domain->SetExtraField($ext_row['key'], $ext_row['value']);
		}
		
		// Load flags
		$flags_data = $this->DB->GetAll('SELECT DISTINCT flag FROM domains_flags WHERE domainid = ?', array($domain->ID));
		$flag_list = array();
		foreach ($flags_data as $flag_row)
			$flag_list[] = $flag_row['flag'];
			
		$domain->SetFlagList($flag_list);
		
		// Load contacts
		foreach ($this->ContactFieldTypeMap as $field => $contact_type)
		{		
			$CLID = $row[$field];
			
			if ($CLID)
			{
				try
				{
					$contact = $this->DBContact->LoadByCLID($CLID, $registry_manifest);
				}
				catch (Exception $e)
				{
					$contact = $registry->NewContactInstance($contact_type);
					$contact->CLID = $CLID;
				}
				$domain->SetContact($contact, $contact_type);
			}
		}
		
		// Add pending operations to domain
		$operations = $this->DB->Execute("SELECT * FROM pending_operations WHERE objecttype='DOMAIN' AND objectid=?", array($domain->ID));
		while($op = $operations->FetchRow())
		{
			if ($op)
				$domain->AddPendingOperation($op["operation"]);
		}
		
		// Add domain to loaded objects storage
		$this->LoadedObjects[$domain->ID] = clone $domain;
		
		return $domain;
	}
	
	/**
	 * Save domain in database
	 *
	 * @param Domain $domain
	 * @return Domain
	 */
	public function Save (Domain $domain)
	{
		if (!$domain->ID)
		{
			// check for duplicate domain
			$duplicate = $this->DB->GetOne(
				'SELECT COUNT(*) FROM domains WHERE name = ? AND TLD = ?',
				array($domain->Name, $domain->Extension)
			);
			if ($duplicate) 
				throw new Exception(sprintf(_('Domain %s already exists in DB and could\'t be added twice'), $domain->GetHostName()));
		}
		
		// Properties data
		$row = array();
		foreach ($this->FieldPropertyMap as $field => $property)
			$row[$field] = ($domain->{$property} !== null) ? $domain->{$property} : "";
		if ($domain->IncompleteOrderOperation === null)
			$row['incomplete_operation'] = null;
						
		// Nameservers data
		$nslist = $domain->GetNameserverList();
		// If nameservers list smaller then size of db slots
		for ($i=2; $i>count($ns_list); $i--)
			$row['ns' . $i] = '';
		$ns_n = array();
		foreach (array_values($nslist) as $i => $ns)
		{
			if ($i < 2)
				$row['ns' . ($i+1)] = $ns->HostName;
			else
				$ns_n[] = $ns->HostName;
		}
		$row['ns_n'] = join(';', $ns_n);
		
		// Contacts data
		$contact_list = $domain->GetContactList();
		foreach ($this->ContactFieldTypeMap as $field => $contact_type)
		{
			$contact = $contact_list[$contact_type];
			// Add/Remove references to contact
			$row[$field] = $contact ? $contact->CLID : ''; 
		}

		// Domain extra fields
		$extra_fields = array();
		foreach ($domain->GetConfig()->xpath('registration/extra_fields/field') as $field)
		{
			settype($field, "array");			
			$field = $field["@attributes"];
			if (isset($domain->{$field['name']}))
				$extra_fields[$field['name']] = $domain->{$field['name']};
		}
		
		foreach ((array)$domain->ExtraFields as $k=>$v)
			$extra_fields[$k] = $v;
				
		// Prepare data for DB
		$row["start_date"] 	= $row["start_date"] 	? date("Y-m-d H:i:s", $row["start_date"]) 	: '0000-00-00 00:00:00';
		$row["end_date"] 	= $row["end_date"] 		? date("Y-m-d H:i:s", $row["end_date"])		: '0000-00-00 00:00:00';
		$row['dtTransfer'] 	= $row['dtTransfer'] 	? date("Y-m-d H:i:s", $row["dtTransfer"])	: '0000-00-00 00:00:00';
		$row["islocked"] 	= (int)(bool)$row["islocked"];
		$row['managed_dns'] = (int)(bool)$row['managed_dns'];
		$row['period']		= (int)$row['period'];
		$row['delete_status'] = (int)$row['delete_status'];
		$row['renew_disabled'] = (int)(bool)$row['renew_disabled'];
		

		// Save it!
		//if ($domain->ID)
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
		
		$this->DB->BeginTrans();
		try
		{
			if ($domain->ID)
			{
				// Perform Update
				$bind[] = $domain->ID;
				$this->DB->Execute("UPDATE domains SET $set WHERE id = ?", $bind);
			}
			else
			{
				// Perform Insert
				$this->DB->Execute("INSERT INTO domains SET $set", $bind);
				$domain->ID = $this->DB->Insert_ID();
			}
			
			// Save extra data
			$this->DB->Execute('DELETE FROM domains_data WHERE domainid = ?', array($domain->ID));
			foreach ($extra_fields as $name => $value)
			{
				$this->DB->Execute(
					'INSERT INTO domains_data SET `domainid` = ?, `key` = ?, `value` = ?',
					array($domain->ID, $name, $value)
				);
			}
			
			// Save flags
			$this->DB->Execute('DELETE FROM domains_flags WHERE domainid = ?', array($domain->ID));
			$flag_list = $domain->GetFlagList();
			foreach ($flag_list as $flag)
				$this->DB->Execute('INSERT INTO domains_flags SET domainid = ?, flag = ?', array($domain->ID, $flag));
			
			// Save contacts
			foreach ($contact_list as $contact)
			{
				if (!$contact->UserID)
				{
					$contact->UserID = $domain->UserID;
				}
				$this->DBContact->Save($contact);
			}
				
			// Save nameserver hosts
			

			$ns_list = $domain->GetNameserverHostList();
			foreach ($ns_list as $ns)
			{
				$ns_id = $this->DB->GetOne(
					'SELECT id FROM nhosts WHERE domainid=? AND hostname=?', 
					array($domain->ID, $ns->GetBaseName())
				); 
				
				if ($ns_id)
				{
					$this->DB->Execute(
						'UPDATE nhosts SET ipaddr=? WHERE hostname=? AND domainid=?',
						array($ns->IPAddr, $ns->GetBaseName(), $domain->ID)
					);
				}
				else
				{
					$this->DB->Execute(
						'INSERT INTO nhosts SET domainid=?, hostname=?, ipaddr=?',
						array($domain->ID, $ns->GetBaseName(), $ns->IPAddr)					
					);
					$ns_id = $this->DB->Insert_ID();
				}
				$ns->ID = $ns_id;
			}
			//$this->DBNameserverHost->SaveList($ns_list, $domain->ID);
		}
		catch(Exception $e)
		{
			$this->DB->RollbackTrans();
			throw new ApplicationException ($e->getMessage(), $e->getCode());
		}
			
		$this->DB->CompleteTrans();
			
		// Update domain in loaded objects storage
		$this->LoadedObjects[$domain->ID] = clone $domain;
		
		return $domain;
	}
	
	public function Delete (Domain $domain)
	{
		$this->DB->BeginTrans();		
		
		try
		{
			$this->DB->Execute('DELETE FROM domains WHERE id = ?', array($domain->ID));
			$this->DB->Execute('DELETE FROM domains_data WHERE domainid = ?', array($domain->ID));
			$this->DB->Execute('DELETE FROM domains_flags WHERE domainid = ?', array($domain->ID));
			$this->DB->Execute("UPDATE zones SET isdeleted = '1' WHERE zone=?", array($domain->GetHostName()));
			
			$purposes = implode(',', array(
				$this->DB->qstr(INVOICE_PURPOSE::DOMAIN_CREATE), 
				$this->DB->qstr(INVOICE_PURPOSE::DOMAIN_RENEW),
				$this->DB->qstr(INVOICE_PURPOSE::DOMAIN_TRANSFER),
				$this->DB->qstr(INVOICE_PURPOSE::DOMAIN_TRADE)
			));
			
			$this->DB->Execute("UPDATE invoices SET itemid=0, status=IF(status = 0, 2, status) WHERE itemid=? AND purpose IN ({$purposes})", array($domain->ID));
			$this->DBNameserverHost->DeleteList($domain->ID);
			$domain->ID = null;
		}
		catch (Exception $e)
		{
			$this->DB->RollbackTrans();
			Log::Log(sprintf("DBDomain::Delete failed. %s", $e->getMessage()), E_ERROR);
			throw new ApplicationException ($e->getMessage(), $e->getCode());
		}
		
		$this->DB->CompleteTrans();
		
		// Remove it from loaded objects storage
		unset($this->LoadedObjects[$domain->ID]);
	}
	
	public function ActiveDomainExists(Domain $domain)
	{
		
		return (bool)Core::GetDBInstance()->GetRow("
			SELECT id FROM domains 
			WHERE 
				domains.name=? AND 
				domains.TLD=? AND 
				domains.status!='".DOMAIN_STATUS::EXPIRED."' AND 
				domains.status!='".DOMAIN_STATUS::TRANSFER_FAILED."' AND 
				domains.status!='".DOMAIN_STATUS::REJECTED."' AND 
				domains.status!='".DOMAIN_STATUS::REGISTRATION_FAILED."' AND
				domains.status!='".DOMAIN_STATUS::APPLICATION_RECALLED."'", 
			array($domain->Name, $domain->Extension)
		);
	}
}

?>