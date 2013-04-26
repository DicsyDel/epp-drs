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
     * @package    Security
     * @subpackage Licensing
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @ignore
     */
    
	Core::Load("Core");
	Core::Load("CoreException");
	Core::Load("Security/Crypto");
	Core::Load("Security/Licensing/LicenseManager");
	
	/**
	 * @ignore
	 *
	 */
	class Security_Licensing_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Security/Licensing Tests');
        }
		
        
        function testSecurity_Licensing_LicenseManager() 
        {
			$base = dirname(__FILE__);
			
			define('LIC_PRODUCTID', 1);
			$LicenseManager = new LicenseManager();
			$LicenseManager->ErrorOnFailure = false;
			$LicenseManager->SetFrequency(100);
			
			
			// Create temp file
			$path = ($path = ini_get("session.save_path")) ? $path : "/tmp";
			@unlink("$path/ip.lic");
			@unlink("$path/trial.lic");
			
			//
			// Generate IP lic
			//
			$lic = $LicenseManager->GenerateLic("ip", "192.168.1.253", time() + 100000000, "10", "Jobs", LIC_PRODUCTID);
			$res = file_put_contents("$path/ip.lic", $lic); 
			//exit();
			$this->assertTrue($lic, "License string is not empty");
			
			// Valdate lic
			if ($LicenseManager->DoTriggerValidation())
			{
				$LicenseManager->LoadLicFiles($path);
				$result = $LicenseManager->ValidateLic();
				$this->assertTrue($result, "IP lic not validated");
			}
			@unlink("$path/ip.lic");
			
			
			
			//
			// Generate trial expired lic
			//
			$lic = $LicenseManager->GenerateLic("trial", "", time()-1, "10", "WHMCluster", LIC_PRODUCTID);
			$res = file_put_contents("$path/trial.lic", $lic); 
			
			// Validate lic
			$LicenseManager->LoadLicFiles($path);
			$result = $LicenseManager->ValidateLic();
			$this->assertFalse($result, "Trial expired lic not validated");
			/* */
			
			
			
			//
			// Validate trial then ip
			//
			$lic = $LicenseManager->GenerateLic("trial", "", time()-1, "10", "WHMCluster", LIC_PRODUCTID);
			$res = file_put_contents("$path/trial.lic", $lic);
			$lic = $LicenseManager->GenerateLic("ip", "192.168.1.253", time()+100000000, "10", "WorldOfEU", LIC_PRODUCTID);
			file_put_contents("$path/ip.lic", $lic); 
			// Now we have 1 expired trial and 1 valid IP
			$LicenseManager->LoadLicFiles($path);
			$result = $LicenseManager->ValidateLic();
			$this->assertTrue($result, "Expired trial and valid IP bundle validated ok");
			
			
			//
			// Generate trial OK lic
			//
			$lic = $LicenseManager->GenerateLic("trial", "", time()+100000000, "10", "WHMCluster", LIC_PRODUCTID);
			$res = file_put_contents("$path/trial.lic", $lic); 
			
			// Valdate lic
			$LicenseManager->LoadLicFiles($path);
			$result = $LicenseManager->ValidateLic();
			$this->assertTrue($result, "Trial lic validated ok");
			@unlink("$path/trial.lic");
			
			
			
			// Check frequency stuff
			$LicenseManager->SetFrequency(0);
			$this->assertFalse($LicenseManager->DoTriggerValidation(), "DoTriggerValidation() returned false");
			
			
			#$lic = $LicenseManager->GenerateLic("trial", "", time()+1000000, "11", "WHMCluster");
			#file_put_contents("$path/trial.lic", $lic); 
			
			#$lic = $LicenseManager->GenerateLic("ip", "209.200.241.38", time()+200000000, "103", "EPP-DRS");
			#$res = file_put_contents("$path/ip.lic", $lic);
			
			/* */
			@unlink("$path/ip.lic");
			@unlink("$path/trial.lic");


			// generate lic for one product and validate for other
			$lic = $LicenseManager->GenerateLic("ip", "192.168.1.253", time()+100000000, "10", "WorldOfEU", LIC_PRODUCTID . "1");
			file_put_contents("$path/ip.lic", $lic); 
			$LicenseManager->LoadLicFiles($path);
			$result = $LicenseManager->ValidateLic();
			$this->assertFalse($result, "License generated for other product then validated");
			@unlink("$path/ip.lic");

			$md5_prepend = @md5_file(LIBWEBTA_BASE . "/../../prepend.inc.php");
			$md5_lic = @md5_file(dirname(__FILE__) . "/class.LicenseManager.php");
			
			
			// check md5 sum of files 
			$lic = $LicenseManager->GenerateLic("ip", "192.168.1.253", time()+100000000, "10", "WorldOfEU", LIC_PRODUCTID, $md5_prepend, $md5_lic);
			file_put_contents("$path/ip.lic", $lic); 
			$LicenseManager->LoadLicFiles($path);
			$result = $LicenseManager->ValidateLic();
			$this->assertTrue($result, "License generated with checking md5 is invalid");
			
			// check md5 sum of files 
			$lic = $LicenseManager->GenerateLic("ip", "192.168.1.253", time()+100000000, "10", "WorldOfEU", LIC_PRODUCTID, $md5_prepend, "---");
			file_put_contents("$path/ip.lic", $lic); 
			$LicenseManager->LoadLicFiles($path);
			$result = $LicenseManager->ValidateLic();
			$this->assertFalse($result, "License generated with checking md5 is valid");
						
        }
    }

?>