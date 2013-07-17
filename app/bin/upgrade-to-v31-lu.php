<?php
	include ("../src/prepend.inc.php");
	
	$args = getopt('u:p:h:d:g:f');
	if (!($args['u'] && $args['p'] && $args['h'] && $args['d'] && isset($args['g'])))
	{
		$php_path = CONFIG::$PHP_PATH;
		
		// Print usage message
		print <<<T
Usage: $php_path -q upgrade-to-v3.php OPTIONS > upgrade.log
  -u     User
  -p     Password
  -h     Host
  -d     Database
  -g     GnuPG home directory (for SRSPlus)
  -f     Do not save failed domains as pending

T;
		die();
	}

	$dsn = "mysqli://{$args['u']}:{$args['p']}@{$args['h']}/{$args['d']}?&fetchmode=ADODB_FETCH_ASSOC#pear:extend:date:transaction";
	
	try
	{
		$options = array();
		if (isset($args['f']))
		{
			$options['SaveFailedAsPending'] = false;
		}
		if (isset($args['g']))
		{
			$options['GPGHomeDir'] = $args['g'];
		}
		
		EPPDRS_Upgrade::Run($dsn, $options);
	}
	catch (Exception $e)
	{
		print "Error: {$e->getMessage()}\n";
	}
	
	
	
	
	
	
	class EPPDRS_Upgrade
	{
		private $DbOld, $DbNew;
		
		/**
		 * @var RegistryModuleFactory
		 */
		private $RegistryFactory;
		
		/**
		 * @var DBContact
		 */
		private $DBContact;
		
		/**
		 * @var DBDomain
		 */
		private $DBDomain;
		
		/**
		 * @var Crypto
		 */
		private $Crypto;
		
		/**
		 * @var array
		 */
		private $TableList;
		
		private $ModuleConfigKeyMap = array(
			'EPPGR' => array(
				// old => new
				'CLID' 			=> 'CLID',
				'UseSSL' 		=> 'UseSSLCert',
				'Password' 		=> 'Password',
				'ClientPrefix' 	=> 'ClientPrefix',
				'SSLpwd'		=> 'SSLpwd',
				'Login'			=> 'Login',
				'ServerHost'	=> 'ServerHost'
								// SSLCertPath
			),
			'EPPLU' => array(
				// old => new
				'Login' 		=> 'Login',
				'Password' 		=> 'Password',
				'ServerHost' 	=> 'ServerHost',
				'ServerPort' 	=> 'ServerPort'			
			),
			'OnlineNIC' => array(
				// old => new
				'Login' 		=> 'Login',
				'Password' 		=> 'Password',
				'ServerHost' 	=> 'ServerHost',
				'ServerPort' 	=> 'ServerPort'
			),
			'SRSPlus' => array(
				// old => new
				'email'			=> 'Email',
				'Login'			=> 'Login',
				'testmode'		=> 'TestMode',
				'host'			=> 'Host',
				'gpgpass'		=> 'GPGPass'
								// GPGPath
								// GPGHomeDir
			),
			'COCCAEPP1' => array(
				// old => new
				'Login' 		=> 'Login',
				'Password' 		=> 'Password',
				'ServerHost' 	=> 'ServerHost',
				'ServerPort' 	=> 'ServerPort'
			),
			'COCCAEPP2' => array(
				// old => new
				'Login' 		=> 'Login',
				'Password' 		=> 'Password',
				'ServerHost' 	=> 'ServerHost',
				'ServerPort' 	=> 'ServerPort'
			),
			'COCCAEPP3' => array(
				// old => new
				'Login' 		=> 'Login',
				'Password' 		=> 'Password',
				'ServerHost' 	=> 'ServerHost',
				'ServerPort' 	=> 'ServerPort'
			)
		);
		
		private $ModuleNameMap = array(
			// old => new
			'EPPGR'			=> 'EPPGR',
			'EPPLU'			=> 'EPPLU',
			'OnlineNIC'		=> 'OnlineNIC',
			'SRSPlus'		=> 'SRSPlus',
			//'GenericEPP'	=> 'GenericEPP',
			// В старой версии перепутаны модули COCCAEPP1 и COCCAEPP2
			'ccTLDCOCCA1' 	=> 'COCCAEPP2',
			'ccTLDCOCCA2' 	=> 'COCCAEPP1', 
			'ccTLDCOCCA3' 	=> 'COCCAEPP3'
		);
		
		private $ModuleNameMapFlip = array();
	
		
		private $FailedActiveDomains = array();
		
		private $operation_purpose_map = array(
			'Create'	=> INVOICE_PURPOSE::DOMAIN_CREATE,
			'Transfer' 	=> INVOICE_PURPOSE::DOMAIN_TRANSFER,
			'Renew'		=> INVOICE_PURPOSE::DOMAIN_RENEW,
			'Trade'		=> INVOICE_PURPOSE::DOMAIN_TRADE
		);
		
		////
		// Options
		// 
		private $SaveFailedAsPending = true;
		private $GPGHomeDir = '/root/.gnupg';
		
		public function __construct($old_dsn, $options)
		{
			foreach ($options as $flag => $b)
			{
				$this->{$flag} = $b;
			}
			
			$this->DbOld = &NewADOConnection($old_dsn);
			$this->DbOld->SetFetchMode(ADODB_FETCH_ASSOC);
			$this->DbNew = Core::GetDBInstance();
			$this->DBDomain = DBDomain::GetInstance();
			$this->DBContact = DBContact::GetInstance();
			$this->RegistryFactory = RegistryModuleFactory::GetInstance();
			$this->Crypto = $GLOBALS['Crypto'];
			
			$this->TableList = array();
			foreach ($this->DbOld->GetAll('SHOW TABLES') as $row)
			{
				$this->TableList[] = array_shift(array_values($row)); // first column in row
			}
			
			$this->ModuleNameMapFlip = array_flip($this->ModuleNameMap);
		}
		
		public function GetSkipList ()
		{
			return array(
				/*
				'ConvertConfig',
				'ConvertRegistryModules',
				'ConvertModulesPricing',
				'ConvertPaymentModules',
				'ConvertUsers',
				'ConvertDiscounts',
				'ConvertInvoices',
				'ConvertZones',
				'ConvertUserSettings',
				*/
				// Works only on customer server or our Web3
				/*
				'ConvertDomains',
				'ConvertActiveDomains',
				'ConvertInactiveDomains',
				'ConvertContacts'
				*/
			);
		}
		
		public function ConvertConfig ()
		{
			$this->Log('Convert config');
	
			// Load new default config 
			$config_defaults = array();		
			$data = $this->DbNew->GetAll('SELECT * FROM config');
			foreach ($data as $row)
			{
				$config_defaults[$row['key']] = $row['value'];
			}
				 
			// Load old eppdrs config
			$config_data = array(); 
			$data = $this->DbOld->GetAll('SELECT * FROM config');
			foreach ($data as $row)
			{
				// Convert old cofig variable syntax like email/admin
				$key = str_replace('/', '_', $row['key']);
				
				$config_data[$key] = $row['value'];
			}
	
			// Merge new defaults with old config
			// array_merge not used here, because keys must be only that defined in $config_defaults  
			foreach (array_keys($config_defaults) as $key)
			{
				$config_data[$key] = array_key_exists($key, $config_data) ? 
					$config_data[$key] : 
					$config_defaults[$key];
			}
			
			// Save config
			foreach ($config_data as $key => $value)
			{
				$this->DbNew->Execute(
					'UPDATE config SET `value` = ? WHERE `key` = ?',
					array($value, $key)
				);
			}
		}
		
		public function ConvertRegistryModules ()
		{
			$this->Log('Convert registry modules');
	
			$this->TruncateTable('modules', 'modules_config');
	
			////
			// Modules
			//
			$module_rset = $this->DbOld->GetAll('SELECT * FROM modules');
			foreach ($module_rset as &$row)
			{
				// Map old module name to new
				$row['name'] = $this->ModuleNameMap[$row['name']];
			}
			$this->BulkInsert('modules', $module_rset);
			
			////
			// Modules configuration
			//
			foreach ($this->ModuleConfigKeyMap as $module_name => $key_map)
			{
				// Get old module name
				$old_module_name = $this->ModuleNameMapFlip[$module_name];
	
				// Check that module was installed
				$module_setuped = (int)$this->DbOld->GetOne(
					'SELECT COUNT(*) FROM modules WHERE name = ?', 
					array($old_module_name)
				);
				if (!$module_setuped)
				{
					// Module was not installed 
					continue;
				}
				
				// Get module configuration from old DB
				$module_config = array();
				foreach ($key_map as $key_old => $key_new)
				{
					$module_config[$key_new] = $this->DbOld->GetOne(
						'SELECT value FROM modules_config WHERE `key` = ? AND module_name = ?', 
						array($key_old, $old_module_name)
					);
				}
				
				// Apply module specific configuration
				if ($module_name == 'EPPGR')
				{
					if (preg_match('/regepp/', $module_config['ServerHost']))
					{
						$module_config['UseSSLCert'] = '';
					}
					$module_config['SSLCertPath'] = $GLOBALS['modules_path'] . '/registries/EPPGR/ssl/cert.pem';	
				}
				else if ($module_name == 'SRSPlus')
				{
					exec('which gpg', $out);
					$module_config['GPGPath'] = $out[0];
					$module_config['GPGHomeDir'] = $this->GPGHomeDir;
				}
	
				$Crypto = $GLOBALS['Crypto'];
				$Registry = $this->RegistryFactory->GetRegistryByName($module_name);
				
				$this->Log("Convert $module_name config");
				foreach ($Registry->GetModule()->GetConfigurationForm()->ListFields() as $Field) 
				{
					$this->DbNew->Execute(
						"INSERT modules_config 
						SET 
							`title`	= ?, 
							`type`	= ?, 
							`key` = ?, 
							`value` = ?, 
							`module_name` = ?",
						array(
							$Field->Title,
							$Field->FieldType,
							$Field->Name,
							$Crypto->Encrypt($module_config[$Field->Name], LICENSE_FLAGS::REGISTERED_TO),
							$module_name
						)
					);
				}
			}
		}
		
		public function ConvertPricing ()
		{
			$this->Log('Convert modules pricing');
			
			$this->TruncateTable('tlds', 'prices');
			
			if ($this->OldColumnExists('prices', 'isres'))
			{
				// Old version
				$this->ConvertPricingOld();
			}
			else
			{
				// The most recent version
				
				// Covert tlds
				$tld_rowset = $this->DbOld->GetAll('SELECT * FROM tlds');
				foreach ($tld_rowset as &$row)
				{
					$row['modulename'] = $this->ModuleNameMap[$row['modulename']];
				}
				$this->BulkInsert('tlds', $tld_rowset);
				
				// Copy prices
				$this->CopyTable('prices');
			}
			
			foreach ($this->operation_purpose_map as $operation => $purpose)
			{
				$this->DbNew->Execute(
					'UPDATE prices SET purpose = ? WHERE purpose = ?', 
					array($purpose, $operation)
				);
			}			
		}
		
		private function ConvertPricingOld ()
		{
			$modules_data = $this->DbNew->GetAll('SELECT * FROM modules');
	
			$package_column = $this->OldColumnExists('prices', 'packageid');
			$extra_stmts = $package_column ? "AND packageid = '0'" : "";
			
			foreach ($modules_data as $module)
			{
				$Registry = $this->RegistryFactory->GetRegistryByName($module['name']);
				$Manifest = $Registry->GetManifest();
				
				foreach ($Manifest->GetExtensionList() as $tld)
				{
					$Manifest->SetExtension($tld);
					$section_config = $Manifest->GetSectionConfig();
					
					$isActive = $this->DbOld->GetOne(
						"SELECT DISTINCT isactive FROM prices WHERE TLD = ? AND isres = 0 $extra_stmts",
						array($tld)
					);
					
					$this->DbNew->Execute(
						"INSERT INTO tlds SET TLD = ?, isactive = ?, modulename = ?",
						array($tld, (int)$isActive, $module['name'])
					);
					
					$prices = $this->DbOld->GetAll("SELECT * FROM prices WHERE TLD=? AND isres=0 $extra_stmts", array($tld));
					foreach ($prices as $price)
					{
						if ($price["operation"] != 'Transfer' && $price["operation"] != 'Repair')
						{
							for ($i = (int)$section_config->domain->registration->min_period; 
								 $i<=(int)$section_config->domain->registration->max_period;
								 $i++)
							{
								$this->DbNew->Execute(
									"INSERT INTO prices SET purpose=?, cost=?, TLD=?, period=?", 
									array($price['operation'], $price['cost'], $tld, $i)
								);
							}
						}
						elseif ($price["operation"] == 'Transfer')
						{
							$this->DbNew->Execute(
								"INSERT INTO prices SET purpose=?, cost=?, TLD=?, period=?", 
								array('Transfer', $price['cost'], $tld, 0)
							);
						}
					}
				}
			}
		}
		
		public function ConvertUsers ()
		{
			$this->Log('Convert users');
			
			$skip_fields = array(
				'opt_canMtech', 'opt_canMbill', 
				'opt_canbulkregister', 'isres',
	  			'tax', 'idnumber', 'mobile'		
			);
			$phone_fields = array(
				'phone', 'fax'
			);
			$Phone = Phone::GetInstance();
	
			$user_data = $this->DbOld->GetAll('SELECT * FROM users');		
			foreach ($user_data as &$row)
			{
				// Process skipped fields
				foreach ($skip_fields as $f)
				{
					unset($row[$f]);				
				}
				
				// Convert phone and fax
				foreach ($phone_fields as $f)
				{
			    	if ($row[$f])
			    	{
						$chunks = str_split(preg_replace("/[^0-9]+/", "", $row[$f]), 3);
				    	$e164 = trim("+".array_shift($chunks).".".implode("", $chunks));				
						$row[$f] = $Phone->E164ToPhone($e164);
			    	}
				}
			}
			
			$this->TruncateTable('users', 'client_fields', 'client_info');
			$this->BulkInsert('users', $user_data);
			
			if ($this->OldTableExists('cliend_fields', 'client_info'))
			{
				$this->CopyTable('client_fields');
				$this->CopyTable('client_info');			
			}
		}
		
		
		public function ConvertDiscounts ()
		{
			$this->Log('Convert discounts');		
			
			$package 		= $this->OldTableExists('packages');
			$discount		= $this->OldTableExists('discounts');
			$price_isres 	= $this->OldColumnExists('prices', 'isres');
			$user_isres 	= $this->OldColumnExists('users', 'isres');
			$price_packageid= $this->OldColumnExists('prices', 'packageid');
			$user_packageid = $this->OldColumnExists('users', 'packageid');
	
			$this->TruncateTable('discounts', 'packages');		
			
			if ($package && $discount)
			{
				// The most recent version
				$this->CopyTable('discounts');			
				$this->CopyTable('packages');
			}
			else if ($package && $price_packageid && $user_packageid)
			{
				// r500
				$this->ConvertDiscountsR500();
			}
			else if ($price_isres && $user_isres)
			{
				// r210
				$this->ConvertDiscountsR210();
			}
			
			foreach ($this->operation_purpose_map as $operation => $purpose)
			{
				$this->DbNew->Execute(
					'UPDATE discounts SET purpose = ? WHERE purpose = ?', 
					array($purpose, $operation)
				);
			}			
		}
		
		private function ConvertDiscountsR500 ()
		{
			$this->Log('Use r500 strategy');
			
			// Copy packges
			$this->CopyTable('packages');
			
			// Convert discounts
			$old_discounts = $this->DbOld->GetAll("
				SELECT CONCAT(TLD, '-', operation) as tldop, prices.* 
				FROM prices WHERE packageid != 0 AND operation != 'Repair' AND isactive = 1
			");
			
			$prices = $this->DbOld->GetAll("
				SELECT CONCAT(TLD, '-', operation) as tldop, cost
				FROM prices WHERE packageid = 0 AND operation != 'Repair'
			");
			$tldop_cost_map = array();
			foreach ($prices as $row)
			{
				$tldop_cost_map[$row['tldop']] = $row['cost'];
			}
			
			$discount_data = array();
			foreach ($old_discounts as $row)
			{
				$original_cost = (float)$tldop_cost_map[$row['tldop']];
				$discount_cost = (float)$row['cost'];
				if ($original_cost)
				{
					$discount_pc = round(100*(1-$discount_cost/$original_cost), 0); // % 
				}
				else
				{
					$discount_pc = 0;	
				}
				 
				
				$discount_data[] = array(
					'packageid' => $row['packageid'],
					'TLD' => $row['TLD'],
					'purpose' => $row['operation'],
					'discount' => $discount_pc
				);
			}
			$this->BulkInsert('discounts', $discount_data);
		}
		
		private function ConvertDiscountsR210 ()
		{
			$this->Log('Use r210 strategy');		
			
			// Create Reseller package
			$this->DbNew->Execute("
				INSERT INTO packages SET id = 1, name = 'Reseller'
			");
			
			// Apply it to resellers
			$user_isres_data = $this->DbOld->GetAll("
				SELECT id FROM users WHERE isres = '1' 
			");
			foreach ($user_isres_data as $row)
			{
				$this->DbNew->Execute("
					UPDATE users SET packageid = 1 WHERE id = ?",
					array($row['id'])
				);
			}
			
			// Convert discounts
			$old_discounts = $this->DbOld->GetAll("
				SELECT CONCAT(TLD, '-', operation) as tldop, operation, TLD, cost 
				FROM prices WHERE isres = 1 AND operation != 'Repair' AND isactive = 1
			");
			
			$prices = $this->DbOld->GetAll("
				SELECT CONCAT(TLD, '-', operation) as tldop, cost
				FROM prices WHERE isres = 0 AND operation != 'Repair'
			");
			$tldop_cost_map = array();
			foreach ($prices as $row)
			{
				$tldop_cost_map[$row['tldop']] = $row['cost'];
			}
	
			$discount_data = array();
			foreach ($old_discounts as $row)
			{
				$original_cost = (float)$tldop_cost_map[$row['tldop']];
				$discount_cost = (float)$row['cost'];
				if ($original_cost)
				{
					$discount_pc = round(100*(1-$discount_cost/$original_cost), 0); // % 
				}
				else
				{
					$discount_pc = 0;	
				}
				 
				
				$discount_data[] = array(
					'packageid' => '1',
					'TLD' => $row['TLD'],
					'operation' => $row['operation'],
					'discount' => $discount_pc
				);
			}
			$this->BulkInsert('discounts', $discount_data);		
		}	
		
		public function ConvertPaymentModules ()
		{
			$this->Log('Convert payment modules');
	
			// Clear tables
			$this->TruncateTable('pmodules', 'pmodules_config');
			
			// Copy pmodules table
			$pmodule_rset = $this->DbOld->GetAll('SELECT * FROM pmodules');
			foreach ($pmodule_rset as &$row)
			{
				if ($row['name'] == 'offline_payment')
				{
					$row['name'] = 'OfflineBank';
				}
			}
			$this->BulkInsert('pmodules', $pmodule_rset);
			// For each pmodule copy config settings
			
			$pmodule_config = array();
			$Crypto = $GLOBALS['Crypto'];
			foreach ($pmodule_rset as $pmodule)
			{
				// Get old config form for current pmodule
				$rset = $this->DbOld->GetAll(
					'SELECT * FROM pmodules_config WHERE module_name = ?',
					array($pmodule['name'])
				);
				foreach ($rset as $row)
				{
					// Encrypt config value
					$row['value'] = $this->Crypto->Encrypt($row['key'], LICENSE_FLAGS::REGISTERED_TO);
					// Push it to pmodule config
					$pmodule_config[] = $row;
				}
	 		}
			$this->BulkInsert('pmodules_config', $pmodule_config);		
		}
		
		
		public function ConvertInvoices ()
		{
			$this->Log('Convert invoices');
	
			$this->TruncateTable('invoices', 'orders');		
			
			$PaymentFactory = PaymentModuleFactory::GetInstance();
			$same_keys = array(
				'id', 'userid', 'dtcreated', 'status', 'description', 'notes', 'orderid', 'vat'
			);			
			$invoice_data = $this->DbOld->GetAll('SELECT * FROM invoices');
			foreach ($invoice_data as &$old_row)
			{
				if ($old_row['gate'] == 'offline_payment')
				{
					$old_row['gate'] = 'OfflineBank';
				}
				
				
				// Copy data with same keys
				foreach ($same_keys as $key)
				{
					$new_row[$key] = $old_row[$key];
				}
				
				// Convert data 
				$new_row['total'] = $old_row['amount'];
				$new_row['itemid'] = $old_row['domainid'];				
				$new_row['purpose'] = 'Domain_' . $old_row['command'];
				try
				{
					$PaymentModule = $PaymentFactory->GetModuleObjectByName($old_row['gate']);
					$payment_module = $PaymentModule->GetModuleName();
				}
				catch (Exception $e)
				{
					$payment_module = $old_row['gate'];
				}
				$new_row['payment_module'] = $payment_module;
				$new_row['dtupdated'] = $new_row['dtcreated'];
				
				$this->Insert("invoices", $new_row);
			}
			
			if ($this->OldTableExists('orders'))
			{
				$this->CopyTable('orders');
			}
		}
		
		public function ConvertZones ()
		{
			if (!$this->OldTableExists('nameservers', 'records', 'zones'))
			{
				return;
			}
				
			$this->Log('Convert dns');
	
			$this->TruncateTable('nameservers', 'records', 'zones');
			$this->CopyTable('nameservers');
			$this->CopyTable('zones');
			$this->CopyTable('records');
		}
		
		public function ConvertUserSettings ()
		{
			if (!$this->OldTableExists('user_settings'))
				return;
				
			$this->Log('Convert user settings');
	
			$this->TruncateTable('user_settings');
			$this->CopyTable('user_settings');
		}
	
		public function ConvertDomains ()
		{
			// Clean all domains related data
			$this->TruncateTable(
				'domains', 'domains_data', 'domains_flags',
				'contacts', 'contacts_data', 'contacts_discloses', 
				'nhosts'
			);
		}
		
		public function ConvertActiveDomains ()
		{
			$this->Log('Import delegated domains');
			
			$this->FailedActiveDomains = array();
			
			// Import delegated domains
			$domains = $this->DbOld->GetAll("
				SELECT * FROM domains 
				WHERE status='Delegated' 
				ORDER BY id ASC
			");
			$ok = $fail = 0;
			foreach ($domains as $i => $dmn)
			{
				// This is long time loop, DbOld connection may be lost due timeout,
				// so we need to ping it
				$this->PingDatabase($this->DbOld);
				
				// Check for duplicate domains
				$imported = (int)$this->DbNew->GetOne(
					'SELECT COUNT(*) FROM domains WHERE name = ? AND TLD = ?', 
					array($dmn['name'], $dmn['TLD'])
				);
				if ($imported)
				{
					// Skip duplicate domain
					continue;
				}
				
				try
				{
					$Registry = $this->RegistryFactory->GetRegistryByExtension($dmn['TLD'], $db_check=false);
					
					$Domain = $Registry->NewDomainInstance();
					$Domain->Name = $dmn['name'];
					
					$this->Log("Import {$Domain->GetHostName()}");
				
					if (!DBDomain::ActiveDomainExists($Domain))
					{						
						try
						{
							$Domain = $Registry->GetRemoteDomain($Domain);
						}
						catch(Exception $e)
						{
							$err[] = sprintf("%s: %s", $Domain->GetHostName(), $e->getMessage());
						}
						
						if ($Domain->RemoteCLID)
						{
						    if ($Domain->RemoteCLID == $Registry->GetRegistrarID() || $Domain->AuthCode != '')
	    					{
	    						$contacts_list = $Domain->GetContactList();
	    						// Apply contacts to domain owner
								foreach ($contacts_list as $Contact)
								{
									$Contact->UserID = $dmn['userid'];
								}
	    						
	    						if (count($err) == 0)
	    						{
	    							$period = date("Y", $Domain->ExpireDate)-date("Y", $Domain->CreateDate);
	    							$Domain->Status = DOMAIN_STATUS::DELEGATED;
	    							$Domain->Period = $period;
	    							$Domain->UserID = $dmn['userid'];
	    							$Domain->ID = $dmn['id'];
	    							
	    							try
	    							{
	    								$this->DbNew->Execute('INSERT INTO domains SET id = ?', array($dmn['id']));
	    								$this->DBDomain->Save($Domain);
										$ok++;
	    							}
	    							catch(Exception $e)
	    							{
	    								$err[] = sprintf("%s: %s", $Domain->GetHostName(), $e->getMessage());
	    							}
	    						}
	    					}
	    					else
	    						$err[] = sprintf(_("'%s' cannot be imported because it does not belong to the current registar."), $Domain->GetHostName());	
						}
					}
					else
						$err[] = sprintf(_("Domain '%s' already exists in our database."), $Domain->GetHostName());
						
					foreach ($err as $errmsg)
					{
						$this->Log($errmsg, E_USER_ERROR);
						$err = array();
						$fail++;
					}
				}
				catch (Exception $e)
				{
					$this->Log($e->getMessage(), E_USER_ERROR);
					$fail++;
					if ($this->SaveFailedAsPending)
					{
						$this->FailedActiveDomains[] = $dmn;
					}
				}
			}
			
			$this->Log(sprintf("Imported: %s; Failed: %s", $ok, $fail));
		}
		
		public function ConvertInactiveDomains ()
		{
			$this->Log('Import inactive domains');
			
			// Get master columns
			$show_columns = $this->DbNew->GetAll('SHOW COLUMNS FROM domains');
			$column_list = array();
			foreach ($show_columns as $row)
			{
				$column_list[] = $row['Field'];
			}
			
			
			// Fetch undelegated domains
			$domain_rset = $this->DbOld->GetAll("SELECT * FROM domains WHERE status != 'Delegated'");
			
			if ($this->SaveFailedAsPending)
			{
				// Prepare undelegated inactive domains
				foreach ($this->FailedActiveDomains as &$dmn)
				{
					$dmn['status'] = DOMAIN_STATUS::PENDING;
				}
				
				// Merge undelegated and failed domains
				$domain_rset = array_merge($domain_rset, $this->FailedActiveDomains);				
			}
			
			foreach ($domain_rset as $row)
			{
				$this->Log("Import {$row['name']}.{$row['TLD']}");
				
				$row['c_billing'] = $row['c_bill'];
				$data = array();
				foreach ($column_list as $key)
				{
					$data[$key] = $row[$key];
				}
				$this->Insert('domains', $data);
			}
			$this->Log(sprintf("Imported: %s; Failed: %s", count($domain_rset), 0));
	
			// Import flags		
			if ($this->OldTableExists('domains_flags'))
			{
				// > r210 
				$domain_flag_rset = $this->DbOld->GetAll("
					SELECT f.* FROM domains_flags AS f
					INNER JOIN domains AS d ON f.domainid = d.id
					WHERE d.status != 'Delegated'
				");
				$this->BulkInsert('domains_flags', $domain_flag_rset);
			}
		}
		
		public function ConvertContacts ()
		{
			$contact_data = $this->DbOld->GetAll('SELECT * FROM contacts');
			$ok = $fail = 0;
			foreach ($contact_data as $i => $contact_row)
			{
				$imported = (int)$this->DbNew->GetOne(
					'SELECT COUNT(*) FROM contacts WHERE clid = ?', 
					array($contact_row['clid'])
				);
				if ($imported)
				{
					// Skip contact, that was imported with domain in $this->ConvertActiveDomains
					continue;
				}
				
				$this->Log(sprintf('Import contact %s', $contact_row['clid']));
				
				if ($contact_row['type'] == 'bill')
				{
					$contact_row['type'] = CONTACT_TYPE::BILLING;
				}
				
				$Registry = $this->RegistryFactory->GetRegistryByExtension($contact_row['TLD'], $db_check = false);
				$Contact = $Registry->NewContactInstance($contact_row['type']);
				$Contact->CLID = $contact_row['clid'];
				$Contact->AuthCode = $contact_row['pw'];
				$Contact->UserID = $contact_row['userid'];
				
				try
				{
					$Contact = $Registry->GetRemoteContact($Contact);
					$this->DBContact->Save($Contact);
					$ok++;
				}
				catch (Exception $e)
				{
					$this->Log($e->getMessage(), E_USER_ERROR);
					$fail++;
				}
			}
			
			$this->Log(sprintf("Imported: %s; Failed: %s", $ok, $fail));
		}
		
		private function Log ($message, $level=E_USER_NOTICE)
		{
			if ($level == E_USER_ERROR)
			{
				print "Error: $message\n";
			}
			else
			{
				print "$message\n";			
			}
			flush();
		}
		
		private function CopyTable ($table)
		{
			$this->Log(sprintf('Copy data from old %s', $table));
			
			$rowset = $this->DbOld->GetAll("SELECT * FROM {$table}");
			$this->BulkInsert($table, $rowset);
		}
		
		private function BulkInsert ($table, $data)
		{
			if (!count($data))
				return;
				
			$this->Log(sprintf('Insert data in new %s', $table));
	
			// Create data bind 
			$bind = array();
			foreach (array_keys($data[0]) as $k)
			{
				$bind[] = "`$k` = ?";
			}
			$stmt = 'INSERT INTO ' . $table . ' SET ' . join(', ', $bind);
				
			foreach ($data as $row)
			{
				$this->DbNew->Execute($stmt, array_values($row));
			}
		}
		
		private function Insert ($table, $row)
		{
			// Create data bind 
			$bind = array();
			foreach (array_keys($row) as $k)
			{
				$bind[] = "`$k` = ?";
			}
			$stmt = 'INSERT INTO ' . $table . ' SET ' . join(', ', $bind);
				
			$this->DbNew->Execute($stmt, array_values($row));
		}
		
		private function TruncateTable ($table1 /* table2 ... tableN */)
		{
			foreach ($tables=func_get_args() as $table)
				$this->DbNew->Execute("TRUNCATE TABLE {$table}");
		}
		
		private function PingDatabase ($Db)
		{
			if ($Db->connectionId instanceof mysqli)
			{
				$Db->connectionId->ping();
			}
		}
		
		private function OldTableExists ($table1 /* table2 ... tableN */)
		{
			$arg_table_list = func_get_args();
			$intersect = array_values(array_intersect($this->TableList, $arg_table_list));
			sort($intersect);
			sort($arg_table_list);
			
			return $intersect == $arg_table_list;
		}
		
		private function OldColumnExists ($table, $column)
		{
			return count($this->DbOld->GetAll("SHOW COLUMNS FROM {$table} LIKE '{$column}'")) == 1;
		}
		
		public static function Run ($dsn, $options=array())
		{
			$Upgrade = new EPPDRS_Upgrade($dsn, $options);
			$skip_list = $Upgrade->GetSkipList();
			
			$Ref = new ReflectionObject($Upgrade);
			foreach ($Ref->getMethods() as $Method)
			{
				$method_name = $Method->getName();
				if (
					strpos($method_name, 'Convert') === 0 && 
					$Method->isPublic() && 
					!in_array($method_name, $skip_list)
				)
				{
					print "\n+ Run {$method_name} \n";
					$Method->invoke($Upgrade);	
				}
			}
		}
	}


?>