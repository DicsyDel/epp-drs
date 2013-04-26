{include file="client/inc/header.tpl"}
<script language="javascript" type="text/javascript">
{literal}
	function CheckPrAdd(tp, id, val)
	{
		if (val == 'MX')
		{
			$(tp+"_"+id).style.display = '';
			$(tp+"_"+id).value = '10';
		}
		else
		{
			$(tp+"_"+id).style.display = 'none';
			$(tp+"_"+id).value = '';
		}
	}
{/literal}
</script>
{include file="client/inc/table_header.tpl"}
<table cellpadding="4" cellspacing="0" width="100%">
	<tr>
		<td>{$domainname}</td>
		<td><input type="text" class="text" name="zone[soa_ttl]" size="6" value="{if $zone.soa_ttl}{$zone.soa_ttl}{else}14400{/if}"></td>
		<td>IN</td>
		<td>SOA</td>
		<td><input type="text" class="text" name="zone[soa_parent]" size="30" value="{if $zone.soa_parent}{$zone.soa_parent}{else}{$def_soa_parent}{/if}"></td>
		<td><input type="text" class="text" name="zone[soa_owner]" size="30" value="{if $zone.soa_owner}{$zone.soa_owner}{else}{$def_soa_owner}{/if}"></td>
		<td></td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Serial Number:</td>
		<td>{if $zone.soa_serial}{$zone.soa_serial}{else}{php} echo date('Ymd');{/php}00{/if}</td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Refresh:</td>
		<td><input type="text" class="text" name="zone[soa_refresh]" size=12  value="{if $zone.soa_refresh}{$zone.soa_refresh}{else}14400{/if}"></td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Retry:</td>
		<td><input type="text" class="text" name="zone[soa_retry]" size=12  value="{if $zone.soa_retry}{$zone.soa_retry}{else}7200{/if}"></td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Expire:</td>
		<td><input type="text" class="text" name="zone[soa_expire]" size=12  value="{if $zone.soa_expire}{$zone.soa_expire}{else}3600000{/if}"></td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Minimum TTL:</td>
		<td><input type="text" class="text" name="zone[min_ttl]" size=12 value="{if $zone.min_ttl}{$zone.min_ttl}{else}86400{/if}"></td>
		<td></td>
	</tr>
	<tr>
		<td class="th">Domain</td>
		<td class="th">TTL</td>
		<td class="th">&nbsp;</td>
		<td class="th">Record Type</td>
		<td class="th" colspan=3>Record value<td>
	</tr>
	{section name=id loop=$zone.records}
	{if ($zone.records[id].rtype == "A" && $smarty.const.CF_ALLOW_A_RECORD == 1) ||
		($zone.records[id].rtype == "CNAME" && $smarty.const.CF_ALLOW_CNAME_RECORD == 1) ||
		($zone.records[id].rtype == "MX" && $smarty.const.CF_ALLOW_MX_RECORD == 1) ||
		($zone.records[id].rtype == "NS" && $smarty.const.CF_ALLOW_NS_RECORD == 1)}
	<tr>
		<td><input type="text" class="text" name="zone[records][{$zone.records[id].id}][rkey]" size=30 value="{$zone.records[id].rkey}"></td>
		<td><input type="text" class="text" name="zone[records][{$zone.records[id].id}][ttl]" size=6 value="{$zone.records[id].ttl}"></td>
		<td>IN</td>
		<td><select class="text" name="zone[records][{$zone.records[id].id}][rtype]" onchange="CheckPrAdd('ed', '{$zone.records[id].id}', this.value)">
				{if $smarty.const.CF_ALLOW_A_RECORD == 1}<option {if $zone.records[id].rtype == "A"}selected{/if} value="A">A</option>{/if}
				{if $smarty.const.CF_ALLOW_CNAME_RECORD == 1}<option {if $zone.records[id].rtype == "CNAME"}selected{/if} value="CNAME">CNAME</option>{/if}
				{if $smarty.const.CF_ALLOW_MX_RECORD == 1}<option {if $zone.records[id].rtype == "MX"}selected{/if} value="MX">MX</option>{/if}
				{if $smarty.const.CF_ALLOW_NS_RECORD == 1}<option {if $zone.records[id].rtype == "NS"}selected{/if} value="NS">NS</option>{/if}
			</select>
		</td>
		<td colspan="2"> <input class="text" id="ed_{$zone.records[id].id}" style="display:{if $zone.records[id].rtype != "MX"}none{/if};" type=text name="zone[records][{$zone.records[id].id}][rpriority]" size=5 value="{$zone.records[id].rpriority}"> <input class="text" type=text name="zone[records][{$zone.records[id].id}][rvalue]" size=30 value="{$zone.records[id].rvalue}"></td>
	</tr>
	{/if}
	{/section}
	<tr>
		<td colspan=7>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=7 class="th">Add New Entries Below this Line</td>
	</tr>
	{section name=id loop=$add}
	<tr>
		<td><input type="text" class="text" name="add[{$add[id]}][rkey]" size=30></td>
		<td><input type="text" class="text" name="add[{$add[id]}][ttl]" size=6 value="14400"></td>
		<td>IN</td>
		<td><select class="text" name="add[{$add[id]}][rtype]" onchange="CheckPrAdd('ad', '{$add[id]}', this.value)">
				{if $smarty.const.CF_ALLOW_A_RECORD == 1}<option selected value="A">A</option>{/if}
				{if $smarty.const.CF_ALLOW_CNAME_RECORD == 1}<option value="CNAME">CNAME</option>{/if}
				{if $smarty.const.CF_ALLOW_MX_RECORD == 1}<option value="MX">MX</option>{/if}
				{if $smarty.const.CF_ALLOW_NS_RECORD == 1}<option value="NS">NS</option>{/if}
			</select>
		</td>
		<td colspan="2"> <input id="ad_{$add[id]}" size="5" style="display:none;" type="text" class="text" name="add[{$add[id]}][rpriority]" value="10" size=30> <input type="text" class="text" name="add[{$add[id]}][rvalue]" size=30></td>
	</tr>
	{/section}
</table>
<input type="hidden" name="domainname" value="{$domainname}" />
<input type="hidden" name="zonename" value="{$zonename}" />
{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}