{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Package information" color="Gray"}
    	<tr>
    		<td width="20%">Package name:</td>
    		<td><input type="text" class="text" name="name" value="{$name}" /></td>
    	</tr>
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
        
        {include file="admin/inc/intable_header.tpl" header="Auto assignment options" color="Gray"}
    	<tr>
    		<td colspan="2">assign this package to users that registered <input name="min_domains" size="3" value="{$min_domains}"> domains</td>
    	</tr>
    	<tr>
    		<td colspan="2">Automatically assign this package to users that have more than {$Currency}<input name="min_balance" size="5" value="{$min_balance}"> on balance</td>
    	</tr>        
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
        
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}