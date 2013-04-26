{include file="client/inc/header.tpl"}
     </form>
	{include file="client/inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items">
    <thead>
        <tr>
          <th nowrap>{t}Hostname{/t}</th>
          <th nowrap>{t}IP Address{/t}</th>
          <th nowrap>{t}Action{/t}</th>
        </tr>
    </thead>
    <tbody>
    {foreach name=id from=$nhosts item=ns key=k}
    	{if $ns != ''}
    	<form action="" method="post" style="padding:0px;margin:0px;">
            <tr id='tr_{$smarty.foreach.id.iteration}'>
                <td nowrap class="Item">{$ns.hostname}.{$Domain->Name}.{$Domain->Extension}</td>
                <td nowrap class="Item"><input {$dsb} type="text" class="text" name="ip" value="{$ns.ipaddr}"/></td>
                <td nowrap class="Item"><input {$dsb} {$dsb2} type="submit" name="modify" class="btn" value="{t}Modify{/t}" /> <input {if $ns.isused}disabled{/if} {$dsb} class="btn" type="submit" name="delete" value="{t}Delete{/t}" /><input type="hidden" name="nid" value="{$ns.id}" style="padding:0px;margin:0px;"></td>
            </tr>
    	</form>
    	{/if}
    {foreachelse}
    	
    {/foreach}
<tr>
  <form action="" method="post" style="padding:0px;margin:0px;">
  <td nowrap class="Item"><input type="text" {$dsb} class="text" name="ns_add" size="6" value=""/>.{$Domain->Name}.{$Domain->Extension}</td>
  <td nowrap class="Item"><input type="text" {$dsb} class="text" name="ip_add" value=""/></td>
  <td nowrap class="Item"><input type="submit" {$dsb} name="add" class="btn" value="{t}Add{/t}" /></td>
  </form>
</tr>
<tr>
    <td colspan="12"></td>
</tr>
</tbody>
</table>
{include file="client/inc/table_footer.tpl" disable_footer_line=1}
{include file="client/inc/footer.tpl"}