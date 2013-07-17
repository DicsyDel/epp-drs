<?
	@error_reporting(E_ALL ^ E_NOTICE);
	date_default_timezone_set(@date_default_timezone_get());
	
	
	define("TRANSACTION_ID", uniqid("tran"));
	define("DEFAULT_LOCALE", "en_US");	

	 
	$AUTOUP_SERVICES = array("http://autoup-1.webta.net/data"/*, "http://k2:95/data"*/);
	define("LICENSE_SERVICE_URL", "https://licserver.epp-drs.com/licserver.php");
	
	// Start session
	//session_start();
 	
	// 	Attempt to normalize settings
	@ini_set('magic_quotes_runtime', '0');
	@ini_set('magic_quotes_gpc', '0');
	@ini_set('variables_order', 'GPCS');
	@ini_set('gpc_order', 'GPC');
	
	@ini_set('session.bug_compat_42', '0');
	@ini_set('session.bug_compat_warn', '0');
	
	/* genocide the damn registered globals if they are on */
	if (ini_get('register_globals'))
	{
		$array = array('_REQUEST', '_SESSION', '_SERVER', '_ENV', '_FILES');
        foreach ($array as $value) {
        	if (is_array($GLOBALS[$value])) {
	            foreach ($GLOBALS[$value] as $key => $var) {
	                if ($var === $GLOBALS[$key]) {
	                    unset($GLOBALS[$key]);
	                }
	            }
        	}
        }
	}    

	// A kind of sanitization :-/
	if (get_magic_quotes_gpc())
	{
		function mstripslashes(&$item, $key)
		{
			$item = stripslashes($item);
		}
		
		array_walk_recursive($_POST, "mstripslashes");
		array_walk_recursive($_GET, "mstripslashes");
		array_walk_recursive($_REQUEST, "mstripslashes");
	}
	
	function mparse_str ($string, &$arr)
	{
		parse_str($string, $arr);
		
        // parse_str uses global `magic_quotes_gpc` setting. prevent data from double slashing.
		if (get_magic_quotes_gpc())
			array_walk_recursive($arr, "mstripslashes");
	}
	
	// Globalize
	@extract($_GET, EXTR_PREFIX_ALL, "get");
	@extract($_POST, EXTR_PREFIX_ALL, "post");
	@extract($_SESSION, EXTR_PREFIX_ALL, "sess");
	@extract($_REQUEST, EXTR_PREFIX_ALL, "req");
	
	// Environment stuff
	$base = dirname(__FILE__);
	
	$srcpath = $base;
	$cachepath = realpath("$base/../cache");
	$ADODB_CACHE_DIR = "$cachepath/adodb";
	$modules_path = realpath("$base/../modules");
	$logs_path = realpath("$base/../logs");
	
	define("MODULES_PATH", $modules_path);
	define("LOGS_PATH", $logs_path);
	define("SRC_PATH", $srcpath);
	define("APP_PATH", realpath("{$base}/.."));
	define("CACHE_PATH", $cachepath);
					
	// Define Smarty path
	define("CF_TEMPLATES_PATH", realpath("$base/../templates"));
	define("CF_SMARTYBIN_PATH", realpath("$base/../cache/smarty_bin"));
	define("CF_SMARTYCACHE_PATH", realpath("$base/../cache/smarty"));	
	
	////////////////////////////////////////
	// Config		                      //
	////////////////////////////////////////
	$cfg = parse_ini_file("$base/../etc/config.ini", true);
	if (!count($cfg)) { 
		die("Cannot parse config.ini file"); 
	};
	
	// For phpinfo
	if ($cfg["dev"]["debug"] == 1 && $get_pnfo)
	{
		phpinfo(); 
		die();
	}
	
	// Exceptions
	require_once("{$srcpath}/exceptions/class.ApplicationException.php");
	require_once("{$srcpath}/exceptions/class.LicensingException.php");
	require_once("{$srcpath}/exceptions/class.RegistryException.php");
	require_once("{$srcpath}/exceptions/class.APIException.php");
	require_once("{$srcpath}/exceptions/class.ErrorList.php");
	
	
	// We need it here for DisplayException()
	require_once("{$srcpath}/class.UI.php");
	// We need it here for Backtrace()
	require_once("{$srcpath}/class.Debug.php");
	
	// Static stuff
	require_once("{$srcpath}/class.FQDN.php");
	
	// All uncaught exceptions will raise ApplicationException
	function exception_handler($exception) 
	{
		UI::DisplayException($exception);
	}
	set_exception_handler("exception_handler");
	
	require_once ("$srcpath/common.inc.php");
	require("$srcpath/LibWebta/prepend.inc.php");
	Core::SetExceptionClassName('ApplicationException');	
	
	// Custom data types. Err.. enums for now.
	require_once("{$srcpath}/types/class.EnumFactory.php");
	require_once("{$srcpath}/types/enum.APPCONTEXT.php");
	require_once("{$srcpath}/types/enum.DOMAIN_STATUS.php");
	require_once("{$srcpath}/types/enum.REGISTRY_RESPONSE_STATUS.php");
	require_once("{$srcpath}/types/enum.CONTACT_TYPE.php");
	require_once("{$srcpath}/types/enum.UPDATE_STATUS.php");
	require_once("{$srcpath}/types/enum.FORM_FIELD_TYPE.php");
	require_once("{$srcpath}/types/enum.APPCONTEXT.php");
	require_once("{$srcpath}/types/enum.SECURITY_CONTEXT.php");
	require_once("{$srcpath}/types/enum.PAYMENT_STATUS.php");
	require_once("{$srcpath}/types/enum.RFC3730_RESULT_CODE.php");
	require_once("{$srcpath}/types/enum.TRANSFER_STATUS.php");
	require_once("{$srcpath}/types/enum.OUTGOING_TRANSFER_STATUS.php");
	require_once("{$srcpath}/types/enum.INVOICE_STATUS.php");
	require_once("{$srcpath}/types/enum.INVOICE_ACTION_STATUS.php");
	require_once("{$srcpath}/types/enum.INCOMPLETE_OPERATION.php");
	require_once("{$srcpath}/types/enum.DOMAIN_DELETE_STATUS.php");
	require_once("{$srcpath}/types/enum.EVENT_HANDLER_PHACE.php");
		
	// A kind of VB6 structures.
	require_once("{$srcpath}/structs/struct.CONFIG.php");
	require_once("{$srcpath}/structs/struct.CONTEXTS.php");
	require_once("{$srcpath}/structs/struct.ENABLE_EXTENSION.php");

	
	//
	// Load LibWebta and Lib classes
	//
	Core::Load("Security/Crypto");
	Core::Load("UI/Paging/class.Paging.php");
	Core::Load("UI/Paging/class.SQLPaging.php");
	Core::Load("Locale/class.Locale.php");
	
	Core::Load("NET/Mail/class.PHPMailer.php");
	Core::Load("NET/Mail/class.PHPSmartyMailer.php");
		
	Core::Load("Data/DB/adodb_lite/adodb.inc.php", "{$srcpath}/Lib");
	Core::Load("Data/DB/adodb_lite/adodb-exceptions.inc.php", "{$srcpath}/Lib");
	Core::Load("UI/Smarty/Smarty.class.php", "{$srcpath}/Lib");
	Core::Load("UI/Smarty/SmartyExt.class.php", "{$srcpath}/Lib");
	Core::Load("Data/Validation/class.Validator.php");
	Core::Load("Distribution");
			
	//
	// Load EPP-DRS classes
	//
	include_once(SRC_PATH."/autoload.php");

	require_once (SRC_PATH."/interface.IConfigurable.php");
	require_once (MODULES_PATH."/payments/interface.IPaymentModule.php");
	require_once (MODULES_PATH."/payments/interface.IDirectPaymentModule.php");
	require_once (MODULES_PATH."/payments/interface.IPostBackPaymentModule.php");
	require_once (MODULES_PATH."/payments/observers/interface.IPaymentObserver.php");
	require_once (MODULES_PATH."/registries/class.RegistryResponse.php");
	require_once (MODULES_PATH."/registries/class.PendingOperationResponse.php");		
	require_once (SRC_PATH."/observers/interface.IInvoiceObserver.php");
	require_once (SRC_PATH."/observers/interface.IGlobalObserver.php");
	require_once (MODULES_PATH."/registries/interface.IRegistryModule.php");
	require_once (MODULES_PATH."/registries/interface.IRegistryTransport.php");
	require_once (MODULES_PATH."/registries/observers/interface.IRegistryObserver.php");
	


	
	/**
	/* A code specific to zencoded release
	 * Get license flags to check against them further during loading
	*/
	if (function_exists("zend_loader_file_licensed") && zend_loader_file_encoded())
	{
		// Get license flags
		$lic_flags = @zend_loader_file_licensed();
		if (!$lic_flags)
			throw new LicensingException("Cannot retrieve license flags. Make sure that you have correct license installed.");
		else
		{
			CONTEXTS::$SECURITY_CONTEXT = SECURITY_CONTEXT::ZENDED;
			EnumFactory::CookEnumFromArray("LICENSE_FLAGS", $lic_flags);
			unset($lic_flags);
		}
	}
	else
	{
		// Include development version (all enabled).
		require_once("{$srcpath}/types/enum.LICENSE_FLAGS.php");
		CONTEXTS::$SECURITY_CONTEXT = SECURITY_CONTEXT::OPENSOURCE;
	}
	require_once("{$srcpath}/class.License.php");
	
	//			   
	// Initialize templates
	//
	if (!defined("NO_TEMPLATES"))
	{
		//
		// Initialize Smarty object
		//		
		$smarty = Core::GetSmartyInstance("SmartyExt");	

		//
		// Smarty cache
		//
		$smarty->caching = false;
		$smarty->force_compile = (CONTEXTS::$SECURITY_CONTEXT == SECURITY_CONTEXT::ZENDED);
		$smarty->compiler_class = "Smarty_CompilerExt";
		
		/* Register a new Smarty tag {eppdrs_include path="relative_path"} */
		function IncludePHPFile($params, &$smarty)
		{
			PHPParser::SafeLoadPHPFile($params['path']);
		}	
		$smarty->register_function('eppdrs_include', 'IncludePHPFile');
		
		function eppdrs_date_format($var)
		{
			if (is_numeric($var))
				$timestamp = $var;
			else
				$timestamp = strtotime($var);

			if ($timestamp)
				return date("M j, Y", $timestamp);
			else
				return "";
		}
		$smarty->register_modifier('eppdrs_date_format', 'eppdrs_date_format');
	}

	CONFIG::$PRODUCT_ID = "epp-drs";
	CONFIG::$APP_REVISION = trim(file_get_contents(APP_PATH."/etc/version"));
	CONFIG::$TRACKBACK_EMAIL = "epp-drs@webta.net";
	CONFIG::$PATH = APP_PATH;
	
	define("CF_DB_DSN", $cfg["database"]["dsn"]);
	try 
	{
		$db = &NewADOConnection($cfg["database"]["dsn"]);
	} catch (Exception $e)
	{
		throw new ApplicationException("Database connection failed. {$e->getMessage()}");
	}
	
	if ($cfg["dev"]["debug"])
	{
		@ini_set('display_errors', '1');
		@ini_set('display_startup_errors', '1'); 
	}
	if (!$cfg["database"]["cache"])
		$db->cacheSecs = 0;
			
		
	// Language support
	require(dirname(__FILE__)."/lang.php");
	define("LANGS_DIR", dirname(__FILE__)."/../lang");
	putenv("LANG=".LOCALE);
	setlocale(LC_ALL, LOCALE);
	define("TEXT_DOMAIN", "default");
	bindtextdomain (TEXT_DOMAIN, LANGS_DIR);
	textdomain(TEXT_DOMAIN);
	bind_textdomain_codeset(TEXT_DOMAIN, "UTF-8");
	$display["lang"] = LOCALE;
	
	// Set locale for time and date
	setlocale(LC_TIME, "en_US");
	$utf8_locales = array(LOCALE.".UTF-8", LOCALE.".utf8");	
	foreach ($utf8_locales as $locale)      
	{  
		if (setlocale(LC_TIME, $locale))  
			break;      
	}
	
		
	//
	// Setup logger
	//
	Core::Load("IO/Logging/Log");
	if (!Log::HasLogger("EPPDRSLogger"))
	{
        Log::RegisterLogger("DB", "EPPDRSLogger", "syslog");						
    	Log::SetAdapterOption("fieldMessage", "message", "EPPDRSLogger");
    	Log::SetAdapterOption("fieldLevel", "severity", "EPPDRSLogger");
    	Log::SetAdapterOption("fieldDatetime", "dtadded", "EPPDRSLogger");
    	Log::SetAdapterOption("fieldTrnID", "transactionid", "EPPDRSLogger");
    	Log::SetAdapterOption("fieldTimestamp", "dtadded_time", "EPPDRSLogger");
    	Log::SetLevel(error_reporting(), "EPPDRSLogger");
	}
	Log::SetDefaultLogger("EPPDRSLogger");

	
	//
	// Cook Invoice purposes
	//
	$purposes = $db->Execute("SELECT * FROM invoice_purposes");
	while ($purpose = $purposes->FetchRow())
		$tmp[$purpose['key']] = $purpose['key'];
	EnumFactory::CookEnumFromArray("INVOICE_PURPOSE", $tmp);
	unset($tmp); 
	
	foreach($db->GetAll("select * from extensions") as $ext)
		ENABLE_EXTENSION::$$ext['key'] = ($ext['enabled'] == 1) ? true : false;
	
	// Select config from db
	foreach ($db->GetAll("select * from config") as $rsk)
		$cfg[$rsk["key"]] = $rsk["value"];
		
	foreach ($cfg as $k=>$v) 
	{ 	
		if (is_array($v)) 
			foreach ($v as $kk=>$vv)
			{
				$key = strtoupper("{$k}_{$kk}");
				CONFIG::$$key = $vv;
				define("CF_{$key}", $vv);
				
			}
		else
		{
			if (is_array($k))
				$nk = strtoupper("{$k[0]}_{$k[1]}");
			else
				$nk = strtoupper("{$k}");

			CONFIG::$$nk = $v;
			define("CF_{$nk}", $v);
		}
	}
	
	unset($cfg);
			
	CONFIG::$IPNURL = CONFIG::$SITE_URL."/ipn.php";
	CONFIG::$PDTURL = CONFIG::$SITE_URL."/pdt.php";
	
	//
	// Init payment module factory
	//
	$PaymentModuleFactory = PaymentModuleFactory::GetInstance();
	$PaymentModuleFactory->SetModulesDirectory(MODULES_PATH."/payments");	
	
	try
	{
		$payment_modules = $PaymentModuleFactory->ListModules();
	}
	catch (Exception $e)
	{
		throw new ApplicationException($e->getMessage(), $e->getCode());
	}	
	
	//
	// Init Registry subsystem
	//			
	$RegistryModuleFactory = RegistryModuleFactory::GetInstance();
	$RegistryModuleFactory->SetModulesDirectory(MODULES_PATH."/registries");
	
	try
	{
		$TLDs = $RegistryModuleFactory->GetExtensionList();
	}
	catch(Exception $e)
	{
		throw new ApplicationException($e->getMessage(), $e->getCode());
	}
	
	//
	// Use JSON.php if JSON extension not installed 
	//
	if ($enable_json)
	{
		if (!function_exists("json_encode"))
		{
			Core::Load("Data/JSON/JSON.php");
			$json = new Services_JSON();
			function json_encode($text)
			{
				global $json;
				return $json->encode($text);
			}
		}
	}
		
	if (!defined("NO_TEMPLATES"))
	{
		// Assign global smarty variables
		$smarty->assign(array(
			"app_version" => "3" . '.'. CONFIG::$APP_REVISION,
			"TLDs" => $TLDs,
			"CurrencyHTML" => CONFIG::$CURRENCY,
			"Currency" => htmlspecialchars_decode(CONFIG::$CURRENCY),
			"default_country" => CONFIG::$DEFAULT_COUNTRY,
			"servicename" => CONFIG::$COMPANY_NAME,
			"payment_modules" => $payment_modules
		));
	}
			
	//
	// Initialize Mailer Object
	//
	$Mailer = Core::GetPHPSmartyMailerInstance(CONFIG::$EMAIL_DSN);
	
	$Mailer->Smarty = new SmartyExt();		
	$Mailer->Smarty->caching = false;
	$Mailer->Smarty->force_compile = false;
	
	$Mailer->Smarty->template_dir = CF_TEMPLATES_PATH;
	$Mailer->Smarty->compile_dir = CF_SMARTYBIN_PATH;
	$Mailer->Smarty->cache_dir = CF_SMARTYCACHE_PATH;
	
	$Mailer->SetSmartyTemplateDir(APP_PATH."/lang/".LOCALE."/email_templates");
	$Mailer->CharSet = "UTF-8";
	
	// Assign global vars
	$Mailer->SetTemplateVars(
						array(
								"client_area_url"	=>	CONFIG::$SITE_URL."/client/",
								"currency"			=>	html_entity_decode(CONFIG::$CURRENCY,ENT_QUOTES,"UTF-8"),
								"servicename"		=>  CONFIG::$COMPANY_NAME,
								"registrant_cp_url" =>  CONFIG::$SITE_URL."/client/",
								"registrar_cp_url"  =>  CONFIG::$SITE_URL."/admin/"
							)
					   );
					   
	function mailer_send($template_name = null, $mail_args = null, $email = null, $name = null) {
		global $Mailer;
   		try {
            $Mailer->Send($template_name, $mail_args, $email, $name);
       	} catch (Exception $e) {
			if (preg_match('/unable to read resource/', $e->getMessage()) && 
					strpos($Mailer->Smarty->template_dir, "en_US") === false) {
				$save_template_dir = $Mailer->Smarty->template_dir;
				$Mailer->SetSmartyTemplateDir(APP_PATH."/lang/".DEFAULT_LOCALE."/email_templates");
				try {
					$Mailer->Send($template_name, $mail_args, $email, $name);
				} catch (Exception $e2) {
					$Mailer->SetSmartyTemplateDir($save_template_dir);
					throw $e2;
				}
				$Mailer->SetSmartyTemplateDir($save_template_dir);
			} else {
				throw $e;	
			}
		}		
	}

	if (CONFIG::$EMAIL_ADMIN && CONFIG::$EMAIL_COPY)
	{
		$Mailer->AddCC(CONFIG::$EMAIL_ADMIN, CONFIG::$EMAIL_ADMINNAME);
	}
		
	
	$Mailer->From = CONFIG::$SUPPORT_EMAIL;
    $Mailer->FromName = CONFIG::$SUPPORT_NAME;

    $Mailer->SetLanguage("en", CONFIG::$PATH."/src/LibWebta/library/NET/Mail/language/");

	$Crypto = new Crypto(LICENSE_FLAGS::REGISTERED_TO);
	
	// Attach Invoice observers
	Invoice::AttachObserver(new MailInvoiceObserver());
	Invoice::AttachObserver(new RegistryInvoiceObserver());
	Invoice::AttachObserver(new BalanceInvoiceObserver());
	if (ENABLE_EXTENSION::$PREREGISTRATION)
	{
		require_once ("{$srcpath}/observers/class.PreregistrationInvoiceObserver.php");
		Invoice::AttachObserver(new PreregistrationInvoiceObserver());
	}
	
	//
	// Attach Global Payment observers
	//
	PaymentModuleFactory::AddModuleObserver(new DBPaymentObserver());
	
	
	//
	// Attach user observers
	//
	$observers = $db->Execute("SELECT * FROM eventhandlers WHERE enabled='1'");
	while ($observer = $observers->FetchRow())
	{
		// eval user class file
		PHPParser::LoadPHPFile(CONFIG::$PATH."/events/class.{$observer['name']}.php");
		// Check for config
		$config_items = $db->GetAll("SELECT COUNT(*) FROM eventhandlers_config WHERE handler_name=?", array($observer['name']));
		if (count($config_items) != 0)
		{
			//Config found
			
			eval("\$Config = {$observer['name']}::GetConfigurationForm();");
			if ($Config instanceof DataForm)
			{
				// Get fields values
				$fields = $Config->ListFields();
				foreach ($fields as &$field)
				{
					$val = $db->GetOne("
						SELECT `value` FROM eventhandlers_config 
						WHERE `key`=? AND handler_name=?", 
						array($field->Name, $observer['name'])
					);
					
					$field->Value = $val;
				}
			}
			else 
				$Config = null;
		}
		else
			$Config = null;
			
		switch($observer['interface'])
		{
			case "IInvoiceObserver":
				Invoice::AttachObserver(new $observer['name']($Config), $observer["phace"]);
				break;
				
			case "IRegistryObserver":
				Registry::AttachClassObserver(new $observer['name']($Config), $observer["phace"]);
				break;
				
			case "IPaymentObserver":
				PaymentModuleFactory::AddModuleObserver(new $observer['name']($Config), $observer["phace"]);
				break;
				
			case "IGlobalObserver":
				Application::AttachObserver(new $observer['name']($Config), $observer["phace"]);				
				break;
		}
	}
?>