{include file="admin/inc/header.tpl"}
   	{include file="admin/inc/table_header.tpl" filter=0}
		{include file="admin/inc/intable_header.tpl" header="Object changes" color="Gray"}
        <tr>
        	<td colspan="2">
        		<ul>
        		{section loop=$changes name=id}
        			<li>{$changes[id]}</li>
        		{/section}
        		</ul>
        	</td>
        </tr>
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" colspan=9 allow_delete=0 disable_footer_line=1 add_new=0}
{include file="admin/inc/footer.tpl"}