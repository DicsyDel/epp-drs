<?php

	/**
	 * @name Domain
	 * @category   EPP-DRS
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @sdk
	 * @author Marat Komarov <http://webta.net/company.html> 
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	
	require_once ('class.Changelist.php');
	
	/**
	 * Registry-side domain object
	 * 
	 */
	final class Domain
	{
		/**
		 * Domain ID in database
		 *
		 * @var integer
		 */
		public $ID;
		
		/**
		 * Domain name without extension
		 *
		 * @var string
		 */
		public $Name;
		
		/**
		 * Domain Extension
		 *
		 * @var string
		 */
		public $Extension;
	
		/**
		 * Expiration date
		 *
		 * @var time
		 */
		public $ExpireDate;
		
		/**
		 * Domain Creation date
		 *
		 * @var time
		 */
		public $CreateDate;
		
		
		/**
		 * Is domain locked
		 *
		 * @var bool
		 */
		public $IsLocked;
		
		/**
		 * Days before expiration
		 *
		 * @var int
		 */
		public $DaysBeforeExpiration;
		
		/**
		 * Transfer request date
		 *
		 * @var time
		 */
		public $TransferDate;
		
		/**
		 * Domain delegation period, years
		 *
		 * @var int
		 */
		public $Period;
		
		/**
		 * Domain auth code (password)
		 *
		 * @var string
		 */
		public $AuthCode;
		
		/**
		 * Domain Application protocol
		 *
		 * @var string
		 */
		public $Protocol;
		
		/**
		 * Domain status
		 *
		 * @var string
		 * @see DOMAIN_STATUS constants
		 */
		public $Status;
		
		/**
		 * Registry domain status
		 * 
		 * @var string
		 */
		public $RegistryStatus;
		
		/**
		 * Domain
		 * @var int
		 */
		public $DeleteStatus;
		
		/**
		 * Owner user of this domain
		 *
		 * @var int
		 */
		public $UserID;
		
	
		/**
		 * Domain crID in registry
		 *
		 * @var string
		 */
		public $RemoteCRID;
		
		/**
		 * Domain clID in registry
		 *
		 * @var string
		 */
		public $RemoteCLID;
			
		/**
		 * @var bool
		 */
		public $RenewDisabled;
		
		/**
		 * Domain Contacts List
		 *
		 * @var Contact[]
		 */
		private $Contacts = array();
		
		/**
		 * Domain nameservers list
		 *
		 * @var Nameserver[]
		 */
		private $Nameservers = array();
	
		/**
		 * Domain nameserver hosts list
		 *
		 * @var array
		 */
		private $NameserverHosts = array();
		
		/**
		 * Domain flags
		 *
		 * @var array
		 */
		private $Flags = array();
		
		/**
		 * Managed DNS enabled for this domain
		 *
		 * @var bool
		 */
		public $IsManagedDNSEnabled = false;
		
		/**
		 * Incomplete order operation (Transfer or Register)
		 *
		 * @var string
		 */
		public $IncompleteOrderOperation = false;
		
		/**
		 * Outgoing transfer status for this domain. 
		 * @var bool
		 */
		public $OutgoingTransferStatus = false;
		
		/**
		 * Domain pending operations
		 *
		 * @var array
		 */
		private $PendingOperations = array();
		
		/**
		 * Domain registry specific configuration
		 *
		 * @var SimpleXMLElement
		 */
		private $ConfigXml;
		
		/**
		 * Domain Extra fields
		 *
		 * @var array
		 */
		public $ExtraFields;
		
		public function __construct ($config_xml)
		{
			$this->ConfigXml = $config_xml;
			$this->Period = 0;
		}
		
		/**
		 * Return domain section from module manifest
		 *
		 * @return SimpleXMLElement
		 */
		public function GetConfig ()
		{
			return $this->ConfigXml;
		}
		
		/**
		 * Return domain name with extension 
		 *
		 * @return unknown
		 */
		public function GetHostName()
		{
			return "{$this->Name}.{$this->Extension}";
		}
		
		/**
		 * Must return domain contact of $contact_type
		 * 
		 * @param string $contact_type 
		 * @return Contact If contact exists, NULL otherwise
		 */
		public function GetContact ($contact_type)
		{
			$list = $this->GetContactList();
			return $list[$contact_type];
		}
		
		/**
		 * Set domain contact
		 *
		 * @param Contact $contact
		 * @throws Exception on contact has no type
		 */
		public function SetContact (Contact $contact = null, $type)
		{
			if (!in_array($type, CONTACT_TYPE::GetKeys()))
				throw new Exception(sprintf(_("Invalid contact type '%s'"), $type));
						
			if ($contact instanceOf Contact)
			{
				$group_arr = $this->ConfigXml->xpath('contacts/contact[@type="'.$type.'"]/@group');
				
				if ((string)$group_arr[0] != $contact->GroupName)
					throw new Exception(sprintf(_("Contact of group '%s' can't be used as '%s'"), $contact->GroupName, $type));
				
				$this->Contacts[$type] = $contact;
			}
			else
				unset($this->Contacts[$type]);
		}
		
		/**
		 * Must return list of domain contacts
		 * 
		 * @return Contact[]
		 */
		public function GetContactList ()
		{
			return $this->Contacts;
		}
	
		/**
		 * Must return list of domain nameservers
		 *
		 * @return Nameserver[]
		 */
		public function GetNameserverList ()
		{
			return $this->Nameservers;
		}
		
		/**
		 * Set new list of domain nameservers
		 *
		 * @param Nameserver[] $nslist
		 * @throws Exception on argument is not an array of Nameserver objects
		 */
		public function SetNameserverList (array $nslist)
		{
			$buf = array();
			$buf_nshost = array();
			
			$nshost_list = array();
			
			foreach ($nslist as $ns)
			{
				if ($ns instanceof Nameserver)
				{
					$buf[] = $ns;
				}
				else
				{
					throw new Exception (_('Argument must be array of Nameserver objects'));
				}
					
				if ($ns instanceof NameserverHost)
				{
					$buf_nshost[] = $ns;
				}			
			}
			
			$this->Nameservers = $buf;
			$this->NameserverHosts = $buf_nshost;
		}
		
		/**
		 * Returns domain nameservers list wrapped into IChangelist
		 * 
		 * @return IChangelist
		 */
		public function GetNameserverChangelist ()
		{
			return new Changelist($this->GetNameserverList());
		}
	
		public function GetNameserverHostList ()
		{
			return $this->NameserverHosts;
		}
		
		/*
		public function SetNameserverHostList (array $nshostList)
		{
			$buf = array();
			foreach ($nshostList as $nshost)
				if ($nshost instanceof NameserverHost)
					$buf[] = $nshost;
				else
					throw new Exception (_('Argument must be array of NameserverHost objects'));
					
			$this->NameserverHosts = $nshostList;
		}
		*/
		
		/**
		 * Adds new flag to domain
		 *
		 * @param string $flag
		 */
		public function AddFlag ($flag)
		{
			if (!$this->HasFlag($flag))
				$this->Flags[] = $flag;
		}
		
		/**
		 * Removes flag from domain
		 *
		 * @param string $flag
		 */
		public function RemoveFlag ($flag)
		{
			if (false !== ($i = array_search($flag, $this->Flags)))
				unset($this->Flags[$i]);
		}
	
		/**
		 * Returns True when domain has flag $flag  set
		 *
		 * @param string $flag
		 * @return bool True if domain has such flag, false otherwise
		 */
		public function HasFlag ($flag)
		{
			return in_array($flag, $this->Flags);
		}
		
		/**
		 * Set new list of flags for this domain
		 *
		 * @param string[] $flag_list
		 */
		public function SetFlagList ($flag_list)
		{
			$this->Flags = array_map('strval', (array)$flag_list);
		}
		
		/**
		 * Returns list of domain flags
		 *
		 * @return string[]
		 */
		public function GetFlagList ()
		{
			return $this->Flags;
		}
		
		/**
		 * Returns flags list wrapped into Changelist
		 *
		 * @return Changelist
		 */
		public function GetFlagChangelist ()
		{
			return new Changelist($this->GetFlagList());
		}
		
		/**
		 * Returns true if domain is in active state
		 *
		 * @return bool
		 */
		public function IsActive ()
		{
			$active_statuses = array(
				DOMAIN_STATUS::DELEGATED,
				DOMAIN_STATUS::TRANSFER_REQUESTED,
				DOMAIN_STATUS::AWAITING_TRANSFER_AUTHORIZATION,
				DOMAIN_STATUS::REGISTRATION_PENDING
			);
			
			return in_array($this->Status, $active_statuses); 
		}
		
		/**
		 * Add pending operation type to domain
		 *
		 * @param string $operation_type
		 */
		public function AddPendingOperation($operation_type)
		{
			$this->PendingOperations[$operation_type] = 1;
		}
		
		/**
		 * Returns true when domain has such operation type in it's pending operation list
		 *
		 * @param string $operation_type
		 * @return bool
		 */
		public function HasPendingOperation($operation_type)
		{
			return isset($this->PendingOperations[$operation_type]);
		}
		
		/**
		 * Returns domain pending operation types list 
		 *
		 * @return array
		 */
		public function GetPendingOperationList()
		{
			return array_keys($this->PendingOperations);
		}
	
		public function SetExtraField($name, $value)
		{
			$this->ExtraFields[$name] = $value;
			$this->{$name} = $value;
		}
		
		public function SetExtraData ($extra)
		{
			foreach ($extra as $k => $v)
				$this->SetExtraField($k, $v);
		}
		
		
	    public function MarkAsExpired (Domain $Domain)
        {
        	$DB = Core::GetDBInstance();
        	
        	//Set to domain 'Expired' 
			Log::Log(sprintf("Mark domain '%s' as expired", $this->GetHostName()), E_USER_NOTICE);
			$DB->Execute("UPDATE domains SET status = ? WHERE id = ?", 
					array(DOMAIN_STATUS::EXPIRED, $this->ID));
					
			// Mark invoice as 'Failed'
			$DB->Execute("UPDATE invoices SET status = ? WHERE itemid = ? AND status = ? AND purpose = ?", 
					array(INVOICE_STATUS::FAILED, $this->ID, INVOICE_STATUS::PENDING, INVOICE_PURPOSE::DOMAIN_RENEW));
			$userinfo = $db->GetRow("SELECT * FROM users WHERE id = ?", array($this->UserID));
					
			// Send domain expired notice
			$args = array
			(
				"login"				=>	$userinfo["login"], 
			  	"domain_name"		=>	$this->Name, 
			  	"extension"			=>	$this->Extension, 
			  	"client"			=>  $userinfo
			);
			mailer_send("expired_notice.eml", $args, $userinfo["email"], $userinfo["name"]);
        }		
		
		/**
		 * Magic method. Property setter
		 *
		 * @param string $name
		 * @param string $value
		 * @throws Exception
		 * @ignore
		 */
		public function __set ($name, $value)
		{
			if ($name == 'ExpireDate')
			{
				$this->ExpireDate = $value;
				
				$exp_sec = $this->ExpireDate - time(); 
				if ($exp_sec > 0)
					$this->DaysBeforeExpiration = ceil($exp_sec/86400); 
			}
			elseif (isset($this->ExtraFields[$name]))
			{
				// Update value
				$this->SetExtraField($name, $value);
			}
			else
			{
				$extra_fields = $this->ConfigXml->xpath('registration/extra_fields/field'); 
				if ($extra_fields)
				{
					foreach ($extra_fields as $field)
					{
						settype($field, "array");			
						$field = $field["@attributes"];				
						
						$Val = String::Fly($value); 
						
						if ($name == $field['name'])
						{
							if ($field["required"] == 1 && $Val->Length() == 0)
								throw new Exception(sprintf(_("Domain field '%s' couldn't be empty"), ucfirst($field["description"])));
		
							if ($Val->Length() < $field["minlength"])
								throw new Exception(sprintf(_("Domain field '%s' must contain at least %d chars"), ucfirst($field["description"]), $field["minlength"]));
							
							if ($Val->Length() > $field["maxlength"] && $field["maxlength"] > 3)
								throw new Exception(sprintf(_("Domain field '%s' cannot be longer than %d chars"), ucfirst($field["description"]), $field["maxlength"]));
								
							if ($field["pattern"] && !preg_match("{$field['pattern']}msi", $value))
								throw new Exception(sprintf(_("Domain field '%s' is not valid", ucfirst($field["description"]))));
								
							$this->{$name} = $value;
						}
					}
				}
			}
		}
		
		/**
		 * Convert Domain to array
		 * @return array
		 */
		public function ToArray()
		{
			return array(
				'Name' 		=> $this->Name,
				'Extension'	=> $this->Extension,
				'CreateDate'=> $this->CreateDate,
				'IsLocked'	=> $this->IsLocked,
				'Period'	=> $this->Period,
				'AuthCode'	=> $this->AuthCode,
				'Protocol'	=> $this->Protocol,
				'UserID'	=> $this->UserID,
				'Contacts'  => $this->Contacts,
				'Namesevers'=> $this->Nameservers,
				'Flags'		=> $this->Flags
			);
		}
		
		/**
		 * Magic method. Called before object serialization
		 *
		 * @return unknown
		 * @ignore
		 */	
		public function __sleep()
	    {
	        return array('Name', 'Extension', 'CreateDate', 'IsLocked', 'Period', 
	        			'AuthCode', 'Protocol', 'UserID', 'Contacts', 'Nameservers',
	        			'Flags');
	    }
	}
?>