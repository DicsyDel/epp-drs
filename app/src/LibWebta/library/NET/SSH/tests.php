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
     * @package    NET
     * @subpackage SSH
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */
    
	Core::Load("NET/SSH/class.SSH2.php");
	
	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage SSH
     * @name  NET_SSH_Test
	 */
	class NET_SSH_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('SSH2 Class Test');
            $this->tmp = ini_get("session.save_path");
        }
        
        
		function testSSH2Remote()
        {
        	$base = dirname(__FILE__);
        	
        	$this->SSH2 = new SSH2();
        	
        	// Failed password
        	$retval = $this->SSH2->Connect("65.38.4.218", 22, "webta", "#webta!");
        	$this->AssertTrue($retval, "Log in with password");
        	
        	$base = dirname(__FILE__);
        	
        	// Correct pubkey
        	//$this->SSH2->AddPubkey("root", "$base/keys/key.pub", "$base/keys/key", "111111");
        	//$retval = $this->SSH2->Connect("webta.net", 60022);
			//$this->AssertTrue($retval, "Logged in with correct public key");
			
			$this->runTests();	
        }
        
        function testSSH2Local()
        {
            /*
        	$base = dirname(__FILE__);
        	
        	$this->SSH2 = new SSH2();
        	
        	$this->SSH2->AddPassword("root", "");
        	
        	// Failed password
        	$retval = $this->SSH2->Connect("192.168.1.200", 22);
        	$this->AssertFalse($retval, "Failed to log in with incorrect root password");
        	
        	$base = dirname(__FILE__);
        	
        	// Correct pubkey
        	$this->SSH2->AddPubkey("root", "$base/keys/key.pub", "$base/keys/key", "111111");
        	$retval = $this->SSH2->Connect("192.168.1.200", 22);
			$this->AssertTrue($retval, "Logged in with correct public key");
			
			$this->runTests();	
			*/
        }
        
        
        function runTests()
        {
        	$res = $this->SSH2->Exec("ls /");
			$this->assertWantedPattern("/boot/", $res, "Received root directory listing");

			$res =  $this->SSH2->GetFile("/etc/passwd");
			$this->assertTrue(strlen($res) > 5, "SSH2->GetFile succesfully retrieve file");
			
			$res =  $this->SSH2->SendFile("/tmp/test.file", __FILE__);
			$this->assertTrue($res, "File ".__FILE__." saved as /tmp/test.file");
			
			$res =  $this->SSH2->GetFile("/tmp/test.file");
			$this->assertWantedPattern("/NET_SSH_Test/", $res, "File /tmp/test.file readed");
			
			$res =  $this->SSH2->Exec("rm -rf /tmp/test.file");
			$this->assertTrue($res, "File /tmp/test.file succesfully deleted");
			
			$res =  $this->SSH2->GetFile("/tmp/test.file");
			$this->assertFalse($res, "Cannot read /tmp/test.file. It was deleted on previous step!");
        }
    }

?>