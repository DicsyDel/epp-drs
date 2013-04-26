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
     * @subpackage WHM
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

    /**
     * @name       CpanelAwstats
     * @category   LibWebta
     * @package    NET_API
     * @subpackage WHM
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class CpanelAwstats extends CPanel
	{
		
		function __construct($host, $user, $pass, $theme="x", $domain="")
		{
			parent::__construct($host, $user, $pass, $theme, $domain);
			$this->host = $host;
			$this->user = $user;
			$this->pass = $pass;
			$this->domain = $domain;
		}
		
		public function FetchStats()
		{
			//
			// Stats for search robots
			//
			$this->Fetch("awstats.pl?config={$this->domain}&lang=en&framename=mainright", true, "cURL");
			$content = $this->htmlresult;
			
			preg_match_all("/<a name=\"robots\">&nbsp;<\/a><br \/>(.*)<tr bgcolor=\"#ECECEC\"><th>([0-9]+) different robots\*<\/th>(.*)<br \/>[^<]*<br \/>[^<]*<a name=\"how\">/msi", $content, $matches);
			$robots = $matches[3][0];
			preg_match_all("/<tr><td class=\"aws\">([^<]+)<\/td><td>([^<]+)<\/td><td>([^<]+)<\/td><td>([^<]+)<\/td><\/tr>/msi", $robots, $matches);
			$numrobots = count($matches[1]);
			
			foreach ($matches[0] as $k=>$v)
				$retval["robots"][] = array("name"=>addslashes($matches[1][$k]), "pages"=>$matches[2][$k], "bw"=>$matches[3][$k]);
			
			$retval["num_robots"] = $numrobots;
			
			//
			// Summary statistics
			//
			preg_match_all("/<tr><td class=\"aws\">Traffic viewed&nbsp;\*<\/td><td><b>([0-9]*)<\/b><br \/>&nbsp;<\/td><td><b>([0-9]+)<\/b><br \/>\(([0-9\.]+)&nbsp;visits\/visitor\)<\/td><td><b>([0-9]+)<\/b><br \/>\(([0-9\.]+)&nbsp;pages\/visit\)<\/td><td><b>([0-9]+)<\/b><br \/>\(([0-9\.]+)&nbsp;hits\/visit\)<\/td><td><b>([^<]+)<\/b><br \/>\(([0-9\.]+)&nbsp;KB\/visit\)<\/td><\/tr>/msi", $content, $matches);			
			$retval["visitors"] = $matches[1][0];
			$retval["nv"] = $matches[2][0];
			$retval["pages"] = $matches[4][0];
			$retval["hits"] = $matches[6][0];
			$retval["bw"] = $matches[8][0];
			
			//
			// Keyword phrases stats
			//
			$this->Fetch("awstats.pl?config={$this->domain}&lang=en&framename=mainright&output=keyphrases", true, "cURL");
			$content = $this->htmlresult;
			preg_match_all("/<tr><td class=\"aws\">([^<]+)<\/td><td>([0-9]+)<\/td><td>([0-9\.]+) %<\/td><\/tr>/msi", $content, $matches);
			
			foreach ($matches[0] as $k=>$v)
				$retval["keywords"][] = array("keyword"=>$matches[1][$k], "clicks"=>$matches[2][$k], "percent"=>$matches[3][$k]);
			
			return $retval;
		}
	
	}
?>