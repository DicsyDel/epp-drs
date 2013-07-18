<?
	require_once('src/prepend.inc.php');

	$display["version"] = CONFIG::$APP_REVISION;
	
	//
	// Determine hostid
	//
	if (function_exists("zend_get_id"))
	{
		$display["hostid"] = zend_get_id();
	}
	else 
	{
		$retval = @exec(CONFIG::$ZENDID_PATH, $output, $return_var);
		if ($return_var != 0)
		{
			$os = strtolower(php_uname("s"));
							
			if ($os == "freebsd")
			{
				$r = php_uname("r");
				$major_version = substr($r, 0, 1);
				if ($major_version > 5) $major_version = 5;
				$zendid_filename = "zendid_{$os}{$major_version}x";
			}
			else 
			{
				if ($os == 'linux')
				{
					$tp = (php_uname("m") == 'i386') ? 32 : 64;
					$zendid_filename = "zendid_{$os}{$tp}";
				}
				else 
					$zendid_filename = "zendid_{$os}";
			}
			
			if (file_exists(CONFIG::$PATH."/bin/zendid/{$zendid_filename}"))
			{
				$retval = @exec(CONFIG::$PATH."/bin/zendid/{$zendid_filename}", $output, $return_var);
				if ($return_var == 0 && $retval)
					$display["hostid"] = $retval;
			}
		}
		else 
		{
			if ($retval)
				$display["hostid"] = $retval;
		}
	}
	
	// If we cannot determine hostid, show error message
	if (!$display["hostid"])
		$display["hostid"] = _("<div style='color:red;'>Cannot determine zend host id. Please run zendid binary tool manually.</div>");
	
	if (is_array($display["hostid"]))
		$display["hostid"] = $display["hostid"][0];
		
	// Read licence file
	if (function_exists("zend_loader_file_licensed"))
	{
		$display["lic_info"] = zend_loader_file_licensed();
	}
	
	// License expire date
	if ($_SESSION["license_check_result"])
	{
		$license_check_result = unserialize($_SESSION["license_check_result"]);
		$display["expire_date"] = date("Y-m-d", $license_check_result->expire_date);
	}
	
	require_once ("src/append.inc.php");
?>
