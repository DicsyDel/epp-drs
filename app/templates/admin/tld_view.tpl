{include file="admin/inc/header.tpl"}
{include file="admin/inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items">
    <thead>
	<tr>
	  <th nowrap width="100%">Domain extension</td>
	  <th nowrap  width="1" nowrap="nowrap" >Module</th>
	  <th nowrap  width="1" nowrap="nowrap" >Pricing</th>
	  <th nowrap  width="1" nowrap="nowrap" >Enabled</th>
	</tr>
	</thead>
	</form>
	<form style="padding:0px;margin:0px;" method="post" action="">
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		  <td class="Item" valign="top">{$rows[id].TLD}</td>
		  <td class="Item" valign="top">{$rows[id].modulename}</td>
		  <td class="Item" valign="top" nowrap="nowrap"><a href="tld_price.php?TLD={$rows[id].TLD}&pn={$pn}&pt={$pt}&pf={$pf}">Configure pricing</a></td> 
  		  <td class="Item" valign="top" align="center" nowrap="nowrap"><input type="checkbox" {if $rows[id].disabled}disabled{/if} size="5" name="isactive[{$rows[id].TLD}]" { if $rows[id].isactive}checked{/if} value="1" /></td> 
	</tr>
	{sectionelse}
	<tr>
		<td colspan="4" align="center">No Domain extensions found.</td>
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