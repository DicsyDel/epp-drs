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
     * @package    Graphics
     * @subpackage ImageMagick
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

    /**
     * @category   LibWebta
     * @package    PE
     * @name       PE_Test
     */
	class PE_Test extends UnitTestCase 
	{
		
		protected $MProcess;
		const TESTDIR = "/usr/local/www/data-dist/ImageFactory3/server/src/Tests/storage/in";
		
        function __construct() 
        {
        	
            $this->UnitTestCase('PE test');
            
            Core::Load("PE/ManagedProcess");
            Core::Load("PE/PipedChain");
            
        }
        
        function testWithFile()
        {
            $test_image_path = "/var/homes/igor/test1.jpg";
            
            @unlink("/tmp/temp/test2.jpg");
            
            $this->PChain = new PipedChain();
        	$this->PChain->AddLink("/usr/local/bin/convert - -normalize -"); 
        	$retval = $this->PChain->Execute(false, "/var/homes/igor/temp/test".round(100000, 9999999).".jpg", $test_image_path);       	
        	
        	$this->AssertTrue($retval, "Chain return true");
        	
        	$this->AssertTrue(@filesize("/var/homes/igor/temp/test2.jpg") > 0, "Result file not empty"); 
      	
        }
        
        function _testWithBlob()
        {
            $test_image_path = "/var/homes/igor/test1.jpg";
            
            @unlink("/tmp/temp/test1.jpg");
            
            $this->PChain = new PipedChain();
        	$this->PChain->AddLink("/usr/local/bin/convert - -normalize jpg:-"); 
        	$retval = $this->PChain->Execute(file_get_contents($test_image_path));       	
        	$this->AssertTrue($this->PChain->StdOut && $retval, "Chain return true and stdout not null");
        	if ($this->PChain->StdOut)
        	{
        	    @file_put_contents("/var/homes/igor/temp/test2".round(100000, 9999999).".jpg", $this->PChain->StdOut);
        	    $this->AssertTrue(@filesize("/var/homes/igor/temp/test1.jpg") > 0, "Result file not empty");
        	}
        }
        
		function _testProcessRun()
        {
        	$this->MProcess = new ManagedProcess(); 
        	
        	// Touch file and see if it does exist
        	$this->MProcess->Execute("touch /tmp/mptest");
        	$this->AssertTrue(file_exists("/tmp/mptest"), "/tmp/mptest exists");
        	
        	// ls and see if its not empty
        	$this->MProcess->Execute("ls");
        	$this->AssertTrue(!empty($this->MProcess->StdOut), "ls returned smth to STDOUT");
        	
        	// Make sure that there is no unclefucker binary in PATH
        	$this->MProcess->Execute("unclefucker");
        	$this->AssertTrue(!empty($this->MProcess->StdErr), "unclefucker returned smth to STDERR");
        	
        	// Make sure that php is in PATH
			$stdin = "<? echo 'test' ?>";
        	$this->MProcess->Execute("php -q", $stdin);
        	$this->AssertEqual("test", $this->MProcess->StdOut, "php executed code and returned correct STDOUT");
        	
        	// Test output to file
        	$tmpfile = "/tmp/tmpfile";
        	$this->MProcess->Execute("php -q", $stdin, $tmpfile);
        	$this->AssertTrue(filesize($tmpfile) > 0, "Output file not empty");
        	@unlink($tmpfile);
        	
        }
        
        function _testChain()
        {
        	$stdin = "<? echo 'test' ?>";
        	
        	$this->PChain = new PipedChain();
        	$this->PChain->AddLink("php -q"); 
        	$this->PChain->AddLink("grep -c 'test'");
        	$retval = $this->PChain->Execute($stdin);
        	$this->AssertTrue($retval, "Chain return true");
        	
        	// Test output to file
        	$tmpfile = "/tmp/tmpfile";
        	$retval = $this->PChain->Execute($stdin, $tmpfile);
        	$this->AssertTrue(filesize($tmpfile) > 0, "Output file not empty");
        	@unlink($tmpfile);
        	
        }

    }


?>