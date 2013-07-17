{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Change contact E-mail"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
		<tr>
			<td width="20%">{t}Your current password:{/t}</td>
			<td><input name="pass" type="password" class="text"  value=""></td>
		</tr>
		<tr>
			<td width="20%">{t}New e-mail address:{/t}</td>
			<td><input name="email" type="text" class="text" size="30" value="{$info.email}"></td>
		</tr>
		{include file="client/inc/intable_footer.tpl" color="Gray"}
	{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}
