<?
	require_once('src/prepend.inc.php');
	
	if ($_POST)
	{	
	    $_POST[ClientSettings::SHOW_INLINE_HELP] = (int)(bool)$_POST[ClientSettings::SHOW_INLINE_HELP];
	    $_POST[ClientSettings::AUTO_PAY_FROM_BALANCE] = (int)(bool)$_POST[ClientSettings::AUTO_PAY_FROM_BALANCE];
	    $_POST[ClientSettings::AUTO_PAY_NO_RENEW] = (int)(bool)$_POST[ClientSettings::AUTO_PAY_NO_RENEW];
	    $_POST[ClientSettings::LOW_BALANCE_NOTIFY] = (int)(bool)$_POST[ClientSettings::LOW_BALANCE_NOTIFY];
	    $_POST[ClientSettings::PREFILL_CONTACT] = (int)(bool)$_POST[ClientSettings::PREFILL_CONTACT];
	    	    
	    if (count($err) == 0)
	    {
    	    foreach($_POST as $k=>$v)
    	    {
    	        if ($k != 'pass')
    	            $db->Execute("REPLACE INTO user_settings SET value=?, `key`=?, userid=?", array($v, $k, $_SESSION['userid']));
    	    }
    
    		$okmsg = _("Settings saved");
    		CoreUtils::Redirect("settings_core.php");
	    }
	}
	
	$cfg = $db->GetAll("SELECT * FROM user_settings WHERE userid=?", array($_SESSION['userid']));
	
	foreach($cfg as $k=>$v)
	   $display[$v["key"]] = $v["value"];   
		
	require_once ("src/append.inc.php");
?>
