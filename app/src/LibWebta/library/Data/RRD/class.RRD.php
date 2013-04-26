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
     * @package    Data
     * @subpackage RRD
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
    /**
     * @name RRD
     * @category   LibWebta
     * @package    Data
     * @subpackage RRD
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class RRD extends Core
	{
	    /**
	     * Path to RRD Database
	     *
	     * @var string
	     * @access private
	     */
		private $DBPath;
		
		/**
		 * RRD Constructor
		 *
		 * @param string $db_path
		 */
		function __construct($db_path)
		{
			$this->DBPath = $db_path;
		}
		
		/**
		 * Add Data source to database
		 *
		 * @param RRDDS $DS
		 */
		public function AddDS($DS)
		{
			if ($DS instanceof RRDDS)
				$this->DSs[] = $DS;
			else 
				Core::RaiseError(_("Argument $DS must be an RRDDS instance."));
		}
		
		/**
		 * Add round robin archive
		 *
		 * @param RRA $RRA
		 */
		public function AddRRA($RRA)
		{
			if ($RRA instanceof RRA)
				$this->RRAs[] = $RRA;
			else 
				Core::RaiseError(_("Argument $RRA must be an RRA instance."));
		}
		
		/**
		 * Create new Round Robin database
		 *
		 * @param int $start Specifies the time in seconds since 1970-01-01 UTC when the first value should be added to the RRD. RRDtool will not accept any data timed before or at the time specified. 
		 * @param int $step Specifies the base interval in seconds with which data will be fed into the RRD. 
		 * @return bool
		 */
		function Create($start = false, $step = false)
		{
			$args = array();
			if ($start)
			{
				array_push($args, "--start");
				array_push($args, $start);
			}
			
			if ($step)
			{
				array_push($args, "--step");
				array_push($args, $step);
			}
			
			foreach($this->DSs as $DS)
				array_push($args, $DS->__toString());

			foreach($this->RRAs as $RRA)
				array_push($args, $RRA->__toString());
				
			if(rrd_create($this->DBPath, $args, count($args)))
				return true;
			else
				Core::RaiseError(_("Cannot create RRD: ".rrd_error()));
		}
		
		/**
		 * Fetch data from RRD Database
		 *
		 */
		function Fetch($CF, $resolution = false, $start=false, $end=false)
		{
		    $args = array($CF);
            if ($start)
            {
                $args[] = "--start";
                $args[] = $start;
            }
		    
            print_r($args);
		    
            print $this->DBPath;
            
            $ret = rrd_fetch($this->DBPath, $args, count($args));
            
            var_dump($ret);
            
            return $ret;
		}
		
		/**
		 * Update data in RRD
		 *
		 * @param array $data
		 * @param integer $timestamp UNIX TimeStamp
		 * @return bool
		 */
		function Update($data, $timestamp = "N")
		{
			$arg = "{$timestamp}";
			foreach ($data as $val)
				$arg .= ":{$val}";
			
			if(rrd_update($this->DBPath, $arg))
				return true;
			else 
				Core::RaiseError(_("Cannot update RRD: ".rrd_error()));
		}
	}
?>