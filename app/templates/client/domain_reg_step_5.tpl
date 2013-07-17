{include file="client/inc/header.tpl"}
    {include file="client/inc/table_header.tpl"}
        <input type="hidden" name="step" value="6" />
        
        {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Checkout"));
	    {/php}
        
        {include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
		{section name=id loop=$modules}
		<tr>
			<td colspan="2"><input name="gate" type="radio" {if $invoice.gate == $modules[id]}checked{/if} value="{$modules[id]}">
			<img src="/images/modules/{$modules[id]}_icon.gif" alt="{$modules[id]}" title="{$modules[id]}" align="absmiddle">
			</td>
		</tr>
		{/section}
        {include file="client/inc/intable_footer.tpl" color="Gray"}
	
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}
	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}