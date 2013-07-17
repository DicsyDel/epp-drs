<?php
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
     * @subpackage RSS
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */
		
	Core::Load("Data/XML/RSS/RSSReader");
	Core::Load("NET/HTTP/HTTPClient");
	
	/**
	 * @category   LibWebta
     * @package    Data_XML
     * @subpackage RSS
     * @name Data_XML_RSS_RSSReader_Test
	 *
	 */
	class Data_XML_RSS_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('Data/XML/RSS/RSSReder test');
        }
        
        function testData_XML_RSS_RSSReader() 
        {
			$http = new HTTPClient();
			
			// rss 2.0
			$url = 'http://aggressiva.livejournal.com/data/rss';
			
			$http->SetTimeouts(30, 15);
			$xml = $http->Fetch($url);
			
			$Reader = new RSSReader();
			$Reader->Parse($xml);
			$data = $Reader->GetData();
			
			$this->assertTrue($data['channel'], "No channel found");
			$this->assertTrue($data['item']['pubdate'], "No items found");
			
        }
        
    }


?>