{include file="client/inc/header.tpl" noheader=1}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="stepno" value="{$stepno}" />
	   
	   <script language="javascript" src="/js/ContactsManagerBulk.js"></script>
	   <script>

	   </script>
	   
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Bulk registration &mdash; Step 4 (Select contacts)"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
			

		{foreach from=$contact_forms item=all_contacts_form}
		
		<tr>
			<td colspan="2" valign="top">
			
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
    		  <select groupname="{$item.groupname}" id="dropdown_{$all_contacts_form.tld}_{$item.type}" class="text tld-{$all_contacts_form.tld}" {if $item.childof != ''}disabled{/if} style="vertical-align:middle; {if $item.isrequired == 0 && $item.selected == false}display:none;{/if}" name="{$item.type}"  onchange="CheckContact('{$item.type}', this.value, '{$all_contacts_form.tld}', '{$item.groupname}')">
    		  	<optgroup label="---" class="actions action-bar2">
	    		  {if $item.isrequired == 0}
    		  	  <option value="" {if $item.selected == 0}selected{/if}>{t ctype=$item.name}Do not set %1 contact for this domain{/t}</option>
	    		  {/if}
	    		  <option value="[CHOSE]">{t}Select contact...{/t}</option>
	    		  <option value="[NEW]">{t}Create new contact...{/t}</option>
    		  	</optgroup>
    		  </select>
    		  {else}
    		  <select groupname="{$item.groupname}" id="dropdown_{$all_contacts_form.tld}_{$item.type}" class="text tld-{$all_contacts_form.tld}"  {if $item.childof != ''}disabled{/if} style="vertical-align:middle; {if $item.isrequired == 0 && $item.selected == false}display:none;{/if}" name="{$item.type}" onchange="CheckContact('{$item.type}', this.value, '{$all_contacts_form.tld}', '{$item.groupname}')">
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

    		  <span id="loader_{$all_contacts_form.tld}_{$item.type}" style="display:none;vertical-align:middle;"><img style="vertical-align:middle;" src="images/snake-loader.gif"> {t}Please wait...{/t}</span>
    		</td>
    	</tr>
    	<tr id="{$item.type}_{$all_contacts_form.tld}_new_contact_cont" style="display:none;">
    		<td></td>
    		<td>
    			<form style="padding:0px;margin:0px;" name="frm_{$item.type}" id="frm_{$item.type}" action="POST" onsubmit="return false;">
    			<div id="{$item.type}_{$all_contacts_form.tld}_new_contact_value"></div>
    			</form>
    			<br />
    		</td>
    	</tr>
    	<tr id="{$item.type}_{$all_contacts_form.tld}_select_contact_cont" style="display:none;">
    		<td></td>
			<td>
				<div id="{$item.type}_{$all_contacts_form.tld}_select_contact_value"></div>
			</td>    	
    	</tr>
    	
    	{/foreach}
    	
		{include file="client/inc/intable_footer.tpl" color="Gray"}		
		

	

		
			
			</td>
		</tr>
		
		

		{/foreach}


	<form name="main_frm" style="padding:0px;margin:0px;" action="" method="POST">
		<input type="hidden" name="stepno" value="{$stepno}">
		 {foreach from=$contact_forms item=all_contacts_form}
		 	{assign var=tld value=$all_contacts_form.tld}
		 	
			<!-- input name="registrant" id="hidd_registrant" type="hidden" value="" -->
			<input name="contact_list[{$tld}][registrant]" id="hidd_registrant_{$tld}" type="hidden" value="">
			
			<!-- input name="tech" id="hidd_tech" type="hidden" value="" -->
			<input name="contact_list[{$tld}][tech]" id="hidd_tech_{$tld}" type="hidden" value="">
			
			<!-- input name="admin" id="hidd_admin" type="hidden" value="" -->
			<input name="contact_list[{$tld}][admin]" id="hidd_admin_{$tld}" type="hidden" value="">
			
			<!-- input name="billing" id="hidd_billing" type="hidden" value="" -->
			<input name="contact_list[{$tld}][billing]" id="hidd_billing_{$tld}" type="hidden" value="">
		 {/foreach}
		 
	{include file="client/inc/intable_footer.tpl" color="Gray"}
  
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}  	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
			 
	</form>


	<script language="Javascript">
		{literal}
		function ShowContactChoise (htmlEl, contactType) {
			var lab = $(htmlEl).up("label");
			lab.next('select').show();
			lab.remove();
		}
		{/literal}	
	
	  	Event.observe(window, 'load', function()	  	
	  	{literal}{{/literal}
	  	
  			{foreach from=$contact_forms item=all_contacts_form}
			{foreach from=$all_contacts_form.contacts item=item key=key}
				{if $item.childof == ''}  
			   	CheckContact('{$item.type}', $('dropdown_{$all_contacts_form.tld}_{$item.type}').value, '{$all_contacts_form.tld}', '{$item.groupname}');
			   	{/if}
			{/foreach}
			{/foreach}
		{literal}});{/literal}
	</script>

			

			

	
{include file="client/inc/footer.tpl"}


