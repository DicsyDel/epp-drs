<?php
	require("src/prepend.inc.php");

	if (CONFIG::$DEV_DEMOMODE == 1)
	{
		$errmsg = _("Log viewer is disabled in demo mode.");
		CoreUtils::Redirect("index.php");
	}
	
	if ($_POST)
	{
		if ($post_action == "report")
		{
			$display["title"] = _("Send report to developers");
			
			$display["log_entries"] = implode(",", $post_id);
			
			$template_name = "admin/send_report";
		}
		elseif ($post_action == "send")
		{
			//Get PHP Info
			ob_start();
			phpinfo();
			$phpinfo = ob_get_contents();
			ob_end_clean();
			
			// Get licence file
			/*
			TODO: Zend license information
            */
	        
			// Composite transactions log
			
			$errorType = array (
               E_ERROR          => 'SYSTEM ERROR',
               E_WARNING        => 'SYSTEM WARNING',
               E_PARSE          => 'PARSING ERROR',
               E_NOTICE         => 'SYSTEM NOTICE',
               E_CORE_ERROR     => 'APP ERROR',
               E_CORE_WARNING   => 'APP WARNING',
               E_COMPILE_ERROR  => 'COMPILE ERROR',
               E_COMPILE_WARNING =>'COMPILE WARNING',
               E_USER_ERROR     => 'ERROR',
               E_USER_WARNING   => 'WARNING',
               E_USER_NOTICE    => 'NOTICE',
               E_STRICT         => 'STRICT NOTICE',
               E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
               );
			
			$transactions = explode(",", $post_log_entries);
			$txt_trans = array();
			foreach ((array)$transactions as $transactionid)
			{
				$txt_trans[$transactionid] = "";
				$logentries = $db->GetAll("SELECT * FROM syslog WHERE transactionid=? ORDER BY dtadded_time ASC, id ASC", array($transactionid));
				foreach ($logentries as $logentry)
				{
					$txt_trans[$transactionid] .= "<br><hr><br>[{$logentry['dtadded']}][{$errorType[$logentry['severity']]}] ".nl2br(htmlspecialchars($logentry['message']));
					if ($logentry["backtrace"])
						$txt_trans[$transactionid] .= "<br>Backtrace:<br>{$logentry["backtrace"]}";
				}
			}
			
			
			// Create mailer instance
			$Mailer = new PHPMailer();
			$Mailer->From 		= CONFIG::$EMAIL_ADMIN;
    		$Mailer->FromName 	= "EPP-DRS Report";
            $Mailer->Subject 	= sprintf("EPP-DRS Report (Version: %s)", CONFIG::$APP_REVISION);
            
            // Add attachments
            $Mailer->AddStringAttachment($phpinfo, "phpinfo.html", $encoding = 'base64', $type = 'text/html');
            //TODO: $Mailer->AddStringAttachment($weakest_lic_text, "licence.txt", $encoding = 'base64', $type = 'plain/text');
            
            foreach ($txt_trans as $trnid => $text)
            	$Mailer->AddStringAttachment($text, "{$trnid}.html", $encoding = 'base64', $type = 'text/html');
			
            $Mailer->AddAddress(CONFIG::$TRACKBACK_EMAIL, "");
            
            $Mailer->Body = $post_comments;
            
            // Send mail
            if ($Mailer->Send())
            {
            	$okmsg = "Report successfully sent";
            	CoreUtils::Redirect("logs_view.php");
            }
            else 
            {
            	Log::Log("Cannot send email: {$Mailer->ErrorInfo}", E_ERROR);
            	
            	$errmsg = "Cannot send report: {$Mailer->ErrorInfo}";
            	
            	$display["log_entries"] = $post_log_entries;
            	$template_name = "admin/send_report";
            }
		}
	}
	
	if (!$template_name)
	{
		Core::Load("Data/Formater");
		
		$display["title"] = _("Logs");
		$display["help"] = _("Almost all EPP-DRS activity being logged. You should check logs in case of any issues. If you are asked to do so, you may submit a report to developers. For that, tick entries that should be included in report and select 'Send report to developers'");
		$display["load_extjs"] = true;
	}
	
	$severities = array(
		array('hideLabel' => true, 'boxLabel'=> 'System error', 'name' => 'severity[]', 'inputValue' => E_ERROR, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Error', 'name' => 'severity[]','inputValue'=> E_USER_ERROR, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Application error','name' => 'severity[]', 'inputValue'=> E_CORE_ERROR, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'System warning', 'name' => 'severity[]', 'inputValue'=> E_WARNING, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Warning', 'name' => 'severity[]', 'inputValue'=> E_USER_WARNING, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Application warning', 'name' => 'severity[]', 'inputValue'=> E_CORE_WARNING, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Notice', 'name' => 'severity[]', 'inputValue'=> E_USER_NOTICE),
		array('hideLabel' => true, 'boxLabel'=> 'Debug', 'name' => 'severity[]', 'inputValue'=> E_NOTICE)
	);
	$display["severities"] = json_encode($severities);
	
	require("src/append.inc.php");
?>
