{include file="admin/inc/header.tpl"}

{if $show_notice}
    <br>
    <table width="100%" cellpadding="10" cellspacing="1" bgcolor="#E5E5E5">
    	<tr>
    		<td bgcolor="#FFFFFF">
                <div style="color:red;font-weight:bold;">Attention! You have domains and contacts already in database assigned to the current registrar ({$curr_login}).<br>Changing registrar will not update all domains and contacts from registry. You will end up with a messed system if you not sure what you are doing.</div>
            </td>
        </tr>
    </table>
{/if}
    <br>
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="$module Settings" color="Gray"}
        	{section name=id loop=$rows}
        		{if $rows[id].type == 'text'}
        			<tr>
        				<td width="18%">{$rows[id].title}:</td>
        				<td width="82%"><input name="{$rows[id].key}" type="text" class="text" id="{$rows[id].key}" value="{$rows[id].value}" size="30">
        				{if $rows[id].hint}&nbsp;<span class="Webta_Ihelp">{$rows[id].hint}</span>{/if}</td>
        			</tr>
        		{elseif $rows[id].type == 'textarea'}
        			<tr>
        				<td width="18%" valign="top">{$rows[id].title}:</td>
        				<td width="82%" valign="top"><textarea rows="9" cols="40" name="{$rows[id].key}" class="text" style="float:left;display:block !important;">{$rows[id].value}</textarea>
        				{if $rows[id].hint}&nbsp;<span class="Webta_Ihelp" style="display:block; float:left; margin-left:5px">{$rows[id].hint}</span>{/if}</td>
        			</tr>        			
        		{elseif $rows[id].type == 'password'}
        			<tr>
        				<td width="18%">{$rows[id].title}:</td>
        				<td width="82%"><input name="{$rows[id].key}" type="password" class="text" id="{$rows[id].key}" value="{$rows[id].value}" size="30">
        				{if $rows[id].hint}&nbsp;<span class="Webta_Ihelp">{$rows[id].hint}</span>{/if}</td>
        			</tr>
        		{elseif $rows[id].type == 'checkbox'}
        			<tr>
        				<td width="18%">{$rows[id].title}:</td>
        				<td width="82%"><input type="checkbox" name="{$rows[id].key}" {if $rows[id].value == 1}checked{/if} value="1" />
        				{if $rows[id].hint}&nbsp;<span class="Webta_Ihelp">{$rows[id].hint}</span>{/if}</td>
        			</tr>
        		{/if}
        	{/section}
	{include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}