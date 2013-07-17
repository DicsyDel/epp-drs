{include file="admin/inc/header.tpl"}
   	{include file="admin/inc/table_header.tpl" filter=0}
   		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('header_text',_("Operation details"));
	    {/php}
		{include file="admin/inc/intable_header.tpl" header=$header_text color="Gray"}
        <tr>
        	<td>{t}Operation{/t}: </td>
        	<td>{$info.operation}</td>
        </tr>
        <tr>
        	<td>{t}Requested at{/t}: </td>
        	<td>{$info.dtbegin}</td>
        </tr>
        {if $details}
        <tr>
        	<td>{t}Details{/t}:</td>
        	<td>{$details}</td>
        </tr>
        {/if}
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}