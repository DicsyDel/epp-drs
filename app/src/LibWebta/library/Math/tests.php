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
     * @ignore
     */

	Core::Load("Math");
    
	/**
	 * @category   LibWebta
     * @package    Math
	 * @name Math_Test
	 */
	class Math_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Math');
        }
        
        function testMath() 
        {
			$math = new Math();
			
			$coord1 = $math->GetGeoShortestDistance(40.6657, -80.3521, 32.7631, -96.7984);
			$this->assertTrue(($coord1 == 1708.05), "Distance 1 received");
			
			$coord2 = $math->GetGeoShortestDistance(50.95, 30.68, 50, 36.25);
			$this->assertTrue(($coord2 == 407.83), "Distance 2 received");
        }
    }
?>
