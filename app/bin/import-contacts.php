<?php
	$registered_to = "Klaus  Spithost";

	set_time_limit(0);
	require_once dirname(__FILE__) . '/../src/prepend.inc.php';

	if (function_exists("zend_loader_file_licensed") && zend_loader_file_encoded())
	{
		$lic = zend_loader_file_licensed();
		if (strtolower($lic["Registered-To"]) != strtolower($registered_to)) {
			die("Script is not registered to {$lic["Registered-To"]}\n\n");
		}
	}
		
	
	$good_phone_format = "+[cc]-[1-4]-[4-10]";
	if (CONFIG::$PHONE_FORMAT != $good_phone_format)
		die("`phone_format` configured in EPP-DRS is not understood by import algorithm. \nPlease, use $good_phone_format\n\n");
	

	// Read arguments
	for ($i=1; $i<$argc; $i+=2)
	{
		$arg = $argv[$i];
		if ($arg == "--module")
			$module = $argv[$i+1];
		else if ($arg == "--userid")
			$userid = $argv[$i+1];
		else if ($arg == "--in")
			$in = $argv[$i+1];
	}
	
	// Check required arguments
	if (!isset($module) || !isset($userid) || !isset($in))
	{
		// print usage
		print "usage: php -q import-contacts.php [options]\n";
		print "    --module  <string>  module name\n";
		print "    --userid  <int>     user id\n";
		print "    --in      <string>  .csv filename\n";
		print "\n";
		die();
	}
	
	// Main.
	try
	{
		$importer = new ContactImporter($module, $userid, $in);
		$importer->import();
	}
	catch (Exception $e)
	{
		die("Caught: {$e->getMessage()}\n");
	}

	/**
	 * Core class for contacts import
	 * 
	 * @author marat
	 *
	 */
	class ContactImporter
	{
		private $module, $userid, $in;

		/**
		 * @var RegistryModuleFactory
		 */
		private $registryFactory;
		
		/**
		 * @var DBContact
		 */
		private $dbContact;
	
		function __construct($module, $userid, $in)
		{
			$this->module = $module;
			$this->userid = $userid;
		
			if (false === ($in0 = realpath(dirname(__FILE__) . "/" . $in)) || !file_exists($in0) || !is_readable($in0))
				throw new Exception("'$in' is not exists or not readable");
			$this->in = $in0;
		
			$this->registryFactory = RegistryModuleFactory::GetInstance();
			$this->dbContact = DBContact::GetInstance();
		}
		
		function import ()
		{
			$module = strtolower($this->module);
			if ($module == "dotnl")
			{
				$parser = new DotNLParser();
				$registry = $this->registryFactory->GetRegistryByName('DotNL');
			}
			else if ($module == "rrpproxy")
			{
				$parser = new RRPProxyParser();
				$registry = $this->registryFactory->GetRegistryByName('RRPProxy');
			}
				
			if ($parser == null)
				throw new Exception("Cannot find line parser for module '$module'");


			// Import process
			$fp = $this->fopen($this->in); 
			$headers = $this->fgetcsv($fp);
			
			for ($lineno=1; !feof($fp); $lineno++)
			{
				$line = $this->fgetcsv($fp);
				$line = array_combine($headers, array_map('trim', $line));
				
				try
				{
					$parseResult = $parser->parseLine($line);
					if (!$parseResult)
						continue;
				}
				catch (Exception $e)
				{
					print "Cannot parse line $lineno\n";
					continue;
				}
				
				if ($this->dbContact->FindByCLID($parseResult['clid']))
				{
					// Skip existing in database
					print "[{$parseResult['clid']}] Skipped. Already exists in database.\n";
					continue;
				}					
				
				// Construct contact
				$contact = $registry->NewContactInstanceByGroup($parseResult['group']);				
				$contact->CLID = $parseResult['clid'];
				$contact->UserID = $this->userid;
				try
				{
					$contact->SetFieldList($parseResult['fields'], 1);					
				}
				catch (ErrorList $e)
				{
					print "[{$contact->CLID}] Contact data violates manifest rules. " . join("; ", $e->GetAllMessages()) . "\n";
					print "[{$contact->CLID}] Set non strict mode\n";
					$contact->SetFieldList($parseResult['fields'], 0);
				}
				
				// Save contact
				try
				{
					$this->dbContact->Save($contact);
					print "[{$contact->CLID}] Imported\n";						
				}
				catch (Exception $e)
				{
					print "[{$contact->CLID}] Cannot save. {$e->getMessage()}\n";
				}
			}
			fclose($fp);
		}

		
		private function fopen ($filename)
		{
			$fp = fopen($filename, "r");
			if (!$fp)
				throw new Exception("Cannot open file '$filename' in read mode");
			return $fp;
		}
		
		private function fgetcsv($fp)
		{
			return fgetcsv($fp, 1024, ";", '"');
		}
		
		
	}
	
	/**
	 * DotNL line parser
	 * 
	 * @author marat
	 *
	 */
	class DotNLParser
	{
		private $registrantNames = array
		(
			"address-town" => "adres",
			"address-street" => "straat",
			"address-houseno" => "huisnummer",
			"address-postcode" => "postcode",						
		);
	
		private $registrantLegalNames = array
		(
			"registrant-name" => "rechtspersoon",
			"legal-form" => "rechtsvorm",
			"registrationnumber" => "registratienummer"
		);
	
		private $registrantNaturalNames = array
		(
			"surname" => "achternaam",
			"initials" => "voorletters",
			"sex" => "aanhef"
		);
	
		private $contactNames = array
		(
			"surname" => "achternaam",
			"initials" => "voorletters",
			"sex" => "aanhef",
			"tel-number" => "telefoon",
			"emailaddress" => "email"
		);		
		
		private $defaultCountry = "NL";
		private $defaultCallcode = "31";  // Netherlands		
		
		private $legalFormField;
		
		function __construct()
		{
			$registry = RegistryModuleFactory::GetInstance()->GetRegistryByName('DotNL');
			$registrantConfig = $registry->GetManifest()->GetContactConfigByGroup('registrant');
			$legalFormField = $registrantConfig->xpath('descendant::field[@name="legal-form"]');
			if (!count($legalFormField))
				throw new Exception("Cannot find 'legal-form' field in DotNL manifest");
			$this->legalFormField = $legalFormField[0];			
		}
		
		function parseLine ($line)
		{
			if (!$line['deelnemer'])
				// Skip empty line
				return;
				
			$fields = array
			(
				// All contact addresses are in NL.
				"address-notnl-1" => "",
				"address-countrycode" => $this->defaultCountry 
			);
			
			if (strtolower($line['rol']) == "registrant")
			{
				// Registrant
				$group = 'registrant';
				
				// Map fields
				foreach ($this->registrantNames as $nameOur => $nameTheir)
					$fields[$nameOur] = $line[$nameTheir]; 
				
				if ($line['rechtspersoon'])
				{
					// Legal
					$fields['isnatural'] = '0'; 						
					foreach ($this->registrantLegalNames as $nameOur => $nameTheir)
						$fields[$nameOur] = $line[$nameTheir];
						
					// Normalize 'legal-form' field
					$legalFormValue = $this->legalFormField->xpath('descendant::value[@name="'.$line['rechtsvorm'].'"]');
					if (count($legalFormValue))
						$fields['legal-form'] = (string)$legalFormValue[0]->attributes()->value;
					else 
						$fields['legal-form'] = 'ANDERS'; // Other
				}
				else
				{
					// Natural person
					$fields['isnatural'] = '1';						
					foreach ($this->registrantNaturalNames as $nameOur => $nameTheir)
						$fields[$nameOur] = $line[$nameTheir];
				}
			}
			else
			{
				// AdminC/TechC
				$group = 'admin_tech';
				
				$fields["tel-intl-xsno"] = "+". $this->defaultCallcode;
				// Map fields
				foreach ($this->contactNames as $nameOur => $nameTheir)
					$fields[$nameOur] = $line[$nameTheir];
			}
			
			return array
			(
				"clid" => $line['handle'],
				"group" => $group,
				"fields" => $fields
			);
		}
	}
	
	/**
	 * RRPProxy line parser
	 * 
	 * @author marat
	 *
	 */
	class RRPProxyParser
	{
		private $copyFieldNames = array
		(
			"firstname", "lastname", 
			"street", "zip", "city", "state", "country", 
			"phone", "fax", "email" 
		);
		
		private $mapFieldNames = array
		(
			"organization" => "organisation" 
		);
		
		/**
		 * @var Phone
		 */
		private $phoneObj;
		
		function __construct()
		{
			$this->phoneObj = new Phone();
		}
		
		function parseLine ($line)
		{
			if (!$line['handle'])
				return;
			
			$fields = array();
			
			// Copy fields
			foreach ($this->copyFieldNames as $name)
				$fields[$name] = $line[$name];
				
			// Map fields
			foreach ($this->mapFieldNames as $nameOur => $nameTheir)
				$fields[$nameOur] = $line[$nameTheir];
				
			// Normalize country
			$fields['country'] = strtoupper($fields['country']);

			// Parse phones
			foreach (array('phone', 'fax') as $key)
			{
				$fields[$key] = $this->parsePhone($fields, $key);
				if ($this->phoneObj->IsPhone($fields[$key]))
				{
					// Some magic to preserve valid phone in non strict contact
					$fields["{$key}_display"] = $fields[$key];
					$fields[$key] = $this->phoneObj->PhoneToE164($fields["{$key}_display"]);
				}				
			}

			return array
			(
				"clid" => $line['handle'],
				"group" => "generic",
				"fields" => $fields
			);
		}
		
		private function parsePhone ($line, $phone_key)
		{
			if ($raw_phone = $line[$phone_key])
			{
				if (preg_match('/\+(\d+)\s\(0\)\s?(.+)/', $raw_phone, $matches))
				{
					$callcode = $matches[1];
					list($areacode, $number) = explode(" ", $matches[2], 2);
				}
				else if ($raw_phone{0} == "0" && strtoupper($line["country"]) == $this->defaultCountry)
				{
					$callcode = $this->defaultCallcode;
					list($areacode, $number) = explode(" ", substr($raw_phone, 1), 2);
				}
				
				if (isset($callcode) && isset($areacode) && isset($number))
					return sprintf("+%s-%s-%s", $callcode, str_replace(" ", "", $areacode), str_replace(" ", "", $number));
			}
			
			return $raw_phone;
		}		
	}
	
	
?>