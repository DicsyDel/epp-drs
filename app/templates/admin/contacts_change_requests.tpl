{include file="admin/inc/header.tpl"}
	<link rel="stylesheet" href="/css/SelectControl.css" type="text/css" />
    <script type="text/javascript" src="/js/class.SelectControl.js"></script>
    <style>
    	{literal}
    	.contacts-diff td {
    		border:none !important;
    		font-size:13px !important;
    		background-color: transparent;
    	}
    	{/literal}
    </style>
    <script>
    
    </script>
    {include file="admin/inc/table_header.tpl"}
	<table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th nowrap>Date</th>
    		<th nowrap>Operation</th>
    		<th nowrap width="60%">Contact</th>
    		<th nowrap></th>
        </tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		  <td class="Item" valign="top">{$rows[id].dtbegin}</td>
		  <td class="Item" valign="top">{$rows[id].operation}</td>
		  <td class="Item" valign="top">
		  	<div>
		  		<a href="contact_full.php?clid={$rows[id].clid}">{$rows[id].clid}</a>
		  		<!-- 
		  		<div id="details_{$rows[id].id}" style="display: none">
		  			<table class="contacts-diff" width="100%" cellpadding="2" cellspacing="0">
	  					{foreach from=$rows[id].fields item=f}		  			
		  				<tr>
		  					<td width="170"><b>{$f.description}:</b></td>
		  					<td>{$f.value}</td>
		  					{if $rows[id].3columns}
		  					<td>{$f.value2}</td>
		  					{/if}
		  				</tr>
	  					{/foreach}		  				
		  			</table>
		  		</div>
		  		-->
		  	</div>
		  </td>
		  <td class="ItemEdit" valign="top" align="center" width="1%" style="padding-right:30px;"><a id="control_{$rows[id].contactid}" href="javascript:void(0)">{t}Options{/t}</a></td>
	</tr>
	<script type="text/javascript">
        // setup an select control
        var id = '{$rows[id].contactid}';
        
        var menu = [
	        {literal}{href: 'contacts_change_requests.php?id='+id+'&approve=1', innerHTML: '{/literal}{t}Approve{/t}{literal}'}{/literal},
	        {literal}{href: 'javascript:if (confirm("Reject contact change request?")) location.href="contacts_change_requests.php?id='+id+'&approve=0"', innerHTML: '{/literal}{t}Reject{/t}{literal}'}{/literal}
        ];        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}
    </script>
	{sectionelse}
	<tr bgcolor="#F9F9F9">
		  <td valign="top" colspan="20" align="center">No requests found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="3" align="center">&nbsp;</td>
		<td class="ItemEdit"></td>
	</tr>
	</tbody>
	</table>
	{include file="admin/inc/table_footer.tpl" colspan=9 disable_footer_line=1}	
{include file="admin/inc/footer.tpl"}