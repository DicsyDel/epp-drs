{include file="admin/inc/header.tpl"}
    <table width="100%" border="0">
        <tr valign="top">
            <td rowspan="5">
            	{include file="admin/inc/table_header.tpl" width="100%" nofilter=1}
            	<table>
            	<tr><td><br /><div id="index_menu_div">{$index_menu}</div><br /><br /></td></tr>
            	</table>
            	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
    	    </td>
    	    <td width="25">&nbsp;</td>
    	    <td>
    	       {include file="admin/inc/table_header.tpl" width="100%" nofilter=1}
            	   {include file="client/inc/intable_header.tpl" header="System summary" color="Gray"}
                	<tr>
                		<td>Total invoices:</td>
                		<td>{$total_invoices}</td>
                	</tr>
                	<tr>
                		<td>Total domains:</td>
                		<td>{$total_domains}</td>
                	</tr>
                	<tr>
                		<td>Total contacts:</td>
                		<td>{$total_contacts}</td>
                	</tr>
                	<tr>
                		<td>Total clients:</td>
                		<td>{$total_clients}</td>
                	</tr>
                	<tr>
                		<td>Balance:</td>
                		<td>{$CurrencyHTML}{$total_balance}</td>
                	</tr>
            	    {include file="client/inc/intable_footer.tpl" color="Gray"}
            	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
    	    </td>
	    </tr>
	    <tr>
	       <td colspan="3" height="25">&nbsp;</td>
	    </tr>
	    <tr>
	       <td></td>
	       <td>
    	        {include file="admin/inc/table_header.tpl" width="100%" nofilter=1}
            	   {include file="client/inc/intable_header.tpl" header="Payment statistics" color="Gray"}
                	<tr>
                		<td>Total invoices:</td>
                		<td>{$total_invoices} [<a href="inv_view.php">View</a>]</td>
                	</tr>
                	<tr>
                		<td>Pending invoices:</td>
                		<td>{$pending_invoices} [<a href="inv_view.php?status=0">View</a>]</td>
                	</tr>
                	<tr>
                		<td>Failed invoices:</td>
                		<td>{$failed_invoices} [<a href="inv_view.php?status=2">View</a>]</td>
                	</tr>
                	<tr>
                		<td>Paid invoices:</td>
                		<td>{$paid_invoices} [<a href="inv_view.php?status=1">View</a>]</td>
                	</tr>
            	    {include file="client/inc/intable_footer.tpl" color="Gray"}
            	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
    	    </td>
	    </tr>
	    <tr>
	       <td colspan="3" height="25">&nbsp;</td>
	    </tr>
    	<tr valign="top">
    	   <td width="25">&nbsp;</td>
    	   <td height="450">
    	       {include file="admin/inc/table_header.tpl" width="100%" nofilter=1}
            	   {include file="client/inc/intable_header.tpl" header="Domains statistics" color="Gray"}
                	<tr>
                		<td>Total domains:</td>
                		<td>{$total_domains}</td>
                	</tr>
                	<tr>
                		<td>Pending domains:</td>
                		<td>{$pending_domains}</td>
                	</tr>
                	<tr>
                		<td>Active domains:</td>
                		<td>{$active_domains}</td>
                	</tr>
            	    {include file="client/inc/intable_footer.tpl" color="Gray"}
            	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
    	   </td>
    	</tr>
	</table>
{include file="admin/inc/footer.tpl"}
