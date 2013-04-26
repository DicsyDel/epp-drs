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
     * @subpackage Text
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
	 * Load TextParser
	 */
	Core::Load("Data/Text/TextParser");
	
    /**
     * @name HTMLParser
     * @category   LibWebta
     * @package    Data
     * @subpackage Text
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
		
	class HTMLParser extends Core
	{
	
		/**
		 * HTML Parser Constructor
		 *
		 */
		function __construct()
		{
			parent::__construst();
		}
		
		/**
		 * Strip tags from content
		 * 
		 * @param string content
		 * @param string|array of tags
		 * @return string
		 */
		static function StripTags($content, $TagName = null)
		{
			if (!$TagName) return strip_tags($content);
			
			if (is_array($TagName)) $TagName = implode("|", $TagName);
			
			return preg_replace("/\<\/?($TagName)([\s\t])?(?(2)[^\>]*\>|\>)/msi", "", $content);
		}
		
		
		/**
		 * Strip <a> tags from content
		 * @param string $content
		 * @return string
		 */
		static function StripLinks($content)
		{
			return self::StripTags($content, 'a');
		}
		
		
		/**
		 *	Strip links from content where target = _blank 
		 * @param string $content
		 * @return string
		 */
		static function StripBlankLinks($content)
		{
			return preg_replace("/<a\b[^>]*target[\s\=\'\"]+_blank[^>]+>(.*?)<\/a>/msi", "", $content);
		}
		
		/**
		 * Strip elements with absolute position
		 * @param string $content
		 * @return string
		 */
		static function StripAbsoluteElements($content)
		{
			return preg_replace("/<([a-z0-9]+)\b[^>]+position\s*\:[\s\t]*absolute[^>]+>.*?<\/\\1>/msi", "", $content);
		}
		
		
		/**
		 * Strip <script> tags from content
		 * @param string $content
		 * @return string
		 */
		static function StripScripts($content)
		{
			return preg_replace("/(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)/msi", "", $content);
		}
		
		/**
		 * Strip styles
		 * @param string $content
		 * @return string
		 */
		static function StripStyles($content)
		{
			$content = preg_replace("/(?:<style.*?>)((\n|\r|.)*?)(?:<\/style>)/msi", "", $content);
			return self::StripTags($content, 'style');
		}
		
		
		/**
		 * Function for adding number to tag
		 * for example <div>content</div> will be <1div>content<1/div>
		 * @param string $content
		 * @param string $tag
		 * @return string
		 */
		static function AddTagDepth($content, $tag = null)
		{
			$tagnumber = array();
			
			for($i = 0; $i < strlen($content); $i++)
			{
				$newcontent .= $content[$i];
				
				if ($content[$i] == '<' && $content[$i+1] != '/')
				{
					// open tag
					$offset = strpos($content, '>', $i) - 1;
					$tagname = substr($content, $i+1, $offset - $i);
					
					if (strstr($tagname, ' '))
						$tagname = substr($content, $i+1, strpos($content, ' ', $i) - 1 - $i);
					$tagname = strtolower($tagname);
					
					if ($tag && $tagname != $tag) continue;
					
					$tagnumber[$tagname]++;
					$newcontent .= $tagnumber[$tagname];
				}
				elseif ($content[$i] == '<')
				{
					// closing tag
					$offset = strpos($content, '>', $i) - 2;
					$tagname = substr($content, $i+2, $offset - $i);
					$tagname = strtolower($tagname);

					if ($tag && $tagname != $tag) continue;
				
					$newcontent .= $tagnumber[$tagname];
					$tagnumber[$tagname]--;
				}
			}
			
			return $newcontent;
		}
		
		
		/**
		 * Remove numbers from tags
		 * @param string $content
		 * @return string
		 */
		static function RemoveTagDepth($content)
		{
			return preg_replace("/<[0-9-]+/ms", "<", $content);
		}
		
		
		/**
		 * Strip Ads from content
		 * @param string $content
		 * @return string
		 */
		static function StripAds($content)
		{
			$content = self::StripAbsoluteElements($content);
			$content = self::StripBlankLinks($content);
			$content = self::StripScripts($content);
			
			return $content;
		}
		
		/**
		 * Get form details of specified form name or form with existance of
		 * field name $field_name
		 * 
		 * @param string html content
		 * @param string form name
		 * @param string field name
		 * 
		 * @return array form details
		 * 	samle array(
		 * 	 'name' => 'frm',
		 *   'method' => 'post',
		 *   'action' => 'tos.php',
		 *   'elements' => array (
		 *     'email' => ,
		 *     'username' =>
		 *    )
		 *  )
		 */
		static function GetFormDetails($content, $form_name = null, $field_name = null)
		{
			if (!$content) return;
			
			$chunks = preg_split("/<form\b/msi", $content);
			for($i = 1; $i < count($chunks); $i++)
			{
				$chunk = $chunks[$i];
				$lines = explode("\n", $chunk);
				$form = array();
				preg_match_all("/(name|action|method)\s*\=\s*(\'|\")(.*?)\\2/msi", $lines[0], $matches, PREG_SET_ORDER);
				
				foreach($matches as $match)
				{
					$form[strtolower($match[1])] = $match[3];
				}

				if ($form_name && $form_name != $form['name'])
					continue;
					
				preg_match_all("/<input[^>]+>/msi", $chunk, $matches, PREG_SET_ORDER);

				for($k = 0; $k < count($matches); $k++)
				{
					preg_match_all("/(type|name|value|checked)\s*\=\s*(\'|\")(.*?)\\2/i", $matches[$k][0], $inputfields, PREG_SET_ORDER);
					
					$field = array();
					foreach($inputfields as $inputfield)
					{
						$field[strtolower($inputfield[1])] = $inputfield[3];							
					}
					
					if (!$field['name'] || (in_array($field['type'], array('checkbox', 'radio')) && !$field['checked']))
						continue;
						
					$form['elements'][$field['name']] = $field['value'];
				}
				
				if ($field_name && !$form['elements'][$field_name])
					continue;
				
				return $form;
			}
		}
		
	}		
	
?>