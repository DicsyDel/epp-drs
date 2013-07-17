<?php

print "Starting manifest2c\n";

$xpaths = array(
	"descendant::contact_groups/group/@title",
	"descendant::field/@description",
	"descendant::field/values/value/@name"
);

function fs($str)
{
	$str = stripslashes($str);
	$str = str_replace('"', '\"', $str);
	$str = str_replace("\r\n", '\n', $str);
	$str = str_replace("\n", '\n', $str);
	return $str;
}

function do_file ($file) {
	global $xpaths;
	
	try {
		$content = file_get_contents($file);
		if (!$content) return;
		
		$xml = new SimpleXMLElement($content);
		if (!$xml) return;
		
		foreach ($xpaths as $xpath) {
			$nodes = $xml->xpath($xpath);
			foreach ($nodes as $node) {
				$fs = fs($node);
				if ($fs) {
					print 'gettext("'.fs($node).'");'."\n";
				}
			}
		}
		
	} catch (Exception $e) {
		return;
	}
	
}

for ($an=1; $arg=$_SERVER["argv"][$an]; $an++) {
	do_file($arg);
}
