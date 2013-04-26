<?
	set_time_limit(0);	

	$base = dirname(__FILE__);

	//
	// SimpleTest stuff and Core tests
	//
	require_once("$base/../src/prepend.inc.php");	
	require_once("$base/../src/LibWebta/tests/tests.php");
	
	require_once("$base/../../../src/tests.php");
	// Lib tests
	//$test0 = &new GroupTest('Core test');
	//$test0->addTestCase(new LibTest());
	//$test0->addTestCase(new ManifestTest());
	//$test0->run(new NiceReporter());
	
	//
	// Application tests
	//
	// SRSPlus
	//$test2 = &new GroupTest('SRSPlus');
	
	//require_once("{$base}/../library/NET/API/SRSPlus/tests.php");
    //$test2->addTestCase(new SRSPlusTest());
    
    // Registries
	$test3 = &new GroupTest('Registry modules test');

	//require_once (MODULES_PATH . "/payments/Sermepa/tests.php");
	//$test3->addTestCase(new SermepaPaymentTests());	
	
	require_once (SRC_PATH . "/Api/tests.php");
	$test3->addTestCase(new ApiTests());
	
    //require_once (MODULES_PATH . "/registries/DotMX/tests.php");
    //$test3->addTestCase(new DotMXRegistryTests());

    //require_once (MODULES_PATH . "/registries/Afilias/tests.php");
    //$test3->addTestCase(new AfiliasRegistryTests());
	
    //require_once (MODULES_PATH . "/registries/COCCAEPP1/tests.php");
    //$test3->addTestCase(new COCCAEPP1RegistryTests());

	//require_once(MODULES_PATH."/registries/DotTEL/tests.php");
	//$test3->addTestCase(new DotTELRegistryTests());

	//require_once(MODULES_PATH."/registries/EPPGR/tests.php");
	//$test3->addTestCase(new EPPGRTests());
	
	//require_once(MODULES_PATH."/registries/DotSE/tests.php");
	//$test3->addTestCase(new DotSERegistryTests());

	//require_once(MODULES_PATH."/registries/DotNO/tests.php");
	//$test3->addTestCase(new DotNORegistryTests());	
	
	
	//require_once(MODULES_PATH."/registries/DotKZ/tests.php");
	//$test3->addTestCase(new DotKZRegistryTests());

	//require_once(MODULES_PATH."/registries/EPPNL/tests.php");
	//$test3->addTestCase(new EPPNLRegistryModuleTest());

	
    //require_once(MODULES_PATH."/registries/OnlineNIC/tests.php");
    //$test3->addTestCase(new OnlineNICRegistryTests());

	//require_once(MODULES_PATH."/registries/DotBE/tests.php");
	//$test3->addTestCase(new DotBERegistryTests());
	
	
	//require_once(MODULES_PATH."/registries/DotEU/tests.php");
	//$test3->addTestCase(new DotEURegistryTests());

	//require_once(MODULES_PATH."/registries/RRPProxy/tests.php");
	//$test3->addTestCase(new RRPProxyRegistryTests());
    
	
	//require_once (MODULES_PATH . '/registries/tests.php');
	//$test3->addTestCase(new RegistryTests());
	
	//require_once(MODULES_PATH."/registries/EPPCH/tests.php");
	//$test3->addTestCase(new EPPCHTests());
    
	
    
    //require_once (MODULES_PATH . "/registries/SRSPlus/tests.php");
    //$test3->addTestCase(new SRSPlusRegistryTests());
    
    //require_once (MODULES_PATH . "/registries/Verisign/tests.php");
    //$test3->addTestCase(new VerisignRegistryTests());
    
	
    //require_once (MODULES_PATH . "/registries/tests.php");
    //$test3->addTestCase(new RegistryTests());
    
    
    //require_once(MODULES_PATH."/registries/EPPLU/tests.php");
    //$test3->addTestCase(new EPPLURegistryTests());
    

    //require_once(MODULES_PATH."/registries/GenericEPP/tests.php");
	//$test3->addTestCase(new GenericEPPTests());
	
	//require_once(MODULES_PATH."/registries/Verisign/tests.php");
	//$test3->addTestCase(new VerisignTests());
	
	//require_once(MODULES_PATH."/registries/RRPProxy/tests.php");
	//$test3->addTestCase(new RRPProxyTests());
	
	//require_once(MODULES_PATH."/registries/EPPLU/tests.php");
	//$test3->addTestCase(new EPPLURegistryTests());
	
	//require_once(MODULES_PATH."/registries/OnlineNIC/tests.php");
	//$test3->addTestCase(new OnlineNICRegistryTests());
	
	
	//require_once (MODULES_PATH . "/registries/Enom/tests.php");
    //$test3->addTestCase(new EnomRegistryTests());
	
	///////////////////
	// Payment modules tests
	//
	//$test4 = &new GroupTest('Payment modules test');
	//require_once(MODULES_PATH."/payments/EZBill/tests.php");
	//$test4->addTestCase(new EZBillTests());
	//require_once(MODULES_PATH."/payments/PayPal/tests.php");
	//$test4->addTestCase(new PayPalTests());
	 
	//////////////////////
	// Mailer test
	//
	//$test5 = &new GroupTest('PHPSmartyMailer');
	
	//require_once("{$base}/../library/NET/Mail/tests.php");
    //$test5->addTestCase(new PHPSmartyMailerTest());
	    
    // Run tests 
	//$test2->run(new NiceReporter());

	$sapi_type = php_sapi_name();
	if (substr($sapi_type, 0, 3) != 'cli')
	{
		$test3->run(new NiceReporter());
	}
	else 
	{
		$test3->run(new ShellReporter());
	}
	
	//$test4->run(new NiceReporter());
	//$test5->run(new NiceReporter()); 
?>
