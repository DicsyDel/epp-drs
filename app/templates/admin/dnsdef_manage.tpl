{include file="admin/inc/header.tpl"}
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
<form action="" method="POST" name="frm2">
{include file="admin/inc/table_header.tpl"}
        <table width="100%" class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items_">
        <thead>
    	<tr>
    		<th>Domain</th>
    		<th>TTL</th>
    		<th>&nbsp;</th>
    		<th>Record Type</th>
    		<th colspan=3>&nbsp;<th>
    	</tr>
    	</thead>
    	<tbody>
    	{section name=id loop=$records}
    	<tr>
    		<td><input type="text" class="text" name="records[{$records[id].id}][rkey]" size=30 value="{$records[id].rkey}"></td>
    		<td><input type="text" class="text" name="records[{$records[id].id}][ttl]" size=6 value="{$records[id].ttl}"></td>
    		<td>IN</td>
    		<td><select class="text" name="records[{$records[id].id}][rtype]" onchange="CheckPrAdd('ed', '{$records[id].id}', this.value)">
    				<option {if $records[id].rtype == "A"}selected{/if} value="A">A</option>
    				<option {if $records[id].rtype == "CNAME"}selected{/if} value="CNAME">CNAME</option>
    				<option {if $records[id].rtype == "MX"}selected{/if} value="MX">MX</option>
    				<option {if $records[id].rtype == "NS"}selected{/if} value="NS">NS</option>
    			</select>
    		</td>
    		<td colspan="3"> <input class="text" id="ed_{$records[id].id}" style="display:{if $records[id].rtype != "MX"}none{/if};" type=text name="records[{$records[id].id}][rpriority]" size=5 value="{$records[id].rpriority}"> <input class="text" type=text name="records[{$records[id].id}][rvalue]" size=30 value="{$records[id].rvalue}"></td>
    	</tr>
    	{sectionelse}
    	<tr>
    		<td colspan=8 align="center">No DNS records found</td>
    	</tr>
    	{/section}
    	<tr>
    		<td colspan=8>&nbsp;</td>
    	</tr>
    	<tr>
    		<th colspan=8 class="th" style="padding:5px;">Add New Entries Below this Line</th>
    	</tr>
    	<tr>
    		<td colspan=8>&nbsp;</td>
    	</tr>
    	{section name=id loop=$add}
    	<tr>
    		<td width="300"><input type="text" class="text" name="add[{$add[id]}][rkey]" size=30></td>
    		<td width="100"><input type="text" class="text" name="add[{$add[id]}][ttl]" size=6 value="14400"></td>
    		<td width="30">IN</td>
    		<td width="50"><select class="text" name="add[{$add[id]}][rtype]" onchange="CheckPrAdd('ad', '{$add[id]}', this.value)">
    				<option selected value="A">A</option>
    				<option value="CNAME">CNAME</option>
    				<option value="MX">MX</option>
    				<option value="NS">NS</option>
    			</select>
    		</td>
    		<td colspan="4" width="100%"> <input id="ad_{$add[id]}" size="5" style="display:none;" type="text" class="text" name="add[{$add[id]}][rpriority]" value="10" size=30> <input type="text" class="text" name="add[{$add[id]}][rvalue]" size=30></td>
    	</tr>
    	{/section}
    	<tr>
    		<td colspan=8>&nbsp;</td>
    	</tr>
    	</tbody>
    	</table>
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}