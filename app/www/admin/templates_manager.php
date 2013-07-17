<? 
	require_once('src/prepend.inc.php');
	$display["help"] = "This page allows you to edit all templates. EPP-DRS uses <a target='_blank' href='http://www.smarty.net/'>Smarty Template Engine</a>, thus templates must conform <a href='http://www.smarty.net/manual/en/smarty.for.designers.php' target='_blank'>Smarty syntax</a>
	<br><span style='color: red;'>Always save backups of original templates before you edit anything! Be extremely carefull, do not brake or remove javascript code!</span>
	<br> Note: email templates are not available in this interface and should be edited manually.";
	
	if ($get_action == "delete")
	{
	    if (CONFIG::$DEV_DEMOMODE != 1)
		{
			$req_dirName = str_replace(".", "", $req_dirName);
		    $req_templateName = str_replace("..", "", $req_templateName);
		    
		    @unlink("{$smarty->template_dir}/{$req_dirName}/{$req_templateName}");
		    $okmsg = "Template successfully deleted";
		    $req_dir = $req_dirName;
		}
	}
	
	if ($_POST)
	{
		if (CONFIG::$DEV_DEMOMODE != 1)
		{
			$i = 0;
			foreach ($post_body as $key=>$val)
			{
				$handle = @fopen ("{$smarty->template_dir}/{$req_dir}/{$key}", "w");
				if ($handle)
				{
	                @fwrite($handle, stripslashes($val));
				    @fclose ($handle);
				}
				else 
	                $err[] = "Cannot modify this template. Please set permissions to 0777 (world-writable).";
			}
			
			if (!$err)
			{
	            $okmsg = "Template file saved";
	            CoreUtils::Redirect("templ_view.php");	
			}
		}
	}
	
	$req_dir = str_replace(".", "", $req_dir);
	$req_cd = str_replace(".", "", $req_cd);
	$req_explode = str_replace("..", "", $req_explode);
	
	if ($req_dir == "up")
	{
	    $chunks = explode("/", $req_cd);
	    array_pop($chunks);
	    $req_dir = @implode("/", $chunks);
	}
	
	if ($req_dir != '')
		$display["folders"][] = array("name" => "up", "curdir" => $req_dir);
	
	if ($req_explode)
	{
		$display["dir"] = $req_dir;
		$display["file"] = $req_explode;
	}
	
	if ($handle = @opendir("{$smarty->template_dir}/{$req_dir}")) 
	{
		while (false !== ($file = @readdir($handle))) 
		{
			if ($file != "." && $file != ".." && $file != ".svn") 
			{
				if (is_file("{$smarty->template_dir}/{$req_dir}/{$file}"))
				{
					$image = (stristr($file, ".eml")) ? "mail_tpl" : "layout_tpl";
					$type = (stristr($file, ".eml")) ? "E-mail" : "Layout";
					
					$display["files"][] = array("name" => $file, "image"=>$image, "type"=>$type);
					if ($req_explode == $file)
						$display["content"] = @file_get_contents("{$smarty->template_dir}/{$req_dir}/{$file}");
				}
				else
					$display["folders"][] = array("name" => trim("{$req_dir}/{$file}", "/"));
			}
		}
		
		@closedir($handle);
	}
	else
		$err[] = "Cannot access templates directory. Please check folder permissions.";
	
	if (CONFIG::$DEV_DEMOMODE == 1)
	{
		$errmsg = _("Templates manager is disabled in demo mode. No data being submitted.");
	}
	
	$display["dir"] = $req_dir;
	
	require_once ("src/append.inc.php");
?>
          