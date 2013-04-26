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
     * @package    Data
     * @subpackage Text
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */
	
	$base = dirname(__FILE__);
		
	Core::Load("Data/Text/TextParser");
	Core::Load("Data/Text/HTMLParser");
	Core::Load("Data/Text/DiffTool");
	
	/**
	 * @category   LibWebta
     * @package    Data
     * @subpackage Text
	 * @name Data_Text_Test
	 * 
	 */
	class Data_Text_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('Data/Text test');
        }
        
        function testData_Text_TextParser() 
        {
			$content = "My name is LuLu. I like meat! I very like meat! Recomended!";
			
			$res = TextParser::ExtractKeywords($content, 1);
			$this->assertEqual($res[0], 'meat', "Invalid keyword extracting");
        }
        
        
        function testData_Text_HTMLParser()
        {
			$content = "<a href='#'>Go Daddy</a><attr name='attr'>ATTR</attr>
						<p align=\"center\"><a href=\"http://www.myspace.com/declareyourself\" target=\"_blank\">
						<img src=\"http://creative.myspace.com/groups/_jc/declareyourself/dy_badge.jpg\" border=\"0\" />
						</a></p>";
        	
        	$res = HTMLParser::StripTags($content);
        	$this->assertEqual($res, strip_tags($content), "Error while stripping all tags");
        	
        	$res = HTMLParser::StripTags($content, 'attr');
        	$this->assertFalse(stristr($res, '<attr'), "Error while stripping [attr] tag");
        	
        	$nolinks = HTMLParser::StripTags($content, 'a');
        	$this->assertFalse(stristr($nolinks, 'href'), "Error while stripping [a] tag");
        	
        	$nolinks = HTMLParser::StripLinks($content);
        	$this->assertFalse(stristr($nolinks, 'href'), "Error while stripping links");

        	$res = HTMLParser::StripScripts($content);
        	$this->assertEqual($res, $content, "Error while stripping scripts");

        	$res = HTMLParser::StripTags($content, 'img');
        	$this->assertFalse(stristr($res, 'img'), "Error while stripping [img] tag");
        	
        }
        
        function testData_Text_DiffTool()
        {
        	$base = dirname(__FILE__);
        	$string1 = file_get_contents("$base/test_file_1.txt");
        	$string2 = file_get_contents("$base/test_file_2.txt");
        	$diff = new DiffTool();
        	
        	// test empty patch
        	$patch = $diff->Diff("", "");
        	$this->assertFalse(trim($patch), "I've found an empty patch!");
        	
        	// test successfull Diff execution
        	$patch = $diff->Diff($string1, $string2);
        	$this->assertTrue(is_string($patch) && $patch, "Can't find patch for two strings");
        	
        	if ($patch) 
        	{
	        	// test successfull Patch function execution
        		$result = $diff->Patch($string1, $patch);
        		$this->assertTrue(is_string($result) && $result, "Can't apply patch for string");
        		
	        	// test unsuccessfull Patch function execution
        		$result = $diff->Patch($string1, "-");
        		$this->assertFalse($result, "I can apply string patch '-' for your string!");
        	}
        	
        	// SetReplacementPatterns
			$diff->SetReplacementPatterns(
				array("/^> (.*?)$/m", "/((< (.*?))+)\n\-\-\-\n>/ms"),
				array("> <div class='new'>\\1</div>", "\\1\n---\n> <div class='old'>\\3</div>")
			);
        	
        	// test GetHighlitedDiff
        	$result = $diff->GetHighlitedDiff($string1, $string2);
        	
       		$this->assertTrue(is_string($result) && $result, "GetHighlitedDiff Failed");
       		$this->assertTrue($result != $string2, "Result string is equial to second string");
        }
    }


?>