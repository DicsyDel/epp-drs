<?php

class RegisterDomainAction_Result
{
	const OK = 1;
	const PENDING = 2;
	const INVOICE_GENERATED = 3;
}

class RegisterDomainAction 
{
	// Config properties
	private
		$tld,
		$Registry,
		$managed_dns_enabled,
		$extra_data,
		$Order,
		
		// Registration period
		$period,
		
		// List of contact CLIDs
		$clid_list,
		// List of contact objects
		$contact_list,
		
		// List of dns server hostnames 
		$ns_list, 
		// List of dns server objects
		$nameserver_list,
		
		// Flag 
		$do_check;
		
	private $prepared = false;
	
	public function __construct($config)
	{
		$Ref = new ReflectionObject($this);
		foreach ($config as $k => $v)
		{
			if ($Ref->hasProperty($k))
			{
				$this->{$k} = $v;
			}
		}
		
		if (!($this->tld || $this->Registry))
		{
			throw new RegisterDomainAction_Exception('$tld or $Registry must be defined');
		}
		if (!(isset($this->clid_list) || isset($this->contact_list)))
		{
			throw new RegisterDomainAction_Exception('$clid_list or $contact_list must be defined');
		}
		if (!(isset($this->ns_list) || isset($this->nameserver_list)))
		{
			throw new RegisterDomainAction_Exception('$ns_list or $nameserver_list must be defined');
		}
		
		$this->Init();
	}
	
	private function Init ()
	{
		if (!$this->prepared)
		{
			$ErrList = new ErrorList();
			
			if (!$this->Registry)
			{
				$Factory = RegistryModuleFactory::GetInstance();
				try
				{
					$this->Registry = $Factory->GetRegistryByExtension($this->tld);
				}
				catch (Exception $e)
				{
					throw new RegisterDomainAction_Exception(sprintf(_("Cannot init registry module. Reason: %s"), $e->getMessage()));
				}
			}
			
		    $registry_config = $this->Registry->GetManifest()->GetSectionConfig();
		    $Validator = new Validator();
		    
		    
		    /**
		     * Validate period
		     */
		    if ($this->period)
		    {
		    	self::ValidatePeriod($this->period, $this->Registry);
		    }
		    
		    
		    /*
		     * Validate additional domain information
		     */
		    $extra_fields = null;
		    if (count($registry_config->domain->registration->extra_fields->field) > 0)
		    {
				$extra_fields = UI::GetRegExtraFieldsForSmarty($registry_config);
				foreach ($extra_fields as $field)
				{
					if ($field["isrequired"] == 1 && $field["type"] != "checkbox")
					{
						if (!strlen($this->extra_data[$field['name']]))
						{
							$ErrList->AddMessage(sprintf(_("%s required"), ucfirst($field["name"])));
						}
					}
				}
		    }
			// Break on errors
		    $this->ExceptionOnErrors($ErrList);
			
		    /**
		     * Validate DNS
		     */
			if (!$this->managed_dns_enabled)
		    {
				if (!$this->nameserver_list)
				{
					foreach ($this->ns_list as $ns)
					{
						$this->nameserver_list[] = new Nameserver($ns);
					}
				}
				$nameserver_list = $this->nameserver_list;
				
				if (count(array_unique($nameserver_list)) < count($nameserver_list))
		    	{
		    		$ErrList->AddMessage(_("Cannot use the same nameserver twice."));
		    	}
		    	else
		    	{
		    		foreach ($nameserver_list as $Nameserver)
		    		{
						if (!$Validator->IsDomain($Nameserver->HostName))
						{
							$ErrList->AddMessage(sprintf(_("'%s' is not a valid host"), $Nameserver->HostName));
						}
		    		}
		    	}		    
		    }
		    else
		    {
		    	$this->nameserver_list = array(
		    		new Nameserver(CONFIG::$NS1),
		    		new Nameserver(CONFIG::$NS2)
		    	);
		    }
			$this->ExceptionOnErrors($ErrList);
		    
		    /*
		     * Validate contacts 
		     */
			if (!$this->contact_list)
			{
				$DbContact = DBContact::GetInstance();
				$this->contact_list = array();
			    foreach ($this->clid_list as $ctype => $clid)
			    {
			    	if (!$clid) 
			    		continue;
			    		
			    	try
			    	{
			    		$Contact = $DbContact->LoadByCLID($clid);
			    		$this->contact_list[$ctype] = $Contact;
			    	}
			    	catch (Exception $e)
			    	{
			    		$ErrList->AddMessage(sprintf(_("Cannot load %s contact. %s"), 
			    			$ctype, $clid, $e->getMessage()));
			    	}
			    }
				// Break on errors
			    $this->ExceptionOnErrors($ErrList);		    
			}
			$this->ExceptionOnErrors(self::ValidateContactList($this->contact_list, $this->Registry));
			
			$this->prepared = true;
		}
	}
	
	public static function ValidateContactList ($contact_list, $Registry)
	{
		$clid_list = array();
		foreach ($contact_list as $ctype => $Contact)
		{
			$clid_list[$ctype] = $Contact->CLID;
		}
		return self::ValidateCLIDList($clid_list, $Registry);
	}
	
	public static function ValidateCLIDList ($clid_list, $Registry)
	{
		$ErrList = new ErrorList();
	    $registry_config = $Registry->GetManifest()->GetSectionConfig();
			
		$RegistryContacts = UI::GetContactsListForSmarty($registry_config);		
        foreach ($RegistryContacts as $k=>$v)
        {
        	if (!$clid_list[$v['type']] && $v["isrequired"] == 1)
	        {
	        	$ErrList->AddMessage(sprintf(_("%s contact not specified"), $v["name"]));
	        }
		}
		return $ErrList;		
	}
	
	public static function ValidatePeriod ($period, $Registry)
	{
		$registry_config = $Registry->GetManifest()->GetSectionConfig();
	    $min_period = (int)$registry_config->domain->registration->min_period;
	    $max_period = (int)$registry_config->domain->registration->max_period;

	    if ($period < $min_period || $period > $max_period)
	    	throw new Exception("Registration period must be in range {$min_period} .. {$max_period}");
	}
	
	//public static function ValidateNameservers ($ns_list, $Registry, $)
	
	public static function ValidateExtraData ($extra_data, $Registry)
	{
		$extra_fields = null;
		if (count($registry_config->domain->registration->extra_fields->field) > 0)
		{
			$extra_fields = UI::GetRegExtraFieldsForSmarty($registry_config);
			foreach ($extra_fields as $field)
			{
				if ($field["required"] == 1 && $field["type"] != "checkbox")
				{
					if (!$Validator->IsNotEmpty($this->extra_data[$field['name']]))
					{
						$ErrList->AddMessage(sprintf(_("%s required"), ucfirst($field["description"])));
					}
				}
			}
		}		
	}
	
	public function Run (Domain $Domain)
	{
		Log::Log("Start domain registration action", E_USER_NOTICE);
		
		$ErrList = new ErrorList();		
		if (!($Domain->Name && $Domain->Period && $Domain->UserID))
		{
			throw new Exception("Domain must have name, period and userid filled");
		}
		
		if ($this->do_check)
		{
			// Perform a domain check
			$chk = $this->Registry->DomainCanBeRegistered($Domain);
			if (!$chk->Result)
			{
				throw new Exception("Domain cannot be registered" . ($chk->Reason ? ". Reason: {$chk->Reason}" : ""));
			}
		}
		
		
		if ($this->period)
		{
			$Domain->Period = $this->period;
		}
		else
		{
			self::ValidatePeriod($Domain->Period, $this->Registry);
		}
		
		/*
		 * Set nameservers
		 */
		if (!$this->managed_dns_enabled)
		{
			$domain_hostname = $Domain->GetHostName();
			foreach ($this->nameserver_list as $Nameserver)
			{
				if (FQDN::IsSubdomain($Nameserver->HostName, $domain_hostname))
				{
					$ErrList->AddMessage(sprintf(_("%s cannot be used as nameserver because %s is not registered yet."), 
						$Nameserver->HostName, $domain_hostname));
				}
			}
		}
		// Break on errors
	    $this->ExceptionOnErrors($ErrList);		    
	    $Domain->IsManagedDNSEnabled = $this->managed_dns_enabled;
	    $Domain->SetNameserverList($this->nameserver_list);
		
		
	    /*
	     * Set contacts
	     */
	    foreach ($this->contact_list as $ctype => $Contact)
	    {
	    	try
	    	{
	    		$Domain->SetContact($Contact, $ctype);
	    	}
	    	catch (Exception $e)
	    	{
	    		$ErrList->AddMessage(sprintf(_("Cannot set %s contact to %s. %s"), 
	    			$ctype, $clid, $e->getMessage()));
	    	}
	    }
		// Break on errors
	    $this->ExceptionOnErrors($ErrList);		    

        
	    /*
	     * Set additional domain data
	     */
	    if ($this->extra_data)
	    {
	    	foreach ($this->extra_data as $field)
	    	{
	    		$Domain->SetExtraField($field['name'], $this->extra_data[$field['name']]);
	    	}
	    }

	    /*
	     * Register domain
	     */
        if ($Domain->IncompleteOrderOperation == INCOMPLETE_OPERATION::DOMAIN_CREATE)
        {
        	Log::Log("Trying to register domain. (Postpaid)", E_USER_NOTICE);
        	try
        	{
        		$this->Registry->CreateDomain($Domain, $Domain->Period, $this->extra_data);
				return $Domain->HasPendingOperation(Registry::OP_CREATE) ?
					RegisterDomainAction_Result::PENDING :
					RegisterDomainAction_Result::OK;          			
        	}
        	catch (Exception $e)
        	{
        		Log::Log($e->getMessage(), E_USER_ERROR);
        		throw new RegisterDomainAction_Exception($e->getMessage());
        	}
        }
        /*
         * Issue invoice for registration
         */
    	else 
    	{
    		$Domain->Status = DOMAIN_STATUS::AWAITING_PAYMENT;
    		try
    		{
    			DBDomain::GetInstance()->Save($Domain);
    		} 
    		catch (Exception $e)
    		{
    			Log::Log($e->getMessage(), E_USER_ERROR);	    			
    			throw new RegisterDomainAction_Exception(sprintf(_('Cannot save domain. Reason: %s'),
    				$e->getMessage()));
    		}
    		
    		try
    		{		    			    
                $Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_CREATE, $Domain->ID, $Domain->UserID);
                $Invoice->Description = sprintf(_("%s domain name registration for %s year(s)"), 
                	$Domain->GetHostName(), $Domain->Period);
                if ($this->Order)
                {
                	// In case of order add invoice.
                	$this->Order->AddInvoice($Invoice);
                	// Save operation must be called from order                		
                }
                else
                {
                	$Invoice->Save();
                	$this->Invoice = $Invoice;	
                }
                return RegisterDomainAction_Result::INVOICE_GENERATED;
    		}
    		catch(Exception $e)
    		{
    			throw new RegisterDomainAction_Exception(sprintf(_("Cannot create invoice. Reason: %s"), 
    				$e->getMessage()));
    		}
    	}   
	}
	
	public function GetInvoice ()
	{
		return $this->Invoice;
	}
	
	private function ExceptionOnErrors (ErrorList $ErrList, $message=null, $code=null)
	{
		if ($ErrList->HasMessages())
		{
			if (!$message)
			{
				$message = _("Cannot register domain");
			}
			$e = new RegisterDomainAction_Exception($message);
			$e->ErrorList = $ErrList;
			throw $e;
		}
	}
	
	
}

class RegisterDomainAction_Exception extends Exception 
{
	public $ErrorList;
}


?>