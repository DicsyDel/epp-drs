<? 
	require("src/prepend.inc.php");
	
	$display["title"] = "Service log&nbsp;&raquo;&nbsp;Transaction details";
	
    if (!$get_trnid)
	   CoreUtils::Redirect("logs_view.php");
    
    $severityTitles = array(
    	E_ERROR => 'SYSTEM ERROR',
    	E_WARNING => 'SYSTEM WARNING',
    	E_PARSE => 'PARSE ERROR',
    	E_NOTICE => 'SYSTEM NOTICE',
    	E_CORE_ERROR => 'APP ERROR',
    	E_CORE_WARNING => 'APP WARNING',
    	E_COMPILE_ERROR  => 'COMPILE ERROR',
    	E_COMPILE_WARNING => 'COMPILE WARNING',
    	E_USER_ERROR => 'ERROR',
    	E_USER_WARNING => 'WARNING',
    	E_USER_NOTICE => 'NOTICE',
    	E_STRICT => 'STRICT NOTICE',
    	E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
    );
    
    $severityIcons = array(
    	E_NOTICE 		=> "images/log_icons/info.png",
    	E_USER_NOTICE  	=> "images/log_icons/info.png",
    	E_CORE_NOTICE  	=> "images/log_icons/info.png",
    	
    	E_WARNING		=> "images/log_icons/warning.png",
    	E_USER_WARNING	=> "images/log_icons/warning.png",
    	E_CORE_WARNING	=> "images/log_icons/warning.png",
    
    	E_ERROR 		=> "images/log_icons/error.png",
    	E_USER_ERROR 	=> "images/log_icons/error.png",
    	E_CORE_ERROR	=> "images/log_icons/error.png"
    );
	   
	$display["rows"] = $db->GetAll("SELECT * FROM syslog WHERE transactionid=? ORDER BY dtadded_time ASC, id ASC", array($get_trnid));
	foreach ($display["rows"] as &$row)
	{
		$row["message"] = nl2br(preg_replace("/[\n]+/", "\n", htmlentities($row["message"], ENT_QUOTES, "UTF-8")));
		$row["severity_ico"] = $severityIcons[$row["severity"]];
		$row["severity_title"] = $severityTitles[$row["severity"]];
	}
		
	require("src/append.inc.php");
?>