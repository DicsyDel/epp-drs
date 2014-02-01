<?php
	/**
	 * @name A global registry object
	 * @category   EPP-DRS
	 * @package Modules
	 * @subpackage RegistryModules
	 * @author Marat Komarov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	
	/**
	 * Global registry
	 *
	 * @package Modules
	 * @subpackage RegistryModules
	 */
	class Registry
	{

		private $RegModule;
		
		/**
		 * @var DBDomain
		 */
		private $DBDomain;
	
		/**
		 * @var DBContact
		 */
		private $DBContact;
		
		/**
		 * @var DBNameserverHost 
		 */
		private $DBHameserverHost;
		
		/**
		 * Database connection
		 *
		 * @var DB
		 */
		private $DB;
		
		private $ObserverImplemented;
		private $ServerPollableImplemented;
		private $ClientPollableImplemented;
		
		
		private $Extension; // ??? 
		
		/**
		 * Colection of observer objects
		 *
		 * @var IRegistryObserver[]
		 */
		private $Observers = array();
		
		private static $ClassObservers = array();
		
		/**
		 * Registry pending operation types 
		 */
		const OP_CREATE 	= 'CREATE';
		const OP_UPDATE 	= 'UPDATE';
		const OP_DELETE 	= 'DELETE';
		const OP_TRANSFER 	= 'TRANSFER';
		const OP_TRADE 		= 'TRADE';
		/**
		 * Registrar policy operation types
		 */
		const OP_CREATE_APPROVE = 'CREATE_APPROVE';
		const OP_UPDATE_APPROVE = 'UPDATE_APPROVE';
	
		/**
		 * Object types for pending operations
		 */
		const OBJ_DOMAIN 		= 'DOMAIN';
		const OBJ_CONTACT 		= 'CONTACT';
		const OBJ_NAMESERVERHOST= 'NAMESERVERHOST';
		
		function __construct (AbstractRegistryModule $reg_module)
		{
			$this->DB = Core::GetDBInstance();
			$this->DBDomain = DBDomain::GetInstance();
			$this->DBContact = DBContact::GetInstance();
			$this->DBHameserverHost = DBNameserverHost::GetInstance();
			
			$this->RegModule = $reg_module;
			$this->Manifest = $reg_module->Manifest;
			$this->Extension = $reg_module->Extension;
			//$reg_module->SetRegistryAccessible(new RegistryAccessible($this));
			$reg_module->SetRegistryAccessible($this);
	
			// Get interfaces implemented by module
			$impls = (array) class_implements(get_class($reg_module));
			$this->ObserverImplemented = in_array('IRegistryObserver', $impls);
			$this->ServerPollableImplemented = in_array('IRegistryModuleServerPollable', $impls);
			$this->ClientPollableImplemented = in_array('IRegistryModuleClientPollable', $impls);
		}
		
		/**
		 * @return RegistryManifest
		 */
		public function GetManifest ()
		{
			return $this->RegModule->Manifest;
		}
		
		/**
		 * @return DataForm
		 */
		public function GetConfig ()
		{
			return $this->RegModule->Config;
		}
		
		public function GetRegistrarID()
		{
			return $this->RegModule->GetRegistrarID();
		}
		
		public function GetModuleName ()
		{
			return $this->RegModule->ModuleName;
		}
		
		/**
		 * 
		 * @return GenericEPPRegistryModule
		 */
		public function GetModule ()
		{
			return $this->RegModule;
		}
		
		public static function AttachClassObserver (IRegistryObserver $observer, $phace = EVENT_HANDLER_PHACE::SYSTEM)
		{
			if (!array_key_exists($phace, self::$ClassObservers))
			{
				self::$ClassObservers[$phace] = array();
			}
			
			if (array_search($observer, self::$ClassObservers[$phace]) !== false)
				throw new Exception(_('Observer already attached to class <Registry>'));
				
			self::$ClassObservers[$phace][] = $observer;
		}
		
		
		public function AttachObserver (IRegistryObserver $observer)
		{
			if (array_search($observer, $this->Observers) !== false)
				throw new Exception(_('Observer already attached to registry module'));
				
			$this->Observers[] = $observer;
		}
		
		public function DetachObserver (IRegistryObserver $observer)
		{
			if (($i = array_search($observer, $this->Observers)) === false)
				throw new Exception(_('Observer not attached to registry module'));
			
			array_splice($this->Observers, $i, 1);
		}
		
		
		private function FireEvent ($event_name /* args1, args2 ... argN */)
		{
			try
			{
				$args = func_get_args();
				array_shift($args); // First argument is event name
				
				Log::Log(sprintf('Fire %s', $event_name), E_USER_NOTICE);
						
				// Notify class observers
				foreach (array(EVENT_HANDLER_PHACE::BEFORE_SYSTEM, EVENT_HANDLER_PHACE::SYSTEM) as $phace)
				{
					if (array_key_exists($phace, self::$ClassObservers))
					{
						foreach (self::$ClassObservers[$phace] as $observer)
						{
							Log::Log(sprintf("Execute %s:On%s", get_class($observer), $event_name), E_USER_NOTICE);
							call_user_func_array(array($observer, "On{$event_name}"), $args);
						}
					}
				}
				
				// Notify registry observers
				foreach ($this->Observers as $observer)
				{
					Log::Log(sprintf("Execute %s:On%s", get_class($observer), $event_name), E_USER_NOTICE);
					call_user_func_array(array($observer, "On{$event_name}"), $args);
				}
				
					
				if ($this->ObserverImplemented)
					// Notify user module
					try
					{
						call_user_func_array(array($this->RegModule, "On{$event_name}"), $args);
					}
					catch (Exception $e)
					{
						Log::Log(sprintf('On%s user handler failed. Error: %s', $event_name, $e->getMessage()), E_USER_WARNING);			
					}
					
				// Notify class observers
				$phace = EVENT_HANDLER_PHACE::AFTER_SYSTEM;
				if (array_key_exists($phace, self::$ClassObservers))
				{
					foreach (self::$ClassObservers[$phace] as $observer)
					{
						Log::Log(sprintf("Execute %s:On%s", get_class($observer), $event_name), E_USER_NOTICE);
						call_user_func_array(array($observer, "On{$event_name}"), $args);
					}
				}
			}
			catch(Exception $e)
			{
				Log::Log(sprintf("Registry::FireEvent thrown exception: %s, file: %s", $e->getMessage(), $e->getFile()), E_ERROR);
			}
				
			return;
		}
		
		/**
		 * Return True if hostname contains non-ascii multibyte characters
		 *
		 * @param string $host_name
		 * @return bool
		 */
		public function IsIDNHostName ($host_name)
		{
			return preg_match('/[\x00-\x1F\x7F-\xFF]+/', $host_name);
		}
		
		/**
		 * Encode a given UTF-8 string
		 *
		 * @param string $utf8_string (UTF-8 or UCS-4)
		 * @return string ACE string
		 */
		public function PunycodeEncode ($utf8_string)
		{
			return Punycode::Encode($utf8_string);
		}
		
		/**
		 * Encode a given ACE string
		 *
		 * @param string $ace_string
		 * @return string UTF-8 or UCS-4
		 */
		public function PunycodeDecode ($ace_string)
		{
			return Punycode::Decode($ace_string);
		}
		
		/**
		 * Factory method for new domain object
		 *
		 * @return Domain
		 */
		public function NewDomainInstance ()
		{
			$domain_config = $this->GetManifest()->GetDomainConfig();
			
			$domain = new Domain($domain_config);
			$domain->Extension = $this->Extension;
			return $domain;
		}
		
		/**
		 * Factory method for new contact object
		 * 
		 * @param string $contact_type
		 * @return Contact
		 */
		public function NewContactInstance ($contact_type)
		{
			if (!$contact_type)
				throw new Exception("\$contact_type required for Registry::NewContactInstance");
			if (!in_array($contact_type, CONTACT_TYPE::GetKeys()))
				throw new Exception(sprintf("Invalid contact type '%s'", $contact_type));
			
			$contact_config = $this->GetManifest()->GetContactConfig($contact_type);
			return $this->_NewContactInstance($contact_config);
		}
		
		/**
		 * Factory method for new contact object
		 *
		 * @param string $contact_group
		 * @return Contact
		 */
		public function NewContactInstanceByGroup ($contact_group)
		{
			$contact_config = $this->GetManifest()->GetContactConfigByGroup($contact_group);
			return $this->_NewContactInstance($contact_config);
		}
		
		private function _NewContactInstance ($contact_config)
		{
			$contact = new Contact($contact_config);
			
			$new_id_pattern = (string)$contact_config->attributes()->new_id;
			if (!$new_id_pattern)
				$new_id_pattern = '%8d';
			
			
			// Replace config variable (maybe) by it value		
			$new_id_pattern = preg_replace_callback('/\{\#([^\}]+)\}/', array($this, '_ReplaceConfigVariable'), $new_id_pattern);
			
			$contact->CLID = $this->DBContact->GenerateCLID($new_id_pattern);
			$contact->GroupName = (string)$contact_config->attributes()->group;
			$contact->ModuleName = $this->GetModuleName();
			
			// XXX Need to rewrite contact -> tld bindings
			if ((bool)$this->GetManifest()->GetRegistryOptions()->ability->section_shared_contacts)
			{
				$contact->SectionName = '';
				$contact->TargetIndex = 0;
			}
			else
			{
				$contact->SectionName = $this->GetManifest()->GetSectionName();
				$i = 0;
				foreach ($contact_config->targets->target as $target)
				{
					$tlds = explode(',', $target->attributes()->tlds);
					if (in_array($this->Extension, $tlds))
					{
						$contact->TargetIndex = $i;
						break;					
					}
					$i++;
				}
			}
			
			if ($contact->TargetIndex === null)
			{
				throw new Exception("Failed to map registry extension to contact target group");
			}
			

			return $contact;
		}
		
		private function _ReplaceConfigVariable ($matches)
		{
			return $this->RegModule->Config->GetFieldByName("{$matches[1]}")->Value;
		}
		
		private function ValidateModuleResponse ($resp, $classname)
		{
			if (!is_a($resp, $classname))
				throw new APIException(sprintf(_('Module returns invalid response. %s expected'), $classname));
		}  
		
		/**
	     * Check transfer availability for $domain_name Domain
	     *
	     * @param Domain $domain
	     * @return true
	     */
	    public function DomainCanBeTransferred(Domain $domain)
	    {
	    	$Resp = $this->RegModule->DomainCanBeTransferred($domain);
	    	$this->ValidateModuleResponse($Resp, 'DomainCanBeTransferredResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('DomainCanBeTransferred failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
	
			return $Resp->Result;
	    }	
		
	    /**
	     * Update domain auth code.
	     *
	     * @param Domain $domain
	     * @param string $authcode A list of changes in domain flags for the domain
	     * @return UpdateDomainAuthCodeResponse
	     */
	    public function UpdateDomainAuthCode(Domain $domain, $authCode)
	    {
	    	$this->FireEvent('BeforeUpdateDomain', $domain, $this->DBDomain->GetInitialState($domain));
	    	
	    	$Resp = $this->RegModule->UpdateDomainAuthCode($domain, $authCode);
	    	$this->ValidateModuleResponse($Resp, 'UpdateDomainAuthCodeResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('UpdateDomainAuthCode failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			
			if ($Resp->Result)
			{
				if ($Resp->Succeed())
				{
					$domain->AuthCode = $authCode;
					$this->DBDomain->Save($domain); // db operation
					$this->FireEvent('DomainOperation', $domain, self::OP_UPDATE);				
					$this->FireEvent('DomainUpdated', $domain);
				}
			}
			else
				throw new RegistryException(_('Registry was unable to update domain auth code'));
			
			return $domain;
	    } 
	    
	    /**
	     * Send Domain Trade request (Change owner)
	     *
	     * @param Domain $domain Domain must have contacts and nameservers 
	     * @param integer $period
	     * @param array $extra
	     * @return Domain
	     */
	    public function ChangeDomainOwner(Domain $domain, $period=null, $extra=array())
	    {
	    	$this->FireEvent('BeforeChangeDomainOwner', $domain, $this->DBDomain->GetInitialState($domain), $period);
	    	
	    	$Resp = $this->RegModule->ChangeDomainOwner($domain, $period, $extra);
	    	$this->ValidateModuleResponse($Resp, 'ChangeDomainOwnerResponse');    	
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('ChangeDomainOwner failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			if ($Resp->Result)
			{
				if ($Resp->Succeed())
				{
					$this->DBDomain->Save($domain);
					$this->FireEvent('DomainOperation', $domain, self::OP_TRADE);				
					$this->FireEvent('DomainOwnerChanged', $domain, $period);
				}
				else if ($Resp->Pending())
				{ 
					Log::Log("Add trade operation", E_USER_NOTICE);
					$this->AddPendingOperation($domain, self::OP_TRADE, $Resp->OperationId);
				}
			}
			else
				throw new RegistryException(_('Registry was unable to change domain owner for %s'), $domain->GetHostName());
	    	
			return $domain;
	    }    
	    
	    /**
	     * Lock Domain
	     *
	     * @param Domain $domain
	     * @param array $extra Some extra data
	     * @return bool
	     */
	    public function LockDomain(Domain $domain, $extra = array())
	    {
	    	$this->FireEvent('BeforeLockDomain', $domain);
	    	
	    	$Resp = $this->RegModule->LockDomain($domain, $extra);
	    	$this->ValidateModuleResponse($Resp, 'LockDomainResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('LockDomain failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
	    	
			if ($Resp->Result)
			{
				$domain->IsLocked = true;
				$this->DBDomain->Save($domain); 
				$this->FireEvent('DomainLocked', $domain);
			}
				
			return $Resp->Result;
	    }
	    
	    /**
	     * Unlock Domain
	     *
	     * @param Domain $domain
	     * @param array $extra Some extra data
	     * @return bool
	     */
	    public function UnlockDomain (Domain $domain, $extra = array())
	    {
	    	$this->FireEvent('BeforeUnlockDomain', $domain);    	
	    	
	    	$Resp = $this->RegModule->UnlockDomain($domain, $extra);
	    	$this->ValidateModuleResponse($Resp, 'UnlockDomainResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('UnlockDomain failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
	    	
			if ($Resp->Succeed() && $Resp->Result)
			{
				$domain->IsLocked = false;
				$this->DBDomain->Save($domain); 
				$this->FireEvent('DomainUnlocked', $domain);
			}
			
			return $Resp->Result;
	    }
	    
	    /**
	     * Update domain flags (options such as clientUpdateProhibited, clientDeleteProhibited)
	     *
	     * @param Domain $domain
	     * @param IChangelist $changes flags changes
	     * @version v1000
	     * @return Domain
	     */
	    public function UpdateDomainFlags(Domain $domain, IChangelist $changes)
	    {
	    	$this->FireEvent('BeforeUpdateDomainFlags', $domain, $changes);
	    	$this->FireEvent('BeforeUpdateDomain', $domain, $this->DBDomain->GetInitialState($domain));
	    	
	    	$Resp = $this->RegModule->UpdateDomainFlags($domain, $changes);
	    	$this->ValidateModuleResponse($Resp, 'UpdateDomainFlagsResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('UpdateDomainFlags failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			
			if ($Resp->Result)
			{
				if ($Resp->Succeed())
				{
					$domain->SetFlagList($changes->GetList());
					$this->DBDomain->Save($domain); // db operation
					$this->FireEvent('DomainOperation', $domain, self::OP_UPDATE);				
					$this->FireEvent('DomainUpdated', $domain);
				}
				else if ($Resp->Pending()) 
					$this->AddPendingOperation($domain, self::OP_UPDATE, $Resp->OperationId);
			}
			else
				throw new RegistryException(_('Registry was unable to update domain flags'));
			
			return $domain;
	    }    
	    
	    /**
		 * Create domain and return transaction status
		 *	 
		 * @access public
		 * @param Domain $domain
		 * @param int $period Domain registration period
		 * @param array $extra Extra data
		 * @version v1000
		 * @return Domain
		 */
		public function CreateDomain(Domain $domain, $period, $extra = array())
		{
			// Validate contacts
	    	$contact_list = $domain->GetContactList();
	    	foreach (CONTACT_TYPE::GetKeys() as $contact_type)
	    	{
	    		$config = $this->GetManifest()->GetContactConfig($contact_type);
	    		if ($config instanceOf SimpleXMLElement)
	    		{
	    			if ((int)$config->attributes()->required && (!$contact_list[$contact_type]))
	    				throw new Exception(sprintf(_('%s contact is required'), ucfirst($contact_type)));
	    		}
	    	}
	    	
	    	// 
	    	$nameserver_list = $domain->GetNameserverList();
	    	foreach ($nameserver_list as $ns)
	    	{
	    		try
	    		{
	    			try
	    			{
	    				$can_create = $this->NameserverCanBeCreated($ns);
	    			}
	    			catch (NotImplementedException $e)
	    			{
	    				$can_create = true;
	    			} 
	    			
	    			if ($can_create)
		    			$this->CreateNameserver($ns);
	    		}
	    		catch (ObjectExistsException $e) 
	    		{
	    			// Ignore errors
	    		}
				catch (NotImplementedException $e) 
	    		{
	    			// Ignore errors
	    		}
	    	}
	    	
	    		
	    	$this->FireEvent('BeforeCreateDomain', $domain);
			$Resp = $this->RegModule->CreateDomain($domain, $period, $extra);
			$this->ValidateModuleResponse($Resp, 'CreateDomainResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('CreateDomain failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
				
			$domain->Period = $period;
			$domain->AuthCode = $Resp->AuthCode;
			$domain->CreateDate = $Resp->CreateDate;
			$domain->ExpireDate = $Resp->ExpireDate;
			$domain->Protocol = $Resp->Protocol;
			$domain->Status = ($Resp->Status == REGISTRY_RESPONSE_STATUS::PENDING) ? 
				DOMAIN_STATUS::REGISTRATION_PENDING : DOMAIN_STATUS::DELEGATED;
	
			$domain->IncompleteOrderOperation = null;
				
			if ($domain->Status == DOMAIN_STATUS::DELEGATED)
			{
				$this->FireEvent('DomainOperation', $domain, self::OP_CREATE);
				$this->FireEvent('DomainCreated', $domain);
			}
			else if ($domain->Status == DOMAIN_STATUS::REGISTRATION_PENDING)
			{
				$this->AddPendingOperation($domain, self::OP_CREATE, $Resp->OperationId);
			}
	
			$this->DBDomain->Save($domain);		// db operation			
				
			return $domain;
		}
		
		/**
		 * Update domain information from registry
		 * 
		 * @access public
		 * @param Domain $domain 
		 * @return Domain
		 */	
		public function GetRemoteDomain(Domain $domain)
		{
			$Resp = $this->RegModule->GetRemoteDomain($domain);
			$this->ValidateModuleResponse($Resp, 'GetRemoteDomainResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('GetRemoteDomain failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			// Set domain properties
			$domain->CreateDate = $Resp->CreateDate;
			$this->SetDomainRemoteExpireDate($domain, $Resp->ExpireDate);
			$domain->AuthCode = $Resp->AuthCode;
			$domain->Protocol = $Resp->Protocol;
			$domain->RegistryStatus = $Resp->RegistryStatus;
			
			$domain->RemoteCLID = $Resp->CLID;
			$domain->RemoteCRID = $Resp->CRID;
	
			// Set domain contacts
			$contact_types_map = array
			(
				CONTACT_TYPE::ADMIN 		=> 'AdminContact',
				CONTACT_TYPE::BILLING 		=> 'BillingContact',
				CONTACT_TYPE::REGISTRANT	=> 'RegistrantContact',
				CONTACT_TYPE::TECH 			=> 'TechContact'
			);
			$contact_list = $domain->GetContactList();
	
			$loaded_contacts = array();
			foreach ($contact_types_map as $contact_type => $resp_property)
			{
				$Contact = null;
				$clid = $Resp->{$resp_property}; 				
				
				if ($clid)
				{
					if (!array_key_exists($clid, $loaded_contacts))
					{
						$was_loaded = false;
						if ($this->DBContact->FindByCLID($clid))
						{
							try
							{
								// Try to load contact
								$Contact = $this->DBContact->LoadByCLID($clid);
								$was_loaded = true;
							}
							catch (Exception $e) 
							{
								// Opps.. Destroy contact object
								$Contact = null;
								Log::Log(sprintf("Failed to load contact. CLID: %s. Error: %s", 
									$clid, $e->getMessage()), E_USER_ERROR);
							}
						}
						else
						{
							// Create contact object
							$Contact = $this->NewContactInstance($contact_type);
							$Contact->CLID = $clid;
						}
						
						try
						{
							// Try to sync contact with registry
							$Contact = $this->GetRemoteContact($Contact);
						}
						catch (Exception $e)
						{
							// Opps.. Destroy contact object
							Log::Log(sprintf("Failed to sync contact with registry. CLID: %s. Error: %s", 
								$clid, $e->getMessage()), E_USER_ERROR);
							if ($was_loaded)
							{
								$Contact = $this->DBContact->GetInitialState($Contact);
							}
							else
							{
								if ($e instanceof NotImplementedException ||
									($e instanceof RegistryException && $e->getCode() == RFC3730_RESULT_CODE::ERR_AUTHORIZE_ERROR))
								{
									Log::Log("Create contact with a fullname=CLID", E_USER_NOTICE);
									$Contact->FullName = $Contact->CLID;
								}
								else
								{
									$Contact = null;
								}
							}
						}
						
						$domain->SetContact($Contact, $contact_type);

						// If contact was loaded (db/registry) store it in contact cache
						if ($Contact)
						{
							$loaded_contacts[$clid] = $Contact;
						}
					}
					else
					{
						// Get contact from cache
						$domain->SetContact($loaded_contacts[$clid], $contact_type);
					}
				}
				else
				{
					$domain->SetContact(null, $contact_type);
				}
			}
			

			// Set nameservers
			$domain->SetNameserverList($Resp->GetNameserverList());
			
			// Set flags
			$domain->SetFlagList($Resp->GetFlagList());
			
			// Set extra domain data.
			$domain->SetExtraData($Resp->GetExtraData());
			
			if (isset($Resp->IsLocked))
			{
				$domain->IsLocked = $Resp->IsLocked; 
			}
			else if (in_array('clientTransferProhibited', $Resp->GetFlagList()))
			{
				// For EPP based LOCK means this flag presence
				$domain->IsLocked = true;
			}
			return $domain;
		}
		
		function SetDomainRemoteExpireDate (Domain $Domain, $expire_date)
		{
			// For auto renew registries we should check 
			// renewal invoice, and neglect registry expire date.
			$neglect = false;
			$config = $this->Manifest->GetRegistryOptions();
			if ((bool)(int)$config->ability->auto_renewal && $Domain->ExpireDate)
			{
				$days = $this->Manifest->GetSectionConfig()->domain->renewal->notifications->period;
				$period = (int)$days[0]; // Biggest period (60 days)
				$issueDate = date("Y-m-d", strtotime("-{$period} day", $expire_date));
				
				// Игнорируем дату экспирации из регистри если есть оплаченный инвойс, 
				// выписанный для этой дать экспирации 
				$neglect = (bool)$this->DB->GetRow(
					"SELECT id FROM invoices WHERE userid=? AND itemid=? AND purpose=? AND status=? AND dtcreated >= ?",
					array($Domain->UserID, $Domain->ID, INVOICE_PURPOSE::DOMAIN_RENEW, INVOICE_STATUS::PAID, $issueDate)
				);
			}
			if ($this->RegModule->ModuleName == "DotNL" && $Domain->ExpireDate)
			{
				$neglect = true;
			}
			
			if (!$neglect)
			{
				$Domain->ExpireDate = $expire_date;
			}
		}
		
		/**
		 * Change domain contact ID
		 * 
		 * @param Domain $domain Domain
		 * @param string $contactType 
		 * @param Contact $oldContact
		 * @param Contact $newContact
		 * @return Domain
		 */
		public function UpdateDomainContact(Domain $domain, $contactType, Contact $oldContact=null, Contact $newContact=null)
		{
	    	$this->FireEvent('BeforeUpdateDomainContact', $domain, $contactType, $oldContact, $newContact);
	    	$this->FireEvent('BeforeUpdateDomain', $domain, $this->DBDomain->GetInitialState($domain));
			
			$Resp = $this->RegModule->UpdateDomainContact($domain, $contactType, $oldContact, $newContact);
			$this->ValidateModuleResponse($Resp, 'UpdateDomainContactResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('UpdateDomainContact failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			if ($Resp->Result)
			{
				if ($Resp->Succeed())
				{
					$domain->SetContact($newContact, $contactType);
					$this->DBDomain->Save($domain); // db operation
					$this->FireEvent('DomainOperation', $domain, self::OP_UPDATE);
					$this->FireEvent('DomainUpdated', $domain);
				}
				else if ($Resp->Pending())
					$this->AddPendingOperation($domain, self::OP_UPDATE, $Resp->OperationId);
			}
			else
				throw new RegistryException(_('Registry was unable to update contact'));
			
			return $domain;
		}
		
		/**
		 * Update nameservers for domain
		 * @access public
		 * @param Domain $domain Domain
		 * @param IChangelist $changelist nameservers changelist 
		 * @return Domain
		 */
		public function UpdateDomainNameservers(Domain $domain, IChangelist $changes)
		{
			if (!$changes->HasChanges())
				throw new Exception(_("No changes in nameserver list"));		
	
	    	$this->FireEvent('BeforeUpdateDomainNameservers', $domain, $changes);
	    	$this->FireEvent('BeforeUpdateDomain', $domain, $this->DBDomain->GetInitialState($domain));
				
		    $added = $changes->GetAdded();
		    foreach ($added as $ns)
	    		try
	    		{
	    			if (($ns instanceof NameserverHost) == false)
	    				$this->CreateNameserver($ns);
	    		}
	    		catch (ObjectExistsException $e) 
	    		{
	    			// Ignore errors
	    		}
				catch (NotImplementedException $e) 
	    		{
	    			// Ignore errors
	    		} 	
	    	
			$Resp = $this->RegModule->UpdateDomainNameservers($domain, $changes);
			$this->ValidateModuleResponse($Resp, 'UpdateDomainNameserversResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('UpdateDomainNameservers failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			if ($Resp->Result)
			{
				
				if ($Resp->Succeed())
				{
					$domain->SetNameserverList($changes->GetList());
					
					$this->DBDomain->Save($domain); // db operation
					$this->FireEvent('DomainOperation', $domain, self::OP_UPDATE);				
					$this->FireEvent('DomainUpdated', $domain);
				}
				else if ($Resp->Pending())
					$this->AddPendingOperation($domain, self::OP_UPDATE, $Resp->OperationId);
				
			}
			else
				throw new RegistryException(_('Registry was unable to update nameservers'));
			
			return $domain;
		}
	
		/**
		 * Check domain availability
		 * @access public
		 * @param Domain $domain Domain
		 * @return DomainCanBeRegisteredResponse
		 */
		public function DomainCanBeRegistered(Domain $domain)
		{		
			$Resp = $this->RegModule->DomainCanBeRegistered($domain);	
			$this->ValidateModuleResponse($Resp, 'DomainCanBeRegisteredResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('DomainCanBeRegistered failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			return $Resp;
		}
			
		/**
		 * Delete domain name
		 *
		 * @param Domain $domain Domain name without TLD
		 * @param int $executeDate Valid timestamp for scheduled delete. Null for immediately delete
		 * @return bool
		 */
		public function DeleteDomain(Domain $domain, $executeDate=null)
		{
			$this->FireEvent('BeforeDeleteDomain', $domain);
			
			if ($domain->IsActive())
			{
				$force_delete = false;
				$Resp = null;
				try
				{
					$Resp = $this->RegModule->DeleteDomain($domain, $executeDate);
					$this->ValidateModuleResponse($Resp, 'DeleteDomainResponse');
				}
				catch(ObjectNotExistsException $e)
				{
					$force_delete = true;
					
				}
				
				if ($Resp && $Resp->IsFailed())
				{
					if ($Resp->Code == 2201) // EPP Authorization error
					{
						$force_delete = true;
					} 
					else
					{
						Log::Log(sprintf('DeleteDomain failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
						throw new RegistryException($Resp->ErrMsg, $Resp->Code);
					}
				}
				
				if ($force_delete || $Resp->Result)
				{
					if ($force_delete || $Resp->Succeed())
					{
						$this->DBDomain->Delete($domain); // db	operation		
						$this->FireEvent('DomainDeleted', $domain);
					}
					else if ($Resp && $Resp->Pending())
					{
						$domain->Status = DOMAIN_STATUS::PENDING_DELETE;
						$this->DBDomain->Save($domain);
						$this->AddPendingOperation($domain, self::OP_DELETE, $Resp->OperationId);
					}
				}
				else
					throw new RegistryException(sprintf(_('Registry was unable to delete domain %s'), $domain->GetHostName()));
			}
			else
			{
				$this->DBDomain->Delete($domain); // db	operation		
				$this->FireEvent('DomainDeleted', $domain);
			}
	
			return true;
		}
		
		/**
		 * Renew domain
		 *
		 * @param string $domain Domain name without TLD
		 * @param array $extradata Extra fields
		 * @return bool
		 */
		public function RenewDomain(Domain $domain, $extra=array())
		{
			$this->FireEvent('BeforeRenewDomain', $domain);
			
			$options = $this->GetManifest()->GetRegistryOptions();
			
			
			if (!$extra["period"])
			{
				$min_renew_period = $this->GetManifest()->GetDomainConfig()->renewal->min_period;
				$extra["period"] = max($min_renew_period, (int)$domain->Period);
			}
			$domain->Period = $extra["period"];
			
			
			$auto_renewal = (int)$options->ability->auto_renewal; 
			if (!$auto_renewal || $this->RegModule->ModuleName == "RRPProxy")
			{
				$Resp = $this->RegModule->RenewDomain($domain, $extra);
				$this->ValidateModuleResponse($Resp, 'RenewDomainResponse');
				
				if ($Resp->IsFailed())
				{
					Log::Log(sprintf('RenewDomain failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
					throw new RegistryException($Resp->ErrMsg, $Resp->Code);
				}
				
				$domain->ExpireDate = $Resp->ExpireDate;
				$domain->Status = DOMAIN_STATUS::DELEGATED;
				$domain->DeleteStatus = DOMAIN_DELETE_STATUS::NOT_SET;
				
				if ($Resp->Succeed())
				{
					$this->FireEvent('DomainRenewed', $domain);
				}
				
				$this->DBDomain->Save($domain);
			}
			else
			{
				$domain->ExpireDate = strtotime("+{$extra["period"]} year", $domain->ExpireDate);
				$domain->Status = DOMAIN_STATUS::DELEGATED;
				// Reset delete status (could be set by Renew cronjob) 
				$domain->DeleteStatus = DOMAIN_DELETE_STATUS::NOT_SET;
				 
				$this->FireEvent('DomainRenewed', $domain);
				$this->DBDomain->Save($domain);				
			}
			
			return true;
		}
		
		/**
		 * Request domain transfer
		 *
		 * @param string $domain Domain name without TLD
		 * @param array $extradata Extra fields
		 * @return TransferRequestResponse
		 */	
		public function TransferRequest(Domain $domain, $extra=array())
		{
			$this->FireEvent('BeforeTransferRequest', $domain);
			
			// В некоторых регистри при трансфере нужно указывать неймсервера.
			// В модуль они передаются через массив $extra и имеют специфичный синтаксис, 
			// позволяющий указывать глю рекорды (ex: ns.webta.net>332.123.65.33).
			// Парсинг из модулей вынесен сюда, чтобы не дублировать код.
			if ($extra["ns1"]) 
			{
				foreach (array("ns1", "ns2") as $k)
				{
					list($ns,$ip) = explode(">", $extra[$k], 2);
					$extra[$k] = $ip ? new NameserverHost($ns, $ip) : new Nameserver($ns);
				}
			}
			
			$Resp = $this->RegModule->TransferRequest($domain, $extra);
			$this->ValidateModuleResponse($Resp, 'TransferRequestResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('TransferRequest failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
	
			$domain->Status = DOMAIN_STATUS::AWAITING_TRANSFER_AUTHORIZATION;
			$domain->TransferDate = time();
			$domain->SetExtraField('TransferID', $Resp->TransferID);
			 
			$this->DBDomain->Save($domain);
			
			$this->AddPendingOperation($domain, self::OP_TRANSFER, $Resp->OperationId);		
			$this->FireEvent('DomainTransferRequested', $domain);
			
			return $Resp->Result;
		}	
		
		/**
		 * Send domain transfer approval
		 *
		 * @param string $domain Domain name without TLD
		 * @param array $extradata Extra fields
		 * @return bool
		 */
		public function TransferApprove(Domain $domain, $extra=array())
		{
			$Resp = $this->RegModule->TransferApprove($domain, $extra);
			$this->ValidateModuleResponse($Resp, 'TransferApproveResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('TransferApprove failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			$domain->Status = DOMAIN_STATUS::TRANSFERRED;
			$this->DBDomain->Save($domain);
			
			return $Resp->Result;
		}	
		
		/**
		 * Send domain transfer rejection
		 *
		 * @param string $domain Domain
		 * @param array $extradata Extra fields
		 * @return bool
		 */
		public function TransferReject(Domain $domain, $extra=array())
		{
	    	$Resp = $this->RegModule->TransferReject($domain, $extra);
	    	$this->ValidateModuleResponse($Resp, 'TransferRejectResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('TransferReject failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			return $Resp->Result;
		}	
		
		/**
		 * Check either nameserver needs to be registered 
		 * @access public
		 * @param Nameserver $ns
		 * @return bool
		 */
		public function NameserverCanBeCreated(Nameserver $ns)
		{
	    	$Resp = $this->RegModule->NameserverCanBeCreated($ns);
	    	$this->ValidateModuleResponse($Resp, 'NameserverCanBeCreatedResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('CreateNameserver failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			return $Resp->Result;
		}
		
		/**
		 * Create nameserver
		 * @access public
		 * @param Nameserver $ns
		 * @return Nameserver
		 */
		public function CreateNameserver (Nameserver $ns)
		{
			$Resp = $this->RegModule->CreateNameserver($ns);
			$this->ValidateModuleResponse($Resp, 'CreateNameserverResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('CreateNameserver failed. Registry error: %s', $Resp->ErrMsg), E_USER_NOTICE);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			return $ns;
		}	
		
		/**
		 * Create ns host
		 * 
		 * @param NameserverHost $ns
		 * @return NameserverHost
		 */
		public function CreateNameserverHost (NameserverHost $ns)
		{
			$this->FireEvent('BeforeCreateNameserverHost', $ns);
			
	    	$Resp = $this->RegModule->CreateNameserverHost($ns);
	    	$this->ValidateModuleResponse($Resp, 'CreateNameserverHostResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('CreateNameserverHost failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			if ($Resp->Succeed())
				$this->FireEvent('NameserverHostCreated', $ns);
			else if ($Resp->Pending())
				$this->AddPendingOperation($ns, self::OP_CREATE, $Resp->OperationId);
				
			return $ns;
		}
		
		/**
		 * Update nameserver host. update IP
		 * @access public
		 * @param NameserverHost $ns
		 * @return NameserverHost
		 */
		public function UpdateNameserverHost(NameserverHost $ns)
		{
			$this->FireEvent('BeforeUpdateNameserverHost', $ns, $this->DBHameserverHost->GetInitialState($ns));	
			
	    	$Resp = $this->RegModule->UpdateNameserverHost($ns);
			$this->ValidateModuleResponse($Resp, 'UpdateNameserverHostResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('UpdateNameserverHost failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			if ($Resp->Succeed())
				$this->FireEvent('NameserverHostUpdated', $ns);
			else if ($Resp->Pending())
				$this->AddPendingOperation($ns, self::OP_UPDATE, $Resp->OperationId);
				
			return $ns;
		}	
		
		/**
		 * Delete nameserver host
		 * @access public
		 * @param NameserverHost $ns
		 * @return bool 
		 */
		public function DeleteNameserverHost(NameserverHost $ns)
		{
			$this->FireEvent('BeforeDeleteNameserverHost', $ns);
			
			try
			{
		    	$Resp = $this->RegModule->DeleteNameserverHost($ns);
		    	$this->ValidateModuleResponse($Resp, 'DeleteNameserverHostResponse');
			}
			catch (ObjectNotExistsException $e)
			{
				// Sync our database with registry 
				$Resp = new DeleteNameserverHostResponse(REGISTRY_RESPONSE_STATUS::SUCCESS, null, 1000);
				$Resp->Result = true;
			}
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('DeleteNameserverHost failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			if ($Resp->Succeed())
				$this->FireEvent('NameserverHostDeleted', $ns);
			else if ($Resp->Pending())
				$this->AddPendingOperation($ns, self::OP_DELETE, $Resp->OperationId);
				
			return true;
		}	
		
		/**
		 * Check contact availability
		 * @access public
		 * @param Contact $contact
		 * @return bool 
		 */
		public function ContactCanBeCreated(Contact $contact)
		{
	    	$Resp = $this->RegModule->ContactCanBeCreated($contact);
	    	$this->ValidateModuleResponse($Resp, 'ContactCanBeCreatedResponse');
	    	
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('ContactCanBeCreated failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
	
			return $Resp->Result;
		}
		
		
		/**
		 * Create contact
		 * @access public
		 * @param Contact $contact
		 * @return Contact
		 */
		public function CreateContact(Contact $contact, $extra=array())
		{
			$this->FireEvent('BeforeCreateContact', $contact);
			
			$Resp = $this->RegModule->CreateContact($contact, $extra);
			$this->ValidateModuleResponse($Resp, 'CreateContactResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('CreateContact failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
	
			$contact->CLID = $Resp->CLID;
			$contact->AuthCode = $Resp->AuthCode;
			
			// RRPProxy registry in case when it find contact with the same data in it's DB,
			// returns clid of existed contact instead of creating new
			if ($id = $this->DBContact->FindByCLID($contact->CLID))
			{
				$contact = $this->DBContact->LoadByCLID($contact->CLID);
			}
			else
			{
				$this->DBContact->Save($contact);
			}
							
			
			if ($Resp->Succeed())
				$this->FireEvent('ContactCreated', $contact);			
			else if ($Resp->Pending())
				$this->AddPendingOperation($contact, self::OP_CREATE, $Resp->OperationId); 
			
			return $contact;
		}
		
		
		/**
		 * Get contact info by ID
		 * @access public
		 * @param Contact $contact
		 * @return Contact
		 */
		public function GetRemoteContact(Contact $contact)
		{
			$Resp = $this->RegModule->GetRemoteContact($contact);
			$this->ValidateModuleResponse($Resp, 'GetRemoteContactResponse');
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('GetRemoteContact failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
	

			if ($Resp->AuthCode) {
				$contact->AuthCode = $Resp->AuthCode;
			}

			// Set discloses
			$contact->SetDiscloseList($Resp->GetDiscloseList());
			
			$ref = new ReflectionObject($Resp);
			$props = $ref->getProperties();
			
			$contact_data = array();
			foreach ($props as $property)
				if ($property->isPublic())
					$contact_data[$property->getName()] = $property->getValue($Resp);

			try
			{
				$contact->SetFieldList($contact_data);
			}
			catch (ErrorList $e)
			{
				$contact->SetFieldList($contact_data, false);
				Log::Log(sprintf("Strict data validation failed for contact %s. Marking this contact as non-strictly validated.", $contact->CLID), E_USER_WARNING);
				Log::Log(join('; ', $e->GetAllMessages()), E_USER_WARNING);
				//throw new Exception(sprintf(_('Set contact fields failed due to multiple errors: %s'), join('; ', $e->GetAllMessages())));
			}
				
			
			return $contact;
		}	
		
		
		/**
		 * Update contact
		 * @access public
		 * @param Contact $contact
		 * @return UpdateContactResponse
		 */
		public function UpdateContact(Contact $contact)
		{
			$this->FireEvent('BeforeUpdateContact', $contact, $this->DBContact->GetInitialState($contact));
			
			$Resp = $this->RegModule->UpdateContact($contact);
			$this->ValidateModuleResponse($Resp, 'UpdateContactResponse');		
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('UpdateContact failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			if ($Resp->Succeed())
			{
				$this->DBContact->Save($contact);
				$this->FireEvent('ContactUpdated', $contact);
			}
			else if ($Resp->Pending())
				$this->AddPendingOperation($contact, self::OP_UPDATE, $Resp->OperationId);
				
			return $contact;
		}
		
		/**
		 * Delete contact
		 *
		 * @param Contact $contact
		 * @param array $extra Extra fields
		 * @return bool
		 */
		public function DeleteContact(Contact $contact, $extra = array())
		{
			$this->FireEvent('BeforeDeleteContact', $contact);
			
			try
			{
				$Resp = $this->RegModule->DeleteContact($contact, $extra);
				$this->ValidateModuleResponse($Resp, 'DeleteContactResponse');
			}
			catch (ProhibitedTransformException $e)
			{
				$linked_domain_cnt = $this->DB->GetOne("
					SELECT COUNT(*) FROM domains 
					WHERE c_registrant = ? OR c_admin = ? OR c_billing = ? OR c_tech = ?",
					array($contact->CLID, $contact->CLID, $contact->CLID, $contact->CLID)
				);
				if (!$linked_domain_cnt)
				{
					$this->DBContact->Delete($contact);
					$this->FireEvent('ContactDeleted', $contact);
					return true;				
				}
				throw new Exception(_('Contact couldn\'t be deleted due to linked domains'));
			}	
			
			if ($Resp->IsFailed())
			{
				Log::Log(sprintf('DeleteContact failed. Registry error: %s', $Resp->ErrMsg), E_USER_ERROR);
				throw new RegistryException($Resp->ErrMsg, $Resp->Code);
			}
			
			if ($Resp->Succeed())
			{
				$this->DBContact->Delete($contact);
				$this->FireEvent('ContactDeleted', $contact);
			}
			else if ($Resp->Pending())
				$this->AddPendingOperation($contact, self::OP_DELETE, $Resp->OperationId);
			
			return true;
		}	
		
		/**
		 * Add pending operation for registry
		 *
		 * @param stdClass $object Domain|Contact|NameserverHost
		 * @param string $op_type self::OP_*
		 */
		public function AddPendingOperation ($object, $op_type, $registry_op_id = null)
		{
			if ($object instanceof Domain)
			{
				$object_type = self::OBJ_DOMAIN;
				try
				{
					$old_object = $this->DBDomain->GetInitialState($object);
				}
				catch (Exception $e)
				{
					$old_object = null;
				}
			}
			else if ($object instanceof Contact)
			{
				$object_type = self::OBJ_CONTACT;
				try
				{
					$old_object = $this->DBContact->GetInitialState($object);	
				}
				catch (Exception $e)
				{
					$old_object = null;					
				}
			}
			else
				throw new Exception(_('Invalid argument $object'));
		
			$this->DB->Execute
			(
				'INSERT INTO pending_operations SET registry_opid = ?, objectid = ?, dtbegin=NOW(), 
					operation=?, objecttype=?, object_before=?, object_after=?',
				array($registry_op_id, $object->ID, $op_type, $object_type, serialize($old_object), serialize($object))
			);
	
			if  ($object instanceof Domain || $object instanceof Contact)
				$object->AddPendingOperation($op_type);
		}
		
		/**
		 * Remove pending operation
		 *
		 * @param int $op_id
		 */
		public function RemovePendingOperation ($op_id)
		{
			$this->DB->Execute('DELETE FROM pending_operations WHERE id = ?', array($op_id));
		}
	
		private static $RespOperationMap = array
		(
			'PollCreateDomainResponse' 		=> array(self::OBJ_DOMAIN, self::OP_CREATE),
			'PollTransferResponse' 			=> array(self::OBJ_DOMAIN, self::OP_TRANSFER),
			'PollChangeDomainOwnerResponse'	=> array(self::OBJ_DOMAIN, self::OP_TRADE),
			'PollUpdateDomainResponse'		=> array(self::OBJ_DOMAIN, self::OP_UPDATE),
			'PollDeleteDomainResponse'		=> array(self::OBJ_DOMAIN, self::OP_DELETE),
			'PollUpdateContactResponse'		=> array(self::OBJ_CONTACT, self::OP_UPDATE),
			'PollDeleteContactResponse'		=> array(self::OBJ_CONTACT, self::OP_DELETE),
			'PollDeleteNamserverHostResponse'=> array(self::OBJ_NAMESERVERHOST, self::OP_DELETE)
		);
		
		private function RemovePendingOperationForResp ($resp)
		{
			// This situation occurs when registry module implements server poll
			// We have response from server and don't know about operation.
			// So try to find it and remove					
			
			$opinfo = self::$RespOperationMap[get_class($resp)];
			if (!$opinfo)
				return;
	
			list($object_type, $op_type) = $opinfo;
				
			// Try to find operation in database
			if ($object_type == self::OBJ_DOMAIN)
			{
				$parsed_host = FQDN::Parse($resp->HostName);
				$object_id = $this->DBDomain->FindByName($parsed_host[0], $parsed_host[1]);
				if (!$object_id)
					return;
			}
			else if ($object_type == self::OBJ_CONTACT)
			{
				$object_id = $this->DBContact->FindByCLID($resp->CLID);
				if (!$object_id)
					return;
			}
			else
				return;
			
			$this->DB->Execute(
				'DELETE FROM pending_operations WHERE objectid = ? AND operation = ? AND objecttype = ?',
				array($object_id, $op_type, $object_type)
			);
		}
		
		/**
		 * Fetch all pending operations
		 *
		 * @return PendingOperation[]
		 */
		public function GetPendingOperationList ($object_type=null, $object_id=null)
		{
			// Domain operations
			if ($object_type && $object_id)
			{
				// XXX
				$arr = $this->DB->GetAll("
					SELECT p.* FROM pending_operations as p 
					INNER JOIN domains as d ON (p.objectid = d.id AND p.objecttype = ?) 
					WHERE d.TLD = ? AND p.objecttype = ? AND p.objectid = ?
					
					UNION
					
					SELECT p.* FROM pending_operations as p
					INNER JOIN contacts AS c ON (p.objectid = c.id AND p.objecttype = ?)
					WHERE (c.TLD = ? OR c.module_name = ?) AND p.objecttype = ? AND p.objectid = ?
					",
					array(
						self::OBJ_DOMAIN, 
						$this->RegModule->Extension,
						$object_type,
						$object_id,
						
						self::OBJ_CONTACT,
						$this->RegModule->Extension,
						$this->RegModule->ModuleName,
						$object_type,
						$object_id
					)
				);
			}
			else
			{
				$arr = $this->DB->GetAll("
					SELECT p.* FROM pending_operations as p 
					INNER JOIN domains as d ON (p.objectid = d.id AND p.objecttype = ?) 
					WHERE d.TLD = ?
					
					UNION
					
					SELECT p.* FROM pending_operations as p
					INNER JOIN contacts AS c ON (p.objectid = c.id AND p.objecttype = ?)
					WHERE (c.TLD = ? OR c.module_name = ?)
					",
					array(
						self::OBJ_DOMAIN, 
						$this->RegModule->Extension,
						self::OBJ_CONTACT,
						$this->RegModule->Extension,
						$this->RegModule->ModuleName
					)
				);
			}
			
			
			$ret = array();
			foreach ($arr as $row)
			{
				try
				{
					$op = new PendingOperation();
					$op->ID = $row['id'];
					$op->Type = $row['operation'];
					$op->InitDate = strtotime($row['dtbegin']);
					$op->RegistryOpId = $row['registry_opid'];
					$op->ObjectBefore = unserialize($row["object_before"]);
					$op->ObjectAfter = unserialize($row["object_after"]);
					
					if ($row['objecttype'] == self::OBJ_DOMAIN)
						$op->Object = $this->DBDomain->Load($row['objectid']);
					else if ($row['objecttype'] == self::OBJ_CONTACT)
						$op->Object = $this->DBContact->Load($row['objectid']);
					else
						continue;
						
					$ret[] = $op;
				}
				catch (Exception $e)
				{
					Log::Log('Fetch pending operation object failed: ' . $e->getMessage(), E_USER_ERROR);
				}
			}
			
			return $ret;
		}
		
		/**
		 * Map between domain pending operation type and registry poll method
		 * 
		 * private const
		 */
		private static $DomainOperationPollMethodMap = array(
			self::OP_CREATE 	=> 'PollCreateDomain',
			self::OP_TRADE		=> 'PollChangeDomainOwner',
			self::OP_TRANSFER	=> 'PollTransfer',
			self::OP_UPDATE		=> 'PollUpdateDomain',
			self::OP_DELETE		=> 'PollDeleteDomain' 
		);
		
		/**
		 * Map between contact pending operation type and registry poll method
		 *
		 * private const
		 */
		private static $ContactPollMethodMap = array(
			self::OP_DELETE => 'PollDeleteContact'	
		);
		
		public function DispatchPendingOperations ()
		{
			Log::Log('Registry::DispatchPendingOperations start', E_USER_NOTICE);
			
			if ($this->ServerPollableImplemented)
			{
				while ($Resp = $this->RegModule->ReadMessage())
				{
					if (CONFIG::$MAIL_POLL_MESSAGES)
					{
						if ($Resp->RawResponse)
						{
							try 
							{
								mailer_send("poll_message_notify.eml", 
									array(
										"message" => $Resp->RawResponse->asXML(),
										"module_name" => $this->GetModuleName()
									),
									CONFIG::$EMAIL_ADMIN, 
									CONFIG::$EMAIL_ADMINNAME
								);
							}
							catch (Exception $e)
							{
								Log::Log("Cannot send poll message recevied notification. ".$e->getMessage(), E_USER_ERROR);
							} 
						}
						else
						{
							Log::Log("Poll message has no raw response. Skip notification", E_USER_NOTICE);
						}
					}
					
					try
					{
						$this->DispatchPendingOperationResponse($Resp);
						$this->RemovePendingOperationForResp($Resp);
					}
					catch (Exception $e)
					{
						Log::Log(sprintf('Pending operation dispatching failed. %s', $e->getMessage()), E_USER_ERROR);
					}
					
					$this->RegModule->AcknowledgeMessage($Resp);
				}
			}
			else if ($this->ClientPollableImplemented)
			{
				$ops = $this->GetPendingOperationList();
				Log::Log(sprintf('%s: %d pending operations to proceed', $this->Extension, count($ops)), E_USER_NOTICE);
				
				foreach ($ops as $op)
				{
					if (time() - $op->InitDate < 60)
					{
						// Give 60 seconds to remote registry proceed her transactions
						Log::Log(sprintf('Too quick to poll for #%s operation', $op->ID), E_USER_NOTICE);
						continue;
					}
					
					$Resp = null;
					
					// Perform client side poll
					try
					{
						if ($op->Object instanceof Domain)
						{
							Log::Log(sprintf('Domain %s pending operation. hostname: %s', $op->Type, $op->Object->GetHostName()), E_USER_NOTICE);
							
							$method = self::$DomainOperationPollMethodMap[$op->Type];
							if ($method)
								$Resp = call_user_func_array(array($this->RegModule, $method), array($op->Object)); 
							else
								throw new Exception(sprintf(_('No poll method for domain operation %s'), $op->Type));
						}
						else if ($po->Object instanceof Contact)
						{
							Log::Log(sprintf('Contact %s pending operation. CLID: %s', $op->Type, $op->Object->CLID), E_USER_NOTICE);
							
							$method = self::$ContactPollMethodMap[$op->Type];
							if ($method)
								$Resp = call_user_func_array(array($this->RegModule, $method), array($op->Object)); 
							else
								throw new Exception(sprintf(_('No poll method for contact operation %s'), $op->Type));
						}
						else
						{
							throw new Exception(sprintf(_('Unsupported object in operation')));
						}
					} 
					catch (Exception $e)
					{
						Log::Log(sprintf('Client poll failed. %s', $e->getMessage()), E_USER_ERROR);
						continue;
					}
					
					
					// Process response
					if ($Resp && !$Resp->Pending()) // Skip pending responses.
					{
						try
						{
							// Dispatch
							$proceed = $this->DispatchPendingOperationResponse($Resp);
							if ($proceed === true)
								$this->RemovePendingOperation($op->ID);
						}
						catch (Exception $e)
						{
							Log::Log(sprintf('Pending operation dispatching failed. %s', $e->getMessage()), E_USER_ERROR);
						}
					}
				}
			}
		}
	
		function DispatchPendingOperationResponse (PendingOperationResponse $resp)
		{
			if (get_class($resp) == 'PendingOperationResponse')
				return true;
			
			// Map response object into dispatcher method
			$method = 'Dispatch' . preg_replace('/Response$/', '', get_class($resp));
			if (method_exists($this, $method))
			{
				Log::Log(sprintf('Call %s', $method));
				return call_user_func_array(array($this, $method), array($resp));
			}
			else
				throw new Exception(sprintf(_('Registry have no dispatcher for %s'), get_class($resp)));
		}
		
		function DispatchPollOutgoingTransfer (PollOutgoingTransferResponse $resp)
		{
			list($name, $extension) = FQDN::Parse($resp->HostName);
			$domain = $this->DBDomain->LoadByName($name, $extension, $this->GetManifest());
			
			if ($resp->TransferStatus == OUTGOING_TRANSFER_STATUS::REQUESTED)
			{
				$domain->OutgoingTransferStatus = OUTGOING_TRANSFER_STATUS::REQUESTED;
								
				$this->FireEvent("DomainOutgoingTransferRequested", $domain);
				
				$this->DBDomain->Save($domain);
				return true;
			}
			else if ($resp->TransferStatus == OUTGOING_TRANSFER_STATUS::AWAY)
			{
				$this->FireEvent("DomainTransferredAway", $domain);
				
				$r = new PollDeleteDomainResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
				$r->HostName = $resp->HostName;
				$r->MsgID = $resp->MsgID;
				$r->Result = true;
				$r->FailReason = $resp->FailReason;
				$r->RawResponse = $resp->RawResponse;				
				
				$this->DispatchPollDeleteDomain($r);				
				return true;
			}
		}
		
		function DispatchPollTransfer (PollTransferResponse $resp)
		{
			if ($resp->IsFailed())
			{
				Log::Log(sprintf('DispatchPollTransfer failed. Registry response: %s', $resp->ErrMsg), E_USER_ERROR);
				throw new Exception($resp->ErrMsg, $resp->Code);
			}
			
			list($name, $extension) = FQDN::Parse($resp->HostName);
			$domain = $this->DBDomain->LoadByName($name, $extension, $this->GetManifest());
			
			if ($resp->TransferStatus == TRANSFER_STATUS::APPROVED)
			{
				$domain = $this->GetRemoteDomain($domain);
				$domain->Status = DOMAIN_STATUS::DELEGATED;

				// Update domain contacts
				$contact_types = array(
					CONTACT_TYPE::REGISTRANT,
					CONTACT_TYPE::ADMIN,
					CONTACT_TYPE::BILLING,
					CONTACT_TYPE::TECH
				);
				
				$SavedDomain = $this->DBDomain->GetInitialState($domain);
				
				// XXX Hack for RRPProxy.
				// 
				// 1) RRPProxy domain comes after transfer without contacts.
				// All 4 contacts must be assigned to domain in a atomic operation
				// EPP-DRS module API doesn't support this
				// 
				// 2) RRPPRoxy domains (.BE, .EU) comes with unoperation contacts (be.tr5544 like)
				// that couldn't be updated in a normal order. To set them, first TradeDomain must be initiated.
				// After that admin, tech, billing contacts could be updated with ModifyDomain command.
				if ($this->GetModuleName() == "RRPProxy")
				{
					$this->RegModule->UpdateContactsAfterTransfer($domain, $SavedDomain);
				} 
				else
				{
					// Default behaviour
					Log::Log("Set contacts after transfer (Default behaviour)", E_USER_NOTICE);
					foreach ($contact_types as $type)
					{
						$SavedContact =  $SavedDomain->GetContact($type);
						$Contact = $domain->GetContact($type);
						Log::Log("{$type} '{$SavedContact->CLID}' is saved, '{$Contact->CLID}' is comming", E_USER_NOTICE);
						
						if (($SavedContact && !$Contact) || 
							(!$SavedContact && $Contact) || 
							($Contact->CLID != $SavedContact->CLID)) 
						{
							// XXX Hack for EPPGR
							if ($Contact->CLID && $SavedDomain->{'x-gr-trn-'.$type} == $Contact->CLID)
							{
								// GR. registrant contact arrived									
								continue;
							}
							
							try 
							{
								Log::Log("Change {$type} contact from {$Contact->CLID} to {$SavedContact->CLID}", E_USER_NOTICE);
								$Resp = $this->RegModule->UpdateDomainContact($domain, $type, $Contact, $SavedContact);
								$this->ValidateModuleResponse($Resp, 'UpdateDomainContactResponse');
								if ($Resp->Result)
								{
									$domain->SetContact($SavedContact, $type);
								}
								else 
								{
									throw new Exception("Module return fault. ".$Resp->ErrMsg);
								}
							} 
							catch (Exception $e)
							{
								Log::Log(sprintf(
									"Failed to update '%s' contact for transferred domain '%s'. Reason: %s", 
									$type, $domain->GetHostName(), $e->getMessage()
								), E_USER_ERROR);
							} 
						}
					}
				} 
				
				
				
				$this->FireEvent('DomainOperation', $domain, self::OP_TRANSFER);
				$this->FireEvent('DomainTransferApproved', $domain);
				
				$this->HandleImpendingExpiration($domain);
				
				$this->DBDomain->Save($domain);
				return true;
			}
			else if ($resp->TransferStatus == TRANSFER_STATUS::DECLINED)
			{
				$domain->Status = DOMAIN_STATUS::TRANSFER_FAILED;
				$this->FireEvent('DomainOperation', $domain, self::OP_TRANSFER, true, $resp->FailReason);
				$this->FireEvent('DomainTransferDeclined', $domain);			
				$this->DBDomain->Save($domain);			
				return true; 
			}
			else if ($resp->TransferStatus == TRANSFER_STATUS::PENDING || $resp->Pending())
			{
				if ($domain->TransferDate !== null)
				{
					// If transfer request was sent
					
					$config = $this->GetManifest()->GetSectionConfig();
					$timeout = (int)$config->domain->transfer->timeout;
					if ($timeout)
					{
						// Check for transfer timeout. If time exceed - transfer failed 
						if (strtotime("+{$timeout} day", $domain->TransferDate) <= time())
						{
							$timeExpired = true;
							$resp->FailReason = _('We gave up while waiting for transfer authorization from domain owner');
						}
					}
				}
			}
			if ($resp->TransferStatus == TRANSFER_STATUS::FAILED || $timeExpired)
			{
				$domain->Status = DOMAIN_STATUS::TRANSFER_FAILED;
				$this->FireEvent('DomainOperation', $domain, self::OP_TRANSFER, true, $resp->FailReason);
				$this->FireEvent('DomainTransferFailed', $domain);
				$this->DBDomain->Save($domain);
				return true;
			}
		}
		
		public function HandleImpendingExpiration (Domain $domain)
		{
			// http://bugzilla.webta.net/show_bug.cgi?id=210
			// Prevent domain expiration
			$Config = $this->GetManifest()->GetSectionConfig();
			$days = $Config->domain->xpath("//renewal/notifications/period");
			$last_notification_period = (int)end($days);
			
			$last_renewal_date = $domain->RenewalDate ? $domain->RenewalDate : $domain->ExpireDate;
			$days_before_expire = (int)ceil(($last_renewal_date - time())/86400);
			if ($days_before_expire <= $last_notification_period) {
				if ($days_before_expire < 0) {
					// Today will expire... OMG!
					$days_before_expire = 0;
				}
				
				// Set renew period
				$period = (int)$Config->domain->renewal->min_period;
				$domain->Period = $period;
				
				// Copypasta from class.RenewProcess.php
				// Issue invoice for renew and send notification to client.
				$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_RENEW, $domain, $domain->UserID);
				$Invoice->Description = sprintf(_("%s domain name renewal for %s years"), $domain->GetHostName(), $period);
				$Invoice->Save();
				
				if ($Invoice->Status == INVOICE_STATUS::PENDING)
				{
					$userinfo = $this->DB->GetRow("SELECT * FROM users WHERE id=?", array($domain->UserID));

					$args = array(
						"domain_name"	=> $domain->Name, 
						"extension"		=> $domain->Extension,
						"invoice"		=> $Invoice,
						"expDays"		=> $days_before_expire,
						"client"		=> $userinfo,
						"renewal_date"  => $domain->RenewalDate
					);
					mailer_send("renew_notice.eml", $args, $userinfo["email"], $userinfo["name"]);

					Application::FireEvent('DomainAboutToExpire', $domain, $days_before_expire);
				}
			}
		}
		
		function DispatchPollChangeDomainOwner (PollChangeDomainOwnerResponse $resp)
		{
			if ($resp->IsFailed())
			{
				Log::Log(sprintf('DispatchPollChangeDomainOwner failed. Registry response: %s', $resp->ErrMsg), E_USER_ERROR);
				throw new Exception($resp->ErrMsg, $resp->Code);
			}
	
			list($name, $extension) = FQDN::Parse($resp->HostName);
			$domain = $this->DBDomain->LoadByName($name, $extension, $this->GetManifest());
	
			if ($resp->Succeed())
			{
				if ($resp->Result)
				{
					$domain = $this->GetRemoteDomain($domain);
					$domain->Status = DOMAIN_STATUS::DELEGATED;
					
					$this->FireEvent('DomainOperation', $domain, self::OP_TRADE);
					$this->FireEvent('DomainOwnerChanged', $domain, $resp->Period);
					
					$this->DBDomain->Save($domain);				
				}
				else
				{
					$domain->Status = DOMAIN_STATUS::REJECTED;
					
					$this->FireEvent('DomainOperation', $domain, self::OP_TRADE, true, $resp->FailReason);
					
					$this->DBDomain->Save($domain);								
				}
				
				return true;			
			}
			
		}
		
		function DispatchPollCreateDomain (PollCreateDomainResponse $resp)
		{
			if ($resp->IsFailed())
			{
				Log::Log(sprintf('DispatchPollCreateDomain failed. Registry response: %s', $resp->ErrMsg), E_USER_ERROR);
				throw new Exception($resp->ErrMsg, $resp->Code);
			}
			
			if ($resp->Succeed())
			{
				list($name, $extension) = FQDN::Parse($resp->HostName);
				$domain = $this->DBDomain->LoadByName($name, $extension, $this->GetManifest());
				
				if ($resp->Result)
				{
					$domain = $this->GetRemoteDomain($domain);
					$domain->Status = DOMAIN_STATUS::DELEGATED;
					if ($resp->ExpireDate !== null)
					{
						$domain->ExpireDate = $resp->ExpireDate;
					}
					
					$this->FireEvent('DomainOperation', $domain, self::OP_CREATE);
					$this->FireEvent('DomainCreated', $domain);				
					
					$this->DBDomain->Save($domain);
				}
				else
				{
					$domain->Status = DOMAIN_STATUS::REJECTED;
					
					$this->FireEvent('DomainOperation', $domain, self::OP_CREATE, true, $resp->FailReason);
					
					$this->DBDomain->Save($domain);
				}
				
				return true;
			}
			
		}
		
		function DispatchPollDeleteDomain (PollDeleteDomainResponse $resp)
		{
			if ($resp->IsFailed())
			{
				Log::Log(sprintf('DispatchPollDeleteDomain failed. Registry response: %s', $resp->ErrMsg), E_USER_ERROR);
				throw new Exception($resp->ErrMsg, $resp->Code);
			}
			
			if ($resp->Succeed())
			{
				list($name, $extension) = FQDN::Parse($resp->HostName);
				$domain = $this->DBDomain->LoadByName($name, $extension, $this->GetManifest());
				
				if ($resp->Result)
				{
					$this->DBDomain->Delete($domain);
					$this->FireEvent('DomainDeleted', $domain);
				}
				else
				{
					$op = $this->DB->GetRow(
						'SELECT * FROM pending_operations WHERE objectid=? AND objecttype=?',
						array($domain->ID, self::OBJ_DOMAIN)
					);
					if (!$op)
					{
						throw new Exception('Pending operation not found');
					}
					$Before = unserialize($op['object_before']);
					$domain->Status = $Before->Status;
					$this->DBDomain->Save($domain);
				}
	
				return true;
			}
		}
		
		function DispatchPollUpdateDomain (PollUpdateDomainResponse $resp)
		{
			if ($resp->IsFailed())
			{
				Log::Log(sprintf('DispatchPollUpdateDomain failed. Registry response: %s', $resp->ErrMsg), E_USER_ERROR);
				throw new Exception($resp->ErrMsg, $resp->Code);
			}
			
			if ($resp->Succeed())
			{
				list($name, $extension) = FQDN::Parse($resp->HostName);
				$domain = $this->DBDomain->LoadByName($name, $extension, $this->GetManifest());
				
				if ($resp->Result)
				{
					$domain = $this->GetRemoteDomain($domain);
					
					$this->FireEvent('DomainOperation', $domain, self::OP_UPDATE);
					$this->FireEvent('DomainUpdated', $domain);				
					$this->DBDomain->Save($domain);
				}
				else
				{
					$this->FireEvent('DomainOperation', $domain, self::OP_UPDATE, true, $resp->FailReason);
				}
				
				return true;
			}
		}

		function DispatchPollUpdateContact (PollUpdateContactResponse $resp)
		{
			if ($resp->IsFailed())
			{
				Log::Log(sprintf('DispatchPollUpdateContact failed. Registry response: %s', $resp->ErrMsg), E_USER_ERROR);
				throw new Exception($resp->ErrMsg, $resp->Code);
			}
			
			if ($resp->Succeed())
			{
				$Contact = $this->DBContact->LoadByCLID($resp->CLID);
				try
				{
					// Get remote updated contact
					$Contact = $this->GetRemoteContact($Contact);
				}
				catch (NotImplementedException $e)
				{
					// Get updates from local history
					$op = $this->DB->GetRow(
						'SELECT * FROM pending_operations WHERE objectid=? AND objecttype=?',
						array($Contact->ID, self::OBJ_CONTACT)
					);
					if (!$op)
					{
						throw new Exception('Pending operation not found');
					}
					$After = unserialize($op['object_after']);
					
					$fields = array();
					foreach ($Contact->GetEditableNames() as $n)
					{
						$fields[$n] = $After->GetField($n);
					}
					$Contact->SetFieldList($fields);
				}
				
				$this->DBContact->Save($Contact);
				$this->FireEvent('ContactUpdated', $Contact);					
			}
		}		
		
		function DispatchPollDeleteContact (PollDeleteContactResponse $resp)
		{
			if ($resp->IsFailed())
			{
				Log::Log(sprintf('DispatchContactDeleted failed. Registry response: %s', $resp->ErrMsg), E_USER_ERROR);
				throw new Exception($resp->ErrMsg, $resp->Code);
			}
			
			$contact = $this->DBContact->LoadByCLID($resp->CLID);
			$this->DBContact->Delete($contact);
			$this->FireEvent('ContactDeleted', $contact);
		}
		
		function DispatchPollDeleteNamserverHost (PollDeleteNamserverHostResponse $resp)
		{
			// TODO
			return;
		}
		
		public function __toString()
		{
			return $this->RegModule->ModuleName;
		}
	}

?>
