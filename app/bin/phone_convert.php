<?
	$users = $db->Execute("SELECT * FROM users");
	while ($user = $users->FetchRow())
	{
		$nfield = preg_replace("/[^0-9]+/", "", $user["phone"]);
		$chunks = @str_split($nfield, 3);
		$phone = @array_shift($chunks)."-".@array_shift($chunks)."-".@implode("", $chunks);
		
		$nfield = preg_replace("/[^0-9]+/", "", $user["fax"]);
		$chunks = @str_split($nfield, 3);
		$fax = @array_shift($chunks)."-".@array_shift($chunks)."-".@implode("", $chunks);
		
		$db->Execute("UPDATE users SET phone=?, fax=? WHERE id=?", array(trim($phone, "-"), trim($fax, "-"), $user["id"]));
	}
	
	$users = $db->Execute("SELECT * FROM contacts_data WHERE field='voice' OR field='fax'");
	while ($user = $users->FetchRow())
	{
		$nfield = preg_replace("/[^0-9]+/", "", $user["value"]);
		$chunks = @str_split($nfield, 3);
		$value = @array_shift($chunks)."-".@array_shift($chunks)."-".@implode("", $chunks);

		$db->Execute("UPDATE contacts_data SET value=? WHERE id=?", array(trim($value, "-"), $user["id"]));
	}
?>