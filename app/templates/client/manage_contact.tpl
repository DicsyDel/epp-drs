{include file="client/inc/header.tpl" noheader=1}

	<div id="Webta_ErrMsg_contact" class="Webta_ErrMsg" style="display:none;"></div>

	<script language="javascript" src="/js/ContactsManager.js"></script>
	{php}
    	// Do not edit PHP code below!
    	$this->assign('table_header_text1',sprintf(_("%s contact"), $this->_tpl_vars["type_name"]));
    {/php}

	{include file="client/inc/table_header.tpl" nofilter=1 table_header_text=$table_header_text1}
	    {assign var="classname" value="text"}
	    {assign var="padding" value="0"}
	    
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Options"));
	    {/php}
	    
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
		{if !$change_allowed}
		<tr>
			<td colspan="2">
				<b>{t type=$type_name extension=$Domain->Extension}.%2 registry does not allow to replace %1 contacts. You can only edit existing contact.{/t}</b>
				<br />
				<br />
			</td>
		</tr>
		{/if}
		
		<script language="Javascript">
		
		var current_contact = '{$Contact->CLID}';
		var domainid = '{$Domain->ID}';
		var TLD = '{$Domain->Extension}';
		var groupname = '{$groupname}';
		var type = '{$type}';
						
		function SetOptions(contact_clid)
		{literal}{{/literal}
		
		if (current_contact == contact_clid)
		{literal}{{/literal}
			$('current_contact_options').style.display = "";
			$('another_contact_options').style.display = "none";
		{literal}}{/literal}
		else
		{literal}{{/literal}
			$('current_contact_options').style.display = "none";
			$('another_contact_options').style.display = "none";
			if (contact_clid != '[NEW]' && contact_clid != '[SEP]' && contact_clid != '[CHOSE]')
				$('another_contact_options').style.display = "";
		{literal}}{/literal}
		
		{literal}}{/literal}
		
		
		{literal}
	  	Event.observe(window, 'load', function() {
		   	CheckContact(type, $('dropdown_'+type).value, TLD, groupname);
		});
		
		function ShowContactChoise (htmlEl, contactType) {
			$('dropdown_' + contactType).show();
			var p = htmlEl.parentNode;
			p.parentNode.removeChild(p);
		}
		{/literal}
	</script>		

		<tr valign="top">
    		<td width="20%">{t ctype=$type_name}%1 contact:{/t}</td>
    		<td>
    		
    		  {if $too_many_items}
    		  <select {if $disable_change}disabled{/if} groupname="{$groupname}" {if !$change_allowed}disabled{/if} style="vertical-align:middle;" name="newcontactid" class="text" id="dropdown_{$type}" class="text" onchange="SetOptions(this.value); CheckContact('{$type}', this.value, '{$Extension}', '{$groupname}')">
    		    {if $Contact}
    		    {assign var="contact_fields" value=$Contact->getFieldList()}
    		    <optgroup label="">
    		      <option value="{$Contact->CLID}">{$Contact->GetTitle()}</option>
    		    </optgroup>
    		    {/if}
    		  	<optgroup label="&mdash;" class="actions action-bar2">
	    		  {if $is_optional}
    		  	  <option {if !$Contact}selected{/if} value="">{t ctype=$type_name}Do not set %1 contact for this domain{/t}</option>
	    		  {/if}
	    		  <option value="[CHOSE]">{t}Select contact...{/t}</option>
	    		  <option value="[NEW]">{t}Create new contact...{/t}</option>
    		  	</optgroup>
    		  </select>
		      {else}    		
    		  <select {if $disable_change}disabled{/if} groupname="{$groupname}" {if !$change_allowed}disabled{/if} style="vertical-align:middle;" name="newcontactid" class="text" id="dropdown_{$type}" class="text" onchange="SetOptions(this.value); CheckContact('{$type}', this.value, '{$Extension}', '{$groupname}')">
    		  <optgroup label="">
    		  {section name=id loop=$contacts}
    		      <option {if $Contact && $contacts[id].clid == $Contact->CLID}selected{/if} value="{$contacts[id].clid}">{$contacts[id].title} {if $Contact && $contacts[id].clid == $Contact->CLID}(current){/if}</option>
    		  {/section}
   		      </optgroup>
    		  	<optgroup label="&mdash;" class="actions action-bar1">
    		  	  {if $is_optional}
    		  	  <option {if !$Contact}selected{/if} value="">{t ctype=$type_name}Do not set %1 contact for this domain{/t}</option>
    		  	  {/if}
    		  	  <option value="[NEW]">{t}Create new contact...{/t}</option>
    		  	  {if !$Contact && !$is_optional}
    		  	  {* Exception *}
    		  	  <option value="" selected></option>
    		  	  {/if}
    		  	</optgroup>
    		  </select>
    		  {/if}
    		  <span id="loader_{$type}" style="display:none;vertical-align:middle;"><img style="vertical-align:middle;" src="images/snake-loader.gif"> {t}Please wait...{/t}</span>
    		  
    		  <span id="current_contact_options" style="vertical-align:middle;display:{if !$Contact}none{/if};">
    		  	<input {if !$edit_allowed}disabled{/if} onClick="EditContact();" style="vertical-align:middle;" type="button" class="btn" name="edit" id="edit" value="{t}Edit{/t}">
    		  </span>
    		  
    		  <span id="another_contact_options" style="vertical-align:middle;display:none;">
    		  	<input {if $disable_change}disabled{/if} onClick="SetContact();" style="vertical-align:middle;" type="button" class="btn" name="edit" id="edit" value="{t}Save{/t}">
    		  </span>
    		</td>
    	</tr>
    	<tr id="{$type}_new_contact_cont" style="display:none;">
    		<td></td>
    		<td>
    			<form style="padding:0px;margin:0px;" name="frm_{$type}" id="frm_{$type}" action="POST" onsubmit="return false;">
    			<div id="{$type}_new_contact_value">
    			
    			</div>
    			</form>
    			<br />
    		</td>
    	</tr>
    	<tr id="{$type}_select_contact_cont" style="display:none;">
    		<td></td>
    		<td>
    			<div id="{$type}_select_contact_value"></div>
    		</td>
    	</tr>    	
	    {include file="client/inc/intable_footer.tpl" color="Gray"}	    
	{include file="client/inc/table_footer.tpl" disable_footer_line=1}
{include file="client/inc/footer.tpl"}