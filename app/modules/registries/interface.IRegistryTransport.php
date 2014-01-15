<?php

	/**
	 * @name RegistryTransport
	 * @category   EPP-DRS
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @sdk
	 * @author Igor Savchenko <http://webta.net/company.html> 
	 * @author Marat Komarov <http://webta.net/company.html>
	 */
	
	/**
	 * Classes which implements this interface provide methods that works on network level. 
	 * They provide a connection to registry, sends request and retrive response
	 */
	interface IRegistryTransport
	{
		/**
		 * Establish connection with remote registry
		 * 
		 * @return bool True on success
		 * @throws Exception 
		 */
		function Connect ();
		
		/**
		 * This method must login to remote registry
		 *
		 * @return bool True on success
		 * @throws Exception
		 */
		function Login ();
		
		/**
		 * This method performs request to remote registry  
		 *
		 * @param string $command Registry command
		 * @param array $data Command dependent data
		 * @return TransportResponse
		 */
		function Request ($command, $data = array());
		
		/**
		 * This method close connection with remote registry.
		 * (Send logout request, close socket or something else implementation specific)
		 * 
		 * @return bool 
		 */
		function Disconnect ();
		
		/**
		 * Returns True if transport is connected to remote registry
		 *  
		 * @return bool
		 */
		function IsConnected();
	}
	
	/**
	 * Transport response
	 */
	class TransportResponse
	{
		/**
		 * Remote registry response code
		 *
		 * @var int
		 * @property-read
		 */
		public $Code;
		
		/**
		 * Response data
		 *
		 * @var mixed
		 * @property-read 
		 */
		public $Data;
		
		/**
		 * Success response flag
		 *
		 * @var bool
		 * @property-read 
		 */
		public $Succeed;
		
		/**
		 * Remote registry error 
		 *
		 * @var string
		 * @property-read 
		 */
		public $RegistryErrorMessage;
		
		public $ErrMsg;
		
		function __construct ($status_code, $data, $is_success, $RegistryErrorMessage)
		{
			$this->Code = $status_code;
			$this->Data = $data;
			$this->Succeed = $is_success;
			$this->ErrMsg = $RegistryErrorMessage;
		}
	}
	
	/**
	 * @ignore 
	 */
	interface ITransportObserver
	{
		public function AfterResponse ($transport, $response);
		
		public function BeforeRequest ($transport);
	}
?>