

{include file="client/inc/header.tpl"}
	<script language="Javascript" type="text/javascript">
	
	var ns1 = '{$ns1}';
	var ns2 = '{$ns2}';
	
	{literal}
	function SetManagedDNS(enable)
	{
		if (enable)
		{
			$('ns1').value = ns1;
			$('ns1').disabled = true;
			
			$('ns2').value = ns2;
			$('ns2').disabled = true;
		}
		else
		{
			$('ns1').disabled = false;
			$('ns2').disabled = false;
		}
	}
	{/literal}
	</script>
	{include file="client/inc/table_header.tpl"}
		<input type="hidden" name="stepno" value="{$stepno}" />
	   
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Bulk registration - Step 6 (DNS settings)"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" intable_first_column_width=200 header=$intable_header color="Gray"}
    	<tr>
    	   <td>{t}Nameserver 1: *{/t}</td>
    	   <td><input type="text" class="text" name="ns1" id="ns1" value="{$ns1}"></td>
    	</tr>
    	<tr>
    	   <td>{t}Nameserver 2: *{/t}</td>
    	   <td><input type="text" class="text" name="ns2" id="ns2" value="{$ns2}"></td>
    	</tr>
    	{if $enable_managed_dns}
    	<tr valign="top">
    	   <td>{t}Enable managed DNS:{/t}</td>
    	   <td>
    	   		<input style="vertical-align:middle;" type="checkbox" name="enable_managed_dns" onclick="SetManagedDNS(this.checked)" value="1" {if $post_enable_managed_dns}checked{/if}>
    	   		<span style="font-size:10px;">{t}If Managed DNS is enabled, {$servicename} nameservers will be used for this domain. You will be able to control your domain DNS zone in your registrant Control Panel.{/t}</span>
    	   </td>
    	</tr>
    	{/if}
    	{include file="client/inc/intable_footer.tpl" color="Gray"}
    	
	
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}
	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}