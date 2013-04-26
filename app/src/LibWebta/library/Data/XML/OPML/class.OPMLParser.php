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
     */
	
	/**
     * @name OPMLParser
     * @category   LibWebta
     * @package    Data_XML
     * @subpackage OPML
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @todo Use HTTPClient instead of file_get_contents() for XML downloading
     */
	class OPMLParser extends Core
	{
	    /**
	     * XML Content
	     *
	     * @var string
	     * @access private
	     */
		private $Content;
		
		/**
		 * Data
		 *
		 * @var string
		 * @access private
		 */
		private $Data;
		
		/**
		 * OPML Parser constructor
		 *
		 * @param string $url
		 */
		function __construct($url)
		{
			$this->Content = @file_get_contents($url);
			$this->Data = array();
		}	
	       
		/**
		 * Parse Downloaded XML
		 *
		 * @return array
		 * @uses simplexml_load_string SimpleXML Extension for PHP
		 */
		public function Parse()
		{
			if (!$this->Content)
				return false;
		
			$parsedXML = @simplexml_load_string($this->Content);
			
			if ($parsedXML && $parsedXML->body && count($parsedXML->body->outline) > 0)
			{
				foreach ((array)$parsedXML->body as $outline)
				{
					foreach ($outline as $row)
					{
						if (!$row->outline || count($row->outline) == 0)
							$this->Data[] = array("title" => (string)$row["title"], "url" => (string)$row["htmlUrl"], "feed" => (string)$row["xmlUrl"]);
						else
						{
							foreach ($row->outline as $row2)
								$this->Data[] = array("title" => (string)$row2["title"], "url" => (string)$row2["htmlUrl"], "feed" => (string)$row2["xmlUrl"]);
						}
					}
				}
					
				if ($parsedXML->head->title)
					$title = (string)$parsedXML->head->title;
				else
					$title = "OPML file";
				
				return array("title" => $title, "feeds" => $this->Data);
			}
			else
				return false;
		}
		
	}
?>