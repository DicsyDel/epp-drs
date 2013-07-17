<?php

class DBContact
{
	private $FieldPropertyMap = array(
		'id' 		=> 'ID',
		'clid' 		=> 'CLID',
		'fullname'	=> 'FullName',
		'TLD' 		=> 'Extension',
		'pw' 		=> 'AuthCode',
		//'status'	=> 'Status',
		'userid'	=> 'UserID',
		'parent_clid'=>'ParentCLID',
		'groupname'	=> 'GroupName',
		'strict_fields' => 'StrictlyValidated',
		'module_name' => 'ModuleName',
		'section_name' => 'SectionName',
		'target_index' => 'TargetIndex'
	);
	
	
	/**
	 * Database instance
	 */
	private $DB;

	private $LoadedObjects = array();		
	
	public function __construct ()
	{
		$this->DB = Core::GetDBInstance();
	}
	
	private static $Instance;
	
	/**
	 * @return DBContact
	 */
	public static function GetInstance ()
	{
		if (self::$Instance === null)
			self::$Instance = new DBContact();
		return self::$Instance;
	}
	
	/**
	 * Return contact in initial state, when it was just loaded
	 *
	 * @param Contact $contact
	 * @return Contact Clone of the domain
	 */
	public function GetInitialState (Contact $contact)
	{
		if ($contact->ID === null || !array_key_exists($contact->ID, $this->LoadedObjects))
			throw new Exception(_('Contact was not loaded through DBContact, or was deleted'));
			
		return clone $this->LoadedObjects[$contact->ID];
	}
	
	/**
	 * Find contact in database
	 *
	 * @param int $id
	 * @return it Primary key
	 */
	public function Find ($id)
	{
		return $this->DB->GetOne('SELECT id FROM contacts WHERE id = ?', array($id));
	}
	
	/**
	 * Find contact in database by CLID
	 *
	 * @param string $clid
	 * @return it Primary key
	 */
	public function FindByCLID ($clid)
	{
		return $this->DB->GetOne('SELECT id FROM contacts WHERE clid = ?', array($clid));
	}
	
	/**
	 * Return Contact object by ID
	 *
	 * @param integer $id
	 * @param RegistryManifest $registry_manifest
	 * @return Contact
	 */
	public function Load ($id, RegistryManifest $registry_manifest = null)
	{
		$row = $this->DB->GetRow("SELECT * FROM contacts WHERE id = ?", array($id));
		if (!$row) 
			throw new Exception(sprintf(_('Contact ID=%s not found in database'), $id));
		
		return $this->CreateObject($row, $registry_manifest);
	}
	
	/**
	 * Load contact from database by CLID
	 *
	 * @param string $CLID
	 * @param RegistryManifest $registry_manifest
	 * @return Contact
	 */
	public function LoadByCLID ($CLID, RegistryManifest $registry_manifest = null)
	{
		$row = $this->DB->GetRow("SELECT * FROM contacts WHERE clid = ?", array($CLID));
		if (!$row)
			throw new Exception(sprintf(_('Contact CLID=%s not found in database'), $CLID));
			
		return $this->CreateObject($row, $registry_manifest);
	}
	
	private function CreateObject ($row, RegistryManifest $registry_manifest = null)
	{
		// TODO:
		// Remove cross-reference to Registry
		
		$RegFactory = RegistryModuleFactory::GetInstance();
		if ($row['TLD'])
		{
			$registry = $RegFactory->GetRegistryByExtension($row['TLD']);			
		}
		else
		{
			$Manifest = new RegistryManifest(sprintf('%s/registries/%s/module.xml', MODULES_PATH, $row['module_name']));
			$registryOptions = $Manifest->GetRegistryOptions();
			if ((bool)$registryOptions->ability->section_shared_contacts)
			{
				// Contacts shared between sections. i.e. contact is suitable for all TLDs
				$rows = $this->DB->GetAll(
					"SELECT tld FROM tlds WHERE modulename = ? AND isactive = 1", 
					array($row['module_name'])
				); 
				$tlds = array();
				foreach ($rows as $_r)
					$tlds[] = $_r['tld'];
			}
			else
			{
				$Manifest->SetSection($row['section_name']);				
				
				list($target) = $Manifest->GetSectionConfig()->xpath(sprintf(
					"contact_groups/group[@name='%s']/targets/target[%d]", 
					$row['groupname'], $row['target_index']+1
				));
				
				if (!$target) {
					throw new ApplicationException(
							sprintf(_("Error in module manifest. Cannot find corresponding contact group for %s:%s"), 
							$row['groupname'], $row['target_index']+1));
				}
	
				$tlds = explode(',', $target->attributes()->tlds);
				if (!$tlds) 
				{
					throw new ApplicationException(sprintf(_("Error in module manifest. Contact target tlds attribute is empty. "
						. "Module=%s, SectionName=%s, Index=%s"), $row["module_name"], $row["section_name"], $row['target_index']));
				}
				
				// Ugly hack that helps contact to find it's configured module 
				$in = array();
				foreach ($tlds as $tld)
					$in[] = "'{$tld}'"; 
				
				$tldrows = $this->DB->GetAll(
					"SELECT TLD FROM tlds WHERE isactive=? AND modulename=? AND TLD IN (".join(",", $in).")",
					array("1", $row['module_name'])
				);
				$tlds = array();
				foreach ($tldrows as $tldrow)
					$tlds[] = $tldrow["TLD"];
			}
			
			
			foreach ($tlds as $tld) 
			{
				try
				{
					$registry = $RegFactory->GetRegistryByExtension($tld);
					break;
				}
				catch (Exception $e)
				{
					// TODO:
					// This may caught when tld going first in section is inactive.
					// Dirty bug itself. We must solve it
					Log::Log("Cannot load contact. tld=$tld message=".$e->getMessage(), E_USER_ERROR);
				}	
			}			

			if (!$registry)
			{
				throw new ApplicationException(sprintf(_("Cannot find registry object, that can handle contact ID=%d"), $row["id"]));
			}
		}
			
		$contact = $registry->NewContactInstanceByGroup($row['groupname']);
		$contact->Registry = $registry;
		

		// Map fields to properties
		foreach ($this->FieldPropertyMap as $field => $property)
			$contact->{$property} = $row[$field];

		$disclose_data = $this->DB->GetAll('SELECT * FROM contacts_discloses WHERE contactid = ?', array($contact->CLID));
		foreach ($disclose_data as $disclose_row)
			$contact->SetDiscloseValue($disclose_row['field_name'], (int)$disclose_row['value']);
			
		// Load contact fields
		//$contact_fields = $registry_manifest->GetContactFields($contact->Type);		
		$contact_data = $this->DB->GetAll('SELECT * FROM contacts_data WHERE contactid = ?', array($contact->CLID));

		$buf = array();
		foreach ($contact_data as $contact_row)
			//if (in_array($contact_row['field'], $contact_fields))
			$buf[$contact_row['field']] = $contact_row['value'];
			
		try 
		{
			$contact->SetFieldList($buf, (bool)$row['strict_fields']);
		} 
		catch (ErrorList $e) 
		{
			Log::Log(sprintf("Strict data validation failed for contact %s. Marking this contact as non-strictly validated.", $contact->CLID), E_USER_WARNING);
			Log::Log(join('; ', $e->GetAllMessages()), E_USER_WARNING);
			$contact->SetFieldList($buf, false);
		}
		
		// Extra stored data is diff between raw contact fields and manifset's defined fields
		$contact->ExtraStoredData = array_diff_key($buf, $contact->GetFieldList());
		
		// Add pending operations to contact
		$operations = $this->DB->GetAll("SELECT * FROM pending_operations WHERE objecttype='CONTACT' AND objectid=?", array($contact->ID));
		foreach ($operations as $op)
			$contact->AddPendingOperation($op["operation"]);
		
		
		// Add contact to loaded objects storage
		$this->LoadedObjects[$contact->ID] = clone $contact;
				
		return $contact;
	}
	
	public function Save (Contact $contact)
	{
		if ($contact->ID && $contact == $this->GetInitialState($contact))
		{
			// Object was not modified since last DB operation 
			return $contact;
		}
		
		if (!$contact->ID)
		{
			// check for duplicate contact
			$duplicate = $this->DB->GetOne(
				'SELECT COUNT(*) FROM contacts WHERE clid = ? AND TLD = ?',
				array($contact->CLID, $contact->Extension)
			);
			if ($duplicate)
			{ 
				throw new Exception(sprintf(
					_('Contact CLID=%s TLD=%s already exists in DB and could\'t be added twice'), 
					$contact->CLID, $contact->Extension
				));
			}
		}		
		
		
		$contact_config = $contact->GetConfig();
		
		$row = array();
		foreach ($this->FieldPropertyMap as $field => $property)
			$row[$field] = ($contact->{$property} !== null) ? $contact->{$property} : "";
			
		// Normalize data
		$row['strict_fields'] = (int)(bool)$row['strict_fields'];
		
		// Prepare data
		$contact_fields = $contact->GetFieldList();
		
		// Add extra stored fields
		foreach ($contact->ExtraStoredData as $k => $v)
		{
			if (!array_key_exists($k, $contact_fields))
			{
				$contact_fields[$k] = $v;
			}
		}
		
		// Save it!
		//if ($contact->ID)
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
			if ($contact->ID)
			{
				// Perform Update
				$bind[] = $contact->ID;
				$this->DB->Execute("UPDATE contacts SET $set WHERE id = ?", $bind);
			}
			else
			{
				// Perform Insert
				$this->DB->Execute("INSERT INTO contacts SET $set", $bind);
				$contact->ID = $this->DB->Insert_ID();
			}
	
			// Save contact fields
			foreach ($contact_fields as $name => $value)
			{
				$this->DB->Execute
				(
					'REPLACE INTO contacts_data SET contactid=?, field=?, value=?',
					array($contact->CLID, $name, $value)
				);
			}
			
			// Save discloses
			$disclose_map = $contact->GetDiscloseList();
			foreach($disclose_map as $name => $value)
			{
				$this->DB->Execute
				(
					'REPLACE INTO contacts_discloses SET contactid=?, field_name=?, value=?',
					array($contact->CLID, $name, $value)
				);
			}
		}
		catch(Exception $e)
		{
			$this->DB->RollbackTrans();
			throw new ApplicationException ($e->getMessage(), $e->getCode());
		}
			
		$this->DB->CompleteTrans();
		
		// Update contact to loaded objects storage
		$this->LoadedObjects[$contact->ID] = clone $contact;
		
		return $contact;
	}
	
	public function Delete (Contact $contact)
	{
		$this->DB->Execute('DELETE FROM contacts WHERE clid = ?', array($contact->CLID));
		$this->DB->Execute('DELETE FROM contacts_data WHERE contactid = ?', array($contact->CLID));
		$this->DB->Execute('DELETE FROM contacts_discloses WHERE contactid = ?', array($contact->CLID));

		// Remove it from loaded objects storage
		unset($this->LoadedObjects[$contact->ID]);
	}
	
	public function GenerateCLID ($pattern)
	{
		// Recursion counter
		static $rc;
		if ($rc >= 10)
			throw new Exception(_('CLID generation not succeeded in reasonable time'));
		
		$id = $pattern;
			
		preg_match_all("/(%([a-z])(\d+))/", $pattern, $matches);
		foreach($matches[2] as $index => $pat)
		{
			switch($pat)
			{
				case "d":
					$id = str_replace($matches[0][$index], 
									$this->GenerateNumber((int)$matches[3][$index]), $id);					
					break;
					
				case "w":
					$id = str_replace($matches[0][$index], 
									$this->GenerateWord((int)$matches[3][$index]), $id);					
					break;
			}
		}			
	
		$exists = $this->DB->GetOne("SELECT COUNT(*) FROM contacts WHERE clid=?", array($id));
		if (!$exists)
		{
			$rc = 0;
			return $id;
		}
		else
		{
			$rc++;
			return self::GenerateCLID($pattern);
		}
	}
	
	private function GenerateNumber ($num_digits)
	{
		//TODO: ��������� �� ����������� � 10 ��������
		if ($num_digits < 1) 
			return '';
		else if ($num_digits > 10)
			$num_digits = 10;
		
		$min = (int) str_pad('1', $num_digits, '0', STR_PAD_RIGHT);
		$max = (int) str_pad('9', $num_digits, '9', STR_PAD_RIGHT);
		return rand($min, $max);
	}
	
	private function GenerateWord ($num_chars)
	{
		if ($num_chars < 1)
			return '';

		$retval = "";
		do
		{
			$retval .= strtoupper(chr(rand(65, 90)));
		}
		while(strlen($retval) != $num_chars);
		
		return $retval;
	}
}

?>