<?php
	include("../src/prepend.inc.php");
	
	// Must be initiated by handler
	$response;
	
	switch ($req_action)
	{
		case 'register':
		case 'transfer':
			try
			{
				list($name, $tld) = explode(".", $req_name, 2);
				$Factory = RegistryModuleFactory::GetInstance();
				$Registry = $Factory->GetRegistryByExtension($tld);
				
				$Domain = $Registry->NewDomainInstance();
				$Domain->Name = $name;
				
				$ok = $req_action == 'register' ?
					$Registry->DomainCanBeRegistered($Domain)->Result :
					$Registry->DomainCanBeTransferred($Domain);
				$response = array(
					'status' => (int)$ok
				);
			}
			catch (Exception $e)
			{
				$response = array(
					'status' => 2,
					'message' => $e->getMessage()
				);
			}
			break;
	}
	
	
	require_once SRC_PATH . "/LibWebta/library/Data/JSON/JSON.php";
	$json = new Services_JSON();
	print $json->encode($response);
	
	
	function notFound404 () 
	{
		header("HTTP/1.1 404 Not Found");
		die();
	}
	
	
?>