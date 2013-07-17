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
     * @package    Math
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
     * @name UploadManager
     * @category   LibWebta
     * @package    Math
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class Math extends Core
	{
	
		/**
		* Get filesystem mount points
		* @access public
		* @ignore 
		*/
		function __construct()
		{
			parent::__construct();
		}
		
		/**
		 * Get Geo Shortest Distance
		 *
		 * @param float $latitude1
		 * @param float $longitude1
		 * @param float $latitude2
		 * @param float $longitude2
		 * @return float
		 * @access public
		 */
		public function GetGeoShortestDistance($latitude1, $longitude1, $latitude2, $longitude2)
		{
		    $cos = cos(($latitude2+$latitude1)/(2*57.3));
		    $latit = pow(($latitude2-$latitude1), 2);
		    $longit = $longitude2-$longitude1;
		    
			$distance = (sqrt($latit + pow($longit * $cos, 2))*60*1.852);
			
			return round($distance, 2);
		}
		
		/**
		 * Greatest common dividend of two whole numbers
		 * 
		 * @param integer $x First number
		 * @param integer $y Second number
		 * @return integer GCD
		 */
		public static function GCD($x, $y)
		{
			if ($x == 0) 
				return $y;
			return self::GCD($y % $x, $x);
		}
		
		
		/**
		 * Least common multiple of two whole numbers
		 * 
		 * @param integer $x First number
		 * @param integer $y Second number
		 * @return integer LCM
		 */
		public static function LCM($x, $y)
		{
			return $x / self::GCD($x, $y) * $y;
		}
		
		
		/**
		 * Least common multiple of `m` whole numbers
		 * 
		 * @param array $numbers Array of numbers
		 * @return integer LCM
		 */
		public static function LCMm(array $numbers)
		{
			$lcm = array_shift($numbers);
			foreach($numbers as $number)
			{
				$lcm = self::LCM($lcm, $number);
			}
			
			return $lcm;
		}
		
	}
	
	
?>