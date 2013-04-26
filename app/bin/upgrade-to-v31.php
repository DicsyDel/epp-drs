<?php

	
	define("NO_TEMPLATES", true);
	
	// Autoup imitation
/*
	$pwd = dirname(__FILE__);
	$srcpath = realpath("$pwd/../src");
	
	$cfg = parse_ini_file(realpath("$pwd/../etc/config.ini"), true);
	define('CF_DB_DSN', $cfg['database']['dsn']);
	require_once("$srcpath/Lib/Data/DB/adodb_lite/adodb.inc.php");
	require_once("$srcpath/Lib/Data/DB/adodb_lite/adodb-exceptions.inc.php");
	$db = NewADOConnection($cfg['database']['dsn']);
	
	$autoup_query_list = array(
	"
CREATE TABLE `extensions` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `enabled` tinyint(1) default '0',
  `license_flag` varchar(255) default NULL,
  `key` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
	",
	"
insert into `extensions`(`id`,`name`,`description`,`enabled`,`license_flag`,`key`) values 
(1, 'Verisign dropcatching', 'Verisign pre-registration (dropped domains catching).', 1, 'EXT_VERISIGN_PREREGISTRATION_DROPCATCHING', 'PREREGISTRATION'),
(2, 'Managed DNS', 'Managed DNS', 1, 'EXT_MANAGED_DNS', 'MANAGED_DNS')	
	",
	"
CREATE TABLE `invoice_purposes` (
  `id` int(11) NOT NULL auto_increment,
  `key` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `issystem` tinyint(1) default '0',
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
	",
	"
insert into `invoice_purposes`(`id`,`key`,`description`,`issystem`,`name`) values 
(1,'Domain_Create','Create new domain name',1,'Domain create'),
(2,'Domain_Renew','Renew domain name',1,'Domain renew'),
(3,'Domain_Transfer','Transfer domain name',1,'Domain transfer'),
(4,'Domain_Trade','Trade domain',1,'Domain trade'),
(5,'Custom','Invoices issues from Registrar CP',1,'Custom invoice'),
(6, 'Preregistration_Dropcatching', 'Verisign pre-registration (dropped domains catching)', 1, 'Verisign Preregistration Dropcatching')
	",
	"
CREATE TABLE  `eventhandlers_config` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) default NULL,
  `key` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  `handler_name` varchar(255) default NULL,
  `type` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1	
	",
	"
CREATE TABLE  `eventhandlers` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `interface` varchar(255) default NULL,
  `enabled` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`name`,`interface`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
	",
	"alter table `prices` change `operation` `purpose` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL",
	"alter table `prices` drop key `TLD`, add unique `TLD` (`purpose`(255), `TLD`(255), `period`)",
	"alter table `domains` change `incomplete_operation` `incomplete_operation` varchar(255) DEFAULT 'Register' NULL",
	"alter table `pending_operations` add column `object_before` text NULL after `objecttype`, add column `object_after` text NULL after `object_before`",
	"alter table `discounts` change `operation` `purpose` varchar(255) NULL",
	"alter table `discounts` drop key `TLD`, add unique `TLD` (`packageid`, `TLD`, `purpose`(255))",
	"alter table `syslog` add index `ix_transactionid` (`transactionid`(255))",
	"alter table `syslog` add index `ix_dtadded_time` (`dtadded_time`)",
	"ALTER TABLE contacts MODIFY COLUMN `clid` VARCHAR(38) NOT NULL DEFAULT ''",
	"ALTER TABLE `users` CHANGE `status` `status` TINYINT(1) NULL DEFAULT '1'"
	);
	foreach ($autoup_query_list as $sql)
	{
		$db->Execute($sql);
	}
*/	
	
	// No dot cut code above ^|^|^|^|^
	// It is helpful when autoup FAIL :(	

	
	
	
	include (dirname(__FILE__) . '/../src/prepend.inc.php');	
	
	
	$backup_query_list = array(
		"CREATE TABLE `invoices_old` AS SELECT * FROM `invoices`"
	);
	
	$migration_query_list = array(
	"DROP TABLE IF EXISTS `invoices`", 
	"CREATE TABLE `invoices` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) default NULL,
  `orderid` int(11) default NULL,
  `purpose` varchar(255) default NULL,
  `total` float default NULL,
  `dtcreated` datetime default NULL,
  `status` tinyint(1) default '0',
  `description` varchar(255) default NULL,
  `itemid` int(11) default NULL,
  `payment_module` varchar(255) default NULL,
  `dtupdated` datetime default NULL,
  `vat` float default NULL,
  `notes` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1");
	
	$cleanup_query_list = array(
		"delete from `config` where `key`='enable_managed_dns'",
		"DROP TABLE `invoices_old`"
	);
	
	$Db = Core::GetDBInstance();
	$Db->StartTrans();	
	
	try
	{	
		// Check there was no upgrade
		if (count($Db->GetAll("SHOW COLUMNS FROM invoices LIKE 'purpose'")) == 1)
		{
			$mess = "Database {$Db->database} is already upgraded to v3.1";
			Log::Log($mess, E_USER_NOTICE);
			print $mess."\n";
			die();
		}

		// Run backup queries
		foreach ($backup_query_list as $sql)
		{
			Log::Log(sprintf("Executing backup SQL: %s", trim($sql)), E_USER_NOTICE);
			$Db->Execute($sql);
		}
		
		// Run migratins
		foreach ($migration_query_list as $sql)
		{
			Log::Log(sprintf("Executing migration SQL: %s", trim($sql)), E_USER_NOTICE);
			$Db->Execute($sql);
		}
		
		// Run data convertation
		Log::Log("Execute EPPDRS_Upgrade_V31::Run", E_USER_NOTICE);
		EPPDRS_Upgrade_V31::Run($Db);

		// Run cleanup
		foreach ($cleanup_query_list as $sql)
		{
			Log::Log(sprintf("Execute cleanup SQL: %s", trim($sql)), E_USER_NOTICE);
			$Db->Execute($sql);
		}
	}
	catch (Exception $e)
	{
		print "Error: {$e->getMessage()}\n";
		Log::Log(sprintf("Error: %s", $e->getMessage()), E_USER_ERROR);
		$Db->RollbackTrans();
		die();
	}
	
	$Db->CompleteTrans();
	
	
	class EPPDRS_Upgrade_V31
	{
		private $Db;		
		
		public function __construct ($Db)
		{
			$this->Db = $Db;
		}
		
		public function GetSkipList ()
		{
			return array(
			);
		}		
			
		public function ConvertExtensions ()
		{
			$this->Db->Execute("
				UPDATE extensions SET enabled = ? WHERE `key` = 'MANAGED_DNS'
			", array(
				CONFIG::$ENABLE_MANAGED_DNS
			));
		}
		
		public function ConvertInvoicing ()
		{
			$same_keys = array(
				'id', 'userid', 'dtcreated', 'status', 'description', 'notes', 'orderid', 'vat'
			);
			$PaymentFactory = PaymentModuleFactory::GetInstance();
			
			$rs = $this->Db->Execute("SELECT * FROM `invoices_old`");
			while ($old_row = $rs->FetchRow())
			{
				$new_row = array();				
				
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
				
				// Save
				$this->Insert('invoices', $new_row);
			}
			$rs->Close();
		}
		
		public function ConvertNameserverPasswords()
		{
			global $Crypto;
			
			$nameservers = $this->Db->GetAll("SELECT * FROM nameservers");
			foreach ($nameservers as $ns)
			{
				$password = trim($Crypto->Decrypt($ns['password'], CONFIG::$CRYPTOKEY));
				$new_password = $Crypto->Encrypt($ns['password'], LICENSE_FLAGS::REGISTERED_TO);
				$this->Db->Execute("UPDATE nameservers SET password=? WHERE id=?", array($new_password, $ns['id']));
			}
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
		
		public static function Run ($Db)
		{
			$Upgrade = new EPPDRS_Upgrade_V31($Db);
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