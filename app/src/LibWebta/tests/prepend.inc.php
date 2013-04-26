<?
	
	// Chdir to src/Tests directory. This is just for VS.NET
	$base = dirname(__FILE__);
	
	//require_once ("$base/../prepend.inc.php");

	/*
	assertTrue(\$x) Fail if \$x is false 
	assertFalse(\$x) Fail if \$x is true 
	assertNull(\$x) Fail if \$x is set 
	assertNotNull(\$x) Fail if \$x not set 
	assertIsA(\$x, \$t) Fail if \$x is not the class or type \$t 
	assertEqual(\$x, \$y) Fail if \$x == \$y is false 
	assertNotEqual(\$x, \$y) Fail if \$x == \$y is true 
	assertIdentical(\$x, \$y) Fail if \$x === \$y is false 
	assertNotIdentical(\$x, \$y) Fail if \$x === \$y is true 
	assertReference(\$x, \$y) Fail unless \$x and \$y are the same variable 
	assertCopy(\$x, \$y) Fail if \$x and \$y are the same variable 
	assertWantedPattern(\$p, \$x) Fail unless the regex \$p matches \$x 
	assertNoUnwantedPattern(\$p, \$x) Fail if the regex \$p matches \$x 
	assertNoErrors() Fail if any PHP error occoured 
	assertError(\$x) Fail if no PHP error or incorrect message 
	*/
	
	@ini_set("implicit_flush", 1);
	
	define("PRINTPASSES", true);
	
	
	$base = dirname(__FILE__);
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', "$base/simpletest/");
    }
   
    require_once(SIMPLE_TEST . 'unit_tester.php');
    require_once(SIMPLE_TEST . 'reporter.php');
    
    require_once('class.NiceReporter.php');
    require_once('class.ShellReporter.php');
	
?>