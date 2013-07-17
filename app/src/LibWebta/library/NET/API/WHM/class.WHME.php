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
     * @name       WHME
     * @category   LibWebta
     * @package    NET_API
     * @subpackage WHM
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class WHME extends WHM
	{
		
		public function GetServerStatus ()
		{
			$result = $this->Request("/scripts/servup");
			$result = explode("<tr>", $result);
			foreach ($result as $r)
			{
				if (ereg("apache", $r))
				{
					$retval["apache"] = ereg("green-status", $r);
				}
				if (ereg("bind", $r))
				{
					$retval["bind"] = ereg("green-status", $r);
				}
				if (ereg("Server Load", $r))
				{
					$retval["load"] = ereg("green-status", $r);
					
					preg_match("/((\d+)?\.\d+)(\s+)\(\d cpu(s{0,1})\)/", $r, $m);
					$retval["loadval"] = $m[0];
				}
				if (ereg("mysql", $r))
				{
					$retval["mysql"] = ereg("green-status", $r);
				}
				if (ereg("ftpd", $r))
				{
					$retval["ftpd"] = ereg("green-status", $r);
				}
				if (ereg("exim", $r))
				{
					$retval["exim"] = ereg("green-status", $r);
				}
				if (ereg("cppop", $r))
				{
					$retval["cppop"] = ereg("green-status", $r);
				}
			}
			
			return $retval;
		}
		
		
		public function GetResellersList ()
		{
			
			$result = $this->Request("/scripts2/resellerlist");
			preg_match("/delres(.*?)<\/SELECT>/msi", $result, $m);
			preg_match_all("/option\s+value=\"(.*?)\"/msi", $m[0], $mm);
			$retval = $mm[1];
			
			return $retval;
			
		}
		
		public function GetResellersBWUsage($username, $year=null, $month=null)
		{
			if (!$year)
				$year = date("Y");
			if (!$month)
				$month = date("n");
			$result = $this->Request("/scripts/statres?res=$username&month=$month&year=$year");
			
			preg_match_all("/Total\<\/td\>\<td\>(.*?)\<\/td\>\<td\>(.*?)\<\/td\>\<td\>(.*?)\<\/td\>\<td\>(.*?)\<\/td\>/msi", $result, $m);
	
			$retval[0] = $m[3][0];
			$retval[1] = $m[4][0];
			
			return $retval;
			
		}
		
		public function GetResellerProps($username)
		{
			$result = $this->Request("/scripts2/editres?res=$username");
			preg_match_all("/rslimit\-bw\s+value\=(.*?)\>/msi", $result, $m);
			
			$retval = $m[1][0];
			
			return $retval;
			
		}
		
		public function GetSuspendedAccounts()
		{
			$result = $this->Request("/scripts/suspendlist");
			
			preg_match_all("/\<tr\>\s*?\<td\>.*?\<\/td\>\s*?\<td\>(\w+)\<\/td\>/msi", $result, $m);
			$retval = $m[1];
			
			return $retval;
			
		}
		
		public function GetSuspendedAccountsWithReason()
		{
			$result = $this->Request("/scripts/suspendlist");
			//<tr><td>asdasd.com</td><td>asdasdc</td><td>root</td><td>Fri Aug  5 09:45:38 2005</td><td>TestReason</td></tr>
			preg_match_all("/\<tr\>\s*?\\<td\>([A-Za-z\.-]*)?\<\/td\>\s*?\<td\>(\w+)\<\/td\><td\>(\w+)\<\/td\><td\>([^<]*)\<\/td\><td\>([^<]*)\<\/td\>/msi", $result, $m);
			
			for($i = 0; $i<sizeof($m[1]);$i++)
			{
				$retval[$i] = array("domain"=> $m[1][$i], "username" => $m[2][$i], "owner" => $m[3][$i], "date" => $m[4][$i], "reason" => $m[5][$i]);
			}
			
			return $retval;
			
		}
		
		public function PackagesMultiCopy($host, $port, $root, $packages)
		{			
			$retval = $this->Request("/scripts2/pkgmover?jsstatus=0&host=$host&root=$root&user=&port=$port&moveid=" . time() . "&suuser=&supass=&packages=".implode(",", $packages));
			return $retval;
		}
		
		public function AccountCopy($host, $root, $port = 22, $username, $ip = false, $skipres = false, $servtype = "WHM45")
		{
			
			$ip = $ip ? 1 : 0;
			$skipres = $skipres ? 1 : 0;
			$retval = $this->Request("/scripts2/copyacct?host=$host&root=$root&port=$port&user=$username&ip=$ip&skipres=$skipres&servtype=$servtype&port=$port");
			
			return $retval;
		}
		
		public function ChangeAcctUsername ($username, $newusername)
		{
			$result = $this->Request("/scripts/edituser?domain=$username&user=$username&submit-domain=Modify");
			$q .= $this->MakeHidden($result, "FEATURELIST");
			$q .= $this->MakeHidden($result, "IP");
			$q .= $this->MakeHidden($result, "OWNER");
			$q .= $this->MakeHidden($result, "PLAN");
			$q .= $this->MakeHidden($result, "STARTDATE");
			$q .= $this->MakeHidden($result, "SUSPENDTIME");
			
			$q .= $this->MakeTextField($result, "MAXADDON");
			$q .= $this->MakeTextField($result, "MAXPARK");
			$q .= $this->MakeTextField($result, "MAXSQL");
			$q .= $this->MakeTextField($result, "MAXSUB");
			$q .= $this->MakeTextField($result, "MAXLST");
			$q .= $this->MakeTextField($result, "MAXFTP");
			$q .= $this->MakeTextField($result, "MAXPOP");
			
			// Shell
			if (stristr($result, "name=shell value=1 sele"))
				$q .= "&shell=1";
			

			

			$retval = $this->Request("/scripts/saveedituser?newuser=$newusername&user=$username&RS=x$q&LANG=english&seeshell=1");
			
			return($retval);
		}
		
		public function GetQuotaList()
		{
			$result = $this->Request("/scripts/quotalist");
			preg_match_all("/user\"\s+value=\"(\w+)\".*?<td>(\d+)\s+/msi", $result, $m);
			return ($m);
		}
		
		public function MakeHidden($str, $param)
		{
			$result = preg_match_all("/name=\"$param\"\s+value=\"(.+)\"/i", $str, $m); 
			return("&".$param."=".$m[1][0]);
		}
		
		public function MakeTextField($str, $param)
		{
			$result = preg_match_all("/name=$param\s+value=\"(.+)\"/i", $str, $m); 
			return("&".$param."=".$m[1][0]);
		}
		
		public function GetDbsList($username="\w+")
		{
			$result = $this->Request("/3rdparty/phpMyAdmin/server_databases.php");
			preg_match_all("/checkprivs=({$username}_*?\w+)/i", $result, $ms);
			preg_match_all("/checkprivs=({$username}_*?)/i", $result, $ms1);
			$retval = array_merge($ms[1], $ms1[1]);
			$retval1 = array_unique($retval);
			return ($retval1);
		}
	
	} 

?>