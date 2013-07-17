<?php
	
	define("NO_TEMPLATES", true);
	include (dirname(__FILE__) . "/../src/prepend.inc.php");

	Log::Log("upgrade-to-v3-autoup.php executed.", E_USER_NOTICE);
	
	$migration_query_list = array(
		"insert into `config`(`id`,`key`,`value`) values ( NULL,'php_path','/usr/local/php')",
		"INSERT INTO config SET `key` = 'phone_format', `value` = '+[cc]-[2-4]-[4-10]'",
		"create table `contacts_discloses` (  `id` int (11) NOT NULL AUTO_INCREMENT , `contactid` varchar (255) , `field_name` varchar (255) , `value` tinyint (1) DEFAULT '0', PRIMARY KEY (`id`))",
		"create table `pending_operations` (  `id` int (11) NOT NULL AUTO_INCREMENT , `objectid` int (11) , `dtbegin` datetime , `operation` enum ('CREATE','UPDATE','DELETE','TRADE','TRANSFER') , `objecttype` enum ('DOMAIN','CONTACT','NAMESERVERHOST') , PRIMARY KEY (`id`))",
		"alter table `contacts_discloses` Engine = InnoDB",
		"alter table `pending_operations` Engine = InnoDB",
		"alter table `contacts_discloses` add unique `contactid` (`contactid`, `field_name`)",
		"alter table `invoices` add column `orderid` int (11) DEFAULT '0' NULL  after `notes`",
		"create table `orders` (  `id` int (11) NOT NULL AUTO_INCREMENT , `userid` int (11) , PRIMARY KEY (`id`))",
		"alter table `orders` change `id` `id` int (11)ZEROFILL   NOT NULL AUTO_INCREMENT",
		"alter table `orders` Engine = InnoDB",
		"alter table `client_fields` add column `elements` text   NULL  after `defval`,change `type` `type` enum ('TEXT','BOOL','SELECT') CHARACTER SET latin1  COLLATE latin1_swedish_ci  DEFAULT 'TEXT' NULL",
		"CREATE TABLE `objects_history` (`id` int(11) NOT NULL auto_increment, `type` enum('DOMAIN','CONTACT','HOST') default NULL, `object` varchar(255) default NULL, `operation` enum('CREATE','UPDATE','DELETE','TRADE','TRANSFER-REQUEST','TRANSFER-APPROVE','TRANSFER-DECLINE','RENEW') default NULL, `state` tinyint(1) default '1',  `before_update` text,  `after_update` text,  `transaction_id` varchar(255) default NULL,  `dtadded` datetime default NULL,  PRIMARY KEY  (`id`)) ENGINE=InnoDB DEFAULT CHARSET=latin1",	
		"alter table `contacts` add column `parent_clid` varchar (16)  NULL  after `status`",
		"alter table `contacts` add column `groupname` varchar (8)  NULL  after `parent_clid`,change `type` `type` enum ('billing','tech','admin','registrant','mixed') CHARACTER SET latin1  COLLATE latin1_swedish_ci  DEFAULT 'registrant' NOT NULL",
		"alter table `contacts` change `groupname` `groupname` varchar (64) CHARACTER SET latin1  COLLATE latin1_swedish_ci   NULL",
		"ALTER TABLE `contacts` ADD COLUMN `strict_fields` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER `groupname`",
		"CREATE TABLE callcodes (`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT, `code` VARCHAR(10) NOT NULL, `area_name` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE = InnoDB",
		"insert  into `callcodes`(`id`,`code`,`area_name`) values (1,'0',''),(2,'1',''),(3,'7',''),(4,'20',''),(5,'27',''),(6,'30',''),(7,'31',''),(8,'32',''),(9,'33',''),(10,'34',''),(11,'36',''),(12,'39',''),(13,'40',''),(14,'41',''),(15,'43',''),(16,'44',''),(17,'45',''),(18,'46',''),(19,'47',''),(20,'48',''),(21,'49',''),(22,'51',''),(23,'52',''),(24,'53',''),(25,'54',''),(26,'55',''),(27,'56',''),(28,'57',''),(29,'58',''),(30,'60',''),(31,'61',''),(32,'62',''),(33,'63',''),(34,'64',''),(35,'65',''),(36,'66',''),(37,'81',''),(38,'82',''),(39,'84',''),(40,'86',''),(41,'90',''),(42,'91',''),(43,'92',''),(44,'93',''),(45,'94',''),(46,'95',''),(47,'201',''),(48,'212',''),(49,'213',''),(50,'216',''),(51,'220',''),(52,'221',''),(53,'222',''),(54,'223',''),(55,'224',''),(56,'225',''),(57,'226',''),(58,'227',''),(59,'228',''),(60,'229',''),(61,'230',''),(62,'231',''),(63,'232',''),(64,'233',''),(65,'234',''),(66,'235',''),(67,'236',''),(68,'237',''),(69,'238',''),(70,'239',''),(71,'240',''),(72,'241',''),(73,'242',''),(74,'243',''),(75,'244',''),(76,'245',''),(77,'247',''),(78,'248',''),(79,'249',''),(80,'250',''),(81,'251',''),(82,'252',''),(83,'253',''),(84,'254',''),(85,'255',''),(86,'256',''),(87,'257',''),(88,'258',''),(89,'260',''),(90,'261',''),(91,'262',''),(92,'264',''),(93,'265',''),(94,'266',''),(95,'267',''),(96,'268',''),(97,'269',''),(98,'290',''),(99,'291',''),(100,'297',''),(101,'298',''),(102,'299',''),(103,'350',''),(104,'351',''),(105,'352',''),(106,'353',''),(107,'354',''),(108,'355',''),(109,'356',''),(110,'357',''),(111,'358',''),(112,'359',''),(113,'370',''),(114,'371',''),(115,'372',''),(116,'373',''),(117,'374',''),(118,'375',''),(119,'376',''),(120,'378',''),(121,'380',''),(122,'381',''),(123,'382',''),(124,'385',''),(125,'386',''),(126,'387',''),(127,'389',''),(128,'420',''),(129,'421',''),(130,'423',''),(131,'500',''),(132,'501',''),(133,'502',''),(134,'503',''),(135,'504',''),(136,'505',''),(137,'506',''),(138,'507',''),(139,'508',''),(140,'509',''),(141,'590',''),(142,'591',''),(143,'592',''),(144,'593',''),(145,'594',''),(146,'595',''),(147,'596',''),(148,'597',''),(149,'598',''),(150,'599',''),(151,'670',''),(152,'672',''),(153,'673',''),(154,'674',''),(155,'675',''),(156,'676',''),(157,'677',''),(158,'678',''),(159,'679',''),(160,'680',''),(161,'681',''),(162,'682',''),(163,'683',''),(164,'684',''),(165,'685',''),(166,'686',''),(167,'687',''),(168,'688',''),(169,'689',''),(170,'690',''),(171,'691',''),(172,'692',''),(173,'809',''),(174,'850',''),(175,'852',''),(176,'853',''),(177,'855',''),(178,'856',''),(179,'872',''),(180,'880',''),(181,'886',''),(182,'960',''),(183,'961',''),(184,'962',''),(185,'964',''),(186,'965',''),(187,'966',''),(188,'967',''),(189,'968',''),(190,'971',''),(191,'972',''),(192,'973',''),(193,'974',''),(194,'975',''),(195,'976',''),(196,'977',''),(197,'993',''),(198,'994',''),(199,'995',''),(200,'998','')",
		"alter table `countries` add column `enabled` tinyint (1) DEFAULT '1' NULL  after `code`, add column `vat` int (2) DEFAULT '0' NULL  after `enabled`",
		"alter table `users` drop column `vat`",
		"alter table `users` add column `vat` int (2) DEFAULT '0' NULL",
		"alter table `invoices` add column `vat` int (2) DEFAULT '0' NULL  after `orderid`",
		"ALTER TABLE `syslog` ADD INDEX `transactionid` (`transactionid` ( 255 ))"
	);
	
	$cleanup_query_list = array(
		"alter table `domains` drop column `transfer_email`, drop column `status_description`,change `sys_status` `sys_status` text  CHARACTER SET latin1  COLLATE latin1_swedish_ci   NULL",
		"alter table `invoices` drop column `proccessed`",
		"alter table `contacts` drop column `disclose_name`, drop column `disclose_org`, drop column `disclose_addr`, drop column `disclose_voice`, drop column `disclose_fax`, drop column `disclose_email`",
		"alter table `contacts` drop column `type`",
		"alter table `users` drop column `opt_canMtech`, drop column `opt_canMbill`, drop column `opt_canbulkregister`, drop column `isres`, drop column `idnumber`, drop column `tax`, drop column `mobile`",
		"drop table `tld_conflicts`"	
	);
	
	
	
	$Db = Core::GetDBInstance();

	
	$Db->StartTrans();	
	
	try
	{	
		if (count($Db->GetAll("SHOW COLUMNS FROM contacts LIKE 'groupname'")) == 1)
		{
			$mess = "Database {$Db->database} is already upgraded to v3";
			print $mess."\n";
			
			Log::Log($mess, E_USER_NOTICE);
			exit();
		}

		// run migratins
		foreach ($migration_query_list as $sql)
		{
			Log::Log(sprintf("Executing migration SQL query: %s", $sql), E_USER_NOTICE);
			$Db->Execute($sql);
		}
		
		Log::Log("Execute EPPDRS_Upgrade_Autoup::Run", E_USER_NOTICE);
		
		// run convertation
		EPPDRS_Upgrade_Autoup::Run($Db);

		// run cleanup
		foreach ($cleanup_query_list as $sql)
		{
			Log::Log(sprintf("Execute cleanup SQL: %s", $sql), E_USER_NOTICE);
			$Db->Execute($sql);
		}
	}
	catch (Exception $e)
	{
		print "Error: {$e->getMessage()}\n";
		Log::Log(sprintf("Error: %s", $e->getMessage()), E_USER_ERROR);
		$Db->RollbackTrans();
		exit();
	}
	
	$Db->CompleteTrans();


	
	
	class EPPDRS_Upgrade_Autoup
	{
		private $Db;
		
		/**
		 * @var RegistryModuleFactory
		 */
		private $RegistryFactory;
		
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
				'gpgpass'		=> 'GPGPass',
				'gpg_path'		=> 'GPGPath',
				'gpg_home_dir'	=> 'GPGHomeDir'
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
			'GenericEPP'	=> 'GenericEPP',
			// В старой версии перепутаны модули COCCAEPP1 и COCCAEPP2
			'ccTLDCOCCA1' 	=> 'COCCAEPP2',
			'ccTLDCOCCA2' 	=> 'COCCAEPP1', 
			'ccTLDCOCCA3' 	=> 'COCCAEPP3'
		);
		
		private $ModuleNameMapFlip = array();
	
		
		private $FailedActiveDomains = array();
		
		////
		// Options
		// 
		private $SaveFailedAsPending = true;
		private $GPGHomeDir = '/root/.gnupg';
		
		public function __construct($Db)
		{
			foreach ($options as $flag => $b)
			{
				$this->{$flag} = $b;
			}
			
			$this->Db = $Db;
			$this->RegistryFactory = RegistryModuleFactory::GetInstance();
			$this->Crypto = $GLOBALS['Crypto'];
			
			
			$this->ModuleNameMapFlip = array_flip($this->ModuleNameMap);
		}
		
		public function GetSkipList ()
		{
			return array(
				//'ConvertConfig',
				//'ConvertRegistryModules',
				//'ConvertModulesPricing',
				//'ConvertPaymentModules',
				//'ConvertUsers',
				//'ConvertDiscounts',
				//'ConvertInvoices',
				//'ConvertZones',
				//'ConvertUserSettings',
				// Works only on customer server or our Web3
				//'ConvertDomains',
				//'ConvertActiveDomains',
				//'ConvertInactiveDomains',
				//'ConvertContacts'
			);
		}
		
		public function ConvertRegistryModules ()
		{
			$this->Log('Convert registry modules');
	
			////
			// Modules
			//
			$module_rset = $this->Db->GetAll('SELECT * FROM modules');
			foreach ($module_rset as &$row)
			{
				// Map old module name to new
				$row['name'] = $this->ModuleNameMap[$row['name']];
			}
			$this->TruncateTable('modules');
			$this->BulkInsert('modules', $module_rset);
			
			
			////
			// Modules configuration
			//
			$this->CreateTableCopy('modules_config');
			$this->TruncateTable('modules_config');						
			foreach ($this->ModuleConfigKeyMap as $module_name => $key_map)
			{
				// Get old module name
				$old_module_name = $this->ModuleNameMapFlip[$module_name];
	
				// Check that module was installed
				$module_setuped = (int)$this->Db->GetOne(
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
					$module_config[$key_new] = $this->Db->GetOne(
						'SELECT value FROM v2_modules_config WHERE `key` = ? AND module_name = ?', 
						array($key_old, $old_module_name)
					);
				}
				
				// Apply module specific configuration
				if ($module_name == 'EPPGR')
				{
					$module_config['SSLCertPath'] = $GLOBALS['modules_path'] . '/registries/EPPGR/ssl/cert.pem';				
				}
				
	
				$Crypto = $GLOBALS['Crypto'];
				$Registry = $this->RegistryFactory->GetRegistryByName($module_name);
				
				$this->Log("Convert $module_name config");
				foreach ($Registry->GetModule()->GetConfigurationForm()->ListFields() as $Field) 
				{
					$this->Db->Execute(
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
			
			// Covert tlds
			$tld_rowset = $this->Db->GetAll('SELECT * FROM tlds');
			foreach ($tld_rowset as &$row)
			{
				$row['modulename'] = $this->ModuleNameMap[$row['modulename']];
			}
			
			$this->TruncateTable('tlds');			
			$this->BulkInsert('tlds', $tld_rowset);
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
	
			$user_data = $this->Db->GetAll('SELECT * FROM users');		
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
			
			$this->TruncateTable('users');
			$this->BulkInsert('users', $user_data);
		}
		
		public function ConvertPaymentModules ()
		{
			$this->Log('Convert payment modules');
	
			// Copy pmodules table
			$pmodule_rset = $this->Db->GetAll('SELECT * FROM pmodules');
			foreach ($pmodule_rset as &$row)
			{
				if ($row['name'] == 'offline_payment')
				{
					$row['name'] = 'OfflineBank';
				}
			}
			$this->TruncateTable('pmodules');
			$this->BulkInsert('pmodules', $pmodule_rset);
			
			
			// For each pmodule copy config settings
			$pmodule_config = array();
			$Crypto = $GLOBALS['Crypto'];
			foreach ($pmodule_rset as $pmodule)
			{
				// Get old config form for current pmodule
				$rset = $this->Db->GetAll(
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
	 		
	 		$this->TruncateTable('pmodules_config');
			$this->BulkInsert('pmodules_config', $pmodule_config);		
		}
		
		
		public function ConvertInvoices ()
		{
			$this->Log('Convert invoices');
			
			$invoice_data = $this->Db->GetAll('SELECT * FROM invoices');
			foreach ($invoice_data as &$row)
			{
				if ($row['gate'] == 'offline_payment')
				{
					$row['gate'] = 'OfflineBank';
				}
				unset($row['proccessed']);
			}
			$this->TruncateTable('invoices');
			$this->BulkInsert('invoices', $invoice_data);
		}
		
		public function ConvertContacts ()
		{
			$map = array(
				'disclose_name' 	=> 'name',
				'disclose_org' 		=> 'org',
				'disclose_addr' 	=> 'addr',
				'disclose_voice' 	=> 'voice',
				'disclose_fax' 		=> 'fax',
				'disclose_email'	=> 'email'
			);
			foreach ($map as $table_field => $name)
			{
				$sql = "
					INSERT INTO contacts_discloses (`contactid`, `field_name`, `value`)
					SELECT c.clid, '{$name}', c.{$table_field}  
					FROM contacts AS c
					WHERE c.{$table_field} = '1'
				";
				
				$this->Db->Execute("
					INSERT INTO contacts_discloses (`contactid`, `field_name`, `value`)
					SELECT c.clid, '{$name}', c.{$table_field}  
					FROM contacts AS c
					WHERE c.{$table_field} = '1'
				");
			}
			
			$contact_data = $this->Db->GetAll('SELECT * FROM contacts');
			foreach ($contact_data as $i => &$contact_row)
			{
				$Registry = $this->RegistryFactory->GetRegistryByExtension($contact_row['TLD'], $db_check = false);
				$Contact = $Registry->NewContactInstance($contact_row['type']);
				$contact_row['groupname'] = $Contact->GroupName;
				$contact_row['strict_fields'] = '0';
			}
			$this->TruncateTable('contacts');
			$this->BulkInsert('contacts', $contact_data);
			
			
			$Phone = Phone::GetInstance();
			
			$phone_data = $this->Db->GetAll("
				SELECT * FROM contacts_data 
				WHERE `field` = 'voice' OR `field` = 'fax'
			");
			
			foreach ($phone_data as $phone_row)
			{
		    	if ($phone_row['value'])
		    	{
					$chunks = str_split(preg_replace("/[^0-9]+/", "", $phone_row['value']), 3);
			    	$e164 = trim("+".array_shift($chunks).".".implode("", $chunks));				
					$phone_row['value'] = $Phone->E164ToPhone($e164);
					
					$this->Db->Execute("
						INSERT INTO contacts_data SET `contactid` = ?, `field` = ?, `value` = ?",
						array($phone_row['contactid'], "{$phone_row['field']}_display", $phone_row['value'])
					);
		    	}
			}
		}
		
		private function Log ($message, $level=E_USER_NOTICE)
		{
			Log::Log(sprintf("%s", $message), $level);
			
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
		
		private function CreateTableCopy ($table1 /* table2 ... tableN */)
		{
			foreach (func_get_args() as $table)
			{
				$this->Db->Execute("CREATE TEMPORARY TABLE v2_{$table} AS SELECT * FROM {$table}");
			}
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
				$this->Db->Execute($stmt, array_values($row));
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
				
			$this->Db->Execute($stmt, array_values($row));
		}
		
		private function TruncateTable ($table1 /* table2 ... tableN */)
		{
			foreach ($tables=func_get_args() as $table)
				$this->Db->Execute("TRUNCATE TABLE {$table}");
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
		
		public static function Run ($Db)
		{
			$Upgrade = new EPPDRS_Upgrade_Autoup($Db);
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