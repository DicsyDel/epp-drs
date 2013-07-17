{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Import domains" color="Gray"}
        <tr>
        	<td colspan="2"></td>
        </tr>
        <tr>
        	<td width="30%">New owner for imported domains:</td>
        	<td>
        		<select name="userid" class="text">
        			{section name=id loop=$users}
        					<option value="{$users[id].id}">{$users[id].login} ({$users[id].email})</option>
        			{/section}
        		</select>
        	</td>
        </tr>
        <tr valign="top">
        	<td>Domain names (one per line):</td>
        	<td>
        		<textarea class="text" name="domains" cols="40" rows="7">{$attr.domains}</textarea>	
        	</td>
        </tr>
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}