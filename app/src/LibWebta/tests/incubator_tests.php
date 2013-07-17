<?
	$base = dirname(__FILE__);
	require_once("{$base}/prepend.inc.php");
	require_once("{$base}/../prepend.inc.php");
	
	
	///
    /// Tests
    ///
    $libpath = "{$base}/../incubator";  
    $loadbase = $libpath;
    
	$test_libwebta = &new GroupTest('LibWebta Incubator Tests');

	// Core tests
	require_once("{$libpath}/NET/API/Steamcast/tests.php");
	$test_libwebta->addTestCase(new NET_API_Steamcast_Test());
			
	$sapi_type = php_sapi_name();
	if (substr($sapi_type, 0, 3) != 'cli')
	{
		$test_libwebta->run(new NiceReporter());
	}
	else 
	{
		$test_libwebta->run(new ShellReporter());
	}
	
?>