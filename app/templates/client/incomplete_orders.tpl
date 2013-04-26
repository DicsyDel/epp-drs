{include file="client/inc/header.tpl"}
{include file="client/inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items">
    <thead>
    <tr>
		<th nowrap>{t}Name{/t}</th>
		<th nowrap>{t}Ordered{/t}</th>
		<th nowrap></th>
	</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id].name}.{$rows[id].TLD}</td>
		<td class="Item" valign="top" width="1%" nowrap>{$rows[id].ordered}</td>
		<td class="ItemEdit" valign="top" width="1%" nowrap>
		{if $rows[id].operation == 'Register'}
			<a href="domain_reg.php?action=complete&id={$rows[id].id}">{t}Complete registration{/t}</a>
		{elseif $rows[id].operation == 'Transfer'}
			<a href="complete_transfer.php?id={$rows[id].id}">{t}Complete Transfer{/t}</a>
		{elseif $rows[id].operation == 'Trade'}
			<a href="complete_trade.php?id={$rows[id].id}">{t}Complete Trade{/t}</a>
		{/if}
		
		{if $rows[id].outgoing_transfer_status == $smarty.class_const.OUTGOING_TRANSFER_STATUS.REQUESTED}
			<a href="update_status.php?id={$rows[id].id}">{t}Complete transfer{/t}</a>
		{/if}
		
		</td>
	</tr>
	{/section}
	</tbody>
	<tr>
		<td colspan="2">&nbsp;</td>
		<td class="ItemEdit" valign="top"></td>
	</tr>
</table>
{include file="client/inc/table_footer.tpl" disable_footer_line=1}
{include file="client/inc/footer.tpl"}