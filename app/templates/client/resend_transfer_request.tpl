{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Transfer information"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
	{if $tfields}
		{foreach from=$tfields key=key item=field}
		<tr>
			<td width="20%">{$key}:</td>
			<td><input type="text" class="text" name="{$field.name}" value=""/> {if $field.required}*{/if}</td>
		</tr>
		{/foreach}
	{/if}
	{include file="client/inc/intable_footer.tpl" color="Gray"}
	{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}