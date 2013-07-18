<?

	$base = dirname(__FILE__);
	require_once("{$base}/prepend.inc.php");
	require_once("{$base}/../prepend.inc.php");
	
	
	///
    /// Tests
    ///
    $libpath = "{$base}/../library";  
   
	$test_libwebta = &new GroupTest('LibWebta Tests');

	// Core tests
	//require_once("{$libpath}/tests.php");
	//$test_libwebta->addTestCase(new Core_Test());
	
	// Independent Shell Test
	//require_once("{$libpath}/System/Independent/Shell/tests.php");
	//$test_libwebta->addTestCase(new System_Independent_Shell_Test());
	
	// Windows Shell test
	//if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
	//{
	//	require_once("{$libpath}/System/Windows/Shell/tests.php");
	//	$test_libwebta->addTestCase(new System_Windows_Shell_Test());
	//}
	// TODO: Shell tests
	
	// IO/Basic test
	//require_once("{$libpath}/IO/Basic/tests.php");
	//$test_libwebta->addTestCase(new IO_Basic_Test());
	
	// Data/Validation
	//require_once("{$libpath}/Data/Validation/tests.php");
	//$test_libwebta->addTestCase(new Data_Validation_Test());
	
	// NOTE: Lib required for tests to pass
	//require_once("{$libpath}/IO/Logging/tests.php");
	//$test_libwebta->addTestCase(new IO_LoggingTest());
	
	//require_once("{$libpath}/Security/Crypto/tests.php");
	//$test_libwebta->addTestCase(new Security_Crypto_Test());
		
	//require_once("{$libpath}/Graphics/Captcha/tests.php");
	//$test_libwebta->addTestCase(new Graphics_Captcha_Test());
	
	// NOTE: make GenerateLic public for this test to pass!
	//require_once("{$libpath}/Security/Licensing/tests.php");
	//$test_libwebta->addTestCase(new Security_Licensing_Test());
	
	// Upload Manager Test
	//require_once("{$libpath}/IO/Upload/tests.php");
	//$test_libwebta->addTestCase(new IO_Upload_Test());
	
	// Cache tests
	//require_once("{$libpath}/IO/Cache/tests.php");
	//$test_libwebta->addTestCase(new IO_Cache_Test());

	//require_once("{$libpath}/NET/SNMP/tests.php");
	//$test_libwebta->addTestCase(new NET_SNMP_Test());
		
	//require_once("{$libpath}/Data/XML/OPML/tests.php");
	//$test_libwebta->addTestCase(new Data_XML_OPML_Test());
	
	// VideoUtil tests
	//require_once("{$libpath}/Graphics/VideoUtil/tests.php");
	//$test_libwebta->addTestCase(new VideoUtilTest());
	
	// NET/DNS tests
	//require_once("{$libpath}/NET/DNS/tests.php");
	//$test_libwebta->addTestCase(new NET_DNS_Test());
	
	// NET/SSH tests
	//require_once("{$libpath}/NET/SSH/tests.php");
	//$test_libwebta->addTestCase(new NET_SSH_Test());
	
	// NET/API/BIND tests
	//require_once("{$libpath}/NET/API/SRSPlus/tests.php");
	//$test_libwebta->addTestCase(new SRSPlusTest());
	
	// NET/API/Ventrilo
	//require_once("{$libpath}/NET/API/Ventrilo/tests.php");
	//$test_libwebta->addTestCase(new NET_API_Ventrilo_Test());
	
	// NET/API/Payments
	//require_once("{$libpath}/NET/API/PaymentProcessor/tests.php");
	//$test_libwebta->addTestCase(new NET_API_PaymentProcessor_Test());
	
	// NET/FTP
	//require_once("{$libpath}/NET/FTP/tests.php");
	//$test_libwebta->addTestCase(new NET_FTP_Test());
	
	// NET/API/GoogleCalendar
	//require_once("{$libpath}/NET/API/Google/tests.php");
	//$test_libwebta->addTestCase(new NET_API_Google_Test());
	
	// Data/Formater
	//require_once("{$libpath}/Data/Formater/tests.php");
	//$test_libwebta->addTestCase(new Data_Formater_Test());
	
	// NET/API/Nginx/Nginx
	// require_once("{$libpath}/NET/API/Nginx/tests.php");
	// $test_libwebta->addTestCase(new NET_API_Nginx_Test());
	
	// $test_libwebta->run(new NiceReporter());
	
	// ZIPArchive Tests
	//require_once("{$libpath}/Data/Compress/tests.php");
	//$test_libwebta->addTestCase(new DATA_Compress_ZipArchive_Test());
	
	// RRD Tests Tests
	//require_once("{$libpath}/Data/RRD/tests.php");
	//$test_libwebta->addTestCase(new Data_RRD_Test());
	
	// NET_NNTP tests
	//require_once("{$libpath}/NET/NNTP/tests.php");
	//$test_libwebta->addTestCase(new NET_NNTP_Test());
	
	// System/Unix/Accounting tests
	//require_once("{$libpath}/System/Unix/Accounting/tests.php");
	//$test_libwebta->addTestCase(new System_Unix_Accounting_Test());

	//require_once("{$libpath}/System/Unix/IO/tests.php");
	//$test_libwebta->addTestCase(new System_Unix_IO_Test());
	
	//require_once("{$libpath}/System/Unix/Stats/tests.php");
	//$test_libwebta->addTestCase(new System_Unix_Stats_Test());
	
	//require_once("{$libpath}/System/Unix/Shell/tests.php");
	//$test_libwebta->addTestCase(new System_Unix_Shell_Test());
	
	// MATH tests
	//require_once("{$libpath}/Math/tests.php");
	//$test_libwebta->addTestCase(new Math_Test());

	// PE tests
	//require_once("{$libpath}/PE/tests.php");
	//$test_libwebta->addTestCase(new PE_Test());
	
	// ImageMagick tests
	//require_once("{$libpath}/Graphics/ImageMagick/tests.php");
	//$test_libwebta->addTestCase(new Graphics_ImageMagick_Test());

	// WHM tests
	//require_once("{$libpath}/NET/API/WHM/tests.php");
	//$test_libwebta->addTestCase(new NET_API_WHM_Test());
	
	// Flickr tests
	//require_once("{$libpath}/NET/API/Flickr/tests.php");
	//$test_libwebta->addTestCase(new NET_API_Flickr_Test());
	
	// AWS tests
	//require_once("{$libpath}/NET/API/AWS/tests.php");
	//$test_libwebta->addTestCase(new NET_API_AWS_Test());
	
	
	// REST tests
	#require_once("{$libpath}/NET/REST/tests.php");
	#$test_libwebta->addTestCase(new NET_REST_Test());
	
	
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
