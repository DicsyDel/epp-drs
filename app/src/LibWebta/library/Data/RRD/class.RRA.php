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
     * @name RRA
     * @category   LibWebta
     * @package    Data
     * @subpackage RRD
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class RRA extends Core
	{
	    /**
	     * CF
	     *
	     * @var string
	     */
		public $CF;
		
		/**
		 * Arguments
		 *
		 * @var array
		 */
		public $Arguments;
		
		/**
		 * Constructor
		 *
		 * RRA:CF:cf arguments
		 * 
		 * @param string $CF CF (AVERAGE | MIN | MAX | LAST | HWPREDICT | SEASONAL | DEVSEASONAL | DEVPREDICT | FAILURES)
		 * 
		 * RRA:AVERAGE | MIN | MAX | LAST:xff:steps:rows
		 * 
		 * xff  - The xfiles factor defines what part of a consolidation interval may be made up from *UNKNOWN* data while the consolidated value is still regarded as known. It is given as the ratio of allowed *UNKNOWN* PDPs to the number of PDPs in the interval. Thus, it ranges from 0 to 1 (exclusive).
		 * steps - defines how many of these primary data points are used to build a consolidated data point which then goes into the archive.
		 * rows - defines how many generations of data values are kept in an RRA.
		 * 
		 * RRA:HWPREDICT:rows:alpha:beta:seasonal period[:rra-num] 
		 * RRA:SEASONAL:seasonal period:gamma:rra-num 
		 * RRA:DEVSEASONAL:seasonal period:gamma:rra-num 
		 * RRA:DEVPREDICT:rows:rra-num 
		 * RRA:FAILURES:rows:threshold:window length:rra-num 
		 * 
		 * @see http://oss.oetiker.ch/rrdtool/doc/rrdcreate.en.html
		 * 
		 * @param array $arguments
		 */
		function __construct($CF, $arguments)
		{
			$this->CF = $CF;
			$this->Arguments = $arguments;
		}
		
		/**
		 * Magic method to string
		 * @return string
		 */
		function __toString()
		{
			$arguments = implode(":", $this->Arguments);
			return "RRA:{$this->CF}:{$arguments}";
		}
	}
?>