{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
		
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Domain options and permissions"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
    		{section name=id loop=$flags}
    		<tr>
    			<td width="600">
    			     <input {$dsb} type="checkbox" {if $flags[id].isset}checked{/if} name="flags[{$flags[id].sysname}]" value="1"> {$flags[id].name}
    			     <br>
    			     <i style="font-size:11px;padding:2px;margin-left:25px;">{$flags[id].description}</i>
    			</td>
    		</tr>
    		{/section}
	    {include file="client/inc/intable_footer.tpl" color="Gray"}
	{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}