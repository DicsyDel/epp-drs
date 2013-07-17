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
     * @package    Data_XML
     * @subpackage OPML
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource 
     */

	require_once("class.OPMLParser.php");

	/**
	 * @category   LibWebta
     * @package    Data_XML
     * @subpackage OPML
     * @name Data_XML_OPML_Test
	 *
	 */
	class Data_XML_OPML_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Data/XML/OPML Test');
        }
        
        function testData_XML_OPML_OPMLParser() 
        {
			$OPMLParser = new OPMLParser("http://share.opml.org/opml/top100.opml");
			//$OPMLParser = new OPMLParser("http://192.168.1.254:9059/test.opml");
			
			$feeds = $OPMLParser->Parse();
			
			$this->assertTrue(($feeds && count($feeds) > 0), "OPML successfully parsed. Feeds returned");
        }
	}
?>
