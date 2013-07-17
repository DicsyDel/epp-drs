{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Full contact information" color="Gray"}
    	<tr>
    			<td width="20%"><b>Extension:</b></td>
    			<td>{$row.TLD|upper}</td>
    	</tr>
    	{foreach from=$fields item=field key=key}
            <tr>
        			<td width="20%"><b>{$field.description}:</b></td>
        			<td>{$field.value}</td>
        	</tr>
    	{/foreach}
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}