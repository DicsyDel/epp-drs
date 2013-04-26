{include file="admin/inc/header.tpl"}


<br/>
{include file="admin/inc/table_header.tpl" nofilter=1}
	
<div style="padding:4px;margin-left:25px;height:30px;vertical-align:middle;line-height:30px;">
	<span>Client: </span>
	
	<select name="userid" style="vertical-align:middle;" class="text">
		{section name=id loop=$users}
		<option value="{$users[id].id}" {if $users[id].id == $attr.userid}selected{/if}>{$users[id].login} ({$users[id].email})</option>
		{/section}
	</select>
	
	<input style="vertical-align:middle;" type="submit" name="dfilter" value="Filter" class="btn{if $dfilter}i{else}{/if}" />
</div>	

{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
	

    {include file="admin/inc/table_header.tpl"}	
    <table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Type</th>
			<th>Funds, {$Currency}</th>
			<th width="20%">Date</th>
			<th width="40%">Description</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		  <td class="Item" valign="top">
		  {if $rows[id]->Type == "Deposit"}
		  <span style="color: green">Deposit</span>
		  {elseif $rows[id]->Type == "Withdraw"}
		  <span style="color: red">Withdraw</span>
		  {/if}
		  </td>
		  <td class="Item" valign="top" style="text-align: right;"><span style="padding-right:8px">{$rows[id]->Amount|number_format:2}</span></td>
		  <td class="Item" valign="top">{$rows[id]->Date|date_format:"%b %e, %Y %H:%M:%S"}</td>
		  <td class="Item" valign="top">
			  {if $rows[id]->InvoiceID}
			  	{if $rows[id]->Type == "Withdraw"}
			  	Paid invoice <a href="inv_details.php?id={$rows[id]->InvoiceID}">#{$rows[id]->InvoiceID}</a>.
			  	{else}
			  	Enlisted invoice <a href="inv_details.php?id={$rows[id]->InvoiceID}">#{$rows[id]->InvoiceID}</a>.
			  	{/if}
			  	{$rows[id]->InvoiceDescription}<br/>
			  {/if}
			  {$rows[id]->Description|nl2br}
		  </td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="4" align="center">No history</td>
	</tr>
	{/section}
	<tr>
		<td colspan="4" align="center">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="admin/inc/table_footer.tpl" colspan=9}	
{include file="admin/inc/footer.tpl"}