<?
	require_once (dirname(__FILE__) . '/../src/prepend.inc.php');
	define('NO_TEMPLATES', true);

	// shift script name
	array_shift($argv);
	do {
		$name = array_shift($argv);
		$value = array_shift($argv);
		switch ($name)
		{
			case '-m':
			case '--module':
				$module_name = $value;
				break;
				
			case '-t':
			case '--tld':
				$tlds = array_map('trim', explode(',', $value));
				break;
		}
	} while ($argv);

	if (!($module_name && $tlds))
	{
		print <<<T
Usage: php -q add-tlds.php --module [module name] --tld "tld1[, tld2, ... tldn]"
  -m
  --module 		Module name (ex: dotkz)
  -t
  --tld			TLDs comma separated (ex: "com, com.us")

T;
		die();		
	}
	
	$modLoaded = $db->GetRow("SELECT * FROM modules WHERE name = ?", array($module_name));
	if ($modLoaded) {
		foreach ($tlds as $tld) {
			$row = $db->GetRow("SELECT * FROM tlds WHERE tld = '{$tld}'");
			if (!$row) {
				$db->Execute("INSERT INTO tlds SET TLD = '{$tld}', isactive='0', modulename='{$module_name}'");
				print "Added {$tld}\n";
			}
		}	
	}

?>