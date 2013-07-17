<?php

	if (!class_exists('RESTObserver'))
		PHPParser::SafeLoadPHPFile('events/includes/class.RESTObserver.php');

	class RESTGlobalObserver extends RESTObserver implements IGlobalObserver, IConfigurable
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
		
		/**
		 * Method is called when new client created
		 *
		 * @param Client $Client
		 */
		public function OnClientCreated(Client $Client)
		{
			$this->Request('OnClientCreated', array('Client' => $Client->ToArray()));
		}
		
		/**
		 * Method is called when client updated
		 *
		 * @param Client $OldClient
		 * @param Client $NewClient
		 */
		public function OnClientUpdated(Client $OldClient, Client $NewClient)
		{
			$this->Request('OnClientUpdated', array('OldClient' => $OldClient->ToArray(), 'NewClient' => $NewClient->ToArray()));
		}
		
		/**
		 * Method is called when client deleted
		 *
		 * @param Client $Client
		 */
		public function OnClientDeleted(Client $Client)
		{
			$this->Request('OnClientDeleted', array('Client' => $Client->ToArray()));
		}
				
		/**
		 * Method called when user try to login
		 *
		 * @param string $login
		 * @param string $password Password hash (SHA256 method)
		 */
		public function OnLoginAttempt($login, $password)
		{
			$this->Request('OnLoginAttempt', array('login' => $login, 'password' => $password));
		}
		
		/**
		 * Method called when user successfully logeed in
		 *
		 * @param string $login
		 * @param string $password Password hash (SHA256 method)
		 */
		public function OnLoginSuccess($login, $password)
		{
			$this->Request('OnLoginSuccess', array('login' => $login, 'password' => $password));
		}
		
		/**
		 * Method called when user successfully logeed in
		 *
		 * @param string $login
		 */
		public function OnLogout($login)
		{
			$this->Request('OnLogout', array('login' => $login));
		}
		
		public function OnDomainAboutToExpire (Domain $Domain, $days_before_expiration)
		{
			$this->Request('OnDomainAboutToExpire', array(
				'Domain' => $Domain->ToArray(), 
				'days_before_expiration' => $days_before_expiration
			));
		} 
	}
?>