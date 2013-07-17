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
     * @package    NET_API
     * @subpackage Whois
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	
    Core::Load("System/Independent/Shell/ShellFactory");    

    /**
     * @name       Whois
     * @category   LibWebta
     * @package    NET_API
     * @subpackage Whois
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
    class Whois extends Core
    {
        /**
         * List of Whois Servers
         *
         * @var array
         */
    	private $Servers;
    	
    	/**
    	 * Shell instance
    	 *
    	 * @var Shell
    	 */
    	private $Shell;
    	
    	/*
    	 * Path to whois binary
    	 */
    	private $WhoisBinPath;
    	
    	/*
    	 * Default path to whois binary
    	 */
    	const WHOIS_BIN_PATH = "/usr/bin/whois";
    	
    	/**
    	 * Whois constructor
    	 * @ignore
    	 *
    	 */
    	function __construct()
    	{    		
			$this->Shell = ShellFactory::GetShellInstance();
    	    
			$this->WhoisBinPath = (defined("CF_WHOIS_BIN_PATH")) ? CF_WHOIS_BIN_PATH : self::WHOIS_BIN_PATH;
			
    		$this->Servers =  array(			    
    							'ac' => array('whois.nic.ac', 'No match'),
    							'ac.cn' => array('whois.cnnic.net.cn', 'no matching record'),
    							'ac.jp' => array('whois.nic.ad.jp', 'No match'),
    							'ac.uk' => array('whois.ja.net', 'No such domain'),
    							'ad.jp' => array('whois.nic.ad.jp', 'No match'),
    							'adm.br' => array('whois.nic.br', 'No match'),
    							'adv.br' => array('whois.nic.br', 'No match'),
    							'aero' => array('whois.information.aero', 'is available'),
    							'ag' => array('whois.nic.ag', 'Not found'),
    							'agr.br' => array('whois.nic.br', 'No match'),
    							'ah.cn' => array('whois.cnnic.net.cn', 'No entries found'),
    							'al' => array('whois.ripe.net', 'No entries found'),
    							'am' => array('whois.amnic.net', 'No match'),
    							'am.br' => array('whois.nic.br', 'No match'),
    							'arq.br' => array('whois.nic.br', 'No match'),
    							'at' => array('whois.nic.at', 'nothing found'),
    							'au' => array('whois.aunic.net', 'No Data Found'),
    							'art.br' => array('whois.nic.br', 'No match'),
    							'as' => array('whois.nic.as', 'Domain Not Found'),
    							'asn.au' => array('whois.aunic.net', 'No Data Found'),
    							'ato.br' => array('whois.nic.br', 'No match'),
    							'av.tr' => array('whois.nic.tr', 'Not found in database'),
    							'az' => array('whois.ripe.net', 'no entries found'),
    							'ba' => array('whois.ripe.net', 'No match for'),
    							'be' => array('whois.geektools.com', 'No such domain'),
    							'bg' => array('whois.digsys.bg', 'does not exist'),
    							'bio.br' => array('whois.nic.br', 'No match'),
    							'biz' => array('whois.biz', 'Not found'),
    							'biz.tr' => array('whois.nic.tr', 'Not found in database'),
    							'bj.cn' => array('whois.cnnic.net.cn', 'No entries found'),
    							'bel.tr' => array('whois.nic.tr', 'Not found in database'),
    							'bmd.br' => array('whois.nic.br', 'No match'),
    							'br' => array('whois.registro.br', 'No match'),
    							'by' => array('whois.ripe.net', 'no entries found'),
    							'ca' => array('whois.cira.ca', 'Status: AVAIL'),
    							'cc' => array('whois.nic.cc', 'No match'),
    							'cd' => array('whois.cd', 'No match'),
    							'ch' => array('whois.nic.ch', 'We do not have an entry'),
    							'cim.br' => array('whois.nic.br', 'No match'),
    							'ck' => array('whois.ck-nic.org.ck', 'No entries found'),
    							'cl' => array('whois.nic.cl', 'no existe'),
    							'cn' => array('whois.cnnic.net.cn', 'No entries found'),
    							'cng.br' => array('whois.nic.br', 'No match'),
    							'cnt.br' => array('whois.nic.br', 'No match'),
    							'com' => array('whois.crsnic.net', 'No match'),
    							'com.au' => array('whois.aunic.net', 'No Data Found'),
    							'com.br' => array('whois.nic.br', 'No match'),
    							'com.cn' => array('whois.cnnic.net.cn', 'No entries found'),
    							'com.eg' => array('whois.ripe.net', 'No entries found'),
    							'com.hk' => array('whois.hknic.net.hk', 'No Match for'),
    							'com.mx' => array('whois.nic.mx', 'Nombre del Dominio'),
    							'com.tr' => array('whois.nic.tr', 'Not found in database'),
    							'com.ru' => array('whois.ripn.ru', 'No entries found'),
    							'com.tw' => array('whois.twnic.net', 'NO MATCH TIP'),
    							'conf.au' => array('whois.aunic.net', 'No entries found'),
    							'co.at' => array('whois.nic.at', 'nothing found'),
    							'co.jp' => array('whois.nic.ad.jp', 'No match'),
    							'co.uk' => array('whois.nic.uk', 'No match for'),
    							'cq.cn' => array('whois.cnnic.net.cn', 'No entries found'),
    							'csiro.au' => array('whois.aunic.net', 'No Data Found'),
    							'cx'	=> array('whois.nic.cx', 'No match'),
    							'cy'	=> array('whois.ripe.net', 'no entries found'),
    							'cz'	=> array('whois.nic.cz', 'No data found'),
    							'de'	=> array('whois.denic.de', 'not found'),
    							'dr.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'dk'	=> array('whois.dk-hostmaster.dk', 'No entries found'),
    							'dz'	=> array('whois.ripe.net', 'no entries found'),
    							'ecn.br'	=> array('whois.nic.br', 'No match'),
    							'ee'	=> array('whois.eenet.ee', 'NOT FOUND'),
    							'edu'	=> array('whois.verisign-grs.net', 'No match'),
    							'edu'	=> array('whois.crsnic.net', 'No match'),
    							'edu.au'	=> array('whois.aunic.net', 'No Data Found'),
    							'edu.br'	=> array('whois.nic.br', 'No match'),
    							'edu.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'eg'	=> array('whois.ripe.net', 'No entries found'),
    							'es'	=> array('whois.ripe.net', 'No entries found'),
    							'esp.br'	=> array('whois.nic.br', 'No match'),
    							'etc.br'	=> array('whois.nic.br', 'No match'),
    							'eti.br'	=> array('whois.nic.br', 'No match'),
    							'eun.eg'	=> array('whois.ripe.net', 'No entries found'),
    							'emu.id.au'	=> array('whois.aunic.net', 'No Data Found'),
    							'eng.br'	=> array('whois.nic.br', 'No match'),
    							'far.br'	=> array('whois.nic.br', 'No match'),
    							'fi'	=> array('whois.ripe.net', 'No entries found'),
    							'fj'	=> array('whois.usp.ac.fj', ''),
    							'fj.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'fm.br'	=> array('whois.nic.br', 'No match'),
    							'fnd.br'	=> array('whois.nic.br', 'No match'),
    							'fo'	=> array('whois.ripe.net', 'no entries found'),
    							'fot.br'	=> array('whois.nic.br', 'No match'),
    							'fst.br'	=> array('whois.nic.br', 'No match'),
    							'fr'	=> array('whois.nic.fr', 'No entries found'),
    							'gb'	=> array('whois.ripe.net', 'No match for'),
    							'gb.com'	=> array('whois.nomination.net', 'No match for'),
    							'gb.net'	=> array('whois.nomination.net', 'No match for'),
    							'g12.br'	=> array('whois.nic.br', 'No match'),
    							'gd.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'ge'	=> array('whois.ripe.net', 'no entries found'),
    							'gen.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'ggf.br'	=> array('whois.nic.br', 'No match'),
    							'gl'	=> array('whois.ripe.net', 'no entries found'),
    							'gr'	=> array('whois.ripe.net', 'no entries found'),
    							'gr.jp'	=> array('whois.nic.ad.jp', 'No match'),
    							'gs'	=> array('whois.adamsnames.tc', 'is not registered'),
    							'gs.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'gov.au'	=> array('whois.aunic.net', 'No Data Found'),
    							'gov.br'	=> array('whois.nic.br', 'No match'),
    							'gov.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'gov.hk'	=> array('whois.hknic.net.hk', 'No Match for'),
    							'gov.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'gob.mx'	=> array('whois.nic.mx', 'Nombre del Dominio'),
    							'gs'	=> array('whois.adamsnames.tc', 'is not registered'),
    							'gz.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'gx.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'he.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'ha.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'hb.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'hi.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'hl.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'hn.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'hm'	=> array('whois.registry.hm', '(null)'),
    							'hk'	=> array('whois.hknic.net.hk', 'No Match for'),
    							'hk.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'hu'	=> array('whois.ripe.net', 'MAXCHARS:500'),
    							'id.au'	=> array('whois.aunic.net', 'No Data Found'),
    							'ie'	=> array('whois.domainregistry.ie', 'no match'),
    							'ind.br'	=> array('whois.nic.br', 'No match'),
    							'imb.br'	=> array('whois.nic.br', 'No match'),
    							'inf.br'	=> array('whois.nic.br', 'No match'),
    							'info'	=> array('whois.afilias.info', 'Not found'),
    							'info.au'	=> array('whois.aunic.net', 'No Data Found'),
    							'info.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'it'	=> array('whois.nic.it', 'No entries found'),
    							'idv.tw'	=> array('whois.twnic.net', 'NO MATCH TIP'),
    							'int'	=> array('whois.iana.org', 'not found'),
    							'is'	=> array('whois.isnic.is', 'No entries found'),
    							'il'	=> array('whois.isoc.org.il', 'No data was found'),
    							'jl.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'jor.br'	=> array('whois.nic.br', 'No match'),
    							'jp'	=> array('whois.nic.ad.jp', 'No match'),
    							'js.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'jx.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'k12.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'ke'	=> array('whois.rg.net', 'No match for'),
    							'kr'	=> array('whois.krnic.net', 'is not registered'),
    							'la'	=> array('whois.nic.la', 'NO MATCH'),
    							'lel.br'	=> array('whois.nic.br', 'No match'),
    							'li'	=> array('whois.nic.ch', 'We do not have an entry'),
    							'lk'	=> array('whois.nic.lk', 'No domain registered'),
    							'ln.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'lt'	=> array('ns.litnet.lt', 'No matches found'),
    							'lu'	=> array('whois.dns.lu', 'No entries found'),
    							'lv'	=> array('whois.ripe.net', 'no entries found'),
    							'ltd.uk'	=> array('whois.nic.uk', 'No match for'),
    							'ma'	=> array('whois.ripe.net', 'No entries found'),
    							'mat.br'	=> array('whois.nic.br', 'No match'),
    							'mc'	=> array('whois.ripe.net', 'No entries found'),
    							'md'	=> array('whois.ripe.net', 'No match for'),
    							'me.uk'	=> array('whois.nic.uk', 'No match for'),
    							'med.br'	=> array('whois.nic.br', 'No match'),
    							'mil'	=> array('whois.nic.mil', 'No match'),
    							'mil.br'	=> array('whois.nic.br', 'No match'),
    							'mil.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'mk'	=> array('whois.ripe.net', 'No match for'),
    							'mn'	=> array('whois.nic.mn', 'Domain not found'),
    							'mo.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'ms'	=> array('whois.adamsnames.tc', 'is not registered'),
    							'mt'	=> array('whois.ripe.net', 'No Entries found'),
    							'mus.br'	=> array('whois.nic.br', 'No match'),
    							'mx'	=> array('whois.nic.mx', 'Nombre del Dominio'),
    							'name'	=> array('whois.nic.name', 'No match'),
    							'name.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'ne.jp'	=> array('whois.nic.ad.jp', 'No match'),
    							'net'	=> array('whois.crsnic.net', 'No match'),
    							'net.au'	=> array('whois.aunic.net', 'No Data Found'),
    							'net.br'	=> array('whois.nic.br', 'No match'),
    							'net.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'net.eg'	=> array('whois.ripe.net', 'No entries found'),
    							'net.hk'	=> array('whois.hknic.net.hk', 'No Match for'),
    							'net.lu'	=> array('whois.dns.lu', 'No entries found'),
    							'net.mx'	=> array('whois.nic.mx', 'Nombre del Dominio'),
    							'net.uk'	=> array('whois.nic.uk', 'No match for '),
    							'net.ru'	=> array('whois.ripn.ru', 'No entries found'),
    							'net.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'net.tw'	=> array('whois.twnic.net', 'NO MATCH TIP'),
    							'nl'	=> array('whois.domain-registry.nl', 'is not a registered domain'),
    							'nm.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'no'	=> array('whois.norid.no', 'no matches'),
    							'no.com'	=> array('whois.nomination.net', 'No match for'),
    							'nom.br'	=> array('whois.nic.br', 'No match'),
    							'not.br'	=> array('whois.nic.br', 'No match'),
    							'ntr.br'	=> array('whois.nic.br', 'No match'),
    							'nu'	=> array('whois.nic.nu', 'NO MATCH for'),
    							'nx.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'nz'	=> array('whois.domainz.net.nz', 'Not Listed'),
    							'plc.uk'	=> array('whois.nic.uk', 'No match for'),
    							'odo.br'	=> array('whois.nic.br', 'No match'),
    							'oop.br'	=> array('whois.nic.br', 'No match'),
    							'or.jp'	=> array('whois.nic.ad.jp', 'No match'),
    							'or.at'	=> array('whois.nic.at', 'nothing found'),
    							'org'	=> array('whois.pir.org', 'NOT FOUND'),
    							'org.au'	=> array('whois.aunic.net', 'No Data Found'),
    							'org.br'	=> array('whois.nic.br', 'No match'),
    							'org.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'org.hk'	=> array('whois.hknic.net.hk', 'No Match for'),
    							'org.lu'	=> array('whois.dns.lu', 'No entries found'),
    							'org.ru'	=> array('whois.ripn.ru', 'No entries found'),
    							'org.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'org.tw'	=> array('whois.twnic.net', 'NO MATCH TIP'),
    							'org.uk'	=> array('whois.nic.uk', 'No match for'),
    							'pk'	=> array('whois.pknic.net', 'is not registered'),
    							'pl'	=> array('whois.ripe.net', 'No information about'),
    							'pol.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'pp.ru'	=> array('whois.ripn.ru', 'No entries found'),
    							'ppg.br'	=> array('whois.nic.br', 'No match'),
    							'pro.br'	=> array('whois.nic.br', 'No match'),
    							'psi.br'	=> array('whois.nic.br', 'No match'),
    							'psc.br'	=> array('whois.nic.br', 'No match'),
    							'pt'	=> array('whois.ripe.net', 'No match for'),
    							'qh.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'qsl.br'	=> array('whois.nic.br', 'No match'),
    							'rec.br'	=> array('whois.nic.br', 'No match'),
    							'ro'	=> array('whois.ripe.net', 'No entries found'),
    							'ru'	=> array('whois.ripn.ru', 'No entries found'),
    							'sc.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'sd.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'se'	=> array('whois.nic-se.se', 'No data found'),
    							'se.com'	=> array('whois.nomination.net', 'No match for'),
    							'se.net'	=> array('whois.nomination.net', 'No match for'),
    							'sg'	=> array('whois.nic.net.sg', 'NO entry found'),
    							'sh'	=> array('whois.nic.sh', 'No match for'),
    							'sh.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'si'	=> array('whois.arnes.si', 'No entries found'),
    							'sk'	=> array('whois.ripe.net', 'no entries found'),
    							'slg.br'	=> array('whois.nic.br', 'No match'),
    							'sm'	=> array('whois.ripe.net', 'no entries found'),
    							'sn.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'srv.br'	=> array('whois.nic.br', 'No match'),
    							'st'	=> array('whois.nic.st', 'No entries found'),
    							'su'	=> array('whois.ripe.net', 'No entries found'),
    							'sx.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'tc'	=> array('whois.adamsnames.tc', 'is not registered'),
    							'tel.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'th'	=> array('whois.nic.uk', 'No entries found'),
    							'tj.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'tm'	=> array('whois.nic.tm', 'No match for'),
    							'tn'	=> array('whois.ripe.net', 'No entries found'),
    							'tmp.br'	=> array('whois.nic.br', 'No match'),
    							'to'	=> array('whois.tonic.to', 'No match'),
    							'tr'	=> array('whois.ripe.net', 'Not found in database'),
    							'trd.br'	=> array('whois.nic.br', 'No match'),
    							'tur.br'	=> array('whois.nic.br', 'No match'),
    							'tv'	=> array('whois.nic.tv', 'MAXCHARS:75'),
    							'tv.br'	=> array('whois.nic.br', 'No match'),
    							'tw'	=> array('whois.twnic.net', 'NO MATCH TIP'),
    							'tw.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'ua'	=> array('whois.ripe.net', 'No entries found'),
    							'uk'	=> array('whois.thnic.net', 'No match for'),
    							'uk.com'	=> array('whois.nomination.net', 'No match for'),
    							'uk.net'	=> array('whois.nomination.net', 'No match for'),
    							'us'	=> array('whois.nic.us', 'Not found'),
    							'va'	=> array('whois.ripe.net', 'No entries found'),
    							'vet.br'	=> array('whois.nic.br', 'No match'),
    							'vg'	=> array('whois.adamsnames.tc', 'is not registered'),
    							'wattle.id.au'	=> array('whois.aunic.net', 'No Data Found'),
    							'web.tr'	=> array('whois.nic.tr', 'Not found in database'),
    							'ws'	=> array('whois.worldsite.ws', 'No match for'),
    							'xj.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'xz.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'yn.cn'	=> array('whois.cnnic.net.cn', 'No entries found'),
    							'yu'	=> array('whois.ripe.net', 'No entries found'),
    							'za'	=> array('whois.frd.ac.za', 'No match for'),
    							'zlg.br'	=> array('whois.nic.br', 'No match'),
    							'zj.cn'	=> array('whois.cnnic.net.cn', 'No entries found')
    		);
    		
        }
        
        /**
         * Whois request
         *
         * @param string $host
         * @return string
         */
    	public function FetchRecord($host)
    	{
            // Cut off www
    		if(substr($host, 0, 4) == 'www.')
                $host = substr($host, 4);
    		
            // Extract TLD and find a suitable whois server
    	    $pi = pathinfo($host);
    	    $whoisinfo = $this->Servers[$pi["extension"]];
    	    
    	    
    	    if (!$whoisinfo[0])
    	    {
    	    	Core::RaiseWarning(sprintf(_("No custom whois server defined for %s. Using default one."), $host));
    	    	$hostparam = "";
    	    }
    	    else 
    	       $hostparam = " -h {$whoisinfo[0]}";
    	    	

    	    // Sanitize
    	    $host = escapeshellcmd($host);
    	   
    	       	     
    	    // Execute Shell command and Get result
    	    $retval = $this->Shell->QueryRaw("{$this->WhoisBinPath} {$hostparam} {$host}");
   	       	        
    	    
    	    // Check domain name existense and return false if domain NOT exists or Raw whois data about domain
    	    if (stristr($retval, $whoisinfo[1]) ||     
    	           stristr($retval, "hostname nor servname provided") || 
    	           preg_match("/((No entries found)|(No match)|(No Data Found)|(No match for)|(No data found))/si", $retval)
    	       )
    	        return false;
    	    else 
    	       return $retval;
    	}
    }
?>