<?
	if ($_GET["lang"])
	{
	    $lang = $db->GetRow("SELECT * FROM languages WHERE name=?", array($_GET["lang"]));
	    if ($lang && $lang["isinstalled"] == 1)
	    {
	        $_SESSION["LOCALE"] = $_GET["lang"];
	        setcookie("Locale", $_SESSION["LOCALE"], time()+86400*30);
	        $locale_is_set = true;
	    }
	}

	if (!$locale_is_set)
	{
		// Select default locale from settings		
		$deflang = $db->GetRow("SELECT * FROM languages WHERE isdefault='1'");		
		if ($_COOKIE["Locale"] && !$locale_is_set) 
		{
			if ($db->GetOne("SELECT count(*) FROM languages WHERE name=?", array($_COOKIE['Locale'])) > 0 && file_exists(CONFIG::$PATH."/lang/{$_COOKIE['Locale']}"))
			{
	        	$_SESSION["LOCALE"] = $_COOKIE["Locale"];
			}
	        else
	        {
	        	setcookie("Locale", "", time()-3600);
	        	$_SESSION["LOCALE"] = DEFAULT_LOCALE;
	        	$db->Execute("DELETE FROM languages WHERE name=?", array($_COOKIE["Locale"]));
	        }
		}
	    else
	    {
	        if ($deflang)
	           $_SESSION["LOCALE"] = $deflang["name"];
	        else 
	           $_SESSION["LOCALE"] = DEFAULT_LOCALE;
	    }
	}
	
    if (!file_exists(CONFIG::$PATH."/lang/{$_SESSION["LOCALE"]}"))
    {
    	$_SESSION["LOCALE"] = DEFAULT_LOCALE;
    }
    
       
    $display["languages"] = $db->GetAll("SELECT name FROM languages WHERE isinstalled='1'");
    if (count($display["languages"]) == 0)
        $display["languages"][] = array("name" => "en_US");
    
    foreach ($display["languages"] as &$lang)
        $lang["language"] = Locale::GetLanguageNameFromLocale($lang["name"]);
        
    $display["languages_num"] = count($display["languages"]);
        
	define("LOCALE", $_SESSION["LOCALE"]);
?>