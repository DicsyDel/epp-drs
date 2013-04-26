<?
	/**
	 * Domain status constants
	 * 
	 * @package Modules
	 * @subpackage RegistryModules
	 * @sdk
	 */
	final class DOMAIN_STATUS
	{
		/**
		 * Invoice issued for this domain was not yet paid.
		 *
		 */
		const AWAITING_PAYMENT = "Awaiting payment";
		
		/**
		 * Invoice was paid, but domain was not yet registered or transferred. Additional actions required from buyer.
		 *
		 */
		const PENDING = "Pending";
		
		/**
		 * Domain is waiting for registry approval.
		 *
		 */
		const REGISTRATION_PENDING = "Registration pending";
		
		/**
		 * Not being used now
		 *
		 */
		const PROCESSING = "Processing";
		
		/**
		 * Domain succesfully registered and is active.
		 *
		 */
		const DELEGATED = "Delegated";
		
		/**
		 * Domain registration attempt failed. Domain cannot be registered.
		 *
		 */
		const REGISTRATION_FAILED = "Registration failed";
		
		/**
		 * Domain transfer was rejected by current owner or registry.
		 *
		 */
		const REJECTED = "Rejected";
		
		/**
		 * For future use
		 *
		 */
		const APPLICATION_PENDING = "Application pending";
		
		/**
		 * For future use
		 *
		 */
		const PENDING_TRANSFER = "Pending transfer";
		
		/**
		 * For future use
		 *
		 */
		const PENDING_RENEWAL = "Pending renewal";
		
		/**
		 * We are waiting for current domain owner to approve domain transfer.
		 */
		const AWAITING_TRANSFER_AUTHORIZATION = "Awaiting transfer authorization";
		
		/**
		 * Domain transfer failed.
		 *
		 */
		const TRANSFER_FAILED = "Transfer failed";
		
		/**
		 * Domain has been transferred.
		 *
		 */
		const TRANSFERRED = "Transferred";
		
		/**
		 * Another registrar requested domain transfer to him. You must either approve or decline transfer.
		 *
		 */
		const TRANSFER_REQUESTED = "Transfer requested";
		
		/**
		 * Domain expired. It some cases it still can be renewed for additional fee. For this you my want to contact registry directly.
		 *
		 */
		const EXPIRED = "Expired";
		
		/**
		 * Domain registration recalled by registrant
		 *
		 */
		const APPLICATION_RECALLED = "Application recalled";
		
		/**
		 * Domain deletion initialized. Registry will remove domain soon. 
		 *
		 */
		const PENDING_DELETE = "Pending delete";
		
		/**
		 * Domain is in pre-registration queue
		 *
		 */
		const AWAITING_PREREGISTRATION = "Awaiting preregistration";
		
		
		/**
		 * Domain is in pre-registration queue
		 *
		 */
		const PREREGISTRATION_DELEGATED = "Preregistration delegated";
	}
?>