<?php
	class EppDrs_Api_Service20090622 extends EppDrs_Api_Service 
	{
		/**
		 * List domains from database by several criteries
		 * 
		 * @param $params = array(
		 * 		query			string				Search over domain name
		 * 		createDate		date				
		 * 		createDateRange array(date, date)	Create date between
		 * 		expireDate		date				
		 * 		expireDateRange	array(date, date)	Expire date between
		 * 		contact			string				Any contact clid assigned to domain
		 * 		userId			int					User ID (Only in Admin mode)
		 * 
		 * 		start			int					Data slice start. Default 0
		 * 		limit			int					Date slice size. Default 25. -1 Unlimited 
		 * )
		 * @return eppdrs-api.xsd#listDomainsResponse
		 */
		function ListDomains ($params=null) 
		{
			$sql = "SELECT CONCAT(name, '.', TLD) as domainname FROM domains";
			
			$where = array();
			$bind = array();
			
			// Name filter
			if ($params["query"])
				$where[] = "CONCAT(name, '.', TLD) LIKE '%".mysql_escape_string($params["query"])."%'";
				
			// Create data filter
			if ($params["createDate"])
			{
				$where[] = "TO_DAYS(start_date) = TO_DAYS(?)";
				$bind[] = $params["createDate"];
			}
			else if ($params["createDateRange"])
			{
				$where[] = "(TO_DAYS(start_date) BETWEEN TO_DAYS(?)	AND TO_DAYS(?))";
				$bind[] = $params["createDateRange"][0];
				$bind[] = $params["createDateRange"][1];
			}

			// Expire date filter
			if ($params["expireDate"])
			{
				$where[] = "TO_DAYS(end_date) = TO_DAYS(?)";
				$bind[] = $params["expireDate"];
			}
			else if ($params["expireDateRange"])
			{
				$where[] = "(TO_DAYS(end_date) BETWEEN TO_DAYS(?) AND TO_DAYS(?))";
				$bind[] = $params["expireDateRange"][0];
				$bind[] = $params["expireDateRange"][1];
			}
			
			// Contact filter
			if ($params["contact"])
			{
				$where[] = "(c_registrant = ? OR c_admin = ? OR c_tech = ? OR c_billing = ?)";
				foreach (range(1, 4) as $i)
					$bind[] = $params["contact"];
			}
			
			// Additional filters
			if ($this->access_mode == self::ACCESS_MODE_ADMIN)
			{
				if ($params["userId"])
				{
					$where[] = "userid = ?";
					$bind[] = $params["userId"]; 
				}
			}
			else // User access mode
			{
				// Users can search only in their domains
				$where[] = "userid = ?";
				$bind[] = $this->user_id;
			}			
			
			// Build SQL 
			$sql .= $where ? " WHERE " . join(" AND ", $where) : "";
			$sql_total = preg_replace('/SELECT[^F]+FROM/', 'SELECT COUNT(*) FROM', $sql, 1);

			// Sorting
			$sql .= " ORDER BY id ASC";
			
			// Apply limits
			if ($params["limit"] != -1)
			{
				$sql .= sprintf(
					" LIMIT %d, %d", 
					$params["start"] ? $params["start"] : 0,
					$params["limit"] ? $params["limit"] : 25
				);
			}
			
			
			// Execute SQL
			$rows = $this->db->GetAll($sql, $bind);
			$total = $this->db->GetOne($sql_total, $bind);
			
	
			// Collect response
			$ret = new stdClass();
			$ret->total = $total;
			$ret->domainList = new stdClass();

			foreach ($rows as $row)
			{
				$domain = $this->GetDomainInfo(array
				(
					"name" => $row["domainname"],
					"mode" => self::INFO_MODE_LOCAL
				));
				$ret->domainList->domain[] = $domain;
			}
			
			return $ret;
		}
		
		/**
		 * Check domain availablility
		 * 
		 * @param $params = array(
		 * 		name 		string		Domain name
		 * )
		 * @return eppdrs-api.xsd#checkDomainResponse
		 */
		function CheckDomain ($params=null)
		{
			// Accept params
			list($name, $tld) = $this->SplitNameAndTLD($params['name']);
			
			$registry = $this->registry_factory->GetRegistryByExtension($tld);
			$domain = $registry->NewDomainInstance();
			$domain->Name = $name;
			
			$check_response = $registry->GetModule()->DomainCanBeRegistered($domain);
			
			$ret = new stdClass();
			$ret->name = $domain->GetHostName();
			$ret->avail = (int)$check_response->Result;
			if ($check_response->Reason)
			{
				$ret->reason = $check_response->Reason;
			}  
						
			return $ret;
		}
		
		/**
		 * 
		 * @param $params = array(
		 * 		name		string		Domain name
		 * 		mode		string		'registry' - get info from registry server
		 * 								'local' - get info from local database
		 * )
		 * @return eppdrs-api.xsd#getDomainInfoResponse
		 */
		function GetDomainInfo ($params=null) 
		{
			// Accept params
			list($name, $tld) = $this->SplitNameAndTLD($params['name']);
			
			// Check access
			$this->CheckDomainAccess($name, $tld);
			
			// Do
			if (strtolower($params['mode']) == self::INFO_MODE_REGISTRY) // Request registry server 
			{
				$registry = $this->registry_factory->GetRegistryByExtension($tld);
				$domain = $registry->NewDomainInstance();
				$domain->Name = $name;
				
				$grd_response = $registry->GetModule()->GetRemoteDomain($domain);

				$ret = new stdClass();
				$ret->name = $domain->GetHostName();
				
				$ret->contacts = new stdClass();
				if ($grd_response->RegistrantContact)
					$ret->contacts->registrant = $grd_response->RegistrantContact;
				if ($grd_response->AdminContact)
					$ret->contacts->admin = $grd_response->AdminContact;
				if ($grd_response->BillingContact)
					$ret->contacts->billing = $grd_response->BillingContact;
				if ($grd_response->TechContact)
					$ret->contacts->tech = $grd_response->TechContact;
					
				if ($grd_response->GetNameserverList())
				{
					$ret->ns = array();
					foreach ($grd_response->GetNameserverList() as $Nameserver)
					{
						$ret->ns[] = $Nameserver->HostName;
					}
				}

				if ($grd_response->CreateDate)					
					$ret->createDate = date($this->date_format, $grd_response->CreateDate);
				if ($grd_response->ExpireDate)
					$ret->expireDate = date($this->date_format, $grd_response->ExpireDate);
				
				$ret->locked = (int)$grd_response->IsLocked;
				$ret->flag = $grd_response->GetFlagList();
				if ($grd_response->AuthCode) {
					$ret->authCode = $grd_response->AuthCode;
				}				
				
				// Remote specific properties
				
				// Registry status (ok, pendingCreate ...)
				$ret->registryStatus = $grd_response->RegistryStatus; 
				$ret->extraFields = $grd_response->GetExtraData();
			}
			else // Request local database
			{
				$db_domain = DBDomain::GetInstance();
				$domain = $db_domain->LoadByName($name, $tld);
				
				$ret = new stdClass();
				$ret->name = $domain->GetHostName();
				
				$ret->contacts = new stdClass();
				$contacts = $domain->GetContactList();
				if ($contacts[CONTACT_TYPE::REGISTRANT])
					$ret->contacts->registrant = $contacts[CONTACT_TYPE::REGISTRANT]->CLID;
				if ($contacts[CONTACT_TYPE::ADMIN])
					$ret->contacts->admin = $contacts[CONTACT_TYPE::ADMIN]->CLID;
				if ($contacts[CONTACT_TYPE::BILLING])
					$ret->contacts->billing = $contacts[CONTACT_TYPE::BILLING]->CLID;
				if ($contacts[CONTACT_TYPE::TECH])
					$ret->contacts->tech = $contacts[CONTACT_TYPE::TECH]->CLID;

				if ($domain->GetNameserverList())
				{
					$ret->ns = array();
					foreach ($domain->GetNameserverList() as $ns)
					{
						$ret->ns[] = $ns->HostName;
					}
				}
				
				if ($domain->CreateDate)
					$ret->createDate = date($this->date_format, $domain->CreateDate);
				if ($domain->ExpireDate)
					$ret->expireDate = date($this->date_format, $domain->ExpireDate);
				
				$ret->locked = (int)$domain->IsLocked;
				$ret->flag = $domain->GetFlagList();
				$ret->authCode = $domain->AuthCode;

				// Local specific properties.
				
				// Local status. See DOMAIN_STATUS::*
				$ret->localStatus = $domain->Status;
			}
			
			return $ret;
		}
		
		/**
		 * Reguest for TLD specific rules and fields
		 * @param $params = array(
		 * 		tld		string		TLD. ex: gr, co.uk
		 * )
		 * @return object
		 */
		function GetTldInfo ($params=null)
		{
			if (!$params["tld"])
				throw new Exception(sprintf("'%s' parameter is required", "tld"));
				
			$registry = $this->registry_factory->GetRegistryByExtension($params["tld"]);
			$manifest = $registry->GetManifest();
			$domain_config = $manifest->GetDomainConfig();
			
			
			$ret = new stdClass();
			$registration = new stdClass();
			$registration->minPeriod = "{$domain_config->registration->min_period}";
			$registration->maxPeriod = "{$domain_config->registration->max_period}";
			if (count($domain_config->registration->extra_fields->field) > 0)
			{
				$registration->extraFields = new stdClass();
				$this->ExportDataFormFields
				(
					$domain_config->registration->extra_fields->field, 
					$registration->extraFields
				);
			}
			$ret->registration = $registration;

			
			$renew_available = (int)$domain_config->renewal->min_period > 0;
			if ($renew_available)
			{
				$renew = new stdClass();				
				$renew->minPeriod = "{$domain_config->renewal->min_period}";
				$renew->maxPeriod = "{$domain_config->renewal->max_period}";
				$ret->renew = $renew;				
			}

			
			$transfer_available = (int)$domain_config->transfer->allow;
			if ($transfer_available)
			{
				$transfer = new stdClass();
				$transfer->contactsRequired = (int)(count($domain_config->transfer->contacts->children()) > 0);
				if (count($domain_config->transfer->fields) > 0)
				{
					$transfer->extraFields = new stdClass();
					$this->ExportDataFormFields($domain_config->transfer->fields->field, $transfer->extraFields);
				}
				
				$ret->transfer = $transfer;
			}
			
			return $ret;
		}
		
		protected function ExportDataFormFields ($xml_fields, $parent)
		{
			foreach ($xml_fields as $xml_field)
			{
				$field = new stdClass();
				foreach ($xml_field->attributes() as $k => $v)
				{
					if (in_array($k, array("description", "name", "required", "type")))
						$field->{$k} = "$v";
					elseif ($k == "minlength")
						$field->minLength = "$v";
					elseif ($k == "maxlength")
						$field->maxLength = "$v";
						
					if ($field->type == "select")
					{
						$field->options = new stdClass();
						if ($xml_field->values->getName())
						{
							foreach ($xml_field->values->value as $xml_value)
							{
								$option = new stdClass();
								$option->value = (string)$xml_value->attributes()->value;
								$option->title = (string)$xml_value->attributes()->name;
								$field->options->option[] = $option;
							}
						}
						else if ($xml_field->database->getName())
						{
							$db = Core::GetDBInstance();
	                        $dbinfo = (array)$xml_field->database->attributes();
	                        $dbinfo = $dbinfo['@attributes'];
		        	            
	                        if ($xml_field->database->sql->getName())
	                        {
	                        	$values = $db->Execute("{$xml_field->database->sql}");
	                        	while ($value = $values->FetchRow())
	                        	{
	                        		$option = new stdClass();
	                        		$option->value = $value[$dbinfo['value_field']];
	                        		$option->title = $value[$dbinfo['name_field']];
	                        		$field->options->option[] = $option;
	                        	}
	                        }
	                        else
	                        {
                        		$values = $db->Execute("SELECT `{$dbinfo['value_field']}` as `key`, `{$dbinfo['name_field']}` as `name` FROM `{$dbinfo['table']}`");
	                        	while ($value = $values->FetchRow())
	                        	{
	                        		$option = new stdClass();
	                        		$option->value = $value['key'];
	                        		$option->title = $value['name'];
	                        		$field->options->option[] = $option;
	                        	}
	                        }
						}
					}
				}
				$parent->field[] = $field;
			}
		}
		
		/**
		 * 
		 * @param $params = array (
		 * 		name		string
		 * 		period		int
		 * 		registrant	string
		 * 		admin		string
		 * 		billing		string
		 * 		tech		string
		 * 		ns			array[string]
		 * 		extraFields array(key => value)
		 * 		userId		int						User ID (In admin mode)
		 * 		noBilling	bool					Disable billing for domain opeartion (In admin mode) 
		 * )
		 * @return object
		 */
		function CreateDomain ($params=null) 
		{
			// Check params
			if ($this->access_mode == self::ACCESS_MODE_ADMIN)
			{
				if (!$params["userId"])
					throw new Exception(sprintf("'%s' parameter is required", "userId"));
			}
			else
			{
				// Reset user disabled params 
				$params["noBilling"] = false;
			}
			$user_id = $this->user_id ? $this->user_id : $params["userId"];
			if (!$params["name"])
				throw new Exception(sprintf("'%s' parameter is required", "name"));			
			if (!$params["period"])
				throw new Exception(sprintf("'%s' parameter is required", "period"));

				
			list($name, $tld) = explode(".", $params["name"], 2);
			$registry = $this->registry_factory->GetRegistryByExtension($tld);

			// Create and init RegisterDomainAction
			$contact_list = array();
			foreach(CONTACT_TYPE::GetKeys() as $ctype)
			{
				if ($params[$ctype])
					$contact_list[$ctype] = $params[$ctype];
			}
			try
			{
				$action = new RegisterDomainAction(array
				(
					"Registry" => $registry,
					"period" => $params["period"],
					"clid_list" => $contact_list,
					"ns_list" => (array)$params["ns"],
					"extra_data" => $params["extraFields"]
				));
			}
			catch (RegisterDomainAction_Exception $e)
			{
				// TODO better error messages instead of 'Comment required' 
				if ($e->ErrorList)
					$message = join("; ", $e->ErrorList->GetAllMessages());
				else
					$message = $e->getMessage();
				throw new Exception($message);
			}

			
			// Create domain object
			
			$db_domain = DBDomain::GetInstance();
			$already_exists = $this->db->GetOne(
				"SELECT * FROM domains WHERE 
					name = ? AND TLD = ? AND userid = ? AND incomplete_operation = ?",
				array($name, $tld, $user_id, INCOMPLETE_OPERATION::DOMAIN_CREATE)
			);
			if ($already_exists)
				$domain = $db_domain->LoadByName($name, $tld);
			else
			{
				$domain = $registry->NewDomainInstance();
				$domain->Name = $name;
				$domain->UserID = $user_id;
				$domain->Period = $params["period"];
				$domain->IncompleteOrderOperation = INCOMPLETE_OPERATION::DOMAIN_CREATE;
				
				if (!$params["noBilling"]) 
				{
					// Trigger domain registration by invoice payment
					
					$domain->Status = DOMAIN_STATUS::AWAITING_PAYMENT;
									
					// Check that enougth money
					$client = Client::Load($user_id);
					$balance = DBBalance::GetInstance()->LoadClientBalance($user_id);
					$invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_CREATE, $domain, $domain->UserID);
					$invoice->Description = sprintf(_("%s domain name registration for %s year(s)"), 
						$domain->GetHostName(), $domain->Period);
					
					$this->CheckEnoughtMoney($client, $balance, $invoice);
				}
				else 
				{
					// Direct domain registration
					
					$domain->Status = DOMAIN_STATUS::PENDING;
				}
			}
			
			// Check that domain is available for registration
			$registry = $this->registry_factory->GetRegistryByExtension($domain->Extension);
			if (!$registry->DomainCanBeRegistered($domain))
				throw new Exception("This domain name is already taken");
						
			if (!$domain->ID)
			{
				// Save domain after all checks 
				$db_domain->Save($domain);
				
				if (!$params["noBilling"])
				{
					$invoice->ItemID = $domain->ID;
		
					// Make payment for domain registration
					$this->MakePayment($client, $balance, $invoice);
					$domain->Status = DOMAIN_STATUS::PENDING;
					$db_domain->Save($domain);
				}
			}


			// Run registration action
			try
			{
				$action_result = $action->Run($domain);
				
				$ret = new stdClass();
				$ret->name = $domain->GetHostName();
				if ($action_result == RegisterDomainAction_Result::OK)
					$ret->status = "ok";
				elseif ($action_result == RegisterDomainAction_Result::PENDING)
					$ret->status = "okPending";
				else
					$ret->status = "unknown";
					
				// Remove incomplete order operation
				$domain->IncompleteOrderOperation = null;
				$db_domain->Save($domain);
					
				return $ret;
			}
			catch (RegisterDomainAction_Exception $e)
			{
				if ($e->ErrorList)
					$message = join("; ", $e->ErrorList->GetAllMessages());
				else
					$message = $e->getMessage();
				throw new Exception($message);				
			}
		}
		
		protected function CheckEnoughtMoney ($client, $balance, $invoice)
		{
			if ($client->GetSettingValue(ClientSettings::AUTO_PAY_FROM_BALANCE) == "0")
				throw new Exception("Deduct funds from account balance is disabled. You can enable it in Settings -> General");

			if ($balance->Total < $invoice->GetTotal())
				throw new Exception(sprintf("Not enough money on balance to perform this operation. Rest: %s, Required: %s", 
						CONFIG::$CURRENCY.$balance->Total, CONFIG::$CURRENCY.$invoice->GetTotal()));
		}
		
		protected function MakePayment ($client, $balance, $invoice)
		{
			// Mark invoice as paid without MarkAsPaid all observers train
			$invoice->SuspendEvents();
			$invoice->MarkAsPaid(null);
			$invoice->ResumeEvents();			
			
			// Deduct funds from balance
			$balance_operation = $balance->CreateOperation(
				BalanceOperationType::Withdraw, $invoice->GetTotal());
			$balance_operation->InvoiceID = $invoice->ID;
			$balance->ApplyOperation($balance_operation);			
		}
		
		/**
		 * Updates one of the domain contacts (registrant, admin, billing, tech)
		 * 
		 * @param $params = array(
		 * 		name 			string		Domain name
		 * 		contactType		string		'registrant', 'admin', 'billing', 'tech' (Use CONTACT_TYPE::*)
		 * 		clid			string		Contact CLID
		 * 		noBilling		bool		Disable billing for domain opeartion (In admin mode) 
		 * )
		 * @return object
		 */
		function UpdateDomainContact ($params=null) 
		{
			foreach (array("name", "contactType") as $pname)
			{
				if (!$params[$pname])
					throw new Exception(sprintf("'%s' parameter is required", $pname));
			}
			if (!in_array($params["contactType"], CONTACT_TYPE::GetKeys()))
				throw new Exception(sprintf("Invalid contact type '%s'", $params["contactType"]));

			if ($this->access_mode == self::ACCESS_MODE_USER)
				$params["noBilling"] = false;
				
			
			list($name, $tld) = $this->SplitNameAndTLD($params["name"]);
			if ($this->access_mode == self::ACCESS_MODE_USER)
				$this->CheckDomainAccess($name, $tld);

			// Initialize domain registry
			$registry = $this->registry_factory->GetRegistryByExtension($tld);				
				
			// Load domain
			$db_domain = DBDomain::GetInstance();
			$domain = $db_domain->LoadByName($name, $tld);
				
			// Load contact
			if ($params["clid"])
			{
				$db_contact = DBContact::GetInstance();				
				$contact = $db_contact->LoadByCLID($params["clid"]);
				if ($contact->UserID != $domain->UserID)
					throw new Exception(sprintf("Contact %s belongs to another user", $contact->CLID));
			}
			
			// Detect trade/update
			$manifest = $registry->GetManifest();
			if ($manifest->GetRegistryOptions()->ability->trade == "1" && 
				$params["contactType"] == CONTACT_TYPE::REGISTRANT)
			{
				// Trade
				
				if (!$params["noBilling"] && 
					$domain->IncompleteOrderOperation != INCOMPLETE_OPERATION::DOMAIN_TRADE)
				{
					// If trade in not charged
					
					// Check that enougth money
					$client = Client::Load($domain->UserID);
					$balance = DBBalance::GetInstance()->LoadClientBalance($domain->UserID);
					$invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_TRADE, $domain, $domain->UserID);
					$invoice->Description = sprintf(_("%s domain name trade"), $domain->GetHostName());
					
					$this->CheckEnoughtMoney($client, $balance, $invoice);
					
					$invoice->ItemID = $domain->ID;
					$domain->IncompleteOrderOperation = INCOMPLETE_OPERATION::DOMAIN_TRADE;
					$db_domain->Save($domain);
	
					// Make payment for domain registration
					$this->MakePayment($client, $balance, $invoice);					
				}
				
				$extra = array("requesttype" => "ownerChange");
				$domain->SetContact($contact, CONTACT_TYPE::REGISTRANT);
				$registry->ChangeDomainOwner($domain, 1, $extra);
				
				$domain->IncompleteOrderOperation = null;
				$db_domain->Save($domain);
				
			}
			else
			{
				// Common contact update
				if ($params["clid"])
				{
					// Set new contact
					$registry->UpdateDomainContact(
							$domain, $params["contactType"], 
							$domain->GetContact($params["contactType"]), $contact);				
				}
				else
				{
					// Unset contact
					$registry->UpdateDomainContact(
							$domain, $params["contactType"], 
							$domain->GetContact($params["contactType"]), null);
				} 
			}
			
			// Empty response
			return new stdClass();
		}
		
		/**
		 * Set domain nameservers
		 * 
		 * @param $params = array(
		 * 		name		string			Domain name
		 * 		ns			array[string]	
		 * )
		 * @return object
		 */
		function UpdateDomainNameservers ($params=null) 
		{
			if (!$params["ns"])
				throw new Exception(sprintf("'%s' parameter is required", "ns"));
			
			list($name, $tld) = $this->SplitNameAndTLD($params["name"]);
			if ($this->access_mode == self::ACCESS_MODE_USER)
				$this->CheckDomainAccess($name, $tld);			

			$db_domain = DBDomain::GetInstance();
			$domain = $db_domain->LoadByName($name, $tld);
				
			$registry = $this->registry_factory->GetRegistryByExtension($tld);
			
			$ns_list = array();
			foreach ($params["ns"] as $ns)
				$ns_list[] = new Nameserver($ns);
			$changes = $domain->GetNameserverChangelist();
			$changes->SetChangedList($ns_list);
			$registry->UpdateDomainNameservers($domain, $changes);
			
			return new stdClass();
		}
		
		/**
		 * Set domain lock
		 *  
		 * @param $params = array(
		 * 		name		string		Domain name
		 * 		locked		boolean
		 * )
		 * @return object
		 */
		function UpdateDomainLock ($params=null) 
		{
			if (!array_key_exists("locked", $params))
				throw new Exception(sprintf("'%s' parameter is required", "locked"));			
			
			list($name, $tld) = $this->SplitNameAndTLD($params["name"]);
			if ($this->access_mode == self::ACCESS_MODE_USER)
				$this->CheckDomainAccess($name, $tld);			

			$db_domain = DBDomain::GetInstance();
			$domain = $db_domain->LoadByName($name, $tld);
				
			$registry = $this->registry_factory->GetRegistryByExtension($tld);
			
			if ($params["locked"])
				$registry->LockDomain($domain);
			else
				$registry->UnlockDomain($domain);
				
			// Empty response				
			return new stdClass();
		}
		
		/**
		 * Request domain name ingoing transfer
		 * 
		 * @param $params = array(
		 * 		name			string					Domain name
		 * 		authCode		string 					Domain auth code
		 * 		registrant		string
		 * 		admin			string
		 * 		billing			string
		 * 		tech			string
		 * 		extraFields		array(key => value)		@see GetTldInfo
		 * 		userId			int						User ID (In admin mode)
		 * 		noBilling		bool					Disable billing for domain opeartion (In admin mode) 
		 * ) 
		 * @return object
		 */
		function TransferDomain ($params=null) 
		{
			// Check params
			if ($this->access_mode == self::ACCESS_MODE_ADMIN)
			{
				if (!$params["userId"])
					throw new Exception(sprintf("'%s' parameter is required", "userId"));
			}
			else
			{
				$params["noBilling"] = false;
			}
			
			$user_id = $this->user_id ? $this->user_id : $params["userId"];
			if (!$params["name"])
				throw new Exception(sprintf("'%s' parameter is required", "name"));			
			
				
			list($name, $tld) = explode(".", $params["name"], 2);
			$registry = $this->registry_factory->GetRegistryByExtension($tld);
			
			
			$db_domain = DBDomain::GetInstance();
			$already_exists = $this->db->GetOne(
				"SELECT * FROM domains WHERE 
					name = ? AND TLD = ? AND userid = ? AND incomplete_operation = ?",
				array($name, $tld, $user_id, INCOMPLETE_OPERATION::DOMAIN_TRANSFER)
			);
			if ($already_exists)
				$domain = $db_domain->LoadByName($name, $tld);
			else
			{
				$domain = $registry->NewDomainInstance();
				$domain->Name = $name;
				$domain->UserID = $user_id;
				$domain->Period = 1;
				$domain->IncompleteOrderOperation = INCOMPLETE_OPERATION::DOMAIN_TRANSFER;
				
				if (!$params["noBilling"])
				{
					$domain->Status = DOMAIN_STATUS::AWAITING_PAYMENT;				
					// Check that enougth money
					$client = Client::Load($user_id);
					$balance = DBBalance::GetInstance()->LoadClientBalance($user_id);
					$invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_TRANSFER, $domain, $domain->UserID); 
					$invoice->Description = sprintf(_("%s domain name transfer"), $domain->GetHostName());			
					
					$this->CheckEnoughtMoney($client, $balance, $invoice);
				}
				else
				{
					$domain->Status = DOMAIN_STATUS::PENDING;
				}
			}

			// Check that domain is available for transfer
			$registry = $this->registry_factory->GetRegistryByExtension($domain->Extension);
			if (!$registry->DomainCanBeTransferred($domain))
				throw new Exception("This domain name cannot be transferred");
			
			if (!$domain->ID)
			{
				// Save domain after all checks 
				$db_domain->Save($domain);
				
				if (!$params["noBilling"])
				{
					$invoice->ItemID = $domain->ID;
		
					$this->MakePayment($client, $balance, $invoice);
					$domain->Status = DOMAIN_STATUS::PENDING;
					$db_domain->Save($domain);
				}
			}
			
			// Run transfer action
			$extra_fields = $params["extraFields"];
			$extra_fields["pw"] = $params["authCode"];
			$db_contact = DBContact::GetInstance();
			foreach (CONTACT_TYPE::GetKeys() as $ctype)
			{
				if ($params[$ctype] || $extra_fields[$ctype])
				{
					$clid = $params[$ctype] ? $params[$ctype] : $extra_fields[$ctype];
					$c = $db_contact->LoadByCLID($clid);
					$domain->SetContact($c, $ctype);
					$extra_fields[$ctype] = $clid;										
				}
 			}
 			if ($params["ns"] && !$extra_fields["ns1"])
 			{
 				$extra_fields["ns1"] = $params["ns"][0];
 				$extra_fields["ns2"] = $params["ns"][1];
 			}

			$registry->TransferRequest($domain, $extra_fields);
			
			// Remove incomplete order operation
			$domain->IncompleteOrderOperation = null;
			$db_domain->Save($domain);

			$ret = new stdClass();
			return $ret;
		}

		/**
		 * Import domain names
		 * @param $params = array(
		 * 		names		array[string]	Domain names 
		 * 		defaultTld	string			Default TLD to use when domain name have no it.
		 * 		userId		int				User ID to whom append these domains. (In admin mode) 
		 * )
		 * @return object
		 */
		function ImportDomains ($params=null) 
		{
			// Check params
			if ($this->access_mode == self::ACCESS_MODE_ADMIN)
			{
				if (!$params["userId"])
					throw new Exception(sprintf("'%s' parameter is required", "userId"));
			}
			
			$user_id = $this->access_mode == self::ACCESS_MODE_ADMIN ? $params["userId"] : $this->user_id;
			$can_change_contact_owner = $this->access_mode == self::ACCESS_MODE_ADMIN;
			
			$ret = new stdClass();
			$ret->importResult = array();
			
			foreach ($params["names"] as $hostname)
			{
				list($name, $tld) = explode(".", $hostname, 2);
				if (!$tld && $params["defaultTld"])
					$tld = $params["defaultTld"];

				$error = null;
					
				try
				{
					$registry = $this->registry_factory->GetRegistryByExtension($tld);
					
					$domain = $registry->NewDomainInstance();
					$domain->Name = $name;
					
					if (!DBDomain::GetInstance()->FindByName($name, $tld))
					{						
						$domain = $registry->GetRemoteDomain($domain);
						if ($domain->RemoteCLID)
						{
						    if ($domain->RemoteCLID == $registry->GetRegistrarID() || $domain->AuthCode != '')
	    					{
	    						// Apply contacts to domain
	    						foreach ($domain->GetContactList() as $contact)
								{
									if ($contact->UserID != $user_id)
									{
										if ($can_change_contact_owner)
											$contact->UserID = $user_id;
										else
											throw new Exception("Contact {$contact->CLID} belongs to another client");
									}
								}    						
	    						
    							$period = date("Y", $domain->ExpireDate)-date("Y", $domain->CreateDate);
    							$domain->Status = DOMAIN_STATUS::DELEGATED;
    							$domain->UserID = $user_id;
	    							
   								DBDomain::GetInstance()->Save($domain);
	    					}
	    				}
    					else
    					{
    						$error = "Cannot be imported because it does not belong to the current registar.";
    					}
					}
					else
					{
						$error = "Already exists in our database."; 
					}
				}
				catch (Exception $e)
				{
					$error = $e->getMessage();
				}
				
				$import_result = new stdClass();
				$import_result->name = "{$name}.{$tld}";
				$import_result->success = (int)!$error;
				if (!$import_result->success)
					$import_result->error = $error;

				$ret->importResult[] = $import_result;
			}
			
			return $ret;
		}		
		
		// Contact methods
		
		/**
		 * 
		 * @param $params = array(
		 * 		query		string		Search over contact fields and clid
		 * 		userId		int 		User ID (Only in admin mode)
		 * 
		 * 		start		int			Data slice start. Default 0
		 * 		limit		int			Date slice size. Default 25. -1 Unlimited 
		 * )
		 * @return object
		 */
		function ListContacts ($params=null) 
		{
			$sql = "SELECT DISTINCT c.* from contacts AS c";
			$where = array();
			$bind = array();
			
			// Apply filter
			if ($params["query"])
			{
				$query = mysql_escape_string($params["query"]);
				$sql .= " LEFT JOIN contacts_data as cd ON c.clid = cd.contactid";
				$where[] = "(c.clid LIKE '%{$query}%' OR cd.`value` LIKE '%{$query}%')";
			}
			
			if ($this->access_mode == self::ACCESS_MODE_ADMIN)
			{
				if ($params["userId"])
				{
					$where[] = "c.userid = ?";
					$bind[] = $params["userId"]; 
				}
			}
			else // User access mode
			{
				// Users can search only in their domains
				$where[] = "c.userid = ?";
				$bind[] = $this->user_id;
			}			
			
			// Build SQL
			$sql .= $where ? " WHERE ".join(" AND ", $where) : "";
			$sql_total = preg_replace('/DISTINCT c\.\*/', 'COUNT(DISTINCT c.id)', $sql, 1);
			
			// Sorting
			$sql .= " ORDER BY id ASC";
			
			// Apply limits
			if ($params["limit"] != -1)
			{
				$sql .= sprintf(
					" LIMIT %d, %d", 
					$params["start"] ? $params["start"] : 0,
					$params["limit"] ? $params["limit"] : 25
				);
			}			
					
			$rows = $this->db->GetAll($sql, $bind);
			$total = $this->db->GetOne($sql_total, $bind);
			
			$ret = new stdClass();
			$ret->contactList = new stdClass();
			$ret->total = $total;
			
			$db_contact = DBContact::GetInstance();
			foreach ($rows as $row)
			{
				$ret_contact = new stdClass();
				$ret_contact->clid = $row["clid"];
				
				try
				{
					$contact = $db_contact->Load($row["id"]);
					$ret_contact->name = $contact->GetFullName();
					$ret_contact->email = $contact->GetEmail();
				}
				catch (Exception $e)
				{
					$ret_contact->name = $ret_contact->email = "Unknown";
				}
				
				$ret->contactList->contact[] = $ret_contact;
			}
			
			return $ret;
		}
		
		// Balance methods
		
		/**
		 * 
		 * @param $params = array(
		 * 		userId		int		User ID (Only in admin mode)
		 * )
		 * @return object
		 */
		function GetBalanceInfo ($params=null) 
		{
			$user_id = $this->access_mode == self::ACCESS_MODE_ADMIN ? $params["userId"] : $this->user_id;
			if (!$user_id)
				throw new Exception(sprintf("'%s' parameter is required", "userId")); 
			
			$db_balance = DBBalance::GetInstance();
			$balance = $db_balance->LoadClientBalance($user_id);
			
			$ret = new stdClass();
			$ret->balance = new stdClass();
			$ret->balance->total = $balance->Total;
			$ret->balance->currency = CONFIG::$CURRENCYISO;
			
			return $ret;
		}	
	}
?>