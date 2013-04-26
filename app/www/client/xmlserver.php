<? 
	define("NO_TEMPLATES", true);
	require("src/prepend.inc.php"); 
	
	if ($_GET["_cmd"] == "search")
	{
		//$XMLNav = new XMLNavigation($get_search_string);
		//$XMLNav->LoadXMLFile(dirname(__FILE__)."/../../etc/client_nav.xml");
		//$XMLNav->Generate();
		
		print $XMLNav->SMenu;
	}
?>
