{include file="client/inc/header.tpl" noheader=1}

	{if $enable_managed_dns}
    <form action="" name="frm1" method="POST" style="margin:0px;padding:0px;">
	<input type="hidden" name="task" value="mdns" />
    {include file="client/inc/table_header.tpl"}
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Managed DNS"));
	    {/php}
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
    	<tr valign="top">
    		<td width="150">{t}Enable managed DNS:{/t}</td>
    		<td>
    			<input {$dsb} type="checkbox" name="enable_managed_dns" {if $Domain->IsManagedDNSEnabled}checked{/if} value="1">
    		</td>
    	</tr>
		{include file="client/inc/intable_footer.tpl" color="Gray"}
    {include file="client/inc/table_footer.tpl" edit_page=1 color="Gray"}
    </form>
    {/if}
    
	{include file="client/inc/table_header.tpl"}
	{if $Domain->IsManagedDNSEnabled == 1}
		{include file="client/inc/intable_header.tpl" color="Gray"}
		<tr valign="top">
    		<td colspan="2" align="center">{t}You have Managed DNS enabled for this domain. You can not set your own nameservers for this domain until you disable Managed DNS.{/t}</td>
    	</tr>
		{include file="client/inc/intable_footer.tpl" color="Gray"}
		{assign var=dsb value=1}
		{assign var=disable_footer_line value=1}
	{else}
		<form action="" name="frm1" method="POST" style="margin:0px;padding:0px;">
		<input type="hidden" name="task" value="modify">
	    <table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items">
	    <thead>
	    	<tr>
	    	  <th nowrap>{t}Name{/t}</th>
	    	  <th nowrap>{t}Hostname{/t}</th>
	    	  <th nowrap>{t}IP Address (only for glue records){/t}</th>
	    	  <th nowrap>{t}Delete{/t}</th>
	    	</tr>
		</thead>
		<tbody>
		{foreach from=$nameservers item=ns key=k}
			{if $ns != ''}
			<tr>
			  <td nowrap class="Item">{t}Nameserver{/t} {$k+1}</td>
			  <td nowrap class="Item"><input {$dsb} type="text" class="text" name="ns[{$k+1}]" value="{$ns.name}"/></td>
			  <td nowrap class="Item">
			  {if $ns.isglue}
			  	{if $host_as_attr}
			  		<input value="{$ns.ip}" name="ns_ip[{$k+1}]" type="text" class="text" />
			  	{else}
			  		{$ns.ip}
			  	{/if}
			  {/if}
			  </td>
			  <td nowrap class="Item"><input {$dsb} type="checkbox" name="delete[]" value="{$ns.name}" /></td>
			</tr>
			{/if}
		{/foreach}
		{if $num_ns < $max_ns}
		<tr>
		  <td nowrap class="Item">{t}Add new{/t}</td>
		  <td nowrap class="Item"><input type="text" class="text" name="ns[{$k+2}]" value=""/></td>
		  <td nowrap class="Item">
		  	{if $host_as_attr}
		  		<input name="ns_ip[{$k+2}]" type="text" class="text">
		  	{/if}
		  </td>
		  <td nowrap class="Item"><input type="checkbox" disabled /></td>
		</tr>
		{/if}
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{/if}
	{if $dsb != ""}
	    {include file="client/inc/table_footer.tpl"}
	{else}
	    {include file="client/inc/table_footer.tpl" edit_page=1}
	{/if}
{include file="client/inc/footer.tpl"}