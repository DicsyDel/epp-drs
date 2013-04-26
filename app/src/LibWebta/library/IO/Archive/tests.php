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
     * @package    IO
     * @subpackage Archive
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("IP/Archive");

	/**
	 * @category   LibWebta
     * @package    IO
     * @subpackage Archive
	 * @name Data_Archive_Test
	 */
	class Data_Archive_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Data/Compress/ZipArchive test');
        }
        
        function testZipArchive() 
        {
			
			$Compressor = new ZipArchive();
			$Compressor->AddFile(__FILE__);
			$Compressor->AddFile(dirname(__FILE__). '/class.ZipArchive.php');
			
			$result = $Compressor->Pack();
			
			$this->assertTrue($result, "Archived successfully created");
			
			$content = $Compressor->GetArchive();
			$this->assertTrue(trim($content), "Archived successfully gotten");
			
			file_put_contents("/tmp/archive.zip", $content);
			chmod("/tmp/archive.zip", 0777);
        }
    }
?>
