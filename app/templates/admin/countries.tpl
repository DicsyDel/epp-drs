{include file="admin/inc/header.tpl"}
{include file="admin/inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items">
    <thead>
	<tr>
	  <th nowrap>Country name</td>
	  <th width="200" nowrap>Country ISO code</td>
	  <th width="100" nowrap>VAT (%)</th>
	  <th width="1" nowrap>Enabled</th>
	</tr>
	</thead>
	</form>
	<form style="padding:0px;margin:0px;" method="post" action="">
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		  <td class="Item" valign="top">{$rows[id].name}</td>
		  <td class="Item" valign="top">{$rows[id].code}</td>
		  <td class="Item" valign="top" nowrap="nowrap"><input type='text' class='text' name='vat[{$rows[id].id}]' value="{$rows[id].vat}" size="2"></td> 
  		  <td class="Item" valign="top" align="center" nowrap="nowrap"><input type="checkbox" size="5" name="enabled[{$rows[id].id}]" { if $rows[id].enabled}checked{/if} value="1" /></td> 
	</tr>
	{sectionelse}
	<tr>
		<td colspan="4" align="center">No Countries found.</td>
	</tr>
	{/section}
	</tbody>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
</table>
	<input type="hidden" name="actionsubmit" value="1">
	<input type="hidden" name="pn" value="{$pn}">
	<input type="hidden" name="pt" value="{$pt}">
	<input type="hidden" name="pf" value="{$pf}">
    {include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}