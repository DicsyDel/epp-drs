<?php

	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @category EPP-DRS
     * @package Modules
     * @subpackage RegistryModules
     * @sdk
     */

	/**
	 * Each registry module must implement IRegistryModule and one of pollable interfaces:
	 * IRegistryModuleServerPollable or IRegistryModuleClientPollable 
	 * @name IRegistryModule
	 * @category   EPP-DRS
	 * @package Modules
     * @subpackage RegistryModules
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 * @author Marat Komarov <http://webta.net/company.html> 
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	interface IRegistryModule extends IConfigurable
	{		
		/**
		 * 
		 * Called when module enabled in admin interface.
		 *
		 */
		public function OnModuleEnabled();
		
		/*
		 * 
		 * Called when module disabled in admin interface.
		 */
		public function OnModuleDisabled();
		
		/**
		 * Called by he core to check either user filled all fields of your configuration form properly.
		 * If you return true, all configuration data will be saved in database. If you return array, user will be presented with values of returned array as errors. 
		 *
		 * @param array $post_values
		 * @return True or array of error messages.
		 */
		public static function ValidateConfigurationFormData($post_values);
		
		/**
		 * Must return current Registrar ID (CLID). Generally, you can return registrar login here.
		 * Used in transfer and some contact operations to determine either object belongs to current registrar.
		 *
		 * @return string
		 */
		public function GetRegistrarID();
		
		
		/**
	     * Called to check either domain can be transferred at this time.
	     *
	     * @param Domain $domain
	     * @return DomainCanBeTransferredResponse
	     */
	    public function DomainCanBeTransferred(Domain $domain);
	    
	    
	    /**
	     * Send domain trade (change of the owner) request.
	     * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.  
	     * 
	     * @param Domain $domain Domain must have contacts and nameservers 
	     * @param integer $period Domain delegation period
	     * @param array $extra Some registry specific fields 
	     * @return ChangeDomainOwnerResponse
	     */
	    public function ChangeDomainOwner(Domain $domain, $period=null, $extra=array());

	    
	    /**
	     * Lock Domain
	     *
	     * @param Domain $domain
	     * @param array $extra Some registry specific fields 
	     * @return LockDomainResponse
	     */
	    public function LockDomain(Domain $domain, $extra = array());
	    
	    /**
	     * Unlock Domain
	     *
	     * @param Domain $domain
	     * @param array $extra Some extra data
	     * @return UnLockDomainResponse
	     */
	    public function UnlockDomain(Domain $domain, $extra = array());
	    
	    /**
	     * Update domain flags.
	     * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollUpdateDomain().
	     *
	     * @param Domain $domain
	     * @param IChangelist $changes A list of changes in domain flags for the domain
	     * @return UpdateDomainFlagsResponse
	     */
	    public function UpdateDomainFlags(Domain $domain, IChangelist $changes);
	    
	    /**
	     * Update domain auth code.
	     *
	     * @param Domain $domain
	     * @param string $authcode A list of changes in domain flags for the domain
	     * @return UpdateDomainAuthCodeResponse
	     */
	    public function UpdateDomainAuthCode(Domain $domain, $authCode);
	    
	    /**
		 * Register domain.
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollCreateDomain().
		 *	 
		 * @param Domain $domain
		 * @param int $period Domain registration period
		 * @param array $extra Extra fields
		 * @return CreateDomainResponse
		 */
		public function CreateDomain(Domain $domain, $period, $extra = array());
		
		/**
		 * Obtain information about specific domain from registry   
		 * 
		 * @param Domain $domain 
		 * @return GetRemoteDomainResponse
		 */
		public function GetRemoteDomain(Domain $domain);
		
		/**
		 * Swap domain's existing contact with another one
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollCreateDomain().
		 * 
		 * @param Domain $domain Domain
		 * @param string $contactType contact type. Should be one of CONTACT_TYPE members.
		 * @param Contact $oldContact Old contact or NULL
		 * @param Contact $newContact
		 * @return UpdateDomainContactResponse
		 */
		public function UpdateDomainContact(Domain $domain, $contactType, Contact $oldContact, Contact $newContact);
		
		/**
		 * Change nameservers for specific domain 
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollUpdateDomain().
		 * 
		 * @param Domain $domain Domain
		 * @param IChangelist $changelist Changes in a list of nameservers 
		 * @return UpdateDomainNameserversResponse
		 */
		public function UpdateDomainNameservers(Domain $domain, IChangelist $changelist);	
		
		/**
		 * Called to check either domain can be registered
		 * 
		 * @param Domain $domain Domain
		 * @return DomainCanBeRegisteredResponse
		 */
		public function DomainCanBeRegistered(Domain $domain);

	
		
		/**
		 * Completely delete domain from registry if it is delegated or  
		 * recall domain name application if it was not yet delegated.
		 * @param Domain $domain Domain
		 * @param int $executeDate Unix timestamp for scheduled delete. Null for immediate delete.
		 * @return DeleteDomainResponse
		 * @throws ProhibitedTransformException 
		 */
		public function DeleteDomain(Domain $domain, $executeDate=null);
		
		/**
		 * Send renew domain request
		 *
		 * @param string $domain Domain
		 * @param array $extradata Extra fields
		 * @return RenewDomainResponse
		 */
		public function RenewDomain(Domain $domain, $extra=array());
	
		/**
		 * Send a request for domain transfer
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollTransfer().
		 * 
		 * @param string $domain Domain
		 * @param array $extradata Extra fields
		 * @return TransferRequestResponse
		 */	
		public function TransferRequest(Domain $domain, $extra=array());
		
		/**
		 * Approve domain transfer
		 * In order to pending operation, response must have status REGISTRY_RESPONSE_STATUS::PENDING
		 *
		 * @param string $domain Domain
		 * @param array $extradata Extra fields
		 * @return TransferApproveResponse
		 */
		public function TransferApprove(Domain $domain, $extra=array());
		
		/**
		 * Reject domain transfer
		 *
		 * @param string $domain Domain
		 * @param array $extradata Extra fields
		 * @return TransferRejectResponse
		 */
		public function TransferReject(Domain $domain, $extra=array());		
		
		/**
		 * Check either this nameserver is a valid nameserver.
		 * 
		 * @param Nameserver $ns
		 * @return NameserverCanBeCreatedResponse
		 */
		public function NameserverCanBeCreated(Nameserver $ns);
		
		/**
		 * Create namserver
		 * 
		 * @param Nameserver $ns
		 * @return CreateNameserverResponse
		 */
		public function CreateNameserver (Nameserver $ns);
		
		/**
		 * Create nameserver host (Nameserver derived from our own domain)
		 * 
		 * @param NameserverHost $nshost
		 * @return CreateNameserverHostResponse
		 */
		public function CreateNameserverHost (NameserverHost $nshost);
		
		/**
		 * Update nameserver host
		 * 
		 * @param NameserverHost $ns
		 * @return UpdateNameserverHostResponse 
		 */
		public function UpdateNameserverHost(NameserverHost $ns);
		
		/**
		 * Delete namserver host from registry
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollDeleteNameserverHost().
		 * 
		 * @param NameserverHost $ns
		 * @return DeleteNameserverHostResponse 
		 * @throws ProhibitedTransformException 
		 */
		public function DeleteNameserverHost(NameserverHost $ns);
		
		/**
		 * Called to check either specific contact can be created 
		 * 
		 * @param Contact $contact
		 * @return ContactCanBeCreatedResponse 
		 */
		public function ContactCanBeCreated(Contact $contact);
	
		/**
		 * Create contact
		 * 
		 * @param Contact $contact
		 * @return CreateContactResponse
		 */
		public function CreateContact(Contact $contact, $extra=array());
		
		/**
		 * Must return detailed information about contact from registry
		 * @access public
		 * @param Contact $contact
		 * @version GetRemoteContactResponse
		 */
		public function GetRemoteContact(Contact $contact);
			
		/**
		 * Update contact fields
		 * 
		 * @param Contact $contact
		 * @return UpdateContactResponse
		 */
		public function UpdateContact(Contact $contact);
	
		/**
		 * Delete contact
		 * 
		 * This operation supports pending status. If you return response object with Status = REGISTRY_RESPONSE_STATUS.PENDING, you must return response later during a poll.
	     * See IRegistryModuleClientPollable::PollDeleteContact().
		 *
		 * @param Contact $contact
		 * @param array $extra Extra fields
		 * @return DeleteContactResponse
		 * @throws ProhibitedTransformException
		 */
		public function DeleteContact(Contact $contact, $extra = array());
	}
	
	
	/**
	 * Your module must implement this interface if registry does support automated polling.
	 * ReadMessage() will be called until it returns false. 
	 * @name IRegistryModuleServerPollable
	 * @category   EPP-DRS
	 * @package Modules
     * @subpackage RegistryModules
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 * @author Marat Komarov <http://webta.net/company.html> 
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	interface IRegistryModuleServerPollable extends IRegistryModule
	{
		/**
		 * Read server message queue, and return first item that was not yet acknowledged.
		 * EPP-DRS will then call IRegistryModuleServerPollable::AcknowledgeMessage() and pass your response in it as parameter.
		 * 
		 * @return PendingOperationResponse or False, if queue is empty.
		 */
		public function ReadMessage ();
	
		/**
		 * Send message acknowledgement to server
		 *
		 * @param PendingOperationResponse $resp_message Object returned by IRegistryModuleServerPollable::ReadMessage() 
		 */
		public function AcknowledgeMessage (PendingOperationResponse $resp_message);
	}
	
	
	/**
	 * Your module must implement this interface if registry does not support automated polling.
	 * In this case, EPP-DRS will call various methods of this Interface to check the status of deferred operations.
	 * @name IRegistryModuleClientPollable
	 * @category   EPP-DRS
	 * @package    Common
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 * @author Marat Komarov <http://webta.net/company.html> 
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	interface IRegistryModuleClientPollable extends IRegistryModule
	{
		/**
		 * Called by the system if CreateDomainResponse->Status was REGISTRY_RESPONSE_STATUS::PENDING in IRegistryModule::CreateDomainResponse() 
		 * EPP-DRS calls this method to check the status of domain registration operation.
		 *  
		 * Must return one of the following:
		 * 1. An object of type DomainCreatedResponse if operation is completed 
		 * 2. PollCreateDomainResponse with Status set to REGISTRY_RESPONSE_STATUS::PENDING, if domain creation is still in progress
		 * 
		 * @param Domain $domain
		 * @return PollCreateDomainResponse
		 */
		public function PollCreateDomain (Domain $domain);
		
		
		/**
		 * EPP-DRS calls this method to check the status of DeleteDomain() operation.
		 *  
		 * Must return one of the following:
		 * 1. An object of type DeleteDomainResponse if operation is completed. 
		 * 2. DeleteDomainResponse with Status set to REGISTRY_RESPONSE_STATUS::PENDING, if domain creation is still in progress
		 *  
		 * @param Domain $domain
		 * @return PollDeleteDomainResponse
		 */
		public function PollDeleteDomain (Domain $domain);
		
		/**
		 * Called by system when change domain owner operation is pending.
		 * Must return valid DomainOwnerChangedResponse if operatation is completed, 
		 * or response with Status = REGISTRY_RESPONSE_STATUS::PENDING if operation is still in progress
		 * 
		 * @param Domain $domain
		 * @return PollChangeDomainOwnerResponse
		 */
		public function PollChangeDomainOwner (Domain $domain);
	
		/**
		 * Called by system when domain transfer operation is pending.
		 * Must return valid PollDomainTransfered on operatation is completed, 
		 * or response with Status = REGISTRY_RESPONSE_STATUS::PENDING if operation is still in progress
		 * 
		 * @param Domain $domain
		 * @return PollTransferResponse
		 */
		public function PollTransfer (Domain $domain);
		
		/**
		 * Called by system when update domain operation is pending.
		 * Must return valid DomainUpdatedResponse on operatation is completed, 
		 * or response with Status = REGISTRY_RESPONSE_STATUS::PENDING if update is still in progress
		 * 
		 * @param Domain $domain
		 * @return PollUpdateDomainResponse
		 */
		public function PollUpdateDomain (Domain $domain);
		
		/**
		 * Called by system when delete contact operation is pending
		 *
		 * @param Contact $contact
		 * @return PollDeleteContactResponse
		 */
		public function PollDeleteContact (Contact $contact);
		
		/**
		 * Called by system when delete nameserver host operation is pending
		 *
		 * @param NamserverHost $nshost
		 * @return PollDeleteNamserverHostResponse
		 */
		public function PollDeleteNamserverHost (NamserverHost $nshost);
	}
?>