{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Add funds to balance"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
        <tr>
        	<td colspan="2"></td>
        </tr>
        <tr valign="top">
        	<td>{t}Amount{/t}:</td>
        	<td>
        		<span>{$Currency}</span>
        		<input name="amount" size="10" maxlength="10" value="{$attr.amount}"> 
        	</td>
        </tr>
        
        {include file="client/inc/intable_footer.tpl" color="Gray"}
	{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}