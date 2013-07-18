<?
	$enable_json = true;
	include(dirname(__FILE__)."/../src/prepend.inc.php");
	
	$attr = array_merge($_GET, $_POST);

	if ($attr["JS_SESSIONID"] != $_SESSION["JS_SESSIONID"] && !$_SERVER['HTTP_X_FLASH_VERSION'])
		$result = false;
	else 
	{
		$chunks = explode(".", $attr["name"]);
	    $domainname = array_shift($chunks);
	    $stld = implode(".", $chunks);
	    
	    $TLD = $attr["TLD"];
	    
	    $whoisservers = @file(dirname(__FILE__)."/../../etc/whoisservers.txt");
	    
	    foreach ($whoisservers as $whoisserver)
	    {
	    	$chunks = explode("|", trim($whoisserver));	
	    	
	    	if ($chunks[0] == $TLD)
	    	{
	    		if ($chunks[2] == "HTTP")
	    		{
	    			$text_html = true;
	    			$url = str_replace("%domainname%", urlencode($domainname), $chunks[1]);
	    			$url = str_replace("%TLD%", $TLD, $url);
	    			
	    			$result = @file_get_contents($url);
	    			if (!$result)
	    				$result = _("Cannot retrieve whois information for this domain.");
	    		}
	    		else 
	    		{
	    			// Whois protocol
	    			$text_plain = true;
	    			$result = "";
	    			$sock = @fsockopen($chunks[1], 43, $errno, $errstr, 5);
	    			if (!$sock)
	    				$result = _("Failed to connect to whois server.");
	    			else 
	    			{
	    				$domain_name = Punycode::Encode("{$domainname}.{$TLD}");
	    				@fwrite($sock, "{$domain_name}\n");
	    				while (!feof($sock))
	    				{
	    					$result .= trim(@fread($sock, 1024));
	    				}
	    			}
	    		}
	    		
	    		break;
	    	}
	    }
	}
    
    if (!$result)
    {
    	$response["status"] = false;
    	$response["data"] = _("Cannot retrieve whois information for this domain.");
    }
    else 
    {
    	$response["status"] = true;
    	$response["data"] = $text_html ? $result : nl2br(htmlspecialchars($result));
    }

	echo json_encode($response);
?>
