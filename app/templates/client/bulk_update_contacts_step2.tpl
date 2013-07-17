

{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}

	    <script type="text/javascript" src="/js/Checklist.js"></script>
	    <link rel="stylesheet" type="text/css" href="/css/checklist.css">
	    <input type="hidden" name="stepno" value="{$stepno}" />
	    
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Bulk contacts update - Step 2 (Select Domains)"));
	    {/php}	    
	    
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
        	<tr valign="top">
        		<td colspan="2">

				{if $checklist.items|@count > 0}
					{include file="inc/checklist.tpl"}
				{else}
					{t}No active domains for selected extension{/t}
				{/if}
        		
				

        		</td>
        	</tr>

		{include file="client/inc/intable_footer.tpl" color="Gray"}
		
	{if $checklist.items|@count > 0}
	
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('button_name',_("Next step"));
	    {/php}
	
	   {include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name color="Gray"}
	{else}
	   {include file="client/inc/table_footer.tpl" color="Gray"}
	{/if}		
		
{include file="client/inc/footer.tpl"}