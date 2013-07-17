{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="step" value="{$stepno}" />
	    
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
		{if $num_tlds > 0}
        	<tr valign="top">
        		<td width="20%">{t}Select domain extension:{/t}</td>
        		<td>
        			<select name="TLD" class="text">
        			{section name=id loop=$tlds}
        			<option value="{$tlds[id]}">{$tlds[id]}</option>
        			{/section}
        			</select>	
        		</td>
        	</tr>
        {else}
            <tr>
                <td colspan="2">
                	{t}Bulk transfer not available.{/t}
                </td>
            </tr>
        {/if}
		{include file="client/inc/intable_footer.tpl" color="Gray"}
		
    {if $num_tlds > 0}
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('button_name',_("Next step"));
	    {/php}
		{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name color="Gray"}
	{else}
	   {include file="client/inc/table_footer.tpl" color="Gray"}
	{/if}
{include file="client/inc/footer.tpl"}