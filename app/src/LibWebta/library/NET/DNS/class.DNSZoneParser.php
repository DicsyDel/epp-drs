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
	
	
	Core::Load("NET/DNS/DNSZone2");	
	
	Core::Load("NET/DNS/DNSRecord");	
	
	Core::Load("NET/DNS/SOADNSRecord");	
	Core::Load("NET/DNS/NSDNSRecord");	
	Core::Load("NET/DNS/PTRDNSRecord");	
	Core::Load("NET/DNS/ADNSRecord");	
	Core::Load("NET/DNS/CNAMEDNSRecord");	
	Core::Load("NET/DNS/MXDNSRecord");
	Core::Load("NET/DNS/TXTDNSRecord");
	Core::Load("NET/DNS/SPFDNSRecord");
	
	/**
     * @name       DNSZoneParser
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class DNSZoneParser extends Core
	{
		
		/**
		 * Bind time format
		 * 
		 * Example: 2003080800, 1d12h, 3W12h
		 * s = seconds = # x 1 seconds
		 * m = minutes = # x 60 seconds 
		 * h = hours = # x 3600 seconds 
		 * d = day = # x 86400 seconds 
		 * w = week = # x 604800 seconds
		 * 
		 */
		const PAT_TIME = "\d[sShHmMwWdD\d]*?";
		
		const PAT_DOMAIN = "([a-zA-Z0-9\-]+\.[a-zA-Z0-9\-]*?)+";
		
		
		/**
		 * Supported record types
		 * 
		 * RR		Value	RFC			Description 
		 * A 		1	RFC 1035		IPv4 Address record. An IPv4 address for a host. 
		 * AAAA		28	RFC 3596		IPv6 Address record. An IPv6 address for a host. Current IETF recommendation for IPv6 forward-mapped zones. 
		 * A6		38	RFC 2874		Experimental. Forward mapping of IPv6 addresses. An IP address for a host within the zone. 
		 * AFSDB	18	RFC 1183		Location of AFS servers. Experimental - special apps only. 
		 * CNAME	5	RFC 1035		Canonical Name. An alias name for a host. 
		 * DNAME	39	RFC 2672		Experimental. Delegation of reverse addresses (primarily IPv6). 
		 * DNSKEY	48	RFC 4034		DNSSEC.bis. DNS public key RR. 
		 * DS		39	RFC 4034		DNSSEC.bis. Delegated Signer RR. 
		 * HINFO	13	RFC 1035		Host Information - optional text data about a host. 
		 * ISDN		20	RFC 1183		ISDN address. Experimental = special applications only. 
		 * KEY		25	RFC 2535		Public key associated with a DNS name. 
		 * LOC		29	RFC 1876		Stores GPS data. Experimental - widely used. 
		 * MX		15	RFC 1035		Mail Exchanger. A preference value and the host name for a mail server/exchanger that will service this zone. RFC 974 defines valid names. 
		 * NAPTR	2	RFC 3403		Naming Authority Pointer Record. Goss misnomer. General purpose definition of rule set to be used by applications e.g. VoIP 
		 * NS		2	RFC 1035		Name Server. Defines the authoritative name server(s) for the domain (defined by the SOA record) or the subdomain. 
		 * NSEC		47	RFC 4034		DNSSEC.bis. Next Secure record. Ssed to provide proof of non-existence of a name. 
		 * NXT		30	DNSSEC			Next Domain record type. Obsolete use NSEC. 
		 * PTR		12	RFC 1035		IP address (IPv4 or IPv6) to host. Used in reverse maps. 
		 * RP		17	RFC 1183		Information about responsible person. Experimental - special apps only. 
		 * RRSIG	46	RFC 4034		DNSSEC.bis. Signed RRset. 
		 * RT		21	RFC 1183		Through-route binding. Experimental - special apps only. 
		 * SOA		6	RFC 1035		Start of Authority. Defines the zone name, an e-mail contact and various time and refresh values applicable to the zone. 
		 * SRV		33	RFC 2872		Defines services available in zone e.g. ldap, http etc.. 
		 * SIG		24	RFC 2931//2535	DNSSEC. Signature - contains data authenticated in a secure DNS. RFC 2535. 
		 * TXT		16	RFC 1035		Text information associated with a name. The SPF record is defined using a TXT record but is not (July 2004) an IETF RFC. 
		 * WKS		11	RFC 1035		Well Known Services. Deprecated in favour of SRV. 
		 * X25		19	RFC 1183		X.25 address. Experimental - special apps only.  
		 * 
		 */
		const REC_TYPES = "CNAME|A|NS|PTR|MX|SRV|TXT|AAAA|A6|DNAME|GPOS|HINFO|KEY|LOC";
		
		/**
		 * Output DNSZone object
		 * @var string
		 * @access public
		 */
		protected $DNSZone;
		
		public $ORIGIN;
		public $TTL;
		private $INCLUDES;
		private $Zone;
		
		/**
		 * Parse zone file content
		 *
		 * @param string $content Zone file contents
		 * @param string $zone Zone name
		 * @return DNSZone DNSZone object or false on error
		 */
		function ParseZone($content, $zone = null)
		{
			$this->Zone = $zone;
			
			// Create empty DNSZone
			$this->DNSZone = new DNSZone();
			
			$content = $this->StripComments($content);
			$this->ParseDirectives($content);
			$this->ParseSOA($content);
			$this->ParseRecords($content);

			return $this->DNSZone;
			
		}
		
		
		/**
		 * Parse SOA record
		 * 
		 * @param string $content
		 * @return void
		 */
		function ParseSOA($content)
		{
			//@      IN      SOA     ns.hostdad.com. ak.webta.net. 
			$p1 = "([\@a-zA-Z0-9\.\-]*?)\s+(([0-9]+)\s+)?IN\s+SOA\s+([a-zA-Z0-9\.\-]+)\s+([a-zA-Z0-9\.\-]+)\s+";
			
			/*(
			2004121202	; serial, todays date+todays
			14400		; refresh, seconds
			7200		; retry, seconds
			3600000		; expire, seconds
			86400 )		; minimum, seconds
			*/
			$p2 = "\(\s+(".self::PAT_TIME.")\s+(".self::PAT_TIME.")\s+(".self::PAT_TIME.")\s+(".self::PAT_TIME.")\s+(".self::PAT_TIME.")\s+";
			
			// Combine and parse
			preg_match("/{$p1}{$p2}/msi", $content, $m);
					
			// Set name
			if ($m[1] == "@")
			{
				if (!$this->ORIGIN)
					$name = $this->Zone;
				else 
					$name = $this->ORIGIN;
			}
			else 
				$name = $m[1];
			
			// Create new SOA record
			$SOA = new SOADNSRecord(	$name, 
										$m[4], 
										$m[5], 
										trim($m[2]), 
										$m[6], 
										$m[7], 
										$m[8], 
										$m[9], 
										$m[10]
									);
									
			$this->DNSZone->AddRecord($SOA);
		}
		
		
		/**
		 * Parse records
		 *
		 * name  ttl  class  type  type-specific-data
		 * 
		 * @param string $content
		 * @return string Stripped zone
		 */
		protected function ParseRecords($content)
		{
			preg_match_all("/[\n]*(.*?)\s+(". self::PAT_TIME ."\s+){0,}IN\s+(". self::REC_TYPES .")\s+(.*?)\n/mi", $content, $m);
			
			for($i=0; $i <= count($m[0]); $i++)
			{				
				$name = $m[1][$i];
				$ttl = $m[2][$i];
				$type = strtoupper($m[3][$i]);
				$data = $m[4][$i];

				switch ($type)
				{
					case "MX":
						$data_chunks = preg_split("/\s+/", $data);
						
						$record = new MXDNSRecord($name, $data_chunks[1], $ttl, $data_chunks[0]);
						$this->DNSZone->AddRecord($record);
						break;
						
					case "A":
						$record = new ADNSRecord($name, trim($data), $ttl);
						$this->DNSZone->AddRecord($record);
						break;	
					
					case "NS":
						$record = new NSDNSRecord($name, trim($data), $ttl);
						$this->DNSZone->AddRecord($record);
						break;
					
					case "CNAME":
						$record = new CNAMEDNSRecord($name, trim($data), $ttl);
						$this->DNSZone->AddRecord($record);
						break;
						
					case "PTR":
						$record = new PTRDNSRecord($name, trim($data), $ttl);
						$this->DNSZone->AddRecord($record);
						break;
						
				    case "TXT":
						$record = new TXTDNSRecord($name, trim($data), $ttl);
						$this->DNSZone->AddRecord($record);
						break;
				}
			}
		}
		
		
		/**
		 * Strip comments from zone content
		 *
		 * @param string $content
		 * @return string Stripped zone
		 */
		protected function StripComments($content)
		{
			return preg_replace("/[;#].*?\n/i", "\n", $content);
		}
		
		
		/**
		 * Parse zone directives
		 * 
		 * $ORIGIN 
		 * $INCLUDE
		 * $TTL
		 *
		 * @param string $content
		 * @return void
		 */
		protected function ParseDirectives($content)
		{
			// $ORIGIN uk.example.com.
			preg_match("/[$]+ORIGIN\s+(".self::PAT_DOMAIN.")[\s\n\t]+/i", $content, $m);
			$this->ORIGIN = $m[1];
					
			// $TTL 14400
			preg_match("/[$]+TTL\s+(".self::PAT_TIME.")[\s\n\t]+/i", $content, $m);
			$this->TTL = $m[1];
			
			$this->DNSZone->TTL = $this->TTL;
			
			// $INCLUDE
			preg_match_all("/[$]+INCLUDE\s+([^\n]+)\n/i", $content, $m);
			for($i = 1; $i <= count($m); $i++)
			{
				//TODO:
			}
		}
		
	}
	
?>
