<?php

	class JWhois
	{
		private static $Instance;
				
		/**
		 * Singleton
		 *
		 * @return JWhois
		 */
		public static function GetInstance ()
		{
			if (self::$Instance === null)
			{
				self::$Instance = new JWhois();
			}
			return self::$Instance;
		}
		
		private $Db;
	
		private $whois_tpl; 
 		
		protected function __construct()
		{
			$this->Db = Core::GetDBInstance();
			
			$this->whois_tpl = 	"Domain: {domain}\n"
				. "Registrar Name: {registrar}\n\n"
				. "{contacts}\n"
				. "Original Creation Date: {create_date}\n"
				. "Expiration Date: {expire_date}\n\n" 
				. "Nameserver Information:\n"
				. "{nameservers}";
				
			$this->contact_tpl = "{title}:\n" 
				. "Name: {name}\n"
				. "Address: {address}\n"
				. "City: {city}\n"
				. "Country: {cc}\n"
				. "Postal Code: {pc}\n"
				. "Phone: {phone}\n"
				. "Fax: {fax}\n"
				. "Email: {email}\n\n";
		}
		
		public function Whois ($domainname)
		{
			$domain_row = $this->Db->GetRow(
				"SELECT * FROM whois_domain WHERE domain = ? AND disabled = 0",
				array($domainname)
			);
			if (!$domain_row)
			{
				return sprintf('No match for "%s"', $domainname);
			}
			
			$domain_tpl_data = array(
				"domain" => $domainname,
				"registrar" => CONFIG::$COMPANY_NAME,
				"create_date" => date("Y/m/d", strtotime($domain_row['registered_date'])),
				"expire_date" => date("Y/m/d", strtotime($domain_row['registerexpire_date'])) 
			);
			
			// Contacts
			foreach (array(
				"holder" => "Registrant",
				"admin_c" => "Administrative Contact",
				"tech_c" => "Technical Contact",
				"bill_c" => "Billing Contact"
			) as $k => $title)
			{
				if ($domain_row[$k])
				{
					$contact_row = $this->Db->GetRow("
						SELECT p.name, p.address, p.city, c.short AS cc, p.pcode AS pc, p.phone, p.fax, p.email
						FROM whois_person AS p INNER JOIN whois_country AS c ON (p.country_fkey = c.country_key)
						WHERE person_key = ?", 
						array($domain_row[$k])
					);
					$contact_tpl_data = array_merge(array('title' => $title), $contact_row);
					$domain_tpl_data["contacts"] .= $this->ApplyTemplate($this->contact_tpl, $contact_tpl_data);
				}
			}
			
			// Nameservers
			$ns_data = $this->Db->GetAll(
				"SELECT nameserver FROM whois_nameserver WHERE domain_fkey = ?",
				array($domain_row['domain_key'])
			);
			foreach ($ns_data as $ns_row)
			{
				$domain_tpl_data["nameservers"] .= "{$ns_row['nameserver']}\n";
			}
			
			return $this->ApplyTemplate($this->whois_tpl, $domain_tpl_data);
		}
		
		private function ApplyTemplate ($tpl, $data)
		{
			foreach ($data as $k => $v)
			{
				$data["{{$k}}"] = $v;
				unset($data[$k]);
			}
			return str_replace(array_keys($data), array_values($data), $tpl);
		}
	} 

?>