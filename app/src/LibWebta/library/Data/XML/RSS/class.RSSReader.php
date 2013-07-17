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
     * @subpackage RSS
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
     * @name RSSReader
     * @category   LibWebta
     * @package    Data_XML
     * @subpackage RSS
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class RSSReader extends Core 
	{
		/**
		 * @var resource Parser created by xml_parser_create function
		 * 
		 */
		private $XMLParser;
		
		/**
		 * @var array result parsed data
		 * 
		 */
		private $Data;
		
		/**
		 * @var string current tag name
		 * 
		 */
		private $CurrentTag;
		
		/**
		 * @var string xml block in feed (channel, image, item, textinput)
		 * 
		 */
		private $XMLBlock;
		
		/**
		 * @var int node number
		 * 
		 */
		private $Node; 
		
		/**
		 * RSS Reader constructor
		 *
		 */
		function __construct() 
		{
			parent::__construct();
		}
		
		
		/**
		 * Get result data
		 * 
		 * @return array array of data
		 */
		public function GetData()
		{ 
			return $this->Data;
		}
		
		
		/**
		 * Reset parset for new parsing
		 * 
		 */
		private function ResetParser()
		{
			$this->Node = 0;
			$this->Data = array();
		}
		
		
		/**
		 * Parse xml/rss into structured array
		 * 
		 * @param string valid xml string
		 */
		public function Parse($xml)
		{ 
			if (!$xml) return;
			
			$this->ResetParser();
			
			$this->XMLParser = xml_parser_create();
			xml_set_object($this->XMLParser, $this);
			xml_parser_set_option($this->XMLParser, XML_OPTION_CASE_FOLDING, true);
			xml_set_element_handler($this->XMLParser, "OpenTag", "CloseTag");
			xml_set_character_data_handler($this->XMLParser, "ParseTag");
			
		    if(!@xml_parse($this->XMLParser, $xml))
		    {
		        $error = xml_error_string(xml_get_error_code($this->XMLParser));
		        Core::RaiseError($error);
		    }
		    
			xml_parser_free($this->XMLParser);
		}
		
		
		/**
		 * Handler for open tag
		 * (start_element_handler)
		 * 
		 * @param resource parser
		 * @param string tag name
		 * @param array associative array with the element's attributes 
		 */
		private function OpenTag(&$parser, &$name, &$attr)
		{
			if($name)
			{
			    switch(strtolower($name))
			    {
					case "channel":
					case "feed":
						$this->XMLBlock = 'channel';
						break;
						
					case "image":
						$this->XMLBlock = 'image';
						break;
						
					case "item":
					case "entry":
						$this->XMLBlock = 'item';
						$this->Node++;
						break;
					
					case 'textinput':
						$this->XMLBlock = 'textinput';
						
					default:
						$this->CurrentTag = strtolower($name);
						break;
			    }
			}
		}
		

		/**
		 * Handler for close tag 
		 * (end_element_handler)
		 * 
		 * @param resource parser
		 * @param string tag name
		 */
		function CloseTag(&$parser, &$name)
		{ 
			$this->CurrentTag = null;
		}
		
		
		/**
		 * Character data handler function for the XML parser
		 * 
		 * @param resource parser
		 * @param string character data
		 */
		function ParseTag(&$parser, &$data)
		{ 
			if (!$this->CurrentTag || !$this->XMLBlock) return;
			
			if ($this->XMLBlock == 'item')
			{ 
				if(isset($this->Data["item"][$this->CurrentTag][$this->Node-1]))
				{
					$this->Data["item"][$this->CurrentTag][$this->Node-1] .= $data;
				} else {
					$this->Data["item"][$this->CurrentTag][$this->Node-1] = $data;
				}
				
				return;
			}
			
			if(isset($this->Data[$this->XMLBlock][$this->CurrentTag]))
			{
				$this->Data[$this->XMLBlock][$this->CurrentTag] .= $data;
			} else {
				$this->Data[$this->XMLBlock][$this->CurrentTag] = $data;
		    }
			    
		}

	}
?>