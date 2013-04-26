{include file="client/inc/header.tpl"}




<table width="100%" cellpadding="4" style="margin-top:20px">
  <tbody>
    {foreach from=$table item=row}
    <tr style="border-bottom:1px dotted #CCCCCC;">
      <td width="1%" valign="top">
		{if $row.action_status == '1'}
		    <img style="vertical-align: middle; margin-right: 10px; margin-left: 5px;" src="/images/avail.gif"/>
		{elseif $row.action_status == '2'}
			<img style="vertical-align: middle; margin-right: 10px; margin-left: 5px;  margin-top:4px" src="/images/unavail.gif"/>
		{/if}
      </td>
      <td valign="top">
      	{$row.description}
      	{if $row.action_status == '2'}
      		<br><em>{$row.action_fail_reason}</em>
      	{/if}
      </td>
      
    </tr>
    {/foreach}
  </tbody>
</table>    

<div style="margin-top:10px; margin-left:48px">
<input type="button" onClick="location.href='domains_view.php'" value="{t}Continue{/t}" class="btn">
</div>


{include file="client/inc/footer.tpl"}