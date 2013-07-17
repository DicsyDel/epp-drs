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
     * @subpackage Validation
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("Data/Validation");
	
	/**
	 * @category   LibWebta
     * @package    Data
     * @subpackage Validation
	 * @name Data_Validation_Test
	 */
	class Data_Validation_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Data/Validation Tests');
        }
        
        function testValidation() 
        {
        	
        	$Validator = new Validator();
        	
        	// IsEmpty
        	$empty_var = 'sdfsdfsdfs';
        	$empty_var2 = 'sdfsdfs';
        	$result = $Validator->IsNotEmpty($empty_var) && $Validator->IsNotEmpty($empty_var2);
        	$this->assertTrue($result, "Validator->IsEmpty returned true on non empty var");
        	
        	$empty_var = '';
        	$empty_var2 = 0;
        	$result = $Validator->IsNotEmpty($empty_var) && $Validator->IsNotEmpty($empty_var2);
        	$this->assertFalse($result, "Validator->IsEmpty returned false on empty var");
        	
        	// IsNumeric
        	$number1 = 0; $number2 = -1; $number3 = 1.2;
        	$result = $Validator->IsNumeric($number1) && $Validator->IsNumeric($number2) && $Validator->IsNumeric($number3);
        	$this->assertTrue($result, "Validator->IsNumeric returned true on number var");
        	
        	$number1 = "1,2"; $number2 = "abc";
        	$result = $Validator->IsNumeric($number1) && $Validator->IsNumeric($number2);
        	$this->assertFalse($result, "Validator->IsNumeric returned false on non-number var");
        	
        	
        	// IsAlpha
        	$alpha = "Alpha";
        	$result = $Validator->IsAlpha($alpha);
        	$this->assertTrue($result, "Validator->IsAlpha returned true");
        	
        	$alpha = "22323";
        	$result = $Validator->IsAlpha($alpha);
        	$this->assertFalse($result, "Validator->IsAlpha returned false");
        	
	       	// IsAlphaNumeric
        	$alpha = "Alpha12";
        	$result = $Validator->IsAlphaNumeric($alpha);
        	$this->assertTrue($result, "Validator->IsAlphaNumeric returned true");
        	
        	$alpha = "22323 sdfsd  fsdf s";
        	$result = $Validator->IsAlphaNumeric($alpha);
        	$this->assertFalse($result, "Validator->IsAlphaNumeric returned false");
        	
        	// IsEmail
        	$email = "test@test.com";
        	$result = $Validator->IsEmail($email);
        	$this->assertTrue($result, "Validator->IsEmail returned true");
        	
        	$email = "asdasdsdfs";
        	$result = $Validator->IsEmail($email);
        	$this->assertFalse($result, "Validator->IsEmail returned false");
        	
        	$email = "yarr!@epp-drs-com-com.com";
        	$result = $Validator->IsEmailPlusDNS($email);
        	$this->assertFalse($result, "Validator->IsEmailPlusDNS returned false for non-existent domain");
        	
        	// IsURL
        	$url = "http://webta.net";
        	$url2 = "webta.net";
        	$url3 = "www.webta.net";
        	$url4 = "http://www.webta.net";
        	$result = 	$Validator->IsURL($url) && 
        				$Validator->IsURL($url2) && 
        				$Validator->IsURL($url3) && 
        				$Validator->IsURL($url4);
        	$this->assertTrue($result, "Validator->IsURL returned true");
        	
        	$url = "asdasdsdfs";
        	$url2 = "asdasdsdfs-222.1-";
        	$url3 = "-webta.com";
        	
        	$result = $Validator->IsURL($url) &&
        			  $Validator->IsURL($url2) &&
        			  $Validator->IsURL($url3);
        	$this->assertFalse($result, "Validator->IsURL returned false");
        	
        	// IsIPAddress
        	$ip = "10.100.10.10";
        	$result = $Validator->IsIPAddress($ip);
        	$this->assertTrue($result, "Validator->IsIPAddress returned true");
        	
        	$ip = "288.1221.11.11";
        	$result = $Validator->IsIPAddress($ip);
        	$this->assertFalse($result, "Validator->IsIPAddress returned false");
        	
        	// IsExternalIPAddress
        	$result = $Validator->IsExternalIPAddress("111.120.11.1");
        	$this->assertTrue($result, "Validator->IsExternalIPAddress returned true for '111.120.11.1'");
        	
        	$result = $Validator->IsExternalIPAddress("192.168.1.1");
        	$this->assertFalse($result, "Validator->IsExternalIPAddress returned false for '192.168.1.1'");
        	
        	$result = $Validator->IsExternalIPAddress("172.16.10.100");
        	$this->assertFalse($result, "Validator->IsExternalIPAddress returned false for '172.16.10.100'");
        	
        	$result = $Validator->IsExternalIPAddress("172.32.10.100");
        	$this->assertTrue($result, "Validator->IsExternalIPAddress returned true for '172.32.10.100'");
        	
        	// IsDomain
        	$result = $Validator->IsDomain("webta.net");
        	$this->assertTrue($result, "Validator->IsDomain returned true for webta.net");
        	
        	$result = $Validator->IsDomain("c1.webta.net");
        	$this->assertTrue($result, "Validator->IsDomain returned true for c1.webta.net");
        	
        	$result = $Validator->IsDomain("webta@net") || $Validator->IsDomain("webtanet");
        	$this->assertFalse($result, "Validator->IsDomain returned false for weird zones");
        	
        	
        	// MatchesPattern
        	$string = "abs_111";
        	$pattern = "/^[A-Za-z]{3}_[0-9]{3}$/";
        	$result = $Validator->MatchesPattern($string, $pattern);
        	$this->assertTrue($result, "Validator->MatchesPattern returned true");
        	
        	$pattern = "/^[A-Za-z]_[0-9]$/";
        	$result = $Validator->MatchesPattern($string, $pattern);
        	$this->assertFalse($result, "Validator->MatchesPattern returned false");
        	
        	// AreEqual
        	$s1 = "abc123";
        	$s2 = "abc123";
        	$result = $Validator->AreEqual($s1, $s2);
        	$this->assertTrue($result, "Validator->AreEqual returned true");
        	
        	$s2 = "sdfsdfasd";
        	$result = $Validator->AreEqual($s1, $s2);
        	$this->assertFalse($result, "Validator->AreEqual returned false");
        }
        
        
        function testAggregated()
        {
        	
        	$array = array();
        	
        	
        	$fields = array(
        		"asda" => "IsNotEmpty",
        		"firstname" => "IsAlpha",
				"email@email.com" => "IsEmail"
        	);
        	
        	
        	$fields2 = array(
        		"" => "IsNotEmpty",
        		"first name" => "IsAlpha",
				"email/email.com" => "IsEmail"
        	);
        	
        	$Validator = new Validator();
        	$result = $Validator->ValidateAll($fields);
        	$this->assertTrue($result === true, "Validator->ValidateAll returned true");
        	
        	$result = $Validator->ValidateAll($fields2);
        	$this->assertTrue(count($result) === 3, "Validator->ValidateAll returned array");
        }
        
        
		function testErrors()
        {
        	
        	$Validator = new Validator();
        	
        	$Validator->IsNotEmpty("", "Empty variable");
        	$Validator->IsAlpha(1000);
        	
        	$this->assertTrue(count($Validator->Errors) === 2, "Validator->Errors returned 2-items long array");
        	
        }
    }

?>