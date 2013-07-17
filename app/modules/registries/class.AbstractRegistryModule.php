<?php
	
	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @category EPP-DRS
     * @package Modules
     * @subpackage RegistryModules
     * @sdk
     */

	/**
	 * Base class to be extended by all registry modules.
	 * If you override any methods, its a good idea to call a parent::__construct() in your code, to be sure that all needed properties are set.
	 * @name AbstractRegistryModule
	 * @category   EPP-DRS
	 * @package Modules
     * @subpackage RegistryModules
	 * @author Igor Savchenko <http://webta.net/company.html>
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	class AbstractRegistryModule extends Module implements IRegistryObserver 
	{
		/**
		 * Module manifest.
		 * A map of the XML manifest file.
		 * 
		 * @var RegistryManifest
		 */
		public $Manifest;
		
		/**
		 * The extension of domain currently being proccessed, without dot.
		 *
		 * @var string
		 */
		public $Extension;
		
		/**
		 * Module name
		 *
		 * @var string
		 */
		public $ModuleName;
		
		/**
		 * Code that implements module, defined in manifest
		 *
		 * @var unknown_type
		 */
		protected $CodebaseName;
		
		/**
		 * Module transport
		 * 
		 * @var A reference to your Transport object that is implementing ITransport.
		 */
		protected $Transport;
		
		/**
		 * Configuration data form 
		 * 
		 * @var DataForm
		 */
		public $Config;	
		
		/**
		 * A reference to global Registry object.
		 *
		 * @var RegistryAccessible
		 */
		protected $RegistryAccessible;
		
		/**
		 * Base module constructor. 
		 * Loads XML manifest. Normalizes ModuleName property.
		 * @return void
		 */
		public function __construct (RegistryManifest $Manifest)
		{
			$this->Manifest = $Manifest;
			$this->ModuleName = basename(dirname(realpath($Manifest->GetPath())));
			
			$reflect = new ReflectionObject($this);
			$this->CodebaseName = substr($reflect->name, 0, -14);
		}
		
		/**
		 * Called by the system during module initialization for specific domain extension.
		 * Initializes transport and sets Transport and Config properties.
		 *
		 * @param string $extension
		 * @param DataForm $config
		 */
		public function InitializeModule($extension, DataForm $config)
		{
			$this->Extension = $extension;
			$this->Config = $config;
					
			//
			//	Init Transport
			//
			$reflect = new ReflectionClass("{$this->CodebaseName}Transport");
			$this->Transport = $reflect->newInstance($this->Config, dirname(__FILE__)."/{$this->CodebaseName}");
			
			//
			// Set Extension in Manifest for manifest section config
			//
			$this->Manifest->SetExtension($this->Extension);
		}
		
		/**
		 * System calls this method to set the RegistryAccessible property.
		 *
		 * @param RegistryAccessible $ra
		 */
		final public function SetRegistryAccessible ($ra)
		{
			$this->RegistryAccessible = $ra;
		}
		
		protected function MakeNameIDNCompatible ($name)
		{
			if ($this->RegistryAccessible->IsIDNHostName($name))
			{
				$config_idn = $this->Manifest->GetSectionConfig()->idn;
				if ($config_idn && $config_idn->getName() 
					&& $config_idn->attributes()->punycode_encode == 1)
				{
					return $this->RegistryAccessible->PunycodeEncode($name);
				}
			}
			return $name;
		}		
		
		
		public final function GetTransport ()
		{
			return $this->Transport;
		}
		
		public function SetTransport ($Transport)
		{
			$this->Transport = $Transport;
		}
		
		/**
		 * Perform request to registry server
		 *
		 * @param string $command
		 * @param array $data
		 * @return TransportResponse
		 */
		public final function Request ($command, $data=array())
		{
			// Init transport
			if (!$this->Transport->IsConnected()) 
			{
				try {
					if (!$this->Transport->Connect())
						throw new Exception(_('Could not connect to registry server'));
				} catch (Exception $e) {
					throw new Exception(_('Could not connect to registry server') .". {$e->getMessage()}");
				}	
				
				try {
					if (!$this->Transport->Login()) 
						throw new Exception(_('Could not authenticate on registry server'));
				} catch (Exception $e) {
					throw new Exception(_('Could not authenticate on registry server') . ". {$e->getMessage()}");
				}
			}
			
			// Perform request
			$start_time = microtime(true);
			
			try
			{
				$result = $this->Transport->Request($command, $data);
			}
			catch(Exception $e)
			{
				// ����� ����������� ������� � �������� ���� � ��� � ���. ����� ������ ��� ������ ����� ��� ���������� ��� ������� ����..
				Log::Log(sprintf("Request to registry failed with Exception: %s", $e->getMessage()), E_USER_ERROR);
				throw $e;
			}
			
			$request_time = microtime(true)-$start_time;
			
			//
			// Override registry errors by ones that defined in XML manifest
			// 
			$errors_override = $this->Manifest->GetOverriddenErrors();
			
			foreach ($errors_override as $eo)
			{
				switch($eo["match"])
				{
					case "like":
						
						if (strpos($result->ErrMsg, $eo["str"]) !== false)
							$result->ErrMsg = $eo["error"];
						
						break;
						
					case "equal":
						
						if ($result->ErrMsg == $eo["str"])
							$result->ErrMsg = $eo["error"];
						
						break;
				}
			}
			
			Log::Log(sprintf("Request took %d seconds", $request_time), E_USER_NOTICE);
			
			return $result;
		}	
		
		/**
		 * Close registry session
		 */
		final public function CloseSession ()
		{
			if ($this->Transport)
				$this->Transport->Disconnect();
		}
	
		/**
		 * Helper method for XML special chars escaping
		 *
		 * @param string $str
		 * @return string
		 */
		final protected function EscapeXML ($str)
		{
			return str_replace(array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $str);
		}
	
		public function GeneratePassword($length=9) 
		{
		    $vowels = 'aeuyAEUY';
		    $consonants = 'bdghjmnpqrstvzBDGHJLMNPQRSTVWXZ';
		    $numbers = '23456789';
		    $others = '@#$%';
		
		    $password = '';
		    list($number_index, $other_index) = array_rand(range(0, $length-1), 2);
		    
		    $alt = (bool) time() % 2;
		    for ($i = 0; $i < $length; $i++) {
		    	if ($i == $number_index) {
		    		$password .= $numbers[rand() % strlen($numbers)];
		    	} else if ($i == $other_index) {
		    		$password .= $others[rand() % strlen($others)];
		    	} else if ($alt) {
		    		$password .= $consonants[(rand() % strlen($consonants))];
		    		$alt = false;
		    	} else {
		    		$password .= $vowels[(rand() % strlen($vowels))];
		    		$alt = true;
		    	}
		    }
		    return $password;
		}

		protected function PunycodeDecodeIf ($hostname)
		{
			return substr($hostname, 0, 4) == "xn--" ?
					$this->RegistryAccessible->PunycodeDecode($hostname) : $hostname;
		}
		
		/**
		 * Return module name
		 *
		 * @return string
		 */
		final public function __toString()
		{
			return $this->ModuleName;
		}
		
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
		
		public function OnDomainOutgoingTransferRequested (Domain $domain) {}
		
		public function OnDomainTransferredAway (Domain $domain) {}
		
		/**
		 * Method is called when domain transfer was requested
		 *
		 * @param Domain $domain Domain object
		 */
		public function OnDomainTransferRequested (Domain $domain) {}
		
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
		 * Method is called when namserver host updated
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