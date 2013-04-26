<?php

	if (!class_exists('RESTObserver'))
		PHPParser::SafeLoadPHPFile('events/includes/class.RESTObserver.php');

	class RESTRegistryObserver extends RESTObserver implements IRegistryObserver, IConfigurable
	{
		public function __construct (DataForm $Config)
		{
			$this->Config = $Config;
		}

		public static function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->SetInlineHelp("");
			
			$methods = get_class_methods(__CLASS__);
			foreach ($methods as $method)
			{
				if ($method != '__construct' && $method != 'GetConfigurationForm')
					$ConfigurationForm->AppendField( new DataFormField("{$method}URL", FORM_FIELD_TYPE::TEXT, "{$method} URL"));
			}
			
			return $ConfigurationForm;
		}
		
		public function OnDomainOperation (Domain $domain, $optype, $failed=false, $errmsg=null)
		{
			$this->Request('OnDomainOperation', array(
				'domain' => $domain->ToArray(),
				'optype' => $optype,
				'failed' => $failed,
				'errmsg' => $errmsg 
			));
		}
		
		/**
		 * Method is called before CreateDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeCreateDomain (Domain $domain)
		{
			$this->Request('OnBeforeCreateDomain', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called when domain was created and become delegated
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainCreated (Domain $domain)
		{
			$this->Request('OnDomainCreated', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called before UpdateDomainContact, UpdateDomainNamservers, UpdateDomainNameservers requests
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeUpdateDomain (Domain $newDomain, Domain $oldDomain)
		{
			$this->Request('OnBeforeUpdateDomain', array(
				'newDomain' => $newDomain->ToArray(),
				'oldDomain' => $oldDomain->ToArray()
			));
		}
		
		/**
		 * Method is called before UpdateDomainContact request
		 *
		 * @param Domain $domain
		 * @param string $contactType
		 * @param Contact $oldContact
		 * @param Contact $newContact
		 */
		public function OnBeforeUpdateDomainContact (Domain $domain, $contactType, Contact $oldContact = null, Contact $newContact = null)
		{
			$this->Request('OnBeforeUpdateDomainContact', array(
				'domain' => $domain->ToArray(),
				'contactType' => $contactType,
				'oldContact' => $oldContact ? $oldContact->ToArray() : '',
				'newContact' => $newContact ? $newContact->ToArray() : ''
			));
		}
		
		/**
		 * Method is called before UpdateDomainNamservers request
		 *
		 * @param Domain $domain
		 * @param IChangelist $changes namservers changelist
		 */
		public function OnBeforeUpdateDomainNameservers (Domain $domain, IChangelist $changes)
		{
			$this->Request('OnBeforeUpdateDomainNameservers', array(
				'domain' => $domain->ToArray(),
				'changes' => $changes->ToArray()
			));
		}
		
		/**
		 * Method is called before UpdateDomainFlags request
		 *
		 * @param Domain $domain
		 * @param IChangelist $changes flags changelist
		 */
		public function OnBeforeUpdateDomainFlags (Domain $domain, IChangelist $changes)
		{
			$this->Request('OnBeforeUpdateDomainFlags', array(
				'domain' => $domain->ToArray(),
				'changes' => $changes->ToArray()
			));
		}
		
		/**
		 * Method is called when a piece of domain specific information was updated 
		 * (contacts, nameserver, flags)
		 *
		 * @param Domain $domain  Domain object
		 */
		public function OnDomainUpdated (Domain $domain)
		{
			$this->Request('OnDomainUpdated', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called before RenewDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeRenewDomain (Domain $domain)
		{
			$this->Request('OnBeforeRenewDomain', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called when domain was renewed
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainRenewed (Domain $domain)
		{
			$this->Request('OnDomainRenewed', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called before TransferRequest request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeTransferRequest (Domain $domain)
		{
			$this->Request('OnBeforeTransferRequest', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called when someone requested outgoing transfer
		 * @param $domain
		 */
		public function OnDomainOutgoingTransferRequested (Domain $domain) 
		{
			$this->Request('OnDomainOutgoingTransferRequested', array(
				'domain' => $domain->ToArray()
			));
		}

		/**
		 * (non-PHPdoc)
		 * @see app/modules/registries/observers/IRegistryObserver#OnDomainTransferredAway()
		 */
		public function OnDomainTransferredAway (Domain $domain)
		{
			$this->Request("OnOnDomainTransferredAway", array(
				"domain" => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called when domain transfer was requested
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferRequested (Domain $domain)
		{
			$this->Request('OnDomainTransferRequested', array(
				'domain' => $domain->ToArray()
			));
		}
		
		//public function OnDomainTransferRejected (Domain $domain) {}
		
		/**
		 * Method is called when domain transfer was approved by registry
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferApproved (Domain $domain)
		{
			$this->Request('OnDomainTransferApproved', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called when domain transfer was declined by registry
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferDeclined (Domain $domain)
		{
			$this->Request('OnDomainTransferDeclined', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called when registry was unable to transfer domain
		 * or transfer timeout exceed
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferFailed (Domain $domain)
		{
			$this->Request('OnDomainTransferFailed', array(
				'domain' => $domain->ToArray()
			));
		}
	
		/**
		 * Method is called before ChangeDomainOwner request
		 *
		 * @param Domain $domain
		 * @param int $period Delegation period
		 */
		public function OnBeforeChangeDomainOwner (Domain $newDomain, Domain $oldDomain, $period)
		{
			$this->Request('OnBeforeChangeDomainOwner', array(
				'newDomain' => $newDomain->ToArray(),
				'oldDomain' => $oldDomain->ToArray(),
				'period'	=> $period
			));
		}
		
		/**
		 * Method is called when domain owner was changed (Trade request completed)
		 *
		 * @param Domain $domain Domain object
		 * @param int $period Domain delegation period
		 */
		public function OnDomainOwnerChanged (Domain $domain, $period)
		{
			$this->Request('OnDomainOwnerChanged', array(
				'domain' => $domain->ToArray(),
				'period'	=> $period
			));
		}
		
		/**
		 * Method is called before UnlockDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeUnlockDomain (Domain $domain)
		{
			$this->Request('OnBeforeUnlockDomain', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called when domain was unlocked
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainUnlocked (Domain $domain)
		{
			$this->Request('OnDomainUnlocked', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called before LockDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeLockDomain (Domain $domain)
		{
			$this->Request('OnBeforeLockDomain', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called when domain was locked
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainLocked (Domain $domain)
		{
			$this->Request('OnDomainLocked', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called before DeleteDomain request
		 * 
		 * @param Domain $domain
		 */
		public function OnBeforeDeleteDomain (Domain $domain)
		{
			$this->Request('OnBeforeDeleteDomain', array(
				'domain' => $domain->ToArray()
			));
		}
		
		/**
		 * Method is called when domain was deleted
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainDeleted (Domain $domain)
		{
			$this->Request('OnDomainDeleted', array(
				'domain' => $domain->ToArray()
			));
		}
		
		// Contacts
		
		/**
		 * Method is called before CreateContact request
		 *
		 * @param Contact $contact
		 */
		public function OnBeforeCreateContact (Contact $contact)
		{
			$this->Request('OnBeforeCreateContact', array(
				'contact' => $contact->ToArray()
			));
		}
		
		/**
		 * Method is called when contact was created
		 *
		 * @param Contact $contact
		 */
		public function OnContactCreated (Contact $contact)
		{
			$this->Request('OnContactCreated', array(
				'contact' => $contact->ToArray()
			));
		}
		
		/**
		 * Method is called before UpdateContact request
		 *
		 * @param Contact $contact
		 */
		public function OnBeforeUpdateContact (Contact $newContact, Contact $oldContact)
		{
			$this->Request('OnBeforeUpdateContact', array(
				'newContact' => $newContact->ToArray(),
				'oldContact' => $oldContact->ToArray(),
			));
		}
		
		/**
		 * Method is called when contact was updated
		 *
		 * @param Contact $contact
		 */
		public function OnContactUpdated (Contact $contact)
		{
			$this->Request('OnContactUpdated', array(
				'contact' => $contact->ToArray()
			));
		}
		
		/**
		 * Method is called before DeleteContact request
		 *
		 * @param Contact $contact
		 */
		public function OnBeforeDeleteContact (Contact $contact)
		{
			$this->Request('OnBeforeDeleteContact', array(
				'contact' => $contact->ToArray()
			));
		}	
		
		/**
		 * Method is called when contact was deleted
		 *
		 * @param Contact $contact
		 */
		public function OnContactDeleted (Contact $contact)
		{
			$this->Request('OnContactDeleted', array(
				'contact' => $contact->ToArray()
			));
		}
		
		// Nameservers
		
		/**
		 * Method is called before CreateNamserverHost request
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnBeforeCreateNameserverHost (NameserverHost $nshost)
		{
			$this->Request('OnBeforeCreateNameserverHost', array(
				'nshost' => $nshost->ToArray()
			));
		}
		
		/**
		 * Method is called when namserver host was created
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnNameserverHostCreated (NameserverHost $nshost)
		{
			$this->Request('OnNameserverHostCreated', array(
				'nshost' => $nshost->ToArray()
			));
		}
	
		/**
		 * Method is called before UpdateNamserverHost request
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnBeforeUpdateNameserverHost (NameserverHost $newNSHost, NameserverHost $oldNSHost)
		{
			$this->Request('OnBeforeUpdateNameserverHost', array(
				'newNSHost' => $newNSHost->ToArray(),
				'oldNSHost' => $oldNSHost->ToArray()
			));
		}
		
		/**
		 * Method is called when namserver host was updated
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnNameserverHostUpdated (NameserverHost $nshost)
		{
			$this->Request('OnBeforeUpdateNameserverHost', array(
				'nshost' => $nshost->ToArray()
			));
		}
		
		/**
		 * Method is called before DeleteNamserverHost request
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnBeforeDeleteNameserverHost (NameserverHost $nshost)
		{
			$this->Request('OnBeforeDeleteNameserverHost', array(
				'nshost' => $nshost->ToArray()
			));
		}
		
		/**
		 * Method is called when namserver host was deleted
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnNameserverHostDeleted (NameserverHost $nshost)
		{
			$this->Request('OnNameserverHostDeleted', array(
				'nshost' => $nshost->ToArray()
			));
		}
	}
?>