<?php

print "Starting xml2c\n";

// extensions of smarty files, used when going through a directory
$extensions = array('xml');

// "fix" string - strip slashes, escape and convert new lines to \n
function fs($str)
{
	$str = stripslashes($str);
	$str = str_replace('"', '\"', $str);
	$str = str_replace("\r\n", '\n', $str);
	$str = str_replace("\n", '\n', $str);
	return $str;
}

// rips gettext strings from $file and prints them in C format
function do_file($file)
{
	$content = @file_get_contents($file);

	if (empty($content)) {
		return;
	}

	preg_match_all("@(title=\"(.*?)\")|(>(.*?)<\/item>)@", $content, $matches);
	
	for ($i=0; $i < count($matches[0]); $i++) 
	{
		$string = ($matches[2][$i]) ? $matches[2][$i] : $matches[4][$i]; 		
		if ($string != '')
		{
			echo "/* $file */\n"; //
			echo 'gettext("'.fs($string).'");'."\n";
		}

		echo "\n";
	}
}

// go through a directory
function do_dir($dir)
{
	$d = dir($dir);

	while (false !== ($entry = $d->read())) {
		if ($entry == '.' || $entry == '..') {
			continue;
		}

		$entry = $dir.'/'.$entry;

		if (is_dir($entry)) { // if a directory, go through it
			do_dir($entry);
		} else { // if file, parse only if extension is matched
			$pi = pathinfo($entry);
			
			if (isset($pi['extension']) && in_array($pi['extension'], $GLOBALS['extensions'])) {
				do_file($entry);
			}
		}
	}

	$d->close();
}

for ($ac=1; $ac < $_SERVER['argc']; $ac++) {
	if (is_dir($_SERVER['argv'][$ac])) { // go through directory
		do_dir($_SERVER['argv'][$ac]);
	} else { // do file
		do_file($_SERVER['argv'][$ac]);
	}
}

?>
