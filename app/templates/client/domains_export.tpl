{include file="admin/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Export domains"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
    	<tr>
    		<td colspan="2">
    			{t}Are you sure you want to export domains into CSV?{/t}
    		</td>
    	</tr>
        {include file="client/inc/intable_footer.tpl" color="Gray"}
        {php}
	    	// Do not edit PHP code below!
	    	$this->assign('button2_name',_("Yes, I want to export domains."));
	    {/php}
        
	{include file="client/inc/table_footer.tpl" button2=1}
{include file="client/inc/footer.tpl"}