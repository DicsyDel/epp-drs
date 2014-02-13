<?php

	// +--------------------------------------------------------------------------+
	// | XMLNavigation Class                                                      |
	// +--------------------------------------------------------------------------+
	// | Copyright (c) 2003-2006 Webta Inc, http://webta.net/copyright.html       |
	// +--------------------------------------------------------------------------+
	// | This program is protected by international copyright laws. Any           |
	// | use of this program is subject to the terms of the license               |
	// | agreement included as part of this distribution archive.                 |
	// | Any other uses are strictly prohibited without the written permission    |
	// | of "Webta" and all other rights are reserved.                            |
	// | This notice may not be removed from this source code file.               |
	// | This source file is subject to version 1.1 of the license,               |
	// | that is bundled with this package in the file LICENSE.                   |
	// | If the backage does not contain LICENSE file, this source file is        |
	// | subject to general license, available at http://webta.net/license.html   |
	// +--------------------------------------------------------------------------+
	// | Authors: Alex Kovalyov <ak@webta.net> 									  |
	// +--------------------------------------------------------------------------+
	
	
	class XMLNavigation extends Core
	{
		
		/**
		 * @var object $HTMLMenu Preformatted HTML menu for DHTML top menu
		 */
		public $HTMLMenu;
		
		/**
		 * Xpath menu object
		 *
		 * @var DOMXpath
		 */
		public $XPath;
		
		/**
		 * XML string
		 *
		 * @var string
		 */
		public $XML;
		
		/**
		 * @var object $DMenu Preformatted HTML menu for DHTML top menu 
		 */
		public $DMenu;
		
		/**
		 * @var string $IMenu Preformatted HTML menu for index page 
		 */
		public $IMenu;
		
		/**
		 * @var string $SMenu Preformatted HTML menu for search 
		 */
		public $SMenu;
		
		/**
		 * @var array $ExternalNodes Nodes from external array (Dinamyc)
		 */
		private $ExternalNodes;
		
		/**
		 * Search string
		 *
		 * @var string
		 */
		private $SearchString;
		
		/**
		 * ADODB Instance
		 *
		 * @var object
		 */
		private $DB;
		
		/**
		 * Crumb separator
		 *
		 * @var object
		 */
		private $CrumbSeparator;
		
		/**
		 * Constructor
		 *
		 * @param string $search
		 */
		function __construct($search = false)
		{
			parent::__construct();
			$this->DB = Core::GetDBInstance();
			
			$this->SearchString = $search;
			
            $this->XML = new DOMDocument;
            $this->XML->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?><menu></menu>");
            $this->XML->formatOutput = true;
            
            $this->XPath = new DOMXPath($this->XML);
            
            // Default separator
            $this->CrumbSeparator = "&nbsp;&raquo;&nbsp;";
		}
		
		/**
		 * Add Node to menu
		 *
		 * @param DOMNode $DOMPart
		 * @param DOMNode $menu_part
		 */
		public function AddNode($DOMPart, $menu_part)
		{
            foreach ($DOMPart->childNodes as $node)
            {
                if (!($node instanceof DOMText))
                {
                    if ($node->nodeName == "item")
                    {
                        $n = $this->XML->importNode($node);
                        
                        foreach ($node->attributes as $attr)
                           $n->setAttribute($attr->name, $attr->value);
                        
                            @$n->nodeValue = $node->nodeValue;
                            
                        $menu_part->appendChild($n);
                    }
                    else 
                    {
                        $XPath = new DOMXPath($this->XML);
                        $entries = $XPath->query('//node[@type = "'.$node->getAttribute("type").'"]', $this->XML->documentElement);
                        
                        if ($entries && $entries->item(0))
                            $this->AddNode($node, $entries->item(0)); 
                        else 
                        {
                            $n = $this->XML->importNode($node);
                            foreach ($node->attributes as $attr)
                               $n->setAttribute($attr->name, $attr->value);
                                               
            	            $menu_part->appendChild($n);        	            
                            $this->AddNode($node, $n); 
                        }
                    }
                }
            }
		}
		
		/**
		 * Add XSLT
		 *
		 * @param string $xslpath
		 */
		public function AddXSL($xslpath)
		{
		    $xsl = new DOMDocument;
            $xsl->load($xslpath);
            
            // Configure the transformer
            $proc = new XSLTProcessor;
            $proc->importStyleSheet($xsl); // attach the xsl rules
           
            $DOMDocument = new DOMDocument;
            $DOMDocument->loadXML($proc->transformToXML($this->XML));
            $this->XML = $DOMDocument;
		}
		
		/**
		 * Load XML from string
		 *
		 * @param string $string
		 */
		public function LoadXML($string)
		{
		    $this->XML = new DOMDocument;
		    $this->XML->loadXML($string);
		    if (!$this->XML)
				self::RaiseError(_("Failed to parse navigation menu XML. Make sure that XML is well-formed."));
				
		    $this->XPath = new DOMXPath($this->XML);
		}
		
		/**
		* Load navigation XML file
		* @access public
		* @return bool Baz
		*/
		public function LoadXMLFile($path)
		{
			if (!is_readable($path))
				self::RaiseError(sprintf(_("Failed to read %s"), $path));
				
			$this->XML = new DOMDocument;
            $this->XML->load($path);
            
			if (!$this->XML)
				self::RaiseError(_("Failed to parse navigation menu XML. Make sure that XML is well-formed."));
		}
		
		/**
		 * Build BreadCrumbs
		 *
		 * @param string $filename
		 * @return string
		 */
		public function GenerateBreadCrumbs($filename = false)
		{
		    if (!$filename)
		      $filename = $_SERVER['PHP_SELF'];
		               
		    $xpath = new DOMXPath($this->XML);
            
            $menu = $this->XML->getElementsByTagName("menu")->item(0);
            
            $query = '//item[@href = "'.basename($filename).'"]';
            
            $entries = $xpath->query($query, $menu);
            
            if (count($entries) == 0)
                return false;
            
            $items = array();
            foreach ($entries as $entry) 
            {
                array_push($items, _($entry->nodeValue));
                
                while ($entry->parentNode->nodeName != 'menu')
                {
                    if ($entry->parentNode->nodeName == 'node')
                    {
                        array_push($items, _($entry->parentNode->getAttribute("title")));
                        $entry = $entry->parentNode;
                    }
                }
            }
            
            $items = array_reverse($items); 
            return implode("{$this->CrumbSeparator}", $items);
		}
		
		/**
		* Generate menus from previously parsed XML object
		* @access public
		* @return void
		*/
		public function Generate()
		{
		    $this->HTMLMenu = "";
			$this->GetNodes($this->XML->getElementsByTagName('menu')->item(0));
			$this->DMenu = "<ul id='TopMenu' class='TopMenu' style='visibility:hidden;'>{$this->HTMLMenu}</ul>";
			$this->IMenu = "<ul id='IndexMenu' class='IndexMenu'>{$this->HTMLMenu}</ul>";
			$this->SMenu = "<ul id='IndexMenu' class='IndexMenu'>{$this->SMenu}</ul>";
		}
		
		
		/**
		* Append nodes from structured array
		* @access public
		* @return void
		*/
		public function AppendNodesFromArray($nodes, $parentid)
		{
			if (is_array($nodes))
			{
				if (!$this->ExternalNodes[$parentid])
					$this->ExternalNodes[$parentid] = $nodes;
				else
					$this->ExternalNodes[$parentid] = array_merge($this->ExternalNodes[$parentid], $nodes);
			}
		}
		
		
		/**
		* Recursively parse XML object
		* @access protected
		* @return void
		*/
		protected function GetNodes($root)
		{
			foreach ($root->childNodes as $node)
			{		
			    switch ($node->nodeName)
				{
					
					case "node":
						
						$class = $node->getAttribute('class') ? " class='{$node->getAttribute('class')}'" : "";
						
						$this->HTMLMenu .= "<li{$class}>"._($node->getAttribute('title'))."<ul>";
						$this->SMenu .= "<li>"._($node->getAttribute('title'))."<ul>";
						
						// Add nodes from externals
						$id = (string)$node->getAttribute('id');
						
						if ($this->ExternalNodes[$id])
						{
							$this->GetExternalNodes($this->ExternalNodes[$id]);
						}

						$this->GetNodes($node);
						$this->HTMLMenu .= "</ul></li>";
						
						$this->SMenu .= "</ul></li>";
						break;
							
					case "item":
						
						$class = $node->getAttribute('class') ? " class='{$node->getAttribute('class')}'" : "";
						
						$this->HTMLMenu .= "<li{$class}><a href='{$node->getAttribute('href')}'>"._($node->nodeValue)."</a></li>";
						if (($this->SearchString !== false) && $node->getAttribute('search'))
						{
							$query = (string)$node->getAttribute('search');
							
							$res = $this->DB->GetOne(sprintf($query, "%{$this->SearchString}%", "%{$this->SearchString}%", "%{$this->SearchString}%", "%{$this->SearchString}%"));
							
							$this->SMenu .= "<li><a href='{$node->getAttribute('href')}?search={$this->SearchString}'>"._($node->nodeValue)."</a> ({$res})</li>";
						}
							
						break; 
					
					case "separator":
					
						$this->HTMLMenu .= "<li class='MenuSep'></li>";
						
					break; 
				};
			}
		}
		
		/**
		 * Recursive add new nodes from external array
		 *
		 * @param array $root
		 * @return void
		 */
		private function GetExternalNodes($root)
		{
			foreach ($root as $node_name=>$node_value)
			{
				if (!is_array($node_value))
				{
					if ($node_value)
						$this->HTMLMenu .= "<li><a href='{$node_value}'>{$node_name}</a></li>";	
					else 
						$this->HTMLMenu .= "<li></li>";
				}
				else
				{
					$this->HTMLMenu .= "<li>{$node_name}<ul>";
						$this->GetExternalNodes($node_value);
					$this->HTMLMenu .= "</ul></li>";
				}
			}
		}
	}
	
?>
