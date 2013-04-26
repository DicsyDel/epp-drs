<? 
	define("NO_TEMPLATES", true);
	require("src/prepend.inc.php"); 
	
	if ($_GET["_cmd"] == "search")
	{
		print $XMLNav->SMenu;
	}
?>
