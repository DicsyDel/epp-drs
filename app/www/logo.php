<?php
	require_once 'src/prepend.inc.php';
	if (file_exists(CACHE_PATH."/logo.gif"))
	{
		header("Content-type: image/gif");
		readfile(CACHE_PATH."/logo.gif");
	}
?>