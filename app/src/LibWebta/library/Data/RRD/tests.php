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
     * @filesource
     */

	Core::Load("Data/RRD");
	
	/**
	 * @category   LibWebta
     * @package    Data
     * @subpackage RRD 
	 * @name Data_RRD_Test
	 */
	class Data_RRD_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Data/RRD Tests');
        }
        
        function testRRDDS() 
        {
        	$ds = new RRDDS("test","GAUGE", 3600);
        	$res = $ds->__toString();
        	$this->assertEqual($res, "DS:test:GAUGE:3600:U:U", "DS Created");
        	
        	
        	$ds = new RRDDS("test2","COUNTER", 86400, 100, 200);
        	$res = $ds->__toString();
        	$this->assertEqual($res, "DS:test2:COUNTER:86400:100:200", "DS Created");
        }
        
        function testRRA() 
        {
        	$rra = new RRA("AVERAGE", array(0.5, 1, 1000));
        	$res = $rra->__toString();
        	$this->assertEqual($res, "RRA:AVERAGE:0.5:1:1000", "RRA created");
        	
        	$rra = new RRA("HWPREDICT", array(1440, 0.1, 0.0035, 288));
        	$res = $rra->__toString();
        	$this->assertEqual($res, "RRA:HWPREDICT:1440:0.1:0.0035:288", "RRA created");
        }
        
        function testRRD() 
        {
        	$RRD = new RRD("/tmp/test.rrd");
        	
        	// Add DS
        	$RRD->AddDS(new RRDDS("test","GAUGE", 3600));
        	$RRD->AddDS(new RRDDS("test2","GAUGE", 3600));
        	
        	// Add RRA
        	$RRD->AddRRA(new RRA("HWPREDICT", array(1440, 0.1, 0.0035, 288)));
        	$RRD->AddRRA(new RRA("AVERAGE", array(0.5, 1, 1000)));
        	
        	$this->assertTrue(($RRD->Create(0, 3600) && file_exists("/tmp/test.rrd")), "Database created.");
        	
        	$this->assertTrue($RRD->Update(array(1, 2)), "Database updated.");
        }
        
        function testRRDGraph() 
        {
        	$graph = new RRDGraph(450, 150);
        	
        	//Add DEF
        	$graph->AddDEF("L1", dirname(__FILE__)."/test.rrd", "L1", "AVERAGE");
        	$graph->AddDEF("L2", dirname(__FILE__)."/test.rrd", "L2", "AVERAGE");
        	$graph->AddDEF("L3", dirname(__FILE__)."/test.rrd", "L3", "AVERAGE");
        	
        	//Add Lines
        	$graph->AddLine(1, "L1", "#00FF00", "Test line 1");
        	$graph->AddLine(2, "L2", "#FF0000", "Test line 2");
        	
        	// Add  Area
        	$graph->AddArea("L3", "#0000FF", "Test area");
        	
        	$graph->SetXGridStyle("WEEK",1, "MONTH", 1, "MONTH", 1, 1, "%b");
        	
        	$graph->Title = "TEST GRAPH";
        	
        	$res = $graph->Plot("/tmp/test.gif", "-1y", "now");
        	
        	$this->assertTrue($res, "Graph created");
        }
    }

?>