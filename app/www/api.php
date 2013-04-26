<?php
	define("NO_TEMPLATES", true);
	require_once (dirname(__FILE__) . "/../src/prepend.inc.php");
	unset($_REQUEST[session_name()]);
	
	try
	{
		$Server = new EppDrs_Api_RestServer($_REQUEST["version"] ? $_REQUEST["version"] : "20090622");
		$Server->Handle($_REQUEST);
	}
	catch (Exception $e)
	{
		header("HTTP/1.1 500 Internal Server Error");
		print $e->getMessage();
	}
?>