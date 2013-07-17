{include file="client/inc/header.tpl" noheader=1}
	<script type="text/javascript" src="/js/ContactsManager.js"></script>
	<script type="text/javascript">
	// {literal}
	Ext.onReady(function () {
		var formEl = Ext.get("complete-transfer-form");

		// Apply nameserver fields
		var nsFields = {};
		var nsRe = /ns\d/;
		var hostname = formEl.child("input[name='domain']").dom.value;
		Ext.select(".extra_field").each(function (inputFly) {
			var name = inputFly.dom.name.replace(/add_fields\[(.+)\]/gi, '$1');
			if (nsRe.test(name)) {
				nsFields[name] = new Ext.ux.NameserverField({
					el: Ext.get(inputFly.dom),
					hostname: hostname
				});
			}
		});

		formEl.on("submit", completeForm);


		function completeForm (ev) {
			var add_data = {};
			var elems = Ext.select(".extra_field").each(function (el) {
				var name = el.dom.name.replace(/add_fields\[(.+)\]/gi, '$1');
				if (nsFields[name]) {
					add_data[name] = nsFields[name].getValue();
				} else {
					add_data[name] = el.dom.value;
				}
			});

			Ext.fly('add_data').dom.value = Ext.urlEncode(add_data);
			
			return true;
		}
	});
	
	// {/literal}
	</script>
	{include file="client/inc/table_header.tpl"}
	
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Transfer details"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
		{foreach from=$contacts item=item key=key}
    	<tr valign="top">
    		<td nowrap>{$item.name} {t}contact:{/t}</td>
    		<td>
    		  {if $item.childof}
    		  <span style="display:none;" id="select_parent_{$item.type}">{t contacttype=$item.childof}Select parent - %1 contact...{/t}</span>
    		  {/if}
    		  {if $item.isrequired == 0 && $item.selected == false}
    		  <label><input type="checkbox" name="{$item.type}" value="" checked="checked" onclick="ShowContactChoise(this, '{$item.type}')">
    		  {t ctype=$item.name}Do not set{/t}
    		  </label>
    		  {/if}
    		  
    		  
    		  {if $item.too_many_items}
    		  <select groupname="{$item.groupname}" {if $item.childof != ''}disabled{/if} style="vertical-align:middle; {if $item.isrequired == 0 && $item.selected == false}display:none;{/if}" name="{$item.type}" id="dropdown_{$item.type}" class="text" onchange="CheckContact('{$item.type}', this.value, '{$TLD}', '{$item.groupname}')">
    		  	<optgroup label="---" class="actions action-bar2">
	    		  {if $item.isrequired == 0}
    		  	  <option value="" {if $item.selected == 0}selected{/if}>{t ctype=$item.name}Do not set %1 contact for this domain{/t}</option>
	    		  {/if}
	    		  <option value="[CHOSE]">{t}Select contact...{/t}</option>
	    		  <option value="[NEW]">{t}Create new contact...{/t}</option>
    		  	</optgroup>
    		  </select>
    		  {else}
    		  <select groupname="{$item.groupname}" {if $item.childof != ''}disabled{/if} style="vertical-align:middle; {if $item.isrequired == 0 && $item.selected == false}display:none;{/if}" name="{$item.type}" id="dropdown_{$item.type}" class="text" onchange="CheckContact('{$item.type}', this.value, '{$TLD}', '{$item.groupname}')">
 	    		  	{if $item.childof == '' and $item.list}
    		  		<optgroup label='Contact group "{$item.target_title}"'>
    		  		  {html_options options=$item.list selected=$item.selected}
	    		 	</optgroup>
    			 	{/if}
	    		 	<optgroup label="---" class="actions action-bar1">
		    		  {if $item.isrequired == 0}
	    		  	  <option value="" {if $item.selected == 0}selected{/if}>{t ctype=$item.name}Do not set %1 contact for this domain{/t}</option>
		    		  {/if}
		    		  <option value="[NEW]">{t}Create new contact...{/t}</option>
	    		 	</optgroup>
    		 	
    		  </select>
    		  {/if}
    		  <span id="loader_{$item.type}" style="display:none;vertical-align:middle;"><img style="vertical-align:middle;" src="images/snake-loader.gif"> {t}Please wait...{/t}</span>
    		</td>
    	</tr>
    	<tr id="{$item.type}_new_contact_cont" style="display:none;">
    		<td></td>
    		<td>
    			<form style="padding:0px;margin:0px;" name="frm_{$item.type}" id="frm_{$item.type}" action="POST" onsubmit="return false;">
    			<div id="{$item.type}_new_contact_value"></div>
    			</form>
    			<br />
    		</td>
    	</tr>
    	<tr id="{$item.type}_select_contact_cont" style="display:none;">
    		<td></td>
			<td>
				<div id="{$item.type}_select_contact_value"></div>
			</td>    	
    	</tr>
    	
    	
    	{/foreach}
	            
    	<form style="padding:0px;margin:0px;" name="addfrm" id="addfrm" action="POST" onsubmit="return false;">
        {foreach from=$fields key=key item=field}
			{assign var="fname" value=$field.name}
			{if $field.type == 'text'}
			<tr>
				<td width="250">{$key}: {if $field.required}*{/if}</td>
				<td><input style="height:18px;" type="text" class="text extra_field" name="add_fields[{$field.name}]" value="{$post_add_data.$fname}"/></td>
			</tr>
			{elseif $field.type == 'checkbox'}
			<tr valign="top">
				<td width="250">{$key}: {if $field.required}*{/if}</td>
				<td style="vertical-align:top;"><input type="checkbox" name="add_fields[{$field.name}]" value="{$post_add_data.$fname}"/></td>
			</tr>
			{elseif $field.type == 'memo'}
			<tr valign="top">
				<td width="250">{$key}: {if $field.required}*{/if}</td>
				<td style="vertical-align:top;"><textarea cols="40" class="text extra_field" rows="5" name="add_fields[{$field.name}]">{if $post_add_data.$fname}{$post_add_data.$fname}{else}{$field.value}{/if}</textarea></td>
			</tr>
			{elseif $field.type == 'select'}
			<tr>
				<td width="250">{$key}: {if $field.required}*{/if}</td>
				<td><select name="add_fields[{$field.name}]" class="extra_field">
						{foreach from=$field.values key=vkey item=vfield}
							<option value="{$vkey}" {if $vkey == $post_add_data.$fname}selected{/if}>{$vfield}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			{/if}
		{/foreach}
    	</form>
    	    	
	{include file="client/inc/intable_footer.tpl" color="Gray"}
	<form name="main_frm" id="complete-transfer-form" style="padding:0px;margin:0px;" action="complete_transfer.php" method="POST">
	
	{* Transferred domain name *}
	<input name="domain" type="hidden" value="{$domain}">
	
	<input name="registrant" id="hidd_registrant" type="hidden" value="">
	<input name="tech" id="hidd_tech" type="hidden" value="">
	<input name="admin" id="hidd_admin" type="hidden" value="">
	<input name="billing" id="hidd_billing" type="hidden" value="">
	<input name="add_data" id="add_data" type="hidden" value="">
	<input name="id" id="id" type="hidden" value="{$id}">
	
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Send Transfer Request"));
    {/php}
	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
	
	<script language="Javascript">   
		  Event.observe(window, 'load', function()	  	
	  	{literal}{{/literal}
	  	
	  		{foreach from=$contacts item=item key=key}
			  {if $item.childof != ''}	  
			  	ContactChilds['{$item.childof}'] = '{$item.type}';
			  {/if}
			 {/foreach}
	  	
			{foreach from=$contacts item=item key=key}
				{if $item.childof == ''}  
			   	CheckContact('{$item.type}', $('dropdown_{$item.type}').value, '{$TLD}', '{$item.groupname}');
			   	{/if}
			 {/foreach}
		{literal}});{/literal}
		
		{literal}
		function ShowContactChoise (htmlEl, contactType) {
			$('dropdown_' + contactType).show();
			var p = htmlEl.parentNode;
			p.parentNode.removeChild(p);
		}
		{/literal}
		
	</script>
{include file="client/inc/footer.tpl"}