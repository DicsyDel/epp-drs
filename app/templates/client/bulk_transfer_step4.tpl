{include file="client/inc/header.tpl"}

	{php}
    	// Do not edit PHP code below!
    	if ($this->_tpl_vars['num_ok'])
    		$this->assign('intable_header', sprintf(_("%s domains can be transferred. To initiate transfer, please <a href=\"checkout.php?string_invoices=%s\">pay the invoices</a> that were generated for each domain. "), $this->_tpl_vars['num_ok'], $this->_tpl_vars['string_invoices']));
    {/php}

    {include file="client/inc/table_header.tpl" table_header_text=$intable_header nofilter=1}
	<table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items_">
    <thead>
	<tr>
		<th nowrap>{t}Domain name{/t}</th>
	</tr>
	</thead>
	<tbody>
	{foreach from=$res_ok key=domain item=tp}
	<tr>
		<td class="Item" nowrap>{$domain}.{$BT_TLD}</td>
	</tr>
	{foreachelse}
	<tr>
		<td class="Item" nowrap>{t}No domains to transfer{/t}</td>
	</tr>
	{/foreach}
	</tbody>
	<tr>
		<td colspan="14"></td>
	</tr>
    </table>
    {include file="client/inc/table_footer.tpl" disable_footer_line=1}
<br>
<br>
{if $res_fail|@count > 0}
	{include file="client/inc/table_header.tpl" webta_table_title='Failed domain transfers' nofilter=1}
		<table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items_">
	    <thead>
		<tr>
			<th nowrap>{t}Domain name{/t}</th>
			<th nowrap>{t}Fail reason{/t}</th>
		</tr>
		</thead>
		<tbody>
		{foreach from=$res_fail key=domain item=tp}
		<tr>
			<td class="Item" nowrap>{$domain}.{$BT_TLD}</td>
			<td class="Item" nowrap>{$tp}</td>
		</tr>
		{/foreach}
		</tbody>
		<tr>
			<td colspan="14"></td>
		</tr>
	</table>
	{include file="client/inc/table_footer.tpl" disable_footer_line=1}
{/if}
{include file="client/inc/footer.tpl"}