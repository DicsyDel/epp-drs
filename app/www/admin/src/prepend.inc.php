<?
	session_start();
	
	require_once (dirname(__FILE__)."/../../../src/prepend.inc.php");
	CONTEXTS::$APPCONTEXT = APPCONTEXT::REGISTRAR_CP;
	
	// Attach notifications on registy events
	Registry::AttachClassObserver(new EmailToRegistrantObserver());
    Registry::AttachClassObserver(new OperationHistory());
    Registry::AttachClassObserver(new ManagedDNSRegistryObserver());
	
	Core::load("Data/JSON/JSON.php");
	Core::load("UI/Paging/Paging");
	Core::load("UI/Paging/SQLPaging");
	Core::load("XMLNavigation", dirname(__FILE__));
	
	define("NOW", "admin/".str_replace("..","", substr(basename($_SERVER['PHP_SELF']),0, -4)));
	
	if (CONTEXTS::$SECURITY_CONTEXT == SECURITY_CONTEXT::ZENDED)
	{
		// Include licensing API
		foreach (glob(dirname(__FILE__) . "/../../../src/licserver-common/*.php") as $filename)
			require_once($filename);
	}	
	
	// Auth
	$newhash = $Crypto->Hash(CONFIG::$LOGIN.":".CONFIG::$PASS.":".$_SESSION["sault"]);
	$valid = ($newhash == $_SESSION["admin_hash"] && !empty($_SESSION["admin_hash"]));
	
	
	if (!$valid && !stristr($_SERVER['PHP_SELF'], "login.php"))
	{
		$mess = "Please login";
		$_SESSION["REQUEST_URI"] = $_SERVER['REQUEST_URI'];
		CoreUtils::Redirect("login.php");
		exit();
	}
	elseif ($valid)
	{
		//
		// Load menu
		//
		require_once (dirname(__FILE__)."/navigation.inc.php");
		
		// Top menu
		$display["dmenu"] = $XMLNav->DMenu;
		
		// Index page menu
		if (NOW == "admin/index")
			if (!$get_searchpage)
				$display["index_menu"] = $XMLNav->IMenu;
			else
			{
				$display["index_menu"] = $XMLNav->SMenu;
				$display["title"] = sprintf(_("Search results for '%s'"), $get_searchpage);
			}
		
		
		if ($get_search && $post_filter_q != $get_search && !$post_filter_q)
		{
			$_POST["filter_q"] = $post_filter_q = rawurldecode($get_search);
			$_POST["Submit"] = $post_Submit = "Filter";
			$_POST["act"] = $post_act = "filter1";
			unset($_SESSION['filter']);
		}
		
		// template subfolder
		$tplpath = $display['tplpath'] = 'admin';
		
		if (CONFIG::$UPDATE_STATUS == UPDATE_STATUS::AVAILABLE || CONFIG::$UPDATE_STATUS == UPDATE_STATUS::AVAILABLE_AND_EMAIL_SENT)
		{
			$display["warn"] = "New updates available. <a href='updates.php'>Click here to see details and approve update.</a>";
		}
		elseif(CONFIG::$UPDATE_STATUS == UPDATE_STATUS::SCHEDULED)
		{
			$display["warn"] = "Autoupdate scheduled. It will be performed with next auto-update cronjob run";
		}
		
		if (CONTEXTS::$SECURITY_CONTEXT == SECURITY_CONTEXT::ZENDED && $_SESSION["license_check_result"])
		{
			$chk_result = unserialize($_SESSION["license_check_result"]);
			if (!$chk_result->valid)
			{
				$display['license_err'] = "Your license is invalid. {$chk_result->message}";
			}
			else if ($chk_result->expire_date - time() < 14*24*60*60)  
			{ 
				// 14 days
				$days = floor(($chk_result->expire_date - time()) / (24*60*60)); 
				$display['license_info'] = "Your license will expire in $days day(s).<br/>
					EPP-DRS will NOT stop working after that day, but you will no more receive auto-updates.<br/>
					Please <a href='http://epp-drs.com/pay-product/5'>follow the link</a> to renew your license";
			}
			
		}
	}
?>