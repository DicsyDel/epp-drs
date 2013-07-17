<?
	require_once('src/prepend.inc.php');
	
	$contactinfo = $db->GetRow("SELECT * FROM contacts WHERE id=?", array($req_id));
	if (!$contactinfo)
		CoreUtils::Redirect("contacts_view.php");

	if ($_POST)
	{
		if ($post_newuserid)
		{
			$db->Execute("UPDATE contacts SET userid=? WHERE id=?", array($post_newuserid, $post_id));
			$okmsg = _("Contact owner successfully changed");
			CoreUtils::Redirect("contacts_view.php");
		}
	}
		
	$display["userinfo"] = $db->GetRow("SELECT id, login, email FROM users WHERE id=?", array($contactinfo['userid']));
	$display["clients"] = $db->GetAll("SELECT id, login, email FROM users WHERE id!=? ORDER BY login ASC", array($contactinfo['userid']));
	if (count($display["clients"]) == 0)
	{
		$errmsg = _("You must have at least 2 clients for changing contacts owner");
		CoreUtils::Redirect("contacts_view.php");
	}
	
	$display["id"] = $req_id;
	
	$display["title"] = _("Contact &nbsp;&raquo;&nbsp; change owner");
	
	require_once ("src/append.inc.php");
?>