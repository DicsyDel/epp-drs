{include file="admin/inc/header.tpl"}
{include file="admin/inc/table_header.tpl" filter=""}
    <table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items">
    <thead>
    <tr>
		<th nowrap>Language</th>
		<th nowrap>.mo file</th>
		<th nowrap>Flag image</th>
		<th nowrap>Default</th>
		<th nowrap>Enabled</th>
	</tr>
	</thead>
	<tbody>
	{section name=id loop=$langs}
	 <tr id='tr_{$smarty.section.id.iteration}'>
		  <td class="Item" valign="top">{$langs[id].name}</td>
		  <td class="Item" nowrap width="1%" valign="top">{if $langs[id].mo}<span style="color:green;">Found</span>{else}<span style="color:red;">Not found</span>{/if}</td>
		  <td class="Item" nowrap width="1%" valign="top">{if $langs[id].img}<span style="color:green;">Found</span>{else}<span style="color:red;">Not found</span>{/if}</td>
		  <td class="Item" nowrap width="1%" valign="top">{if $langs[id].isdefault}<img border="0" alt="{t}Default{/t}" align="absmiddle" src="/admin/images/true.gif">{else}<a href="?action=setdefault&name={$langs[id].name}"><img border="0" align="absmiddle" src="/admin/images/false.gif"></a>{/if}</td>
		  <td class="Item" width="1%" nowrap valign="top">
		  	{if $langs[id].isinstalled == 1}
				<img border="0" align="absmiddle" src="/admin/images/true.gif"> [<a alt="Disable language" title="Disable language" href="?action=disable&name={$langs[id].name}">Disable</a>]
			{else}
				{if $langs[id].isinstalled == '0'}
					<img border="0" align="absmiddle" src="/admin/images/false.gif"> [<a alt="Enable language" title="Enable language" href="?action=enable&name={$langs[id].name}">Enable</a>]
				{else} 
					Corrupt language package!
				{/if}
			{/if}
			</td> 
	</tr>
	{/section}
	</tbody>
	<tr>
		<td colspan="12">&nbsp;</td>
	</tr>
</table>
{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}