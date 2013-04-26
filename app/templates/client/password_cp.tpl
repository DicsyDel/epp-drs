{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Password information"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
		<tr>
			<td width="20%">{t}Old password:{/t}</td>
			<td><input name="o_pass" type="password" class="text"  value=""></td>
		</tr>
		<tr>
			<td width="20%">{t}New password:{/t}</td>
			<td><input name="n_pass" type="password" class="text"  value=""></td>
		</tr>
		<tr>
			<td width="20%">{t}Confirm new password:{/t}</td>
			<td><input name="n_pass_c" type="password" class="text"  value=""></td>
		</tr>
		{include file="client/inc/intable_footer.tpl" color="Gray"}
	{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}