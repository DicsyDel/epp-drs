<?php

	/**
	 * Global application actions observer interface 
	 * @category EPP-DRS
	 * @package Common
	 * @sdk
	 */
	interface IGlobalObserver
	{
		//
		// Client management events
		//
		
		/**
		 * Method is called when new client created
		 *
		 * @param Client $Client
		 */
		public function OnClientCreated(Client $Client);
		
		/**
		 * Method is called when client updated
		 *
		 * @param Client $OldClient
		 * @param Client $NewClient
		 */
		public function OnClientUpdated(Client $OldClient, Client $NewClient);
		
		/**
		 * Method is called when client deleted
		 *
		 * @param Client $Client
		 */
		public function OnClientDeleted(Client $Client);
		
		
		//
		// Authorization events
		//
		
		/**
		 * Method called when user try to login
		 *
		 * @param string $login
		 * @param string $password Password hash (SHA256 method)
		 */
		public function OnLoginAttempt($login, $password);
		
		/**
		 * Method called when user successfully logeed in
		 *
		 * @param string $login
		 * @param string $password Password hash (SHA256 method)
		 */
		public function OnLoginSuccess($login, $password);
		
		/**
		 * Method called when user successfully logeed in
		 *
		 * @param string $login
		 */
		public function OnLogout($login);
		
		//
		// Domains events
		//
		public function OnDomainAboutToExpire (Domain $Domain, $days_before_expiration);
	}
?>