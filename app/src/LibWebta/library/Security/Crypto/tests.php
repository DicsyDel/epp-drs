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
     * @package    Security
     * @subpackage Crypto
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource 
     */

	Core::Load("Core");
	Core::Load("Security/Crypto");
	
	/**
	 * @category   LibWebta
     * @package    Security
     * @subpackage Crypto
     * @name Security_Crypto_Test
	 *
	 */
	class Security_Crypto_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Crypto Class Test');
        }
        
        function testSecurity_Crypto_Crypto() 
        {

			//
			// Encrypt and decrypt back
			//
			$input = "testAB*#~!CD123489-++)(7";
			$key = "627~@@h(728_=-2";
			
			// Encrypt
			$Crypto = new Crypto($key);
			$retval = $Crypto->Encrypt($input);
			$this->assertTrue(!empty($retval), "Crypto->Encrypt returned non-empty value");
			
			// Decrypt
			$retval = $Crypto->Decrypt($retval);
			$this->assertTrue(!empty($retval), "Crypto->Decrypt returned non-empty value");
			$this->assertEqual($retval, $input, "Decrypted string is equal to initial one");
			
			// Hash
			$Crypto = new Crypto($key);
			$retval = $Crypto->Hash($input);
			$retval2 = $Crypto->Hash($input."stuff");
			$this->assertTrue($retval != $retval2, "Crypto->Hash returned different hashes from different strings");
			
        }
    }

?>