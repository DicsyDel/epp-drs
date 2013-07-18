<?
	session_start();
	require_once (dirname(__FILE__)."/../../../src/prepend.inc.php");
	CONTEXTS::$APPCONTEXT = APPCONTEXT::REGISTRANT_CP;
		
	// Attach notifications on registy events
	Registry::AttachClassObserver(new EmailToRegistrantObserver());
    Registry::AttachClassObserver(new OperationHistory());
    Registry::AttachClassObserver(new ManagedDNSRegistryObserver());   
    
	Core::load("Data/JSON/JSON.php");
	Core::load("UI/Paging/Paging");
	Core::load("UI/Paging/SQLPaging");
	Core::load("XMLNavigation", dirname(__FILE__));
	
	Log::Log(sprintf("Client requested: %s", $_SERVER['REQUEST_URI']), E_USER_NOTICE);
	
	define("NOW", "client/".str_replace("..","", substr(basename($_SERVER['PHP_SELF']),0, -4)));
	
	// Auth
	try
	{
		$Client = Client::Load($_SESSION['userid']);
	}
	catch(Exception $e)
	{
		
	}
	
	
	$newhash = $Crypto->Hash("{$Client->Login}:{$Client->Password}:{$_SESSION['sault']}");
	$valid = ($newhash == $_SESSION["hash"]);
	
	//var_dump($valid);
	//var_dump($_SESSION);
	
	if (!$valid && !stristr($_SERVER['PHP_SELF'], "login.php"))
	{
		$GLOBALS["mess"] = "Please login";
		$_SESSION["REQUEST_URI"] = $_SERVER['REQUEST_URI'];
		CoreUtils::Redirect("login.php");
	}
	
	if ($_SESSION['userid'] && $valid)
	{
		//
		// Load menu
		//
		require_once (dirname(__FILE__)."/navigation.inc.php");
					
		// Top menu
		$display["dmenu"] = $XMLNav->DMenu;
		
		// Index page menu
		if (NOW == "client/index")
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
			
		// tempalte folder
		$tplpath = $display['tplpath'] = 'client';
		
		// Get User settings
		$user_cfg = $db->GetAll("SELECT * FROM user_settings WHERE userid='{$_SESSION['userid']}'");
		foreach ($user_cfg as $ucfg)
			define("CF_USERS_".strtoupper($ucfg["key"]), $ucfg["value"]);
			
		// Load client balance
		try
		{
			$Balance = DBBalance::GetInstance()->LoadClientBalance($_SESSION['userid']);
			$display["balance"] = $Balance->Total;
			$display["currency"] = CONFIG::$CURRENCY;
		}
		catch (Exception $e)
		{
			Log::Log("Cannot load client balance. ".$e->getMessage(), E_USER_ERROR);
		}
	}
	
?>
