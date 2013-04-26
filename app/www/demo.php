<?
	session_start();
	
	$_SESSION["customerDSN"] = false;
	
    require("../src/prepend.inc.php");    
	
    $hash = preg_replace("/[^A-Za-z0-9]+/", "", $_SERVER['QUERY_STRING']);

    $org = $db->GetRow("SELECT * FROM companies WHERE hash='{$hash}'");
    if ($org)
        $_SESSION["customerDSN"] = "mysql://{$org['dbuser']}:{$org['dbpass']}@localhost/{$org['dbname']}";
    
    if ($_SESSION["customerDSN"])
	{
		$display["org"] = $org["org"];
		$smarty->assign($display);
		$smarty->display("demo.tpl");	  
	}
	else 
	{
        header("HTTP/1.0 404 Not Found");
        exit(); 
	}
?>