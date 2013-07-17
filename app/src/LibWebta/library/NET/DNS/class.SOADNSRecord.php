<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
		
	/**
     * @name       SOADNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class SOADNSRecord extends DNSRecord
	{

		const DEFAULT_REFRESH = 14400;
		const DEFAULT_RETRY = 1800;
		const DEFAULT_EXPIRY = 86400;
		const DEFAULT_MINIMUM = 10800;
		
		const DEFAULT_TEMPLATE = "
@   {ttl}   {class}      SOA     {nameserver} {email} (
       {serial}    ; serial, todays date+todays
       {refresh}        ; refresh, seconds
       {retry}        ; retry, seconds
       {expire}        ; expire, seconds
       {minimum} )    ; minimum, seconds				
";
				
		public $TTL;
		public $Class;
		public $Name;
		public $Nameserver;
		public $Email;
		public $Serial;
		public $Refresh;
		public $Retry;
		public $Expire;
		public $Minimum;
		public $Template;
		private $Content;
		private $Error;

		/**
		* Constructor
		* 
		* 
		* @param $name	The 'root name' of the zone. Most commonly written as @ or Origin Value. 
		* 
		* @param $ttl		Standard TTL values apply (range 0 to 2147483647 clarified by RFC 2181). 
		* 				The data contained in the SOA record applies TTL values to the slave DNS - see below. 
		* 				For more information about TTL values. 
		* 
		* @param $class	Defines the class of record and normally takes the value IN = Internet. 
		* 				It may also take the value HS = Hesiod and CH = Chaos both historic MIT protocols. 
		* 
		* @param $nameserver	A name server that will respond authoritateively for the domain and called the Primary Master 
		* 				in the context of dynamic DNS. 
		* 				If DDNS is not used this may be any suitable name server either in the zone file or in an external or foreign zone. 
		* 				This is most commonly written as a Fully-qualified Domain Name (FQDN and ends with a dot). 
		* 				If the record points to an EXTERNAL server (not defined in this zone) it MUST end with a '.' (dot) e.g. ns1.example.net. 
		* 				If the name server is defined in this domain (in this zone file) it can be written as ns1 (without the dot) which will be expanded to include the $ORIGIN. 
		* 				In the jargon this field is called MNAME field which is why we called it name-server. 
		* 
		* @param $email	Email address of the person responsible for this zone. 
		* 				In the jargon this is called the RNAME field which is why we called it email. 
		* 				A suitable admin but more commonly the technical contact for the domain. 
		* 				By convention (in RFC 2412) it is suggested that the reserved mailbox hostmaster 
		* 				be used for this purpose but any sensible and stable email address will work. 
		* 				NOTE: Format is mailbox-name.domain.com e.g. hostmaster.example.com (not the more normal @ sign 
		* 				since it has other uses in the zone file) but mail is sent to hostmaster@example.com. 
		* 				Most commonly ending with a '.' (dot) but if the email address is in this domain you can just 
		* 				use hostmaster (see also example below). when to use the dot. 
		* 
		* @param $serial	Serial number Unsigned 32 bit value in range 1 to 4294967295 with a maximum increment of 2147483647. 
		* 				In BIND implementations this is defined to be a 10 digit field. 
		* 				This value MUST change when any resource record in the zone file is updated. 
		* 				The convention is to use a date based value to simplify this task - the most popular being 
		* 				yyyymmddss where yyyy = year, mm = month and dd = day ss = a sequence number in case you 
		* 				update it more than once in the day! 
		* 				Using this date format means that the value 2005021002 means the last 
		* 				update was on the 10th Febrary 2005 and it was the third update that day. 
		* 				The date format is just a convention not a requirement so BIND will provide no validation of the field. 
		* 				It is easy to make mistakes and get serial numbers out of sequence. Fix Serial Numbers. 
		* 				Note: the arithmetic used by the serial number is defined in RFC 1982. 
		* 
		* @param $refresh	Signed 32 bit time value in seconds. 
		* 				Indicates the time when the slave will try to refresh the zone from the master. 
		* 				RFC 1912 recommends 1200 to 43200 seconds, low (1200) if the data is volatile or 43200 (12 hours) 
		* 				if it's not. If you are using NOTIFY you can set for much higher values e.g. 
		* 				1 or more days > 86400. BIND Time format. 
		* @param $retry	Signed 32 bit value in seconds. 
		* 				It defines the time between retries if the slave (secondary) fails to contact the master 
		* 				when refresh (above) has expired. 
		* 				Typical values would be 180 (3 minutes) to 900 (15 minutes) or higher. 
		* 
		* @param $expiry	Signed 32 bit value in seconds. 
		* 				Indicates when the zone data is no longer authoritative. 
		* 				Applies to Slaves or Secondaries servers only. 
		* 				BIND9 slaves stop responding to queries for the zone when this time has expired and no 
		* 				contact has been made with the master. 
		* 				Thus when the ref values expires the slave will attempt to read the SOA record for the zone - 
		* 				and request a zone transfer AXFR/IXFR if the sn has changed. 
		* 				If contact is made the expiry and refresh values are reset and tyhe cycle starts again. 
		* 				If the slave fails to contact the master it will retry every retry period but continue to 
		* 				supply authoritative data for the zone until the expiry value is reached at which point 
		* 				it will stop answering queries for the domain. 
		* 				RFC 1912 recommends 1209600 to 2419200 seconds (2-4 weeks) to allow for major 
		* 				outages of the master. BIND Time format. 
		* 
		* @param $min		Minimum Signed 32 bit value in seconds. 
		* 				RFC 2308 (implemented by BIND 9) redefined this value to be the negative caching time - 
		* 				the time a NAME ERROR = NXDOMAIN record is cached. 
		* 				The maximum value allowed by BIND 9 for this parameter is 3 hours (10800 seconds). 
		* 				This value was (in BIND 4 and 8) used by any RR from the zone that did not specify an 
		* 				explicit TTL i.e. the zone default TTL. 
		* 				BIND 9 uses the $TTL directive as the zone default TTL 
		* 				(and which was also standarized in RFC 2308). You may find older documentation or zone 
		* 				file configurations which reflect the old usage (there there are still a lot of BIND 4 
		* 				sites operational).
		* 
		* @return bool  True on success, false failure.
		* @see http://www.zytrax.com/books/dns/ch8/soa.html
		*/	
		
		public function __construct($name, 
									$nameserver, 
									$email,
									$ttl = false, 
									$serial = false, 
									$refresh = false, 
									$retry = false, 
									$expire = false, 
									$minimum = false, 
									$class = "IN"
		)
		{
							
				parent::__construct();
				
				$this->Template = self::DEFAULT_TEMPLATE;
				
				// Defaults
				if (!$refresh)
					$refresh = self::DEFAULT_REFRESH;
				if (!$retry)
					$retry = self::DEFAULT_RETRY;	
				if (!$expire)
					$expire = self::DEFAULT_EXPIRY;	
				if (!$minimum)
					$minimum = self::DEFAULT_MINIMUM;
				if (!$serial)
				    $serial = date("Ymd")."01";
				
				// Email
				$this->Email = str_replace('@', '.', $email);
				$this->Email = trim($this->Email, '.') . '.';
				
				// Name
				if (!$this->Validator->IsDomain($name))
				{
					self::RaiseWarning("Zone name '{$name}' is not a valid domain name");
					$this->Error = true;
				}
				else 	
					$this->Name = $this->Dottify($name);
				
				// Nameserver
				if (!$this->Validator->IsDomain($nameserver))
				{
					self::RaiseWarning("Zone nameserver '{$nameserver}' is not a valid domain name");
					$this->Error = true;
				}
				else	
					$this->Nameserver = $this->Dottify($nameserver);
				
				// Class
				$this->Class = $class;
				
				// TTL
				$this->TTL = $ttl;
				
				// Serial
				$this->Serial = $serial;
				
				// Refresh
				$this->Refresh = $refresh;
				
				// Retry
				$this->Retry = $retry;
				
				// Expire
				$this->Expire = $expire;
				
				// Minimum
				$this->Minimum = $minimum;
		}
		
		/**
		* Set zone template
		* @access public
		* @param string $template
		* @return string Zone file content
		*/ 
		public function  SetTemplate($template)
		{
			$this->Template = $template;
		}
		
		/**
		* Generate a new serial based on given one.
		*
		* This generates a new serial, based on the often used format
		* YYYYMMDDXX where XX is an ascending serial,
		* allowing up to 100 edits per day. After that the serial wraps
		* into the next day and it still works.
		*
		* @param int  $serial Current serial
		* @return int New serial
		*/
		static function RaiseSerial($serial = 0)
		{
			if (substr($serial, 0, 8) == date('Ymd')) 
			{
				//Serial's today. Simply raise it.
				$serial = $serial + 1;
			} 
			elseif ($serial > date('Ymd00')) 
			{
				//Serial's after today.
				$serial = $serial + 1;
			} 
			else 
			{
				//Older serial. Generate new one.
				$serial = date('YmdH');
			}
			
			return intval($serial);
		}
		
		/**
		 * __toString
		 * @return string $this->Content
		 */
		public function __toString()
		{
			if (!$this->Error)
			{
				$tags = array(	"{name}"		=> $this->Name,
								"{ttl}"			=> $this->TTL,
								"{nameserver}"	=> $this->Nameserver,
								"{email}"		=> $this->Email,
								"{serial}"		=> $this->Serial,
								"{refresh}"		=> $this->Refresh,
								"{retry}"		=> $this->Retry,
								"{expire}"		=> $this->Expire,
								"{minimum}"		=> $this->Minimum,
								"{class}"		=> $this->Class,
								"{ttl}"			=> $this->TTL
							);
		
				$this->Content = str_replace(
					array_keys($tags),
					array_values($tags),
					$this->Template
				);
				
				return $this->Content;
			}
			else 
				return "";
		}
	}
	
?>
