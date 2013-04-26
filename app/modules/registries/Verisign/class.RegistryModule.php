<?php

	/**
	 * @name Verisign Registry module
	 * @category   EPP-DRS
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @author Marat Komarov <http://webta.net/company.html>
	 */
	
	class VerisignRegistryModule extends GenericEPPRegistryModule implements IRegistryModuleServerPollable
	{	
		protected $XmlNamespaces = array(
			'domain' 	=> 'urn:ietf:params:xml:ns:domain-1.0',
			'contact' 	=> 'http://www.verisign-grs.com/epp/rcccontact-1.1', // ??? ??
			'host' 		=> 'urn:ietf:params:xml:ns:host-1.0',
			'rgp'		=> 'urn:ietf:params:xml:ns:rgp-1.0' 
		);	
		
		private $Db;
		
		public function __construct(RegistryManifest $Manifest)
		{
			parent::__construct($Manifest);
			$this->Db = Core::GetDBInstance();
		}
		
		/**
		 * This method is called to validate either user filled all fields of your configuration form properly.
		 * If you return true, all configuration data will be stored in database. If you return array, user will be presented with values of this array as errors. 
		 *
		 * @param array $post_values
		 * @return true or array of error messages.
		 */
		public static function ValidateConfigurationFormData($post_values)
		{
			return true;
		}
		
		public static function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->AppendField( new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
			$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
			$ConfigurationForm->AppendField( new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$ConfigurationForm->AppendField( new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
			$ConfigurationForm->AppendField( new DataFormField("SSLCertPath", FORM_FIELD_TYPE::TEXT , "Path to SSL certificate", 1));
			$ConfigurationForm->AppendField( new DataFormField("SSLCertPass", FORM_FIELD_TYPE::TEXT , "SSL private key password", 1));
			$ConfigurationForm->AppendField( new DataFormField("GURID", FORM_FIELD_TYPE::TEXT , "GURID", 1, null, null, null, 'Your NameStore manager panel -> Accounts -> View account information'));
			
			return $ConfigurationForm;
		}
		
		public function GetRegistrarID()
		{
			return $this->Config->GetFieldByName("GURID")->Value;
		}		
		
		/**
		 * Executed by EPPDRS when user activate module 
		 */
		public function OnModuleEnabled()
		{
			$sql = "SHOW TABLES FROM {$this->Db->database} like 'whois_domain'";
			if ($this->Db->GetOne($sql))
			{
				// Module was previously installed
				return;
			}
			
			
			$queries = array(
				"/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */",
				"DROP TABLE IF EXISTS whois_country",
				"CREATE TABLE IF NOT EXISTS whois_country (`country_key` int(3) unsigned NOT NULL auto_increment, `short` varchar(2) NOT NULL, `country` varchar(255) NOT NULL, PRIMARY KEY  (`country_key`), UNIQUE KEY `unique_country` (`short`)) ENGINE=InnoDB DEFAULT CHARSET=latin1",
				"DROP TABLE IF EXISTS whois_type",			
				"CREATE TABLE IF NOT EXISTS whois_type (type_key INT(2) UNSIGNED NOT NULL AUTO_INCREMENT, type VARCHAR(100) NOT NULL, PRIMARY KEY (type_key))TYPE=InnoDB;",
				"CREATE UNIQUE INDEX unique_type ON whois_type (type ASC);",
				"DROP TABLE IF EXISTS whois_mntnr",
				"CREATE TABLE whois_mntnr (mntnr_key BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, login VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, pcode VARCHAR(20) NOT NULL, city VARCHAR(255) NOT NULL, country_fkey INT(3) UNSIGNED NOT NULL DEFAULT 0, phone VARCHAR(100) NOT NULL, fax VARCHAR(100), email VARCHAR(255) NOT NULL, remarks VARCHAR(255), changed TIMESTAMP(19) NOT NULL DEFAULT CURRENT_TIMESTAMP, disabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 0, UNIQUE UQ_mntnr_login (login), PRIMARY KEY (mntnr_key), INDEX (country_fkey), CONSTRAINT FK_mntnr_country FOREIGN KEY (country_fkey) REFERENCES whois_country (country_key) ON DELETE NO ACTION ON UPDATE NO ACTION)TYPE=InnoDB;",
				"DROP TABLE IF EXISTS whois_person",
				"CREATE TABLE whois_person (person_key BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, type_fkey INT(2) UNSIGNED NOT NULL DEFAULT 0, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, pcode VARCHAR(20) NOT NULL, city VARCHAR(255) NOT NULL, country_fkey INT(3) UNSIGNED NOT NULL DEFAULT 0, phone VARCHAR(100) NOT NULL, fax VARCHAR(100), email VARCHAR(255) NOT NULL, remarks VARCHAR(255), changed TIMESTAMP(19) NOT NULL DEFAULT CURRENT_TIMESTAMP, mntnr_fkey BIGINT(20) UNSIGNED NOT NULL DEFAULT 0, disabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (person_key), INDEX (country_fkey), CONSTRAINT FK_person_country FOREIGN KEY (country_fkey) REFERENCES whois_country (country_key) ON DELETE NO ACTION ON UPDATE NO ACTION, INDEX (mntnr_fkey) , CONSTRAINT FK_person_mntnr FOREIGN KEY (mntnr_fkey) REFERENCES whois_mntnr (mntnr_key) ON DELETE NO ACTION ON UPDATE NO ACTION, INDEX (type_fkey), CONSTRAINT FK_person_type FOREIGN KEY (type_fkey) REFERENCES whois_type (type_key) ON DELETE NO ACTION ON UPDATE NO ACTION)TYPE=InnoDB;",
				"DROP TABLE IF EXISTS whois_domain",
				"CREATE TABLE `whois_domain` (`domain_key` bigint(20) unsigned NOT NULL auto_increment,`domain` varchar(255) NOT NULL,`registered_date` datetime NOT NULL, `registerexpire_date` datetime NOT NULL, `changed` timestamp NOT NULL default CURRENT_TIMESTAMP, `remarks` varchar(255) default NULL, `holder` bigint(20) unsigned NOT NULL, `admin_c` bigint(20) unsigned default NULL, `tech_c` bigint(20) unsigned NOT NULL, `bill_c` bigint(20) unsigned default NULL, `mntnr_fkey` bigint(20) unsigned NOT NULL, `publicviewabledata` tinyint(1) unsigned NOT NULL default '1', `disabled` tinyint(1) unsigned NOT NULL default '0', PRIMARY KEY  (`domain_key`), UNIQUE KEY `UQ_domain_1` (`domain`), KEY `admin_c` (`admin_c`), KEY `mntnr_fkey` (`mntnr_fkey`), KEY `tech_c` (`tech_c`), KEY `holder` (`holder`), KEY `privacyidx` (`publicviewabledata`), KEY `disabledidx` (`disabled`), KEY `FK_domain_person_zonec` (`bill_c`), CONSTRAINT `FK_domain_person_zonec` FOREIGN KEY (`bill_c`) REFERENCES `whois_person` (`person_key`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `FK_domain_1` FOREIGN KEY (`admin_c`) REFERENCES `whois_person` (`person_key`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `FK_domain_6` FOREIGN KEY (`holder`) REFERENCES `whois_person` (`person_key`), CONSTRAINT `FK_domain_mntnr` FOREIGN KEY (`mntnr_fkey`) REFERENCES `whois_mntnr` (`mntnr_key`) ON DELETE NO ACTION ON UPDATE NO ACTION,  CONSTRAINT `FK_domain_person_techc` FOREIGN KEY (`tech_c`) REFERENCES `whois_person` (`person_key`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE=InnoDB DEFAULT CHARSET=latin1",
				"DROP TABLE IF EXISTS whois_nameserver",
				"CREATE TABLE `whois_nameserver` ( `nameserver_key` bigint(20) unsigned NOT NULL auto_increment, `nameserver` varchar(255) NOT NULL, `domain_fkey` bigint(20) unsigned NOT NULL, PRIMARY KEY  (`nameserver_key`), KEY `domain_fkey` (`domain_fkey`), CONSTRAINT `FK_nameserver_1` FOREIGN KEY (`domain_fkey`) REFERENCES `whois_domain` (`domain_key`)) ENGINE=InnoDB DEFAULT CHARSET=latin1",
				"INSERT INTO whois_country (short, country) SELECT code, name FROM countries",
				"INSERT INTO whois_mntnr (mntnr_key, login, password, name, address, pcode, city, country_fkey, phone, fax, email, remarks, changed, disabled) VALUES (1, '', '', 'EPPDRS', '', '', '', 0, '', '', '', NULL, NOW(), 0)",
				"INSERT INTO whois_type SET type_key = 1, type = 'Generic'",
				"/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */"
			);
			
			// Create whois tables
			foreach ($queries as $query)
			{
				$this->Db->Execute($query);
			}
		}
		
		/**
		 * Executed by EPPDRS when user deactivate module
		 */
		public function OnModuleDisabled()
		{
		}
		
	
		/**
		 * Enter description here...
		 */
		public function RunTest (DataForm $DF)
		{
			$filename = '/tmp/eppdrs-verisign-certtest-' . date('YmdHis') . '.log';
		    Log::RegisterLogger("File", "Verisign", $filename);
			Log::SetDefaultLogger("Verisign");			
			
			
			// Build dataforms for modules
			
			$DF1 = self::GetConfigurationForm();
			$DF1->GetFieldByName('Login')->Value 		= $DF->GetFieldByName('Login_1')->Value;
			$DF1->GetFieldByName('Password')->Value 	= $DF->GetFieldByName('Password_1')->Value;
			$DF1->GetFieldByName('ServerHost')->Value 	= $DF->GetFieldByName('ServerHost')->Value;
			$DF1->GetFieldByName('ServerPort')->Value 	= $DF->GetFieldByName('ServerPort')->Value;
			$DF1->GetFieldByName('SSLCertPath')->Value 	= $DF->GetFieldByName('SSLCertPath')->Value;
			$DF1->GetFieldByName('SSLCertPass')->Value 	= $DF->GetFieldByName('SSLCertPass')->Value;
			
			$DF2 = self::GetConfigurationForm();
			$DF2->GetFieldByName('Login')->Value 		= $DF->GetFieldByName('Login_2')->Value;
			$DF2->GetFieldByName('Password')->Value 	= $DF->GetFieldByName('Password_2')->Value;
			$DF2->GetFieldByName('ServerHost')->Value 	= $DF->GetFieldByName('ServerHost')->Value;
			$DF2->GetFieldByName('ServerPort')->Value 	= $DF->GetFieldByName('ServerPort')->Value;
			$DF2->GetFieldByName('SSLCertPath')->Value 	= $DF->GetFieldByName('SSLCertPath')->Value;
			$DF2->GetFieldByName('SSLCertPass')->Value 	= $DF->GetFieldByName('SSLCertPass')->Value;
			 
			// Initialize modules
			$Module = new VerisignRegistryModule(new RegistryManifest(MODULES_PATH . "/registries/Verisign/module.xml"));
			$Module->InitializeModule('com', $DF1);
			$Registry = new Registry($Module);
			
			$Module2 = new VerisignRegistryModule(new RegistryManifest(MODULES_PATH . "/registries/Verisign/module.xml"));
			$Module2->InitializeModule('com', $DF2);
			$Registry2 = new Registry($Module2);
			
			// The subject domain
			
			$Domain = $this->RegistryAccessible->NewDomainInstance();
			$Domain->Name = 'webta' . rand(1000, 9999);
			$Domain->UserID = 1;
			
			////
			// 1. Using your OT&E1 account perform a CHECK command on domain name(s) until you
			// receive domain available response
			
			$oplog = array();
			
			
			$op = array('title' => 'Perform a CHECK command on domain name(s)');
			try
			{
				$ok = $Registry->DomainCanBeRegistered($Domain)->Result;
				$op['ok'] = (bool)$ok;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
			
			////
			// 2. CREATE the Domain name using the CREATE command, term of registration should be
			// 2 years
			
			
			$op = array('title' => "CREATE the Domain name using the CREATE command");
			try
			{
				$Contact = $Registry->NewContactInstanceByGroup('generic');
				$Domain->SetContact($Contact, CONTACT_TYPE::REGISTRANT);
				$Domain->SetContact($Contact, CONTACT_TYPE::BILLING);
				$Domain->SetContact($Contact, CONTACT_TYPE::TECH);
				$Domain->SetContact($Contact, CONTACT_TYPE::ADMIN);
				
				
				$Registry->CreateDomain($Domain, 2);
				$op['ok'] = true;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
							
			
			////
			// 3. CREATE 2 child name servers of newly created domain
			//
			
			$op = array('title' => "CREATE 2 child name servers of newly created domain");
			try
			{
				$ns1 = new NameserverHost('ns1.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
				$ns2 = new NameserverHost('ns2.' . $Domain->GetHostName(), gethostbyname('hostdad.com'));
				
				$Registry->CreateNameserverHost($ns1);
				$Registry->CreateNameserverHost($ns2);
								
				$op['ok'] = true;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
							
			
			////
			// 4. UPDATE Domain to attach child name servers to domain
			//
			
			$op = array('title' => "UPDATE Domain to attach child name servers to domain");
			try
			{
				$nslist = $Domain->GetNameserverChangelist();
				$nslist->Add($ns1);
				$nslist->Add($ns2);
				
				$Registry->UpdateDomainNameservers($Domain, $nslist);
				
				$op['ok'] = count($Domain->GetNameserverList()) == 2; 
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
							
			
			////
			// 5. UPDATE Domain's status to 
			// clientHold, clientUpdateProhibited, clientDeleteProhibited, and clientTransferProhibited 
			// within one command
			
			$op = array('title' => "UPDATE Domain's status");
			try
			{
				$flag_list = $Domain->GetFlagChangelist();
				$flag_list->SetChangedList(array(
					'clientHold', 
					'clientUpdateProhibited',
					'clientDeleteProhibited', 
					'clientTransferProhibited'
				));
				
				$Registry->UpdateDomainFlags($Domain, $flag_list);
				
				$op['ok'] = count($Domain->GetFlagList()) == count($flag_list->GetList()); 
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
							
			
			////
			// 6. Perform an INFO command on the domain to verify update
			//
			
			$op = array('title' => "Perform an INFO command on the domain to verify update");
			try
			{
				$RDomain = $Registry->NewDomainInstance();
				$RDomain->Name = $Domain->Name;
				
				$RDomain = $Registry->GetRemoteDomain($RDomain);
				
				$flags = $Domain->GetFlagList();
				$rflags = $RDomain->GetFlagList();
				sort($flags);
				sort($rflags);
				
				$op['ok'] = 
					$RDomain->Name == $Domain->Name &&
					date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
					date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
					$rflags == $flags;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
							
			
			////
			// 7. UPDATE Domain's status to OK
			//
			
			$op = array('title' => "UPDATE Domain's status to OK");
			try
			{
				$changes = $Domain->GetFlagChangelist();
				foreach ($RDomain->GetFlagList() as $flag)
					$changes->Remove($flag);
				
				$Registry->UpdateDomainFlags($Domain, $changes);
				
				//$changes = $Domain->GetFlagChangelist();
				//$changes->Add('ok');
				//$this->Registry->UpdateDomainFlags($Domain, $changes);
				$op['ok'] = $Domain->GetFlagList() == array(); 
				
				$Domain->SetFlagList(array('ok')); // ok flag set automatical when all other were removed
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 8. Perform an INFO command on the domain to verify update
			//
			
			$op = array('title' => "Perform an INFO command on the domain to verify update");
			try
			{
				$RDomain = $Registry->NewDomainInstance();
				$RDomain->Name = $Domain->Name;
				
				$RDomain = $Registry->GetRemoteDomain($RDomain);
				
				$op['ok'] = 
					$RDomain->Name == $Domain->Name &&
					date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
					date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
					$RDomain->GetFlagList() == array('ok');
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 9. UPDATE Domain's AUTH INFO Code
			//
			
			$op = array('title' => "UPDATE Domain's AUTH INFO Code");
			try
			{
				// ���������� AUTH ����������� �������������� � �����-�������� API
				
				$VerisignModule = $Registry->GetModule();
	
				$Domain->AuthCode = "ae2Yo&#YARR1";			
				$params = array(
					'name' => $Domain->GetHostName(),
					'subproduct' => 'dot' . strtoupper($Domain->Extension),
					'add' => '',
					'remove' => '',
					'change' => "<domain:chg><domain:authInfo><domain:pw>{$Domain->AuthCode}</domain:pw></domain:authInfo></domain:chg>"
				);
				
				$Resp = $VerisignModule->Request('domain-update', $params);
				$success = $Resp->Succeed || $Resp->Code == RFC3730_RESULT_CODE::OK_PENDING;
				
				$op['ok'] = $success;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 10. UPDATE one of the name server's IP Address
			//
			
			$op = array('title' => "UPDATE one of the name server's IP Address");
			try
			{
				$ns1->IPAddr = gethostbyname('ns.hostdad.com');
				$Resp = $Module->UpdateNameserverHost($ns1);
				
				$op['ok'] = $Resp->Result;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 11. Perform a HELLO command  
			//
			
			$op = array('title' => "Perform a HELLO command");
			try
			{
				$VerisignModule = $Registry->GetModule();
				
				$Resp = $VerisignModule->Request('hello', $params=array());
				
				$op['ok'] = $Resp == true; 
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 12. Renew Domain for 2 years
			//
			
			$op = array('title' => "Renew Domain for 2 years");
			try
			{
				$old_expire_date = $Domain->ExpireDate;
				$Registry->RenewDomain($Domain, $extra=array('period' => 2));
				
				$op['ok'] = date('Ymd', $Domain->ExpireDate) == date('Ymd', strtotime('+2 year', $old_expire_date)); 
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 13. Open Second Session using the OT&E2 account logon
			//
			
			$op = array('title' => "Open Second Session using the OT&E2 account logon");
			$op['ok'] = true;
			// It will be done automatical in next command
			
			
			////
			// 14. Perform INFO command on the newly created domain from step 1 using the AUTH
			// INFO code populated in step 9 to get INFO results
			//
			
			$op = array('title' => "Perform INFO command on the newly created domain from step 1");
			try
			{
				$RDomain = $Registry2->NewDomainInstance();
				$RDomain->Name = $Domain->Name;
				$RDomain->AuthCode = $Domain->AuthCode;
				
				$RDomain = $Registry2->GetRemoteDomain($RDomain);
				
				$flags = $Domain->GetFlagList();
				$rflags = $RDomain->GetFlagList();
				sort($flags);
				sort($rflags);
				
				$op['ok'] = 
					$RDomain->Name == $Domain->Name &&
					date('Ymd', $RDomain->CreateDate) == date('Ymd', $Domain->CreateDate) &&
					date('Ymd', $RDomain->ExpireDate) == date('Ymd', $Domain->ExpireDate) &&
					$rflags == $flags;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 15. Initiate Transfer domain command using your OT&E2 account
			//
			
			$op = array('title' => "Initiate Transfer domain command using your OT&E2 account");
			try
			{
				$ok = $Registry2->TransferRequest($Domain, array('pw' => $Domain->AuthCode));
				
				$op['ok'] = $ok;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 16. Perform a Transfer Query command using your OT&E2 account
			//
			
			$op = array('title' => "Perform a Transfer Query command using your OT&E2 account");
			try
			{
				// Not implemented in cross-registry API
				
				$VerisignModule2 = $Registry2->GetModule();
				
				$params = array(
					'name' => $Domain->GetHostName(),
					'pw' => $Domain->AuthCode,
					'subproduct' => 'dot' . strtoupper($Domain->Extension)
				);
				
				$Resp = $VerisignModule2->Request('domain-trans-query', $params);
				
				$op['ok'] = $Resp->Succeed;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
	
			////
			// 17. Approve the Transfer using your OT&E1 account
			//
			
			$op = array('title' => "Approve the Transfer using your OT&E1 account");
			try
			{
				$ok = $Registry->TransferApprove($Domain);
				$op['ok'] = $ok;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
					
			
			////
			// 18. Perform a Poll Command to check for messages in poll queue, ACK first poll message
			//
			
			// not works
			
			$op = array('title' => "Perform a Poll Command to check for messages in poll queue");
			try
			{
				$VerisignModule2 = $Registry->GetModule();
				
				$max_iter = 5;
				$i = 0;
				while ($i < $max_iter && ($Mess = $VerisignModule2->ReadMessage()) === false)
				{
					sleep(1);
					$i++;
				}
				
				$op['ok'] = true;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
				
			
			////
			// 19. Initiate the Transfer again using your OT&E1 account 
			//
			
			$op = array('title' => "Initiate the Transfer again using your OT&E1 account");
			try
			{
				$ok = $Registry->TransferRequest($Domain, array('pw' => $Domain->AuthCode));
				$op['ok'] = $ok;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 20. Perform a Transfer Query command using your OT&E2 account
			//
			
			$op = array('title' => "Perform a Transfer Query command using your OT&E2 account");
			try
			{
				// Not implemented in cross-registry API
				
				$VerisignModule2 = $Registry2->GetModule();
				
				$params = array(
					'name' => $Domain->GetHostName(),
					'pw' => $Domain->AuthCode,
					'subproduct' => 'dot' . strtoupper($Domain->Extension)
				);
				
				$Resp = $VerisignModule2->Request('domain-trans-query', $params);
				
				$op['ok'] = $Resp->Succeed;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 21. Reject the Transfer using your OT&E2 account
			//
			
			$op = array('title' => "Reject the Transfer using your OT&E2 account");
			try
			{
				$ok = $Registry2->TransferReject($Domain);
				
				$op['ok'] = $ok;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
						
			
			////
			// 22. From OT&E2 sync the domain to the 15th day of the next month
			//
			
			$op = array('title' => "From OT&E2 sync the domain to the 15th day of the next month");
			try
			{
				$m = (int)date('n')+1;
				$ok = $VerisignModule2->UpdateDomainConsoliDate($Domain, array('expMonth' => $m, 'expDay' => 15));
				
				$op['ok'] = $ok;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
				
			
			////
			// 23. Exit Gracefully from both sessions by issuing the LOGOUT command
			//
			
			$op = array('title' => "Exit Gracefully from both sessions by issuing the LOGOUT command");
			try
			{
				$VerisignModule->Request('logout', $params=array());
				$VerisignModule2->Request('logout', $params=array());
				
				$op['ok'] = true;
			}
			catch (Exception $e)
			{
				$op['ok'] = false;
				$op['fail_reason'] = $e->getMessage();
			}
			$oplog[] = $op;
			
			
			$passed = true;
			foreach ($oplog as $op)
				$passed = $passed && $op['ok'];
			
			$out_filename = sprintf('eppdrs-verisign-certtest-%s.log', $passed ? 'passed' : 'failed'); 
			
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . $out_filename . '"');
			
			foreach ($oplog as $i => $op)
			{
				$n = $i+1;
				print str_pad("{$n}. {$op['title']}", 100, ' ', STR_PAD_RIGHT);
				printf("[%s]\n", $op['ok'] ? 'OK' : 'FAIL');
				if (!$op['ok'])
				{
					print "fail reason: {$op['fail_reason']}\n";
				}
			}
	
			print "\n\n";
			
			print file_get_contents($filename);
			unlink($filename);
			die();
		} 
		
		public function GetTestConfigurationForm ()
		{
			$CF = new DataForm();
			$CF->AppendField(new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$CF->AppendField(new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
			$CF->AppendField(new DataFormField("SSLCertPath", FORM_FIELD_TYPE::TEXT , "Path to SSL certificate", 1));
			$CF->AppendField(new DataFormField("SSLCertPass", FORM_FIELD_TYPE::TEXT , "SSL private key password", 1));			
			$CF->AppendField(new DataFormField("Login_1", FORM_FIELD_TYPE::TEXT, "Login 1", 1, null, null, null, 'Your OT&E1 account'));
			$CF->AppendField(new DataFormField("Password_1", FORM_FIELD_TYPE::TEXT, "Passsword 1", 1));
			$CF->AppendField(new DataFormField("Login_2", FORM_FIELD_TYPE::TEXT, "Login 2", 1, null, null, null, 'Your OT&E2 account'));
			$CF->AppendField(new DataFormField("Password_2", FORM_FIELD_TYPE::TEXT, "Passsword 2", 1));
			
			return $CF;
		}
		
		protected function BeforeRequest($command, &$data, $method /* args */)
		{
			switch ($command)
			{
				case 'domain-check':
				case 'domain-info':
				case 'domain-renew':
				case 'domain-delete':
				case 'domain-trans-request':
				case 'domain-trans-approve':
				case 'domain-trans-reject':
				case 'domain-update-contact':
				case 'host-info':
				case 'host-check':
				case 'host-create':
				case 'host-update':
				case 'host-delete':
					$data['subproduct'] = 'dot' . strtoupper($this->Extension);
					break;
			}
		}	
		
		/**
	     * Checks transfer opportunity for domain
	     *
	     * @param Domain $domain
	     * @return DomainCanBeTransferredResponse
	     */
	    //public function DomainCanBeTransferred(Domain $domain) { }
	    
	    
	    /**
	     * This method send domain trade request (Change owner).
	     * In order to pending domain trade, response must have status REGISTRY_RESPONSE_STATUS::PENDING
	     * 
	     * @param Domain $domain Domain must have contacts and nameservers 
	     * @param integer $period Domain delegation period
	     * @param array $extra Some registry specific fields 
	     * @return ChangeDomainOwnerResponse
	     */
	    public function ChangeDomainOwner(Domain $domain, $period=null, $extra=array()) 
	    {
	    	throw new NotImplementedException();
	    }

		/**
	     * Update domain auth code.
	     *
	     * @param Domain $domain
	     * @param string $authcode A list of changes in domain flags for the domain
	     * @return UpdateDomainAuthCodeResponse
	     */
	    public function UpdateDomainAuthCode(Domain $domain, $authCode) 
	    {
			$params = array(
				'name'   		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				'subproduct' 	=> 'dot' . strtoupper($domain->Extension),
				'add'    		=> '',
				'remove' 		=> '',
				'change' 		=> ''
			);
			
			$params['change'] = "<domain:chg><domain:authInfo><domain:pw>{{$this->EscapeXML($authCode)}}</domain:pw></domain:authInfo></domain:chg>";
				
			$response = $this->Request("domain-update", $params);
			
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
				
			return new UpdateDomainAuthCodeResponse($status, $response->ErrMsg, $response->Code);    	
	    }
	    
	    /**
	     * This method request registry to update domain flags (ex: clientUpdateProhibited, clientDeleteProhibited)
	     * In order to pending flags update, response must have status REGISTRY_RESPONSE_STATUS::PENDING 
	     *
	     * @param Domain $domain
	     * @param IChangelist $changes flags changes
	     * @return UpdateDomainFlagsResponse
	     */
	    public function UpdateDomainFlags(Domain $domain, IChangelist $changes) 
	    {
			$params = array(
				'name'   		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				'subproduct' 	=> 'dot' . strtoupper($domain->Extension),
				'add'    		=> '',
				'remove' 		=> '',
				'change' 		=> ''
			);
			
			if ($changes->GetAdded())
				$params['add'] = "<domain:add>" . $this->GetFlagsXML($changes->GetAdded()) . "</domain:add>";
				
			if ($changes->GetRemoved())
				$params['remove'] = "<domain:rem>" . $this->GetFlagsXML($changes->GetRemoved()) . "</domain:rem>";
				
			$response = $this->Request("domain-update", $params);
			
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
				
			return new UpdateDomainFlagsResponse($status, $response->ErrMsg, $response->Code);    	
	    }
	    
	    protected function GetFlagsXML ($flags)
	    {
	    	$ret = '';
	    	foreach ($flags as $flag)
	    		$ret .= "<domain:status s=\"{$flag}\"/>";
	    	return $ret;
	    }
	    
	    /**
		 * This method send create domain request.
		 * In order to pending domain creation, response must have status REGISTRY_RESPONSE_STATUS::PENDING
		 *	 
		 * @param Domain $domain
		 * @param int $period Domain registration period
		 * @param array $extra Extra data
		 * @return CreateDomainResponse
		 */
		public function CreateDomain(Domain $domain, $period, $extra = array()) 
		{
			$contact_list = $domain->GetContactList();
			$ns_list = $domain->GetNameserverList();
	
			$is_idna = $this->RegistryAccessible->IsIDNHostName($domain->GetHostName()); 
			
			if ($is_idna)
				$idn = '<idnLang:tag xmlns:idnLang="http://www.verisign.com/epp/idnLang-1.0" ' .
						'xsi:schemaLocation="http://www.verisign.com/epp/idnLang-1.0 idnLang-1.0.xsd">'.$domain->IDNLanguage.'</idnLang:tag>';
				
				// $idn = '<idnLang:tag xmlns:idnLang="http://www.verisign.com/epp/idnLang-1.0" xsi:schemaLocation="http://www.verisign.com/epp/idnLang-1.0 idnLang-1.0.xsd">en</idnLang:tag>';
			
				//$idn = '<idnLang:tag xmlns:idnLang="http://www.verisign.com/epp/idnLang-1.0" ' .
				//		'xsi:schemaLocation="http://www.verisign.com/epp/idnLang-1.0 idnLang-1.0.xsd">de</idnLang:tag>';
				
			else
				$idn = '';
			
				
			$params = array(
				"name"				=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"period"			=> $period,
				"pw"				=> $this->GeneratePassword(10),
				'subproduct'		=> 'dot' . strtoupper($this->Extension),
				'idn'				=> $idn
			);
			
			$ns_str = '';
			if (count($ns_list))
			{
				$ns_str .= '<domain:ns>';
				$ns_str .= $this->GetNSXML($ns_list);
				$ns_str .= '</domain:ns>';
			}
			$params['ns_list'] = $ns_str;
			
			$contact_str = '';
			if ($contact_list[CONTACT_TYPE::REGISTRANT])
				$contact_str .= "<domain:registrant>{$contact_list[CONTACT_TYPE::REGISTRANT]->CLID}</domain:registrant>";
			unset($contact_list[CONTACT_TYPE::REGISTRANT]);
			foreach ($contact_list as $type => $Contact)
				$contact_str .= "<domain:contact type=\"{$type}\">{$Contact->CLID}</domain:contact>";
			$params['contact_list'] = $contact_str;
			
			
			$response = $this->Request("domain-create", $params);
		
			
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
			
			$resp = new CreateDomainResponse($status, $response->ErrMsg, $response->Code);
			
			if ($response->Succeed)
			{
				$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
				$info = $info[0];
	
				$resp->CreateDate = $this->StrToTime((string)$info->crDate[0]); 
				$resp->ExpireDate = $this->StrToTime((string)$info->exDate[0]); 
				
				$resp->AuthCode = (string)$params["pw"];
			}
			
			return $resp;
		}
		
		/**
		 * This method request registry for information about domain
		 * 
		 * @param Domain $domain 
		 * @return GetRemoteDomainResponse
		 */
		public function GetRemoteDomain(Domain $domain) 
		{
			$params = array(
				"name"	=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				'authinfo' => '',
				'subproduct' => 'dot' . strtoupper($this->Extension)			
			);
			if ($domain->AuthCode)
				$params['authinfo'] = "<domain:authInfo><domain:pw>{$domain->AuthCode}</domain:pw></domain:authInfo>";				
			
			$response = $this->Request("domain-info", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$resp = new GetRemoteDomainResponse($status, $response->ErrMsg, $response->Code);
	
			$response->Data->registerXPathNamespace('rgp', $this->XmlNamespaces['rgp']);
			
			if ($response->Succeed)
			{
				$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
				$info = $info[0];
				
				$resp->CLID = (string)$info->clID[0];
				
				try
				{
					$resp->CRID = (string)$info->crID[0];
				}
				catch(Exception $e){}
				
				if ($resp->CRID)
				{
					$resp->AuthCode = ($info->authInfo[0]) ? (string)$info->authInfo[0]->pw[0] : "";
					
					$resp->CreateDate = $this->StrToTime((string)$info->crDate[0]); 
					$resp->ExpireDate = $this->StrToTime((string)$info->exDate[0]); 
					
					// Request whois for contacts
					$domain_whois = $this->Db->GetRow("
						SELECT * FROM whois_domain WHERE domain = ?",
						array($domain->GetHostName())
					);
					if ($domain_whois)
					{
						$resp->RegistrantContact = $this->PersonIdToCLID($domain_whois['holder']);
						$resp->AdminContact = $this->PersonIdToCLID($domain_whois['admin_c']);
						$resp->TechContact = $this->PersonIdToCLID($domain_whois['tech_c']);
						$resp->BillingContact = $this->PersonIdToCLID($domain_whois['bill_c']);
					}
					
					// Get nameservers
					$ns_arr = array();
					if ($info->ns)
					{
						foreach ($info->ns->hostObj as $v)
						{
							$hostname = (string)$v;
							if (FQDN::IsSubdomain($hostname, $domain->GetHostName()))
							{
								try
								{
									$ip = $this->GetHostIpAddress($hostname);
									$ns_arr[] = new NameserverHost($hostname, $ip);
								}
								catch (Exception $e) 
								{
									$ns_arr[] = new NameserverHost($hostname, '');
								}
							}
							else
							{
								// nameserver
								$ns_arr[] = new Nameserver($hostname);						 
							}
						}
					}
					$resp->SetNameserverList($ns_arr);
					
					// Flags (Domain status)
					$flags = array();
					if ($nodes = $info->xpath('domain:status/@s'))
						foreach ($nodes as $flag)
							$flags[] = (string)$flag;
							
					// Not works. Why ??
	//				if ($nodes = $response->Data->response->xpath('//rgp:infData/rgp:rgpStatus/@s'))
	//					foreach ($nodes as $flag)
	//						$flags[] = (string)$flag;
					// Intead of glamour XPath expression use ugly hack
					if ($response->Data->response->extension)
					{
						$rgpInfo = $response->Data->response->extension->children($this->XmlNamespaces['rgp']);
						foreach ($rgpInfo->rgpStatus as $status)
							$flag[] = (string)$status->attributes()->s;
					}
	
							
					$resp->SetFlagList($flags);
					
					$resp->RegistryStatus = $flags[0];
				}
			}
		
			return $resp;
		}
		
		/**
		 * This method request regsitry to change domain contact
		 * In order to pending operation, response must have status REGISTRY_RESPONSE_STATUS::PENDING
		 * 
		 * @param Domain $domain Domain
		 * @param string $contactType contact type @see CONTACT_TYPE::TYPE_*
		 * @param Contact $oldContact Old contact or NULL
		 * @param Contact $newContact
		 * @return UpdateDomainContactResponse
		 */
		public function UpdateDomainContact(Domain $domain, $contactType, Contact $oldContact, Contact $newContact)
		{
			$map = array
			(
				CONTACT_TYPE::REGISTRANT => 'holder',
				CONTACT_TYPE::TECH => 'tech_c',
				CONTACT_TYPE::BILLING => 'bill_c',
				CONTACT_TYPE::ADMIN => 'admin_c'
			);
			if (key_exists($contactType, $map))
			{
				$sql = "UPDATE whois_domain SET ".$map[$contactType]." = ? WHERE domain = ?";
				$this->Db->Execute($sql, array($this->CLIDToPersonId($newContact->CLID), $domain->GetHostName()));
				return new UpdateDomainContactResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);				
			}
			
			return new UpdateDomainContactResponse(REGISTRY_RESPONSE_STATUS::FAILED);
		}
		
		
		/**
		 * This method request registry to change namservers list for domain
		 * In order to pending operation, response must have status REGISTRY_RESPONSE_STATUS::PENDING 
		 * 
		 * @param Domain $domain Domain
		 * @param IChangelist $changelist nameservers changelist 
		 * @return UpdateDomainNameserversResponse
		 */
		public function UpdateDomainNameservers(Domain $domain, IChangelist $changelist) 
		{
			$params = array(
				'name'   		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				'subproduct' 	=> 'dot' . strtoupper($domain->Extension),
				'add'    		=> '',
				'remove' 		=> '',
				'change' 		=> ''
			);
			
			if ($changelist->GetAdded())
			{
				$params['add'] = "<domain:add><domain:ns>".$this->GetNSXML($changelist->GetAdded())."</domain:ns></domain:add>";
				
				// @see http://webta.net/support/admin/replies_view.php?id=1338
				foreach ($changelist->GetAdded() as $ns)
				{
					try
					{
						if (!preg_match('/(com|net)$/', $ns->HostName)) 
						{
							if ($ns instanceof NameserverHost) 
							{
								$this->CreateNameserverHost($ns);
							}
							else 
							{
								$nshost = new NameserverHost($ns->HostName, null);
								$this->CreateNameserverHost($nshost);
							}
						}
					}
					catch (Exception $ignore) {}
				}
			}
				
			if ($changelist->GetRemoved())
				$params['remove'] = "<domain:rem><domain:ns>".$this->GetNSXML($changelist->GetRemoved())."</domain:ns></domain:rem>";
				
			$response = $this->Request("domain-update", $params);
			
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
				
			return new UpdateDomainNameserversResponse($status, $response->ErrMsg, $response->Code);
		}	
		
		/**
		 * Enter description here...
		 *
		 * 
		 * @param Domain $domain
		 * @param array $extra
		 * @return bool
		 */
		public function UpdateDomainConsoliDate (Domain $domain, $extra=array())
		{
			$params = array(
				'name' 		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				'expMonth' 	=> sprintf('%02d', $extra['expMonth']),
				'expDay' 	=> sprintf('%02d', $extra['expDay']),
				'subproduct' => 'dot' . strtoupper($domain->Extension)
			);
			
			$Resp = $this->Request('domain-update-consolidate', $params);
			
			return $Resp->Succeed;
		}

		/**
		 * This method request registry to create namserver
		 * 
		 * @param Nameserver $ns
		 * @return CreateNameserverResponse
		 */
		public function CreateNameserver (Nameserver $ns)
		{
			$ret = new CreateNameserverResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$ret->Result = true;
			return $ret;
		}
	
		public function ReadMessage ()
		{
			$Resp = $this->Request('poll-request', array());

			//print "<pre>";
			//print htmlspecialchars($Resp->Data->asXML());
			//print "</pre>";
	
			if ($Resp->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE)
			{
				$msgID = (string)$Resp->Data->response->msgQ->attributes()->id;
				$resData = $Resp->Data->response->resData;
				if ($trnData = $resData->children($this->XmlNamespaces['domain']))
				{
					// Domain transfer message
					$trnData = $trnData[0];
					$trStatus = (string)$trnData->trStatus;
					$reID = (string)$trnData->reID;

					// Test for outgoing transfer message
					if ($reID != $this->Config->GetFieldByName('GURID')->Value)
					{
						// Message relates to OUTGOING transfer. skip it.
						$this->Request('poll-ack', array('msgID' => $msgID));
						return $this->ReadMessage();						
					}
					
					switch ($trStatus)
					{
						case self::TRANSFER_CLIENT_APPROVED:
						case self::TRANSFER_SERVER_APPROVED:
							$transfer_status = TRANSFER_STATUS::APPROVED;
							break;
	
						case self::TRANSFER_CLIENT_CANCELLED:
						case self::TRANSFER_SERVER_CANCELLED:
						case self::TRANSFER_CLIENT_REJECTED:
							$transfer_status = TRANSFER_STATUS::DECLINED;
							break;
							
						case self::TRANSFER_PENDING:
							$transfer_status = TRANSFER_STATUS::PENDING;
							break;
							
						default:
							$transfer_status = TRANSFER_STATUS::FAILED;
					}
					
					$Ret = new PollTransferResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$Ret->MsgID = $msgID;
					$hostname = (string)$trnData->name;
					if (substr($hostname, 0, 4) == "xn--")
					{
						$hostname = $this->RegistryAccessible->PunycodeDecode($hostname);
					}
					$Ret->HostName = $hostname;
					$Ret->RawResponse = $Resp->Data;
					$Ret->TransferStatus = $transfer_status;
					return $Ret;
				}
				else
				{
					$Ret = new PendingOperationResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
					$Ret->MsgID = $msgID;
					$Ret->RawResponse = $Resp->Data;
					return $Ret;
				}
			}
			
			return false;
		}		
		
		private function CLIDToPersonId ($clid)
		{
			return substr($clid, 5);
		}
		
		private function PersonIdToCLID ($person_id)
		{
			return "VRSGN{$person_id}";
		}
		
		private function MapContactToPerson (Contact $contact)
		{
			$fields = $contact->GetRegistryFormattedFieldList();
			
			$type_fkey = $this->Db->GetOne("
				SELECT type_key 
				FROM whois_type 
				WHERE UPPER(whois_type.`type`) = UPPER(?)", 
				array($contact->GroupName)
			);
			//throw new Exception($contact->GroupName);
			//throw new Exception("type key: " . $type_fkey);			
			
			$country_fkey = $this->Db->GetOne("
				SELECT country_key 
				FROM whois_country 
				WHERE short = ?", 
				array($fields['cc'])
			);
			
			return array(
				'type_fkey' => $type_fkey,
				'name' => $fields['name'],
				'country_fkey' => $country_fkey,			
				'city' => $fields['city'],
				'pcode' => $fields['pc'],
				'address' => $fields['street'],
				'phone' => $fields['voice'],
				'fax' => $fields['fax'],
				'email' => $fields['email']
			);
		}
		
		private function PersonExists ($person_id)
		{
			return $this->Db->GetOne("
				SELECT COUNT(*) 
				FROM whois_person
				WHERE person_key = ?",
				array($person_id)
			);
		}
		
		private $MaintainerId;
		
		private function GetMaintainerId ()
		{
			if ($this->MaintainerId === null)
			{
				$this->MaintainerId = $this->Db->GetOne("SELECT `mntnr_key` FROM whois_mntnr WHERE 1 LIMIT 1");
			}
			return $this->MaintainerId;
		}
		
		/**
		 * This method request registry for ability to create contact
		 * 
		 * @param Contact $contact
		 * @return ContactCanBeCreatedResponse 
		 */
		public function ContactCanBeCreated(Contact $contact) 
		{
			$Ret = new ContactCanBeCreatedResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$Ret->Result = true;
			return $Ret;
		}
	
		/**
		 * This method request registry to create contact
		 * 
		 * @param Contact $contact
		 * @return CreateContactResponse
		 */
		public function CreateContact(Contact $contact, $extra=array()) 
		{
			$data = $this->MapContactToPerson($contact);
			$data['mntnr_fkey'] = $this->GetMaintainerId();
			
			// Create data bind 
			$bind = array();
			foreach (array_keys($data) as $k)
			{
				$bind[] = "`$k` = ?";
			}
			$stmt = "INSERT INTO whois_person SET " . join(', ', $bind);
			$this->Db->Execute($stmt, array_values($data));
	
			// Get contact CLID
			$id = $this->Db->Insert_ID();
			
			// 
			$Ret = new CreateContactResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$Ret->CLID = $this->PersonIdToCLID($id);
			return $Ret;
		}
		
		
		
		/**
		 * This method request registry for information about contact
		 * @access public
		 * @param Contact $contact
		 * @version GetRemoteContactResponse
		 */
		public function GetRemoteContact(Contact $contact) 
		{
			$id = $this->CLIDToPersonId($contact->CLID);
			
			$data = $this->Db->GetRow("
				SELECT 
					p.name,
					c.short AS cc,
					p.city,
					p.pcode AS pc,
					p.address AS street,
					p.phone AS voice,
					p.fax,
					p.email
				FROM whois_person AS p
				LEFT JOIN whois_country AS c ON p.country_fkey = c.country_key
				WHERE p.person_key = ?",
				array($id)
			);
			if (!$data)
			{
				throw new ObjectNotExistsException();
			}
			
			$Ret = new GetRemoteContactResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			foreach ($data as $k => $v)
			{
				$Ret->{$k} = $v;
			}
			return $Ret;
		}
			
		/**
		 * This method request registry to update contact
		 * 
		 * @param Contact $contact
		 * @return UpdateContactResponse
		 */
		public function UpdateContact(Contact $contact) 
		{
			$id = $this->CLIDToPersonId($contact->CLID);
			if (!$this->PersonExists($id))
			{
				throw new ObjectNotExistsException();
			}
	
			// Create data bind
			$data = $this->MapContactToPerson($contact);
			$bind = array();
			foreach (array_keys($data) as $k)
			{
				$bind[] = "`$k` = ?";
			}
			$vals = array_values($data);
			$vals[] = $id;
			
			$ok = $this->Db->Execute("
				UPDATE whois_person 
				SET " . join(', ', $bind) . "
				WHERE `person_key` = ?", 
				$vals
			);
			
			$Ret = new UpdateContactResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$Ret->Result = (bool)$ok;
			return $Ret;
		}
	
		/**
		 * This method request registry to delete contact
		 *
		 * @param Contact $contact
		 * @param array $extra Extra fields
		 * @return DeleteContactResponse
		 */
		public function DeleteContact(Contact $contact, $extra = array()) 
		{
			$id = $this->CLIDToPersonId($contact->CLID);
			if (!$this->PersonExists($id))
			{
				throw new ObjectNotExistsException();
			}
			
			$binded = (int)$this->Db->GetOne("
				SELECT COUNT(*) FROM whois_domain 
				WHERE holder = ? OR admin_c = ? OR tech_c = ? OR bill_c = ?", 
				array($id, $id, $id, $id)
			);
			if ($binded)
			{
				throw new ProhibitedTransformException();
			}
			
			$ok = $this->Db->Execute("
				DELETE FROM whois_person
				WHERE `person_key` = ?",
				array($id)
			);
			
			$Ret = new DeleteContactResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
			$Ret->Result = (bool)$ok;
			return $Ret;
		}
	
		private function MapDomainToWhois (Domain $domain)
		{
			$Registrant = $domain->GetContact(CONTACT_TYPE::REGISTRANT);
			$Admin = $domain->GetContact(CONTACT_TYPE::ADMIN);
			$Tech = $domain->GetContact(CONTACT_TYPE::TECH);
			$Billing = $domain->GetContact(CONTACT_TYPE::BILLING);
			
			$data = array(
				'domain' => $domain->GetHostName(),
				'registered_date' => date('Y-m-d H:i:s', $domain->CreateDate),
				'registerexpire_date' => date('Y-m-d H:i:s', $domain->ExpireDate),
				'remarks' => '',
				'mntnr_fkey' => $this->GetMaintainerId()
			);
			
			if ($Registrant)
				$data['holder'] = $this->CLIDToPersonId($Registrant->CLID);
			if ($Admin)
				$data['admin_c'] = $this->CLIDToPersonId($Admin->CLID);
			if ($Tech)
				$data['tech_c'] = $this->CLIDToPersonId($Tech->CLID);
			if ($Billing)
				$data['bill_c'] = $this->CLIDToPersonId($Billing->CLID);
				
			return $data;
		}
		
		public function OnDomainCreated (Domain $domain)
		{
			$data = $this->MapDomainToWhois($domain);
			
			// Save domain to whois database
			$bind = array();
			foreach (array_keys($data) as $k)
			{
				$bind[] = "`$k` = ?";
			}
			$vals = array_values($data);
			
			$stmt = "INSERT INTO whois_domain SET " . join(', ', $bind);
			$this->Db->Execute($stmt, array_values($data));
			$id = $this->Db->Insert_ID();
			
			// Save nameservers
			foreach ($domain->GetNameserverList() as $ns)
			{
				$this->Db->Execute("
					INSERT INTO whois_nameserver
					SET nameserver = ?, domain_fkey = ?",
					array($ns->HostName, $id)
				);
			}
		}
	
		public function OnDomainUpdated (Domain $domain)
		{
			$id = $this->Db->GetOne("
				SELECT domain_key FROM whois_domain 
				WHERE domain = ?", 
				array($domain->GetHostName())
			);
			if (!$id)
			{
				// ??
				$this->OnDomainCreated($domain);
			}
			
			$data = $this->MapDomainToWhois($domain);
			// Save domain to whois database
			$bind = array();
			foreach (array_keys($data) as $k)
			{
				$bind[] = "`$k` = ?";
			}
			$vals = array_values($data);
			$vals[] = $id;
			
			$this->Db->Execute("
				UPDATE whois_domain 
				SET " . join(", ", $bind) . "
				WHERE domain_key = ?", 
				$vals
			);
			
			// Remove old nslist
			$this->Db->Execute("
				DELETE FROM whois_nameserver 
				WHERE domain_fkey = ?",
				array($id)
			);
			// Save nameservers
			foreach ($domain->GetNameserverList() as $ns)
			{
				$this->Db->Execute("
					INSERT INTO whois_nameserver
					SET nameserver = ?, domain_fkey = ?",
					array($ns->HostName, $id)
				);
			}
		}
		
		public function OnDomainDeleted (Domain $domain)
		{
			$id = $this->Db->GetOne("
				SELECT domain_key FROM whois_domain 
				WHERE domain = ?", 
				array($domain->GetHostName())
			);
			if ($id)
			{
				$this->Db->Execute("
					DELETE FROM whois_nameserver WHERE domain_fkey = ?", 
					array($id)
				);
				$this->Db->Execute("
					DELETE FROM whois_domain 
					WHERE domain = ?",
					array($domain->GetHostName())
				);
			}
		}
	
		/**
		 * Parse datetime description into a Unix timestamp Ignores timezone
		 */
		protected function StrToTime ($str)
		{
			return strtotime(substr($str, 0, -3));
		}		
	}
?>