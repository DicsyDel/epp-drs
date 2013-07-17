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
     * @name RRDDS
     * @category   LibWebta
     * @package    Data
     * @subpackage RRD
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class RRDDS extends Core
	{
	    /**
	     * RRD Data Source
	     *
	     * @var string
	     */
		public $Name;
		
		/**
		 * DataSource type
		 *
		 * @var string
		 */
		public $Type;
		
		/**
		 * HeartBeat
		 *
		 * @var string
		 */
		public $HeartBeat;
		
		/**
		 * Min
		 *
		 * @var integer
		 */
		public $Min;
		
		/**
		 * Max
		 *
		 * @var integer
		 */
		public $Max;
		
		/**
		 * Constructor
		 *
		 * DS:ds-name:GAUGE | COUNTER | DERIVE | ABSOLUTE:heartbeat:min:max
		 * 
		 * @param string $name DS name
		 * @param string $type DS type (GAUGE | COUNTER | DERIVE | ABSOLUTE)
		 * @param int $heartbeat
		 * @param float $min
		 * @param float $max
		 */
		function __construct($name, $type, $heartbeat = 1, $min = "U", $max = "U")
		{
			$this->Name = $name;
			$this->Type = $type;
			$this->HeartBeat = $heartbeat;
			$this->Min = $min;
			$this->Max = $max;
		}
		
		/**
		 * Magic method to string
		 * @return string
		 */
		function __toString()
		{
			return "DS:{$this->Name}:{$this->Type}:{$this->HeartBeat}:{$this->Min}:{$this->Max}";
		}
	}
?>