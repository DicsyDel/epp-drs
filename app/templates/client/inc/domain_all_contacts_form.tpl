

	<script language="javascript" src="/js/ContactsManager.js"></script>
	{include file="client/inc/table_header.tpl"}
	
		{include file="client/inc/intable_header.tpl" header=$all_contacts_form.form_title color="Gray"}
		
		{foreach from=$all_contacts_form.contacts item=item key=key}
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
    		  <select groupname="{$item.groupname}" {if $item.childof != ''}disabled{/if} style="vertical-align:middle; {if $item.isrequired == 0 && $item.selected == false}display:none;{/if}" name="{$item.type}" id="dropdown_{$item.type}" class="text" onchange="CheckContact('{$item.type}', this.value, '{$all_contacts_form.tld}', '{$item.groupname}')">
    		  	<optgroup label="---" class="actions action-bar2">
	    		  {if $item.isrequired == 0}
    		  	  <option value="" {if $item.selected == 0}selected{/if}>{t ctype=$item.name}Do not set %1 contact for this domain{/t}</option>
	    		  {/if}
	    		  <option value="[CHOSE]">{t}Select contact...{/t}</option>
	    		  <option value="[NEW]">{t}Create new contact...{/t}</option>
    		  	</optgroup>
    		  </select>
    		  {else}
    		  <select groupname="{$item.groupname}" {if $item.childof != ''}disabled{/if} style="vertical-align:middle; {if $item.isrequired == 0 && $item.selected == false}display:none;{/if}" name="{$item.type}" id="dropdown_{$item.type}" class="text" onchange="CheckContact('{$item.type}', this.value, '{$all_contacts_form.tld}', '{$item.groupname}')">
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
	{include file="client/inc/intable_footer.tpl" color="Gray"}
	
	<form name="main_frm" style="padding:0px;margin:0px;" action="{$all_contacts_form.form_action}" method="{$all_contacts_form.form_method}">
	{foreach from=$all_contacts_form.form_fields item=v key=k}
	<input type="hidden" name="{$k}" value="{$v}" />
	{/foreach}
	
	<input name="registrant" id="hidd_registrant" type="hidden" value="">
	<input name="contact_list[registrant]" id="hidd2_registrant" type="hidden" value="">
	
	<input name="tech" id="hidd_tech" type="hidden" value="">
	<input name="contact_list[tech]" id="hidd2_tech" type="hidden" value="">
	
	<input name="admin" id="hidd_admin" type="hidden" value="">
	<input name="contact_list[admin]" id="hidd2_admin" type="hidden" value="">
	
	<input name="billing" id="hidd_billing" type="hidden" value="">
	<input name="contact_list[billing]" id="hidd2_billing" type="hidden" value="">
	

	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$all_contacts_form.button_text}
	</form>
	
	<script language="Javascript">
	  	Event.observe(window, 'load', function()	  	
	  	{literal}{{/literal}
	  	
	  		{foreach from=$all_contacts_form.contacts item=item key=key}
			  {if $item.childof != ''}	  
			  	ContactChilds['{$item.childof}'] = '{$item.type}';
			  {/if}
			 {/foreach}
	  	
			{foreach from=$all_contacts_form.contacts item=item key=key}
				{if $item.childof == ''}  
			   	CheckContact('{$item.type}', $('dropdown_{$item.type}').value, '{$all_contacts_form.tld}', '{$item.groupname}');
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
