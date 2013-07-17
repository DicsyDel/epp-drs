{include file="admin/inc/header.tpl"}

{literal}
<script type="text/javascript">
function removeRow (node) {
	Ext.fly(node).parent("tr").remove();
}
</script>
{/literal}

	{include file="admin/inc/table_header.tpl"}
	   <input type="hidden" name="step" value="{$step}" />


	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Bulk renew"));
	    {/php}
	   
		{include file="admin/inc/intable_header.tpl" header=$intable_header color="Gray"}

	<tr valign="top">
		<td width="20%">Domains to renew:</td>
		<td>
			<p class="Webta_Ihelp" style="margin:0 0 15px 0; padding-top:3px; padding-bottom:3px; background-position: 4px 6px;">Domain will be renewed without any fee for client</p>
			
		
			<table width="100%" id="bulk-renew">
				{foreach from=$rows item=row}
				<tr>
					<td><img src="/images/s.gif" class="ico {$row.icon_cls}"/></td>
					<td>{$row.name}</td>
					<td>
						{if $row.show_message}
							<span class="{$row.message_cls}">{$row.message}</span>
						{else}
							<select name="domains[{$row.id}]">
								{html_options options=$row.periods}
							</select>
						{/if}
					</td>
					<td>
					{if !$row.show_message}
					<a class="remove" href="javascript://" onclick="removeRow(this)">Remove</a>
					{/if}
					</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
	
	
  {include file="admin/inc/intable_footer.tpl" color="Gray"}
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}  	
	{include file="admin/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="admin/inc/footer.tpl"}
	