<?
	require_once('src/prepend.inc.php');

	if (isset($req_domainid))
		require_once("src/set_managed_domain.php");
	else
		require_once("src/get_managed_domain_object.php");

	if ($Domain->Status != DOMAIN_STATUS::DELEGATED)
	{
		$errmsg = _("Domain status prohibits operation");
		CoreUtils::Redirect("domains_view.php");
	}

	    
	if (!$errmsg)
	{	
	    $whoisservers = @file(dirname(__FILE__)."/../../etc/whoisservers.txt");
	    
	    foreach ($whoisservers as $whoisserver)
	    {
	    	$chunks = explode("|", trim($whoisserver));	
	    	
	    	if ($chunks[0] == $Domain->Extension)
	    	{
	    		if ($chunks[2] == "HTTP")
	    		{
	    			$text_html = true;	    			
	    			$url = str_replace("%domainname%", urlencode($Domain->Name), $chunks[1]);
	    			$url = str_replace("%TLD%", $Domain->Extension, $url);
	
	    			$result = @file_get_contents($url);
	    			if (!$result)
	    				$result = "Cannot retrieve whois information for this domain now.";
	    		}
	    		else 
	    		{
	    			// Whois protocol
	    			$text_plain = true;	    			
	    			$result = "";
	    			$sock = @fsockopen($chunks[1], 43, $errno, $errstr, 5);
	    			if (!$sock)
	    				$result = "Whois connection error: {$errstr} ({$errno})";
	    			else 
	    			{
	    				$domain = Punycode::Encode($Domain->GetHostName());
	    				@fwrite($sock, "{$domain}\n");
	    				while (!@feof($sock))
	    					$result .= trim(@fread($sock, 1024));
	    			}
	    		}
	    		
	    		break;
	    	}
	    }
	    
    	$display["whois"] = $text_html ? $result : nl2br(htmlspecialchars($result)); 
	}
	else
		CoreUtils::Redirect("domains_view.php");

	require_once ("src/append.inc.php");
?>
