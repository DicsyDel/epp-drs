<?
	$enable_json = true;
	include(dirname(__FILE__)."/../src/prepend.inc.php");
	
	$attr = array_merge($_GET, $_POST);

	if ($attr["JS_SESSIONID"] == $_SESSION["JS_SESSIONID"])
	{
		switch($attr['action'])
		{
			case "recover_pwd":
				
				try
				{
					$Client = Client::LoadByEmail($attr['email']);
				}
				catch(Exception $e)
				{
					print "INVALID_EMAIL";
					exit();
				}
				
				if ($Client)
				{
					$code = base64_encode($Crypto->Hash("{$Client->ID}::{$Client->Password}::{$Client->Email}"));
			
					$link = CONFIG::$SITE_URL."/client/login.php?id={$Client->ID}&code={$code}&action=sendpwd";
						
					$args = array( "link" => $link, "client" => $db->GetRow('SELECT * FROM users WHERE id=?', array($Client->ID)));
					mailer_send("password_change_confirm.eml", $args, $Client->Email, $Client->Name);
					
					print "OK";
					exit();
				}
				
				break;
		}
	}
	
	print "ERROR";
	exit();
?>