{include file="admin/inc/header.tpl"}
   	{include file="admin/inc/table_header.tpl" filter=0}
   	
<style>
{literal}
.ItemSeverity {padding:2px 10px !important; text-align: center;}
{/literal}
</style>
   	
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
		<thead>
			<tr>
				<th>ID</th>
				<th></th>
				<th>Date</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" nowrap="nowrap" valign="top">{$rows[id].id}</td>
			<td class="ItemSeverity" nowrap="nowrap" valign="top">
				<img src="{$rows[id].severity_ico}" title="{$rows[id].severity_title}">
			</td>
			<td class="Item" nowrap="nowrap" valign="top">{$rows[id].dtadded}</td>
			<td class="Item" valign="top">{$rows[id].message}</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="4" align="center">No log entries found</td>
		</tr>
		{/section}
		<tr>
			<td colspan="4" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{include file="admin/inc/table_footer.tpl" colspan=9 allow_delete=0 disable_footer_line=1 add_new=0}
	<br>
{include file="admin/inc/footer.tpl"}