{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	    <input type="hidden" name="TLD" value="{$TLD}" />
	    <input type="hidden" name="step" value="3" />
	    
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Contact details"));
	    {/php}
	    
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
    	<tr>
    			<td>{t}Type:{/t}</td>
    			<td>
    			    <select name="type" class="text" onchange="document.location = 'contact_create.php?group='+this.value+'&step=2&TLD={$TLD}'">
    			    {html_options options=$groups selected=$group}
    			    </select> 
    			</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
		{assign var="classname" value="text"}
	    {assign var="padding" value="0"}
    	{include file="inc/contact_dynamic_fields.tpl"}
    	{if $parentcontacts|@count > 0}
    	<tr>
			<td>{t}Parent contact:{/t}</td>
			<td>
			    <select name="parentCLID" class="text">
			    {section loop=$parentcontacts name=id}
                    <option {if $contactinfo.parentCLID == $parentcontacts[id].clid}selected{/if} value="{$parentcontacts[id].clid}">{$parentcontacts[id].name}</option>
			    {/section}
			    </select> 
			</td>
    	</tr>
    	{/if}
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
	    {include file="client/inc/intable_footer.tpl" color="Gray"}
	{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}