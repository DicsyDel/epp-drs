<?
	$sapi_type = php_sapi_name();
	if (substr($sapi_type, 0, 3) == 'cli')
		$CLI = true;
		
		
	// Check POSIX
	if (!function_exists('posix_getpid')) 
		$err[] = "Cannot find posix_getpid function. Make sure that POSIX Functions enabled.";
		
	// Check PCNTL
	if (!function_exists('pcntl_signal'))
		$err[] = "Cannot find pcntl_signal function. Make sure that PCNTL Functions enabled.";

	// Check Zend Optimazer
	if (!function_exists("zend_loader_file_licensed")) 
		$err[] = "Cannot find Zend Optimizer extension. Make sure that Zend Optimizer installed.";
		
	// Check XSLT
	if (!class_exists('XSLTProcessor')) 
		$err[] = "Cannot find XSLTProcessor class. Make sure that XSL Functions enabled.";
		
	// Check DOM
	if (!class_exists('DOMDocument')) 
		$err[] = "Cannot find DOM functions. Make sure that DOM Functions enabled.";
	
	// Check CURL
	if (!function_exists('curl_init')) 
		$err[] = "Cannot find curl_init function. Make sure that CURL Functions enabled.";
	
	// Check SimpleXML
	if (!function_exists('simplexml_load_string')) 
		$err[] = "Cannot find simplexml_load_string function. Make sure that SimpleXML Functions enabled.";
		
	// Check MySQLi
	if (!function_exists('mysqli_connect')) 
		$err[] = "Cannot find mysqli_connect function. Make sure that MYSQLi Functions enabled.";
	
	// Check GetText
	if (!function_exists('gettext')) 
		$err[] = "Cannot find gettext function. Make sure that GetText Functions enabled.";
		
	// Check MCrypt
	if (!function_exists('mcrypt_encrypt')) 
		$err[] = "Cannot find mcrypt_encrypt function. Make sure that mCrypt Functions enabled.";

	if (!(function_exists("hash") || function_exists("mhash")))
		$err[] = "Cannot find mhash function. Make sure that mHash Functions enabled";
		
	// Check OpenSSL
	if (!function_exists('openssl_verify')) 
		$err[] = "Cannot find OpenSSL functions. Make sure that OpenSSL Functions enabled.";	
		
	// Check Sockets
	if (!function_exists('socket_create')) 
		$err[] = "Cannot find Sockets functions. Make sure that Sockets Functions enabled.";	

	//
	// Check php sessings
	//
	if (ini_get('safe_mode') == 1)
		$err[] = "PHP safe mode enabled. Please disable it.";
		
	if (ini_get('register_gloabls') == 1)
		$err[] = "PHP register globals enabled. Please disable it.";
		
	if (ini_get("zend.ze1_compatibility_mode") == 1) {
		$err[] = "PHP zend.ze1_compatibility_mode must be Off";
	}

	if (ini_get("allow_url_fopen") == 0) {
		$err[] = "PHP allow_url_fopen must be enabled.";
	}
	
	if (ini_get("allow_call_time_pass_reference") == 0) {
		$err[] = "PHP allow_call_time_pass_reference must be enabled.";
	}
		
	if (str_replace(".", "", PHP_VERSION) < 525)
		$err[] = "PHP version must be 5.2.5 or greater.";		
		
		
	if (!$CLI)
	{
		if (count($err) == 0)
			print "<span style='color:green'>Congratulations, your environment settings match EPP-DRS requirements!</span>";
		else 
		{
			print "<span style='color:red'>Errors:</span><br>";
			foreach ($err as $e)
				print "<span style='color:red'>&bull; {$e}</span><br>";
		}
	}
	else
	{
		if (count($err) == 0)
			print "\033[32mCongratulations, your environment settings match EPP-DRS requirements!\033[0m\n";
		else 
		{
			print "\033[31mErrors:\033[0m\n";
			foreach ($err as $e)
				print "\033[31m- {$e}\033[0m\n";
		}
	}		
?>