<?
    require("../src/prepend.inc.php");

    switch($req__cmd)
    {
        case "get_template_content":
            
            $dir = str_replace(".", "", $req_tdir);
        	$file = str_replace("..", "", $req_tname);
            
        	$content = @file_get_contents("{$smarty->template_dir}/{$dir}/{$file}");
        	if ($content !== FALSE)
        	   print $content;
        	else 
        	   print 'false';
        	
        break;
        
        case "set_template_content":
            if (CONFIG::$DEV_DEMOMODE != 1)
			{
	            $dir = str_replace(".", "", $req_tdir);
	        	$file = str_replace("..", "", $req_tname);
	        	
	        	$path = "{$smarty->template_dir}/{$dir}/{$file}";
	            
	        	$res = @file_put_contents($path, stripslashes(rawurldecode($req_content)));
	        	
	        	print $res;
			}
			else 
				print 0;
        	
        break;
    }
?>