<? 
	require("src/prepend.inc.php");
	$display["help"] = _("This page allows you to manage DNS records that will be appended to all DNS zones upon creation.");
	
	if ($_POST) 
	{
		$db->BeginTrans();
		try
		{
			foreach ($post_records as $k=>$v)
			{
				if ($v["rkey"] != '' && $v["rvalue"] != '')
					$db->Execute("UPDATE records SET `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=? WHERE id='{$k}'", 
					array($v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"]));
				else
					$db->Execute("DELETE FROM records WHERE id='{$k}'");
			}
			
			foreach ($post_add as $k=>$v)
			{
				if ($v["rkey"] != '' && $v["rvalue"] != '')
					$db->Execute("INSERT INTO records SET zoneid='0', `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?", 
					array($v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"]));
			}
		}
		catch(Exception $e)
		{
			$db->RollbackTrans();
			throw new ApplicationException ($e->getMessage(), $e->getCode());
		}
			
		$db->CompleteTrans();
		
			
		$mess = "Default DNS records updated";
		CoreUtils::Redirect("dnsdef_manage.php");
	}
	
	$records = $db->GetAll("SELECT * FROM records WHERE zoneid='0'");
	$display["records"] = $records;	
	$display["add"] = array(1, 2, 3, 4, 5);
		
	require("src/append.inc.php"); 
?>