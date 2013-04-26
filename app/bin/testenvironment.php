<?
	// Check POSIX
	if (!function_exists('posix_getpid')) 
		$err[] = "<span style='color:red;'>Cannot find posix_getpid function. Make sure that POSIX Functions enabled.</span>";
	
	// Check DOM
	if (!class_exists('DOMDocument')) 
		$err[] = "<span style='color:red;'>Cannot find DOM functions. Make sure that DOM Functions enabled.</span>";
	
	// Check CURL
	if (!function_exists('curl_init')) 
		$err[] = "<span style='color:red;'>Cannot find curl_init function. Make sure that CURL Functions enabled.</span>";
	
	// Check SimpleXML
	if (!function_exists('simplexml_load_string')) 
		$err[] = "<span style='color:red;'>Cannot find simplexml_load_string function. Make sure that SimpleXML Functions enabled.</span>";
		
	// Check MySQLi
	if (!function_exists('mysqli_connect')) 
		$err[] = "<span style='color:red;'>Cannot find mysqli_connect function. Make sure that MYSQLi Functions enabled.</span>";
	
	// Check GetText
	if (!function_exists('gettext')) 
		$err[] = "<span style='color:red;'>Cannot find gettext function. Make sure that GetText Functions enabled.</span>";
		
	// Check MCrypt
	if (!function_exists('mcrypt_encrypt')) 
		$err[] = "<span style='color:red;'>Cannot find mcrypt_encrypt function. Make sure that mCrypt Functions enabled.</span>";

	// Check OpenSSL
	if (!function_exists('openssl_verify')) 
		$err[] = "<span style='color:red;'>Cannot find OpenSSL functions. Make sure that OpenSSL Functions enabled.</span>";	
		
	// Check OpenSSL
	if (!function_exists('socket_create')) 
		$err[] = "<span style='color:red;'>Cannot find Sockets functions. Make sure that Sockets Functions enabled.</span>";	
			
	if (count($err) == 0)
		print "<span style='color:green'>Congratulations, your environment settings match EPP-DRS requirements!</span>";
	else 
	{
		foreach ($err as $e)
			print $e."<br>";
	}
?>