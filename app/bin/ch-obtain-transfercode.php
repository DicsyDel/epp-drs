<?php

require_once (dirname(__FILE__) . '/../src/prepend.inc.php');
define('NO_TEMPLATES', true);
error_reporting(E_ALL ^ E_NOTICE);

$filename = $_SERVER['argv'][1];
if (!$filename) {
	print <<<USAGE
Usage: ch-obtain-tarnsfercode.php OUTFILE


USAGE;
	die();
}

print "Obtaining transfer codes...\n";

$Registry = $RegistryModuleFactory->GetRegistryByName('EPPCH');
$Module = $Registry->GetModule();

$fp = fopen($filename, 'w+');
while ($Resp = $Module->ReadMessage()) {
	try {
		$resData = $Resp->RawResponse->response->resData;
		if (!$resData) {
			$resData = $Resp->RawResponse->response->children('urn:ietf:params:xml:ns:epp-1.0');
			preg_match('/\<epp\:resData[\s\S]+(?<=\<\/epp:resData\>)/', $Resp->RawResponse->asXML(), $matches);
			$resData = simplexml_load_string($matches[0]);
		}
		
		$infData = $resData->children('urn:ietf:params:xml:ns:domain-1.0');
		if ($infData) {
			$line = sprintf("%s\t%s\n", $infData[0]->name, $infData[0]->authInfo->pw);
			print $line;
			fwrite($fp, $line);
		}
	
	} catch (Exception $e) {
		print "Caught: {$e->getMessage}\n";
	}
	$Module->AcknowledgeMessage($Resp);
}

print "Done\n";