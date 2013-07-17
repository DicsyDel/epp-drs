<? 
	require_once('src/prepend.inc.php');
	$display["title"] = "Login";

	if (isset($get_logout))
	{
		$okmsg = "Succesfully logged out";
		session_destroy();
		CoreUtils::Redirect("login.php");
	}
	
	if ($_POST)
	{
		if ($post_login == CONFIG::$LOGIN && $Crypto->Hash($post_pass) == CONFIG::$PASS)
		{
			$sault = $Crypto->Sault();
			$_SESSION["sault"] = "$sault";
			$_SESSION["uid"] = "";
			$_SESSION["admin_hash"] = $Crypto->Hash($post_login.":".$Crypto->Hash($post_pass).":$sault");
			
			// License check
			
			if (CONTEXTS::$SECURITY_CONTEXT == SECURITY_CONTEXT::ZENDED)
			{
				// Detect license version
				$lic_info = zend_loader_file_licensed();
				$license_uuid = $lic_info[ZendLicense::X_LICENSE_ID];
				if ($license_uuid) // New type. Check license
				{
					try
					{
						Log::Log("Check license", E_USER_NOTICE);
						$license_service = new RestClient(LICENSE_SERVICE_URL);
						$license_service->SetInterface("PublicLicenseService");						
						$chk_result = $license_service->CheckLicense($license_uuid);
						$_SESSION["license_check_result"] = serialize($chk_result); 						
					}
					catch (Exception $e)
					{
						Log::Log("License check failed. {$e->getMessage()}", E_USER_NOTICE);
					}
				}
			}			
			
			
			if (!$_SESSION["REQUEST_URI"])
				CoreUtils::Redirect ("/admin/index.php");
			else 
				CoreUtils::Redirect($_SESSION["REQUEST_URI"]);
		}
		else
		{
			$errmsg = _("Invalid login or password");
			CoreUtils::Redirect("login.php");
		}
	}

	require_once('src/append.inc.php');
?>
