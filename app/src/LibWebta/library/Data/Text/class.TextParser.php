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
	 * Load HTMLParser
	 */
	Core::Load("Data/Text/HTMLParser");
	
	/**
     * @name TextParser
     * @category   LibWebta
     * @package    Data
     * @subpackage Text
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class TextParser extends Core
	{
	
		/**
		 * Text Parser Constructor
		 *
		 */
		function __construct()
		{
			parent::__construst();
		}
		
		
		/**
		 * Validate all data in array
		 *
		 * @param string $text
		 * @param integer $limit
		 * @return array of keywords
		 * @static 
		 */
		public static function ExtractKeywords($text, $limit = 5)
		{
			$Keywords = array();
			// skip some words
			// scrap only english content
			$SkipWords = array(
				"the", "any", "can", "for", "got", "get", "lot", "and", "see", "sow", 
				"will", "was", "twu", "some", "few", "more", "little", "here", "all", "this",
				"that", "what", "they", "are", "his", "her", "out", "pre", "but", "who",
				"also", "has", "have", "him", "did", "off", "anti", "into", "well", "ever",
				"been", "too", "much", "many", "how", "most", "you", "very", "with", "not",
				"like", "meet", "met", "from", "say", "she", "our", "then", "nice", "there",
				"long", "between", "small", "now", "new", "said", "late", "even", "down", "yes",
				"let", "high", "about", "back", "one", "through", "sex", "two", "three",
				"nbsp", "font", "var", "would", "may", "use", "must", "when"
			);
			
			if ($text)
			{
				$ReducedText = strip_tags($text);
				if (md5($ReducedText) != md5($text))
				{
					$MetaKeywords = array();
					$MetaDescription = "";
					
					//get meta tags
					preg_match_all("/\<meta\s+([^\>]*(?:keywords|description)[^\>]*)\s*\>/msi", $text, $matches);
					$Metas = array_map('strtolower', $matches[1]);
					
					if ($Metas)
					{
						foreach($Metas as $Meta)
						{
							if (preg_match("/name\s*\=\s*(?:\"|\')\s*keywords/i", $Meta))
							{
								// meta keywords
								preg_match("/content\s*\=\s*(\"|\')?([^\>]*)\\1/i", $Meta, $content);
								$MetaKeywords = explode(",", $content[2]);
								$MetaKeywords = array_map('trim', $MetaKeywords);
							}
							elseif (preg_match("/name\s*\=\s*(?:\"|\')\s*description/i", $Meta))
							{
								// meta description
								preg_match("/content\s*\=\s*(\"|\')?([^\>]*)\\1/i", $Meta, $content);
								$MetaDescription = trim($content[2]);
							}
						} // end foreach metas
					}
					
					$ReducedText .= " {$MetaDescription}";
					
					// this is an html formatting text
					preg_match_all("/[a-z][a-z0-9]{2,}/msi", $ReducedText, $matches);
					$Words = array_map('strtolower', $matches[0]);
					$Words = array_diff($Words, $SkipWords);
					$Words = array_count_values($Words);
					arsort($Words);
					array_splice($Words, $limit);
					
					// add meta keywords
					if ($MetaKeywords)
					{
						$maxvalue = max($Words);
						$MetaKeywords = array_flip($MetaKeywords);
						foreach($MetaKeywords as $k => $MetaKeyword)
							$MetaKeywords[$k] = $maxvalue;

						$Words = array_merge($Words, $MetaKeywords);
						arsort($Words);
						array_splice($Words, $limit);
					}
				}
				else
				{
					// this is plain text
					preg_match_all("/[a-z][a-z0-9]{2,}/msi", $text, $matches);
					$Words = array_map('strtolower', $matches[0]);
					$Words = array_diff($Words, $SkipWords);
					$Words = array_count_values($Words);
					arsort($Words);
					array_splice($Words, $limit);
					
				}
				
				$Keywords = array_keys($Words);
			}
			
			return $Keywords;
		}
		
		
	}		
	
?>