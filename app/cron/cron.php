<?php
	declare(ticks = 1);
	define("NO_TEMPLATES", true);
	ob_start(); // To avoid "headers already sent"
	require_once(dirname(__FILE__)."/../src/prepend.inc.php");    
	ob_end_clean();
	CONTEXTS::$APPCONTEXT = APPCONTEXT::CRONJOB;
	
	Log::SetLevel(E_ALL, "EPPDRSLogger");
	
	Core::Load("IO/PCNTL/interface.IProcess.php");
	Core::Load("IO/PCNTL");
    Core::Load("System/Independent/Shell/ShellFactory");
    //Core::Load("NET/SNMP");
        
    // Get JobLauncher Instance
    $JobLauncher = new JobLauncher(dirname(__FILE__));
    $process_name = $JobLauncher->GetProcessName();
    // Register shutdown function
    register_shutdown_function("shutdown");
    function shutdown()
    {
    	global $process_name;
    	
    	@file_put_contents(CACHE_PATH."/epp-drs.cron.{$process_name}.pid", "");
    }
    
    //
    // Check for same processes
    //
    $fname = basename($argv[0]);	
	$Shell = ShellFactory::GetShellInstance();
	    
    $pid = @file_get_contents(CACHE_PATH."/epp-drs.cron.{$process_name}.pid");
    
    if ($pid)
    {
        $ps = $Shell->QueryRaw("ps aux | grep '{$pid}' | grep 'cron' | grep -v grep");
        if ($ps)
            die("{$fname} already running. Exiting.");
    }
    
    //
    // Create PID file
    //
    file_put_contents(CACHE_PATH."/epp-drs.cron.{$process_name}.pid", getmypid());
		
	//
	// Launch job
	//
	Log::Log(sprintf("Starting %s cronjob...", $JobLauncher->GetProcessName()), E_USER_NOTICE);
	$JobLauncher->Launch(5);
?>
