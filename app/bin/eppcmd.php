<?php
	
	require_once (dirname(__FILE__) . '/../src/prepend.inc.php');
	define('NO_TEMPLATES', true);

	if (!$argv[1])
	{
		print "usage: php -q eppcmd.php [options]\n\n";

		print "Execute domain:info command\n";
		print "\t-i --info <domain> [--pw <authinfo>]\n";
		print "Execute contact:info command\n";
		print "\t--contact-info <clid> --tld <tld> [--pw <authinfo>]\n";
		print "Execute host:info command\n";
		print "\t--host-info <hostname>\n";
		print "Delete domain from database\n";
		print "\t-d --delete <domain>\n";
		print "Delete contact from database\n";
		print "\t--delete-contact <clid>\n";
		print "\n";
		print "Execute domain:renew command\n";
		print "\t--renew-domain <domain> [--period <period>]\n";
		print "\n";		
		print "Execute domain:restore command (DNS.LU)\n";
		print "\t--restore-domain <domain>\n";
		print "Cancel domain delete (IIS.SE)\n";
		print "\t--se-cancel-delete <domain>\n";
		print "\n";
		print "IDN options\n";
		print "\t--is-idn <str>\n";
		print "\t--idn-encode <str>\n";
		print "\t--idn-decode <str>\n";
		print "\n";	
		die();
	}
	
	switch ($argv[1])
	{
		case '--poll':
			$cmd = "poll";
			$tld = $arg[3];
			break;
		
		case '-i':
		case '--info':
			list($domainname, $tld) = explode(".", $argv[2], 2);
			$cmd = 'info';
			if ($argv[3] == '--pw')
				$pw = $argv[4];
			break;
			
		case '--host-info':
			list(,, $tld) = explode(".", $argv[2], 3);
			$host = $argv[2]; 
			$cmd = 'host-info';
			break;

		case '--c-info':
		case '--contact-info':
			$clid = $argv[2];
			$tld = $argv[4];
			if ($argv[5] == "--pw")
				$pw = $argv[6];
			$cmd = "contact-info";
			break;

		case '-d':
		case '--delete':
			list($domainname, $tld) = explode(".", $argv[2], 2);
			$cmd = 'delete';
			break;
			
		case '--delete-contact':
			$clid_list = array();
			for ($i=2; $i<count($argv); $i++)
			{
				$clid_list[] = $argv[$i];
			}
			$cmd = 'delete-contact';
			break;
			
		case '--renew-domain':
			$cmd = substr($argv[1], 2);
			list($domainname, $tld) = explode(".", $argv[2], 2);
			$period = ($argv[5] == "--period") ? $argv[5] : 1;
			break;

		case "--restore-domain":
			$cmd = substr($argv[1], 2);
			list($domainname, $tld) = explode(".", $argv[2], 2);
			break;
			
		case "--se-cancel-delete":
			$cmd = substr($argv[1], 2);
			list($domainname, $tld) = explode(".", $argv[2], 2);
			break;

		default:
			$cmd = substr($argv[1], 2);
	}
	
	if ($tld || $regname)
	{
		$Factory = RegistryModuleFactory::GetInstance();
		if ($tld)
		{	
			$Registry = $Factory->GetRegistryByExtension($tld);	
		}
		else
		{
			$Registry = $Factory->GetRegistryByName($regname);
		}
	}

	switch ($cmd)
	{
		case 'info':
			if (isset($Registry) && isset($domainname) && isset($tld))
			{
				$Module = $Registry->GetModule();
				$Transport = $Module->GetTransport();
				$Transport->SetDumpTraffic(true);
				
				$Domain = $Registry->NewDomainInstance();
				$Domain->Name = $domainname;
				$Domain->Extension = $tld;
				if ($pw)
				{
					if ($Registry->GetModuleName() == "DotPT")
					{
						$Domain->SetExtraField("pt-ext-roid", $pw);
					}
					else
						$Domain->AuthCode = $pw;	
				}	
					
				
				$Module->GetRemoteDomain($Domain);	
			}
			break;
			
		case 'contact-info':
			if (isset($Registry) && isset($clid))
			{
				$Module = $Registry->GetModule();
				$Transport = $Module->GetTransport();
				$Transport->SetDumpTraffic(true);
				
				$Contact = $Registry->NewContactInstance(CONTACT_TYPE::REGISTRANT);
				$Contact->CLID = $clid;
				if ($pw)
					$Contact->AuthCode = $pw;
				$Module->GetRemoteContact($Contact);
			}
			break;
			
		case 'host-info':
			if (isset($Registry) && isset($host))
			{
				$Module = $Registry->GetModule();
				$Transport = $Module->GetTransport();
				$Transport->SetDumpTraffic(true);
				
				$ip = $Module->GetHostIpAddress($host);
				print "\nIP: {$ip}\n";
			}
			break;
			
		case 'delete':
			if (isset($domainname) && isset($tld))
			{
				$DbDomain = DBDomain::GetInstance();
				try 
				{
					$Domain = $DbDomain->LoadByName($domainname, $tld);
					try
					{
						$DbDomain->Delete($Domain);						
						printf("Domain %s deleted from epp-drs database\n", $Domain->GetHostName());
					}
					catch (Exception $e)
					{
						printf("Domain delete failed. Reason: %s\n", $e->getMessage());
					}
				}
				catch (Exception $e)
				{
					printf("Couldn't load domain. Reason: %s\n", $e->getMessage());
				}
			}
			break;
			
		case 'delete-contact':
			if (isset($clid_list))
			{
				$DbContact = DBContact::GetInstance();
				foreach ($clid_list as $clid)
				{
					try
					{
						$Contact = $DbContact->LoadByCLID($clid);
						try
						{
							$DbContact->Delete($Contact);
							printf("Contact %s deleted from epp-drs database\n", $Contact->CLID);
						}
						catch (Exception $e)
						{
							printf("Contact delete failed. Reason: %s\n", $e->getMessage());
						}
					}
					catch (Exception $e)
					{
						printf("Couldn't load contact. Reason: %s\n", $e->getMessage());
					}
				}				
			}
			break;

		case 'poll':
			if (isset($Registry))
			{
				$Module = $Registry->GetModule();
				$Transport = $Module->GetTransport();
				$Transport->SetDumpTraffic(true);
				
				$Module->ReadMessage();
			}			
			
			break;
			
		case 'reset-pass':
		case 'restore-pass':
			$default_hash = '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918';
			$hash_length = strlen($default_hash);			
			$pass_backup_file = CACHE_PATH . '/passhash';
			
			
			if ($cmd == 'reset-pass')
			{
				// Reset admin password
				$pass = $db->GetRow("SELECT `value` FROM config WHERE `key` = 'pass'");
				$pass = $pass['value'];
				file_put_contents($pass_backup_file, $pass);
				$db->Execute("UPDATE config SET `value` = ? WHERE `key` = 'pass'", array($default_hash));
				printf("Original password saved to %s\n", $pass_backup_file);
			}
			else
			{
				// Restore admin password
				if (!file_exists($pass_backup_file))
				{
					printf("Backup password file %s not found\n", $pass_backup_file);
					break;
				}
				$pass = file_get_contents($pass_backup_file);
				if (strlen($pass) != $hash_length)
				{
					printf("Contents of backup file %s is not a valid password hash\n", $pass_backup_file);
					break;
				}
				$db->Execute("UPDATE config SET `value` = ? WHERE `key` = 'pass'", array($pass));
				print "Password restored\n";
			}
			break;

		case 'renew-domain':
			if ($Registry && $domainname)
			{
				$DbDomain = DBDomain::GetInstance();
				$Domain = $DbDomain->LoadByName($domainname, $tld);
				printf("Renewing %s for %s year(s)\n", $Domain->GetHostName(), $period);
				try {
					$Registry->RenewDomain($Domain, array('period' => $period));
					printf("Request is sent\n");
				} catch (Exception $e) {
					printf("Caught [%s]: %s\n", get_class($e), $e->getMessage());
				}

			}
			break;
		
		case 'restore-domain':
			if ($Registry && $domainname)
			{
				$module = $Registry->GetModule();
				try
				{
					$module->Request("domain-restore", array("domain" => "$domainname.$tld"));
					print "Restore request for '$domainname.$tld' is sent\n";
				}
				catch (RegistryException $e)
				{
					print "{$e->getMessage()}\n";
				}
			}
			break;	

		case "se-cancel-delete":
			if ($Registry && $domainname)
			{
				$module = $Registry->GetModule();
				try
				{
					$module->Request("test-domain-delete", array(
						"name" => "$domainname.$tld",
						"clientDelete" => 0
					));
					print "Cancel delete request for '$domainname.$tld' is sent\n";
				}
				catch (RegistryException $e)
				{
					print "{$e->getMessage()}\n";
				}
			}
			break;

		case "idn-decode":
			print Punycode::Decode($argv[2]) . "\n";
			break;
			
		case "idn-encode":
			print Punycode::Encode($argv[2]) . "\n";
			break;
			
		case "is-idn":
			print preg_match('/[\x00-\x1F\x7F-\xFF]+/', $argv[2]) ? "Yes\n" : "No\n";
			break;
			
		case "decrypt":
			$str = $argv[2];
			$key = $argv[3];
			print $Crypto->Decrypt($str, $key) . "\n";
			break; 
	}
?>
