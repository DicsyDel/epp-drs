{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="step" value="2" />
	   
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Create contact - Step 1"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
	<tr valign="top">
		<td width="20%">{t}Select domain extension:{/t}</td>
		<td>
			<select name="TLD" class="text">
			{section name=id loop=$TLDs}
			<option value="{$TLDs[id]}">{$TLDs[id]}</option>
			{/section}
			</select>	
		</td>
	</tr>
	{include file="client/inc/intable_footer.tpl" color="Gray"}
	
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}
	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}