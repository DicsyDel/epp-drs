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
     * @subpackage NNTP
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
    
	Core::Load("NET/NNTP/NNTPClient");
	
	/**
     * @name NNTPRouter
     * @category   LibWebta
     * @package    NET
     * @subpackage NNTP
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class NNTPRouter extends NNTPCore
	{
		/**
		 * Servers
		 *
		 * @var array
		 */
		private $Servers;
		
		/**
		 * Net_NNTP_Client
		 *
		 * @var Net_NNTP_Client
		 */
		private $NNTP;
		
		/**
		 * Auth data
		 *
		 * @var array
		 */
		private $AuthData;
		
		/**
		 * ADODB Instance
		 *
		 * @var object
		 */
		private $DB;
	
		function __construct($servers)
		{
			parent::__construct();
		    
			$count = count($servers);
			Log::Log("NNTP Router initialized with {$count} servers", 1, "NNTPLog");
			
			$NNTPServerStatus = new NNTPServerStatus();			
			foreach ($servers as $server)
			{
				$this->Servers[$server["hostname"]] = $NNTPServerStatus->GetSpeedScore($server["hostname"]);
				$this->AuthData[$server["hostname"]] = $server;
			}	
			
			asort($this->Servers);		
			$this->Servers = array_reverse($this->Servers);
					
			$this->DB = Core::GetDBInstance();
			$this->NNTP = new NNTPClient();
		}
	
		/**
		Select optimal server for group
		@access public
		@param string $group
		@return array $server
		*/
		public function GetOptimalServer($group)
		{	
			foreach ($this->Servers as $server=>$score)
			{
			    $score = (int)$score;
				if ($score > 1)
				{
					if($this->NNTP->Connect($server, $this->AuthData[$server]["port"], $this->AuthData[$server]["login"], $this->AuthData[$server]["password"], 5))
					{
					    Log::Log("Connect to {$server} on port {$this->AuthData[$server]["port"]} established ({$this->AuthData[$server]["login"]}:{$this->AuthData[$server]["password"]})", 1, "NNTPLog");
						$res = $this->NNTP->SelectGroup($group);
						
						Log::Log("Found {$res["count"]} messages in group {$group}", 1, "NNTPLog");
						
						$results[$server]["score"] = $score;	
						$results[$server]["load"] = $this->DB->GetOne("SELECT COUNT(*) FROM newsgroups WHERE serverid='{$this->AuthData[$server]['id']}'");	
						
						if ($res)
							$results[$server]["count"] = $res["count"];
						else
							$results[$server]["count"] = 0;
						
						$this->NNTP->Disconnect();
					}
					else
					{ 
						Log::Log("Cannot connect to {$server} on port {$this->AuthData[$server]["port"]} ({$this->AuthData[$server]["login"]}:{$this->AuthData[$server]["password"]})", 1, "NNTPLog");
					    $results[$server]["count"] = 0;
					}
				}
			}
			
			$total = array();
			foreach ((array)$results as $server=>$v)
			{
				if ($v["count"]>0)
				{
					$score = $v["count"]*$v["score"];
					
					if ($v["load"] > 0)
						$score = $score/$v["load"];
						
					$total[$server] = $score;
				}
			}
						
			if (count($total)>0)
			{
				asort($total);
				$total = array_reverse($total);
				$servers = array_keys($total);
				$srv = array_shift($servers);
				
				return $this->AuthData[$srv];
			}
			
			return false;
		}
	}
?>