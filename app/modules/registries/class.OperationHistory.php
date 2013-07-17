<?php
	
	
	/**
	 * @name HISTORY_OBJECT_TYPE
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @sdk-doconly
	 * @author Marat Komarov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	final class HISTORY_OBJECT_TYPE
	{
		const DOMAIN = "DOMAIN";
		const CONTACT = "CONTACT";
		const HOST = "HOST";
	}

	final class HISTORY_OP
	{
		const CREATE = "CREATE";
		const UPDATE = "UPDATE";
		const DELETE = "DELETE";
		const TRADE = "TRADE";
		const TRANSFER_REQUEST = "TRANSFER-REQUEST";
		const TRANSFER_APPROVE = "TRANSFER-APPROVE";
		const TRANSFER_DECLINE = "TRANSFER-DECLINE";
		const RENEW = "RENEW";
	}
	
	/**
	 * @name HISTORY_OP_STATE
	 * @category   EPP-DRS
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @sdk-doconly
	 * @author Marat Komarov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	final class HISTORY_OP_STATE
	{
		const INPROGRESS = 0;
		const COMPLETED = 1;
	}
	
	/**
	 * Logs all operations on objects in objects history log.
	 * @name OperationHistory
	 * @category   EPP-DRS
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @sdk-doconly
	 * @author Marat Komarov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	final class OperationHistory extends RegistryObserverAdapter
	{
		private $DB;
		
		function __construct()
		{
			$this->DB = Core::GetDBInstance();
		}
		
		/**
		 * HOST Object
		 */
		public function OnNameserverHostCreated (NameserverHost $nshost) 
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::HOST,
									HISTORY_OP::CREATE,
									$nshost->HostName,
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		
		public function OnBeforeUpdateNameserverHost (NameserverHost $newNSHost, NameserverHost $oldNSHost)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::HOST,
									HISTORY_OP::UPDATE,
									$oldNSHost->HostName,
									HISTORY_OP_STATE::INPROGRESS,
									$oldNSHost
								  );
		}
		
		public function OnNameserverHostUpdated (NameserverHost $nshost)
		{
			$this->CompleteHistoryEntry(HISTORY_OBJECT_TYPE::HOST,
										HISTORY_OP::UPDATE,
										$nshost->HostName,
										$nshost
									   );
		}
		
		public function OnNameserverHostDeleted (NameserverHost $nshost)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::HOST,
									HISTORY_OP::DELETE,
									$nshost->HostName,
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		/**
		 * CONTACT Object
		 */
		public function OnContactCreated (Contact $contact)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::CONTACT,
									HISTORY_OP::CREATE,
									$contact->CLID,
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		
		public function OnBeforeUpdateContact (Contact $newContact, Contact $oldContact)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::CONTACT,
									HISTORY_OP::UPDATE,
									$oldContact->CLID,
									HISTORY_OP_STATE::INPROGRESS,
									$oldContact
								  );
		}
		
		public function OnContactUpdated (Contact $contact)
		{
			$this->CompleteHistoryEntry(HISTORY_OBJECT_TYPE::CONTACT,
										HISTORY_OP::UPDATE,
										$contact->CLID,
										$contact
									   );
		}
		
		public function OnContactDeleted (Contact $contact)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::CONTACT,
									HISTORY_OP::DELETE,
									$contact->CLID,
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		
		/**
		 * DOMAIN Object 
		 */
		
		function OnDomainCreated(Domain $domain)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::DOMAIN,
									HISTORY_OP::CREATE,
									$domain->GetHostName(),
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		
		function OnBeforeChangeDomainOwner(Domain $newDomain, Domain $oldDomain, $period)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::DOMAIN,
									HISTORY_OP::TRADE,
									$oldDomain->GetHostName(),
									HISTORY_OP_STATE::INPROGRESS,
									$oldDomain
								  );
		}
		
		function DomainOwnerChanged(Domain $domain, $period)
		{
			$this->CompleteHistoryEntry(HISTORY_OBJECT_TYPE::DOMAIN,
										HISTORY_OP::TRADE,
										$domain->GetHostName(),
										$domain
									   );
		}
		
		function OnBeforeUpdateDomain (Domain $newDomain, Domain $oldDomain)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::DOMAIN,
									HISTORY_OP::UPDATE,
									$oldDomain->GetHostName(),
									HISTORY_OP_STATE::INPROGRESS,
									$oldDomain
								  );
		}
		
		function OnDomainUpdated(Domain $domain)
		{
			$this->CompleteHistoryEntry(HISTORY_OBJECT_TYPE::DOMAIN,
										HISTORY_OP::UPDATE,
										$domain->GetHostName(),
										$domain
									   );
		}
		
		function OnDomainRenewed(Domain $domain)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::DOMAIN,
									HISTORY_OP::RENEW,
									$domain->GetHostName(),
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		
		public function OnDomainDeleted (Domain $domain)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::DOMAIN,
									HISTORY_OP::DELETE,
									$domain->GetHostName(),
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		
		public function OnDomainOwnerChanged(Domain $domain, $period)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::DOMAIN,
									HISTORY_OP::TRADE,
									$domain->GetHostName(),
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		
		public function OnDomainTransferDeclined (Domain $domain)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::DOMAIN,
									HISTORY_OP::TRANSFER_DECLINE,
									$domain->GetHostName(),
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		
		public function OnDomainTransferApproved (Domain $domain)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::DOMAIN,
									HISTORY_OP::TRANSFER_APPROVE,
									$domain->GetHostName(),
									HISTORY_OP_STATE::COMPLETED
								  );
		}
		
		public function OnDomainTransferRequested (Domain $domain)
		{
			$this->AddHistoryEntry(
									HISTORY_OBJECT_TYPE::DOMAIN,
									HISTORY_OP::TRANSFER_REQUEST,
									$domain->GetHostName(),
									HISTORY_OP_STATE::COMPLETED
								  );
		}
				
		private function CompleteHistoryEntry($object_type, $operation_type, $object_name, $after_update = null)
		{
			$this->DB->Execute("UPDATE 
										objects_history 
									SET
										state			= ?,
										after_update	= ?
									WHERE
								 		object	= ? AND
								 		type	= ? AND
								 		operation = ?
							   ", array(
										HISTORY_OP_STATE::COMPLETED,
										serialize($after_update),
										$object_name,
										$object_type,
										$operation_type
										)
								);
		}
		
		private function AddHistoryEntry($object_type, $operation_type, $object_name, $state, $before_update = null)
		{
			try
			{
				$this->DB->Execute("INSERT INTO 
											objects_history 
										SET
											type		= ?,
											object		= ?,
											operation	= ?,
											state		= ?,
											before_update	= ?,
											transaction_id	= ?,
											dtadded		= NOW()
							   		", array(
												$object_type, 
												$object_name, 
												$operation_type, 
												$state,
												serialize($before_update),
												TRANSACTION_ID
											)
									);
			}
			catch(Exception $e)
			{
				Log::Log(sprintf("Cannot add entry into history: %s", $e->getMessage()), E_ERROR);
			}
		}
	}
?>