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
     * @package    Math
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @ignore
     */

	Core::Load("Distribution");
    
	/**
	 * @category   LibWebta
     * @package    Distribution
	 * @name Distribution_Test
	 */
	
	class EventListenerStub
	{
		 public function OnEvent()
		 {
		 	echo "<hr>";
		 	var_dump(func_get_args());
		 	echo "<hr>";
		 }
	}
	
	class Distribution_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Distribution');
        }
        
        function testAutoUpdateClient() 
        {
        	/*
			$AutoUpdateClient = new AutoUpdateClient();
			
			// Preparation
			$AutoUpdateClient->AddService("http://k2:95/data");
			$AutoUpdateClient->AddService("http://autoup-1.webta.net/data");
			$AutoUpdateClient->SetProductID("epp-drs");
			$AutoUpdateClient->TarCmd = "C:\\\\windows\\tar.exe";
			// Set tmp directory
			global $cachepath;
			$AutoUpdateClient->SetTempDir($cachepath);
			// Bind event listener
			$AutoUpdateClient->BindEventListener(new EventListenerStub());
			
			// Extract current license
        	$LicenseManager = new LicenseManager("Cheeshoo1ahph0pieH7Sei5a");
			$LicenseManager->LoadLicFiles("/src/epp-drs/app/etc");
			$LicenseManager->SetFrequency(0);			
			$lic = $LicenseManager->SelectWeakestLic();
			$AutoUpdateClient->SetLicense($lic);
			
			// Get a revisions list
			$result = $AutoUpdateClient->ListRevisions();
			$this->assertTrue(is_array($result), "ListRevisions returned array");
			
			// List Hops needed for update
			$AutoUpdateClient->SetLocalRevision(565);
			$hops = $AutoUpdateClient->ListHops(565, 570);
			$this->assertTrue(count($hops) == 3, "ListHops returned array of correct length");
			
			// Most recent revision
			$result = $AutoUpdateClient->GetLatestRevision();
			$this->assertTrue(is_int($result), "GetLatestRevision returned number");
			
			// Update! Yarr!
			$AutoUpdateClient->UpdateToLatest();
			$report = $AutoUpdateClient->BuildReport();
			*/
        }
    }
?>
