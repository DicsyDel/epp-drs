<?php

	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @package Modules
     * @subpackage RegistryModules
     */

	/**
	 * Each registry module must implement IRegistryModule and one of pollable interfaces:
	 * IRegistryModuleServerPollable or IRegistryModuleClientPollable 
	 * @name IRegistryObserver
	 * @package    Common
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 * @author Marat Komarov <http://webta.net/company.html> 
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	/**
	 * @name RegistryObserver
	 * @package    Modules
	 * @author Marat Komarov <http://webta.net/company.html> 
	 */
	
	/**
	 * Observer interface for registry
	 */
	interface IRegistryObserver
	{
		/**
		 * @ignore 
		 */
		public function OnDomainOperation (Domain $domain, $optype, $failed=false, $errmsg=null);	
		
		/**
		 * Method is called before CreateDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeCreateDomain (Domain $domain);
		
		/**
		 * Method is called when domain was created and become delegated
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainCreated (Domain $domain);
		
		/**
		 * Method is called before UpdateDomainContact, UpdateDomainNamservers, UpdateDomainNameservers requests
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeUpdateDomain (Domain $newDomain, Domain $oldDomain);
		
		/**
		 * Method is called before UpdateDomainContact request
		 *
		 * @param Domain $domain
		 * @param string $contactType
		 * @param Contact $oldContact
		 * @param Contact $newContact
		 */
		public function OnBeforeUpdateDomainContact (Domain $domain, $contactType, Contact $oldContact = null, Contact $newContact = null);
		
		/**
		 * Method is called before UpdateDomainNamservers request
		 *
		 * @param Domain $domain
		 * @param IChangelist $changes namservers changelist
		 */
		public function OnBeforeUpdateDomainNameservers (Domain $domain, IChangelist $changes);
		
		/**
		 * Method is called before UpdateDomainFlags request
		 *
		 * @param Domain $domain
		 * @param IChangelist $changes flags changelist
		 */
		public function OnBeforeUpdateDomainFlags (Domain $domain, IChangelist $changes);
		
		/**
		 * Method is called when a piece of domain specific information was updated 
		 * (contacts, nameserver, flags)
		 *
		 * @param Domain $domain  Domain object
		 */
		public function OnDomainUpdated (Domain $domain);
		
		/**
		 * Method is called before RenewDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeRenewDomain (Domain $domain);
		
		/**
		 * Method is called when domain was renewed
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainRenewed (Domain $domain);
		
		/**
		 * Method is called before TransferRequest request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeTransferRequest (Domain $domain);
		
		/**
		 * Method is called when domain transfer was requested
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferRequested (Domain $domain);
		
		/**
		 * Method is called when someone requested outgoing transfer
		 * @param $domain
		 */
		public function OnDomainOutgoingTransferRequested (Domain $domain);
		
		public function OnDomainTransferredAway (Domain $domain);
	
		//public function OnDomainTransferRejected (Domain $domain);
		
		/**
		 * Method is called when domain transfer was approved by registry
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferApproved (Domain $domain);
		
		/**
		 * Method is called when domain transfer was declined by registry
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferDeclined (Domain $domain);
		
		/**
		 * Method is called when registry was unable to transfer domain
		 * or transfer timeout exceed
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferFailed (Domain $domain);
	
		/**
		 * Method is called before ChangeDomainOwner request
		 *
		 * @param Domain $domain
		 * @param int $period Delegation period
		 */
		public function OnBeforeChangeDomainOwner (Domain $newDomain, Domain $oldDomain, $period);
		
		/**
		 * Method is called when domain owner was changed (Trade request completed)
		 *
		 * @param Domain $domain Domain object
		 * @param int $period Domain delegation period
		 */
		public function OnDomainOwnerChanged (Domain $domain, $period);
		
		/**
		 * Method is called before UnlockDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeUnlockDomain (Domain $domain);
		
		/**
		 * Method is called when domain was unlocked
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainUnlocked (Domain $domain);
		
		/**
		 * Method is called before LockDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeLockDomain (Domain $domain);
		
		/**
		 * Method is called when domain was locked
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainLocked (Domain $domain);
		
		/**
		 * Method is called before DeleteDomain request
		 * 
		 * @param Domain $domain
		 */
		public function OnBeforeDeleteDomain (Domain $domain);
		
		/**
		 * Method is called when domain was deleted
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainDeleted (Domain $domain);
		
		// Contacts
		
		/**
		 * Method is called before CreateContact request
		 *
		 * @param Contact $contact
		 */
		public function OnBeforeCreateContact (Contact $contact);
		
		/**
		 * Method is called when contact was created
		 *
		 * @param Contact $contact
		 */
		public function OnContactCreated (Contact $contact);
		
		/**
		 * Method is called before UpdateContact request
		 *
		 * @param Contact $contact
		 */
		public function OnBeforeUpdateContact (Contact $newContact, Contact $oldContact);
		
		/**
		 * Method is called when contact was updated
		 *
		 * @param Contact $contact
		 */
		public function OnContactUpdated (Contact $contact);
		
		/**
		 * Method is called before DeleteContact request
		 *
		 * @param Contact $contact
		 */
		public function OnBeforeDeleteContact (Contact $contact);	
		
		/**
		 * Method is called when contact was deleted
		 *
		 * @param Contact $contact
		 */
		public function OnContactDeleted (Contact $contact);
		
		// Nameservers
		
		/**
		 * Method is called before CreateNamserverHost request
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnBeforeCreateNameserverHost (NameserverHost $nshost);
		
		/**
		 * Method is called when namserver host was created
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnNameserverHostCreated (NameserverHost $nshost);
	
		/**
		 * Method is called before UpdateNamserverHost request
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnBeforeUpdateNameserverHost (NameserverHost $newNSHost, NameserverHost $oldNSHost);
		
		/**
		 * Method is called when namserver host was updated
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnNameserverHostUpdated (NameserverHost $nshost);
		
		/**
		 * Method is called before DeleteNamserverHost request
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnBeforeDeleteNameserverHost (NameserverHost $nshost);
		
		/**
		 * Method is called when namserver host was deleted
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnNameserverHostDeleted (NameserverHost $nshost);
	} 
	
	/**
	 * Observer adapter for registry 
	 */
	class RegistryObserverAdapter implements IRegistryObserver
	{
		/**
		 * @ignore 
		 */
		public function OnDomainOperation (Domain $domain, $optype, $failed=false, $errmsg=null) {}
		
		/**
		 * Method is called before CreateDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeCreateDomain (Domain $domain) {}
		
		/**
		 * Method is called when domain was created and become delegated
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainCreated (Domain $domain) {}
		
		/**
		 * Method is called before UpdateDomainContact, UpdateDomainNamservers, UpdateDomainNameservers requests
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeUpdateDomain (Domain $newDomain, Domain $oldDomain) {}
		
		/**
		 * Method is called before UpdateDomainContact request
		 *
		 * @param Domain $domain
		 * @param string $contactType
		 * @param Contact $oldContact
		 * @param Contact $newContact
		 */
		public function OnBeforeUpdateDomainContact (Domain $domain, $contactType, Contact $oldContact = null, Contact $newContact = null) {}
		
		/**
		 * Method is called before UpdateDomainNamservers request
		 *
		 * @param Domain $domain
		 * @param IChangelist $changes namservers changelist
		 */
		public function OnBeforeUpdateDomainNameservers (Domain $domain, IChangelist $changes) {}
		
		/**
		 * Method is called before UpdateDomainFlags request
		 *
		 * @param Domain $domain
		 * @param IChangelist $changes flags changelist
		 */
		public function OnBeforeUpdateDomainFlags (Domain $domain, IChangelist $changes) {}
		
		/**
		 * Method is called when a piece of domain specific information was updated 
		 * (contacts, nameserver, flags)
		 *
		 * @param Domain $domain  Domain object
		 */
		public function OnDomainUpdated (Domain $domain) {}
		
		/**
		 * Method is called before RenewDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeRenewDomain (Domain $domain) {}
		
		/**
		 * Method is called when domain was renewed
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainRenewed (Domain $domain) {}
		
		/**
		 * Method is called before TransferRequest request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeTransferRequest (Domain $domain) {}
		
		/**
		 * Method is called when domain transfer was requested
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferRequested (Domain $domain) {}
		
		/**
		 * Method is called when someone requested outgoing transfer
		 * @param $domain
		 */
		public function OnDomainOutgoingTransferRequested (Domain $domain) {}
		
		public function OnDomainTransferredAway (Domain $domain) {}
		
		//public function OnDomainTransferRejected (Domain $domain) {}
		
		/**
		 * Method is called when domain transfer was approved by registry
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferApproved (Domain $domain) {}
		
		/**
		 * Method is called when domain transfer was declined by registry
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferDeclined (Domain $domain) {}
		
		/**
		 * Method is called when registry was unable to transfer domain
		 * or transfer timeout exceed
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferFailed (Domain $domain) {}
	
		/**
		 * Method is called before ChangeDomainOwner request
		 *
		 * @param Domain $domain
		 * @param int $period Delegation period
		 */
		public function OnBeforeChangeDomainOwner (Domain $newDomain, Domain $oldDomain, $period) {}
		
		/**
		 * Method is called when domain owner was changed (Trade request completed)
		 *
		 * @param Domain $domain Domain object
		 * @param int $period Domain delegation period
		 */
		public function OnDomainOwnerChanged (Domain $domain, $period) {}
		
		/**
		 * Method is called before UnlockDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeUnlockDomain (Domain $domain) {}
		
		/**
		 * Method is called when domain was unlocked
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainUnlocked (Domain $domain) {}
		
		/**
		 * Method is called before LockDomain request
		 *
		 * @param Domain $domain
		 */
		public function OnBeforeLockDomain (Domain $domain) {}
		
		/**
		 * Method is called when domain was locked
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainLocked (Domain $domain) {}
		
		/**
		 * Method is called before DeleteDomain request
		 * 
		 * @param Domain $domain
		 */
		public function OnBeforeDeleteDomain (Domain $domain) {}
		
		/**
		 * Method is called when domain was deleted
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainDeleted (Domain $domain) {}
		
		// Contacts
		
		/**
		 * Method is called before CreateContact request
		 *
		 * @param Contact $contact
		 */
		public function OnBeforeCreateContact (Contact $contact) {}
		
		/**
		 * Method is called when contact was created
		 *
		 * @param Contact $contact
		 */
		public function OnContactCreated (Contact $contact) {}
		
		/**
		 * Method is called before UpdateContact request
		 *
		 * @param Contact $contact
		 */
		public function OnBeforeUpdateContact (Contact $newContact, Contact $oldContact) {}
		
		/**
		 * Method is called when contact was updated
		 *
		 * @param Contact $contact
		 */
		public function OnContactUpdated (Contact $contact) {}
		
		/**
		 * Method is called before DeleteContact request
		 *
		 * @param Contact $contact
		 */
		public function OnBeforeDeleteContact (Contact $contact) {}	
		
		/**
		 * Method is called when contact was deleted
		 *
		 * @param Contact $contact
		 */
		public function OnContactDeleted (Contact $contact) {}
		
		// Nameservers
		
		/**
		 * Method is called before CreateNamserverHost request
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnBeforeCreateNameserverHost (NameserverHost $nshost) {}
		
		/**
		 * Method is called when namserver host was created
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnNameserverHostCreated (NameserverHost $nshost) {}	
	
		/**
		 * Method is called before UpdateNamserverHost request
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnBeforeUpdateNameserverHost (NameserverHost $newNSHost, NameserverHost $oldNSHost) {}
		
		/**
		 * Method is called when namserver host was updated
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnNameserverHostUpdated (NameserverHost $nshost) {}
		
		/**
		 * Method is called before DeleteNamserverHost request
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnBeforeDeleteNameserverHost (NameserverHost $nshost) {}
		
		/**
		 * Method is called when namserver host was deleted
		 *
		 * @param NameserverHost $nshost
		 */
		public function OnNameserverHostDeleted (NameserverHost $nshost) {}
	}

?>
