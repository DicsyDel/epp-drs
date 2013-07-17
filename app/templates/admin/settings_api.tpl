{include file="admin/inc/header.tpl" upload_files=true}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="API settings" color="Gray"}
		<tr>
			<script type="text/javascript">
			{literal}
			function toggleApi (checkboxEl) {
				var disabled = !checkboxEl.checked;
				var n;
				if (n = $("api_key_id")) n.disabled = disabled;
				if (n = $("api_key")) n.disabled = disabled;
				for (var i=0; n=$("api_allowed_ips_"+i); i++) {
					n.disabled = disabled;
				}
			}
			{/literal}
			</script>
			<td width="18%">API enabled:</td>
			<td width="82%"><input type="checkbox" name="api_enabled" value="1" {if $api_enabled}checked{/if} onclick="toggleApi(this)"></td>
		</tr>
		{if $api_key_id}
		<tr>
			<td>API key-id:</td>
			<td><input name="api_key_id" id="api_key_id" class="text" value="{$api_key_id}" size="30" autocomplete="off" {if !$api_enabled}disabled{/if}></td>
		</tr>
		<tr>
			<td valign="top">Api key:</td>
			<td><textarea name="api_key" id="api_key" class="text" rows="2" cols="32" style="word-wrap: break-word;" {if !$api_enabled}disabled{/if}>{$api_key}</textarea></td>
		</tr>
		{/if}
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Allowed IP list" color="Gray"}
		
		{foreach from=$api_allowed_ips item=ip key=i}
		<tr>
			<td width="18%">Address {$i+1}:</td>
			<td width="82%"><input name="api_allowed_ips[]" id="api_allowed_ips_{$i}" class="text" value="{$ip}" size="30"  {if !$api_enabled}disabled{/if}></td>
		</tr>
		{/foreach}
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		

	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}
