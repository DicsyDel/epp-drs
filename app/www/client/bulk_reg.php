<?php
	require_once('src/prepend.inc.php');

	$Controller = new DomainRegistrationController(array_merge($_GET, $_POST));
	$Controller->Run();

	include_once("src/append.inc.php");
?>