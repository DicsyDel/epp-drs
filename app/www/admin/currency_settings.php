<?php
	require_once ('src/prepend.inc.php');
	
	if (CONFIG::$DEV_DEMOMODE != 1)
	{
		if ($_POST)
		{
			$allowed_keys = array(
				'currency', 'currencyISO', 'billing_currencyISO', 'currency_rate'
			);
			
			foreach ($_POST as $k => $v)
			{
				if (in_array($k, $allowed_keys))
				{
					if ($k == 'currency_rate')
					{
						$v = (float)$v;
					}
					
					// Check for blank value
					if (!$v)
					{
						$errmsg = _("Required field missed");
						break;
					}
					
					$db->Execute("REPLACE INTO config SET `value`=?, `key`=?", array($v, $k));
				}
				
				$display[$k] = $v;
			}
			
			if (!$errmsg)
			{
	    		$okmsg = _("Settings saved");
	    		CoreUtils::Redirect("currency_settings.php");
			}			
		}
		else
		{
			$cfg = $db->GetAll("SELECT * FROM config");
			foreach($cfg as $k=>$v)
			{
		   		$display[$v["key"]] = $v["value"];
			}
		}
		
		$display['currency'] = htmlspecialchars($display['currency']);		
	}
	else
	{
		$errmsg = _("Configuration is disabled in demo mode. No data being displayed or submitted.");
	}
	
	require_once ('src/append.inc.php');
?>