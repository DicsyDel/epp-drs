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
     * @name NNTPServerStatus
     * @category   LibWebta
     * @package    NET
     * @subpackage NNTP
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class NNTPServerStatus extends NNTPClient
	{
		
	    /**
	     * Constructor
	     *
	     */
		function __construct()
		{
			parent::__construct();
		}
		
		/**
		 * Return Server Score calculated from ping time and server connection time
		 *
		 * @param string $server Server hostname
		 * @return float
		 */
		public function GetSpeedScore($server)
		{
			$ping = round($this->GetPingSpeed($server),2);
			$connect = round($this->GetNNTPSpeed($server),2);
			
			if (Log::HasLogger("PCNTL"))
				Log::Log("Server score for '{$server}': ping={$ping}, connect={$connect}", 1, "PCNTL");
			else
				Log::Log("Server score for '{$server}': ping={$ping}, connect={$connect}", 1, "NNTPLog");
					
			if ($connect)
			{
				$total = round(($ping+$connect)/2, 2);
				return $total;
			}
			else
				return false;			
		}
		
		/**
		 * Calculate Download speed
		 *
		 * @param string $group
		 * @param int $articleid
		 * @return float
		 */
		public function GetDownloadSpeed($group, $articleid)
		{
		    if ($this->SelectGroup($group))
		    {
		        $start = $this->TimeStamp();

		        $temp = $this->GetArticleBody($articleid);
		        $length = strlen($temp);
		        
		        $end = $this->TimeStamp();
				$time = $end-$start;
		
				$retval = round($length/$time/1024, 2);
		    }
		    else 
                $retval = false;
		      
		      
            return $retval;
		}
		
		/**
		 * Calculate ping speed
		 *
		 * @param string $server
		 * @return float
		 */
		private function GetPingSpeed($server)
		{
		
			@exec("ping -c 4 $server", $ping);
			$ping = implode("/n", $ping);
						
			preg_match_all("/[0-9]+\spackets\stransmitted,\s[0-9]+\spackets\sreceived,\s([0-9]+)%\spacket\sloss[^r]+round-trip\smin\/avg\/max\/stddev\s=\s[0-9.]+\/([0-9.]+)\/[0-9.]+\/[0-9.]+\sms/msi", $ping, $matches);
			$time = $matches[2][0];
			$lost = $matches[1][0];
			
			if ($lost == 100)
				return 0;
			else
			{
				$speed = 100-($time/1000*100);
				if ($lost > 0)
					return $speed/(100/$lost);
				else
					return $speed;
			}
		}
		
		/**
		 * Calculate connection speed
		 *
		 * @param string $server
		 * @return bool
		 */
		public function GetNNTPSpeed($server)
		{
			$start = $this->TimeStamp();
	
			$conn = @fsockopen($server, 119, $errno, $errstr, 10);
			if ($conn)
			{
				@fclose($conn);
				
				$end = $this->TimeStamp();
				$time = $end-$start;
				$score = 100-(10/100*$time);
				
				return $score;
			}
			else
				return false;
		}
		
		/**
		 * Return current timestamp
		 *
		 * @return float
		 */
		private function TimeStamp()
		{
			list($usec, $sec) = explode(" ", microtime());
			return ((float)$usec + (float)$sec);
		}
	}
?>