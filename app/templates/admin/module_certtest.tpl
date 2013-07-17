{include file="admin/inc/header.tpl"}


{include file="admin/inc/table_header.tpl"}
{include file="admin/inc/intable_header.tpl" header="$module certification test" color="Gray"}
<tr>
  <td colspan="2">
<p>Registry requires certification/approval test to be passed for this module.
You must run the test and pass results (log) to registry to be allowed to connect to production server.
For detailed inormation, please check registry documentation or documentation for this EPP-DRS module.</p>
<p>Note: This test can take some time to finalize. Make sure that timeout limits in your PHP settings are high enough.<br></p>	  
  </td>
</tr>

{if $has_configform}
{include file="inc/dynamicform.tpl"}
{/if}

{include file="admin/inc/intable_footer.tpl" color="Gray"}
{include file="admin/inc/table_footer.tpl" button2=1 button2_name="Run"}



{include file="admin/inc/footer.tpl"}