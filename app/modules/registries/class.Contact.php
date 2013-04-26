<?

	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @category EPP-DRS
     * @package Modules
     * @subpackage RegistryModules
     * @sdk
     */

	/**
	 * Contact object
	 * @name Contact
	 * @category   EPP-DRS
	 * @package Modules
     * @subpackage RegistryModules
	 * @author Marat Komarov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	final class Contact
	{
		/**
		 * EPP-DRS Contact ID (Database ID)
		 *
		 * @var int
		 */
		public $ID;
		
		/**
		 * Registry contact ID
		 *
		 * @var string
		 */
		public $CLID;
		
		/**
		 * Contact full name. 
		 * Usefull when contact cannot be syncronized. 
		 * @var string
		 */
		public $FullName;
		
		/**
		 * Registry parent contact ID. Optional.
		 *
		 * @var string
		 */
		public $ParentCLID;
		
		/**
		 * Group name of this contact
		 *
		 * @var string
		 */
		public $GroupName;
		
		/**
		 * 
		 * @var string
		 * @deprecated 
		 */
		public $Extension;
		
		// Contact identity:
		public $ModuleName;
		public $SectionName;
		public $TargetIndex;
		//
		
		/**
		 * Database ID of the client who owns this contact 
		 *
		 * @var int
		 */
		public $UserID;
		
		/**
		 * Contact authentification code/password
		 *
		 * @var string
		 */
		public $AuthCode;
	
		/**
		 * Contact data. Registry-specific contact fields (name, email, voice etc.)
		 *
		 * @var array (associative)
		 */
		private $Fields = array();
	
		/**
		 * Extra fields values (not stored in database)
		 *
		 * @var array
		 */
		public $ExtraData = array();
		
		/**
		 * Extra fields, that stored in database
		 *
		 * @var array
		 */
		public $ExtraStoredData = array();
		
		/**
		 * Associative
		 *
		 * @var bool
		 */
		public $StrictlyValidated = true;
		
		/**
		 * @var Registry
		 */
		public $Registry;
		
		/**
		 * Flags of contact fields disclosing
		 *
		 * @var array (field=>bool)
		 */
		private $Discloses = array();
		
		/**
		 * Contact pending operations
		 *
		 * @var array
		 */
		private $PendingOperations = array();	
		
		/**
		 * @var SimpleXMLElement
		 */
		private $Config;
		
		private $PhonePattern;
		
		public function __construct ($contact_config)
		{
			$this->Config = $contact_config;
			
			if ($this->Config->disclose instanceof SimpleXMLElement)
			{
				$options = $this->Config->disclose->xpath("option");
				foreach($options as $option)
				{
					settype($option, "array");
					$this->Discloses[$option['@attributes']['name']] = 1;
				}	
			}
			
			$this->UserID = 0;
		}
		
		/**
		 * Must return full name of contact person
		 *
		 * @return string
		 * @throws Exception
		 */
		public function GetFullName()
		{
			if ($this->FullName)
				return $this->FullName;
			
			if (!$this->Fields)
				throw new Exception("You cannot use GetFullName() method before SetFieldList() method called.");
				
			$retval = array();
			$fields = $this->Config->xpath("display_fields/name/*/@*");
			if ($fields && count($fields) > 0)
			{
				foreach($fields as $field)
					$retval[] = $this->Fields[(string)$field->name];
			}
			else
				throw new Exception("Malformed module manifest. <display_fields><name>...</name></display_fields> not defined");
			
			return implode(" ", $retval);
		}
		
		public function SetFullName ($full_name)
		{
			
		}
		
		/**
		 * Must return email address of contact person
		 *
		 * @return string
		 * @throws Exception
		 */
		public function GetEmail()
		{
			if (!$this->Fields)
				throw new Exception("You cannot use GetFullName() method before SetFieldList() method called.");
				
			$retval = array();
			$fields = $this->Config->xpath("display_fields/email/*/@*");
			if ($fields && count($fields) > 0)
			{
				foreach($fields as $field)
					$retval[] = $this->Fields[(string)$field->name];
			}
			else
			{
				if (!$this->Config->display_fields->email)
					throw new Exception("Malformed module manifest. <display_fields><email>...</email></display_fields> not defined");
				else
					return "";
			}
			
			return implode(" ", $retval);
		}
		
		public function GetTitle ()
		{
	        $title = substr($this->CLID, 0, 12);
	        if (strlen($this->CLID) > 12)
	        {
	        	$title .= '...';
	        }

	        $name = $this->GetFullName();
	        $email = $this->GetEmail();	        
	        
	        if ($this->Fields['org'])
	        {
	        	$title .= ", {$this->Fields['org']}";
	        }
	        
            if ($name)
            {
            	$title .= ", $name"; 
            }
            if ($email)
            {
            	$title .= ", $email";
            }
            
            return $title;
		}
		
		public function GetTargetExtensions ()
		{
			// Backward compatibility
			if ($this->Extension)
			{
				return array($this->Extension);
			}
			
			$target = $this->Config->targets->target[(int)$this->TargetIndex];
			return explode(',', $target->attributes()->tlds);
		}
		
		public function GetTargetTitle ()
		{
			if ($this->ModuleName && !($this->SectionName || $this->TargetIndex))
			{
				return $this->ModuleName;
			}
			else
			{
				$tlds = $this->GetTargetExtensions();
				if (count($tlds) <= 3)
				{
					return join(',', $tlds);
				}
				else
				{
					if (count($this->Config->targets->target) > 1)
					{
						return sprintf("%s %s, %s", $this->ModuleName, ucfirst($this->SectionName), $this->TargetIndex);
					}
					else
					{
						return sprintf("%s %s", $this->ModuleName, ucfirst($this->SectionName));
					}
				}
			}
		}
		
		/**
		 * Must return contact section from module manifest
		 *
		 * @return SimpleXMLElement
		 */
		public function GetConfig ()
		{
			return $this->Config;
		}
		
		/**
		 * Must return contact data
		 *
		 * @return array Array of fields, keys are equal to field names
		 */
		public function GetFieldList ()
		{
			return $this->Fields;
		}
		
		/**
		 * Must return registry formatted contact data
		 *
		 * @return array Array of fields, keys are equal to field names
		 */
		public function GetRegistryFormattedFieldList ()
		{
			$ret = $this->Fields;
			foreach ($this->Config->fields->field as $FieldObject)
			{
				$field = (array)$FieldObject;
				$field = $field['@attributes'];
				if ($field['type'] == 'phone')
				{
					unset($ret[$field['name']. '_display']);
				}
			}
			
			return $ret;
		}
		
		/**
		 * Must return value of $fieldname contact field
		 *
		 * @param string $fieldname
		 * @return string
		 */
		public function GetField($fieldname)
		{
			return $this->Fields[$fieldname];
		}
		
		/**
		 * Must return editable fields names
		 *
		 * @return string[]
		 */
		public function GetEditableNames () 
		{
			$editable_names = array();
			foreach ($this->Config->fields->field as $XmlField)
			{
				if ($XmlField->attributes()->iseditable == '1')
				{
					$editable_names[] = (string)$XmlField->attributes()->name;
				}
			}
			if ($this->Config->extra_fields)
			{
				foreach ($this->Config->extra_fields->children() as $if)
				{
					$n = (string)$if->attributes()->field;
					$v = (string)$if->attributes()->value;
					
					if ($this->Fields[$n] == $v)
					{
						foreach ($if->field as $XmlField)
						{
							if ($XmlField->attributes()->iseditable == '1')
							{
								$editable_names[] = (string)$XmlField->attributes()->name;
							}
						}
					}
				}
			}				
			return $editable_names;
		}
		
		/**
		 * Set contact data fields
		 *
		 * @param array(key=>value) $data
		 * @throws ErrorList
		 */
		public function SetFieldList ($data, $strict=true)
		{
			$data = array_map("trim", $data);

			$ErrList = new ErrorList();
			
			if (($callback = $this->Config->attributes()->validation_callback) != null)
			{
				list($class, $method) = explode('::', $callback);
				$func = array($class, $method);
				if (is_callable($func))
				{
					$err = array();
					try
					{
						$err = call_user_func($func, $data);
					}
					catch (Exception $e)
					{
						Log::Log(sprintf("Contact validation user-defined handler failed. %s", $e->getMessage()));
					}
					
					if ($err)
					{
						foreach ((array)$err as $errmsg)
						{
							$ErrList->AddMessage($errmsg);
						}
					}
				}
			}
			
			$buf = array();
			
			$this->AcceptFieldList($this->Config->fields->field, $data, $buf, $ErrList, $strict);
			
			if ($this->Config->extra_fields)
			{
				foreach ($this->Config->extra_fields->children() as $n => $if)
				{
					$n = (string)$if->attributes()->field;
					$v = (string)$if->attributes()->value;
					
					if ($buf[$n] == $v)
					{
						$this->AcceptFieldList($if->children(), $data, $buf, $ErrList, $strict);
					}
				}
			}

			if ($ErrList->HasMessages() && $strict)
			{
				// remove duplicate messages
				$errors = array_unique($ErrList->GetAllMessages());
				$ErrList = new ErrorList();
				foreach ($errors as $err)
					$ErrList->AddMessage($err);
				
				throw $ErrList;
			}
				
			$this->StrictlyValidated = $strict;
			$this->Fields = $buf;
		}
		
		private function AcceptFieldList ($xml_fields, $data, &$buf, $ErrList, $strict=true)
		{
			foreach ($xml_fields as $f_obj)
			{			
				settype($f_obj, "array");			
				$field = $f_obj["@attributes"];
							
				$field_name = (string)$field["name"];
				
				$err = false;
				
				if ($field_name)
				{
					$description = ucfirst($field['description']);
					
					if ($strict && (int)$field['iseditable'] == 0)
					{
						if ((int)$field['iseditable'] == 0 && isset($data[$field_name]) && isset($this->Fields[$field_name]))
							$err = sprintf(_("'%s' is uneditable"), $description);
						else if (isset($this->Fields[$field_name]))
						{
							$buf[$field_name] = $this->Fields[$field_name];
							continue;
						}
					}
					
					// �������� �� ������������ ����
					if ($strict && (bool)$field['required'] && (strlen($data[$field_name]) == 0 && ($field['iseditable'] || (!$field['iseditable'] && !array_key_exists($field_name, $this->Fields)))))
					{
						$err = sprintf(_("'%s' is required"), $description);
					}
					elseif ($strict && strlen($data[$field_name]))
					{
						if ((int)$field["minlength"])
						{
							if (strlen($data[$field_name]) < (int)$field["minlength"])
								$err = sprintf(_("'%s' must contain at least %d chars"), $description, (int)$field["minlength"]);
						}
							
						if ((int)$field["maxlength"])
						{
							if (strlen($data[$field_name]) > (int)$field["maxlength"])
								$err = sprintf(_("'%s' cannot be longer than %d chars"), $description, (int)$field["maxlength"]);
						}
							
						if ($field["pattern"] && !preg_match("{$field['pattern']}msi", $data[$field_name]))
							$err = sprintf(_("'%s' is not valid"), $description);
					}
					
					
					if ($field['type'] == 'phone' && $data[$field_name])
					{
						$Phone = Phone::GetInstance();
						if ($Phone->IsE164($data[$field_name]))
						{
							// E164 phone
							$buf[$field_name] = $data[$field_name];
							if (isset($data[$field_name . '_display']))  
								$buf[$field_name . '_display'] = $data[$field_name . '_display'];
							else
								$buf[$field_name . '_display'] = $Phone->E164ToPhone($data[$field_name]);
								 
							continue;
						}
						else if ($Phone->IsPhone($data[$field_name]))
						{
							// Display phone 
							$buf[$field_name] = $Phone->PhoneToE164($data[$field_name]);
							$buf[$field_name . '_display'] = $data[$field_name];
							 
							continue;
						}
						else if ($strict)
						{
							$err = sprintf(_('%s must be in %s format'), $description, CONFIG::$PHONE_FORMAT);
						}
					}
	
					if (!$err) 
					{
						$buf[$field_name] = $data[$field_name];
					} 
					else 
					{
						$ErrList->AddMessage($err);
					}
				}
			}
			
		}
		
		
		/**
		 * Set disclose flag to contact field.
		 * Show/hide $field in WHOIS
		 * 
		 * @param string $field
		 * @param bool $showIt
		 */
		public function SetDiscloseValue ($field, $showIt)
		{
			if (array_key_exists($field, $this->Discloses))
				$this->Discloses[$field] = (int)(bool)$showIt;
		}
		
		/**
		 * This method set list of disclose flags
		 *
		 * @param array $disclose_list Assoc array, where keys - field names, values - boolean flag
		 */
		public function SetDiscloseList ($disclose_list)
		{
			foreach ($disclose_list as $field => $showIt)
				$this->SetDiscloseValue($field, $showIt);
		}
		
		/**
		 * This method must return list of contact fields discloses 
		 *
		 * @return array  Assoc array, where keys - field names, values - boolean flag
		 */
		public function GetDiscloseList ()
		{
			return $this->Discloses;
		}
		
		/**
		 * Must add pending operation type to contact
		 *
		 * @param string $operation_type
		 */
		public function AddPendingOperation($operation_type)
		{
			$this->PendingOperations[$operation_type] = 1;
		}
		
		/**
		 * Returns true when contact has such operation type in it's pending operation list
		 *
		 * @param string $operation_type
		 * @return bool
		 */
		public function HasPendingOperation($operation_type)
		{
			return isset($this->PendingOperations[$operation_type]);
		}
		
		/**
		 * Returns contact pending operation types list 
		 *
		 * @return array
		 */
		public function GetPendingOperationList()
		{
			return array_keys($this->PendingOperations);
		}	
		
		/**
		 * Magic method. Called before object serialization
		 *
		 * @ignore 
		 * @return array
		 */
		public function __sleep()
	    {
	        return array('ID', 'CLID', 'Extension', 'UserID', 'AuthCode', 
	        			 'Fields', 'ExtraData', 'Discloses', 
	        			 'ModuleName', 'SectionName', 'TargetIndex', 'GroupName');
	    }
	    
	    /**
	     * Convert phone from E.164 (+XXX.XXXXXXXX) format to display format
	     *
	     * @param string $string
	     * @return string
	     */
	    public function E164ToPhone($string)
	    {
	    	if (empty($string))
	    		return "";
	
			$numbers = preg_replace("/[^0-9]+/", "", $string);
	    	
	    	$chunks = str_split($numbers, 3);
	    	
	    	return trim(array_shift($chunks)."-".array_shift($chunks)."-".implode("", $chunks), "-");
	    }
	    
	    /**
	     * Convert display phone to e.164 (+XXX.XXXXXXXX)
	     *
	     * @param string $string
	     * @return unknown
	     */
	    public function PhoneToE164($string)
	    {
	    	if (empty($string))
	    		return "";
	    		
	    	$numbers = preg_replace("/[^0-9]+/", "", $string);
	    	$chunks = str_split($numbers, 3);
	    	
	    	return trim("+".array_shift($chunks).".".implode("", $chunks));
	    }
	    
	    /**
	     * Converts Contact to Array
	     * @return array
	     */
	    public function ToArray()
	    {
	    	return array(
	    		'ID'		=> $this->ID,
	    		'CLID'		=> $this->CLID,
	    		'Extension' => $this->Extension,
	    		'AuthCode'	=> $this->AuthCode,
	    		'UserID'	=> $this->UserID,
	    		'Data'		=> $this->Fields,
	    		'ExtraData' => $this->ExtraData,
	    		'Discloses' => $this->Discloses	
	    	);
	    }
	}

?>