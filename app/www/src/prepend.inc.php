<?php
	session_start();
	include (dirname(__FILE__)."/../../src/prepend.inc.php");
	// Set context
	CONTEXTS::$APPCONTEXT = APPCONTEXT::ORDERWIZARD;
	
	// Show autoup stub if we are in the proccess of updating
	if (CONFIG::$UPDATE_STATUS == UPDATE_STATUS::RUNNING)
	{
		$smarty->display("updating.tpl");
		exit();
	}
?>