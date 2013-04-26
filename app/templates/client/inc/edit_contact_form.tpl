<div {if $intableid}id="{$intableid}"{/if} style="display:{$visible};padding: {if $intablepadding}{$intablepadding}{else}7{/if}px;">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
{if !$noheaderline}
<tr>
	<td width="7"><div class="TableHeaderLeft_Gray"></div></td>
	<td>
	<div id="webta_table_header{$header_id}" class="SettingsHeader_Gray">
		<strong>{t ctype=$contact_name}%1 contact details{/t}</strong>
	</div>
	</td>
	<td width="7"><div class="TableHeaderRight_Gray"></div></td>
</tr>
{/if}
<tbody id="Webta_InnerTable_{$header}">
<tr>
	<td width="6" class="TableHeaderCenter_Gray" style="border-left:1px solid #dddddd;border-bottom:1px solid #BBBBBB;background-color:#F9F9F9;">&nbsp;</td>
	<td class="Inner_Gray" style="border-bottom:1px solid #BBBBBB;background-color:#F9F9F9;">
		<table width="100%" style="margin:10px;" cellspacing="0" cellpadding="2" {if $section_closed}style="display: none;"{/if}>		
		<tr id="error_{$type}" style="display:none;">
			<td colspan="2" width="100%" align="left">
				<div class="Webta_ErrMsg" style="width:auto;margin-right:10px;" id="error_text_{$type}"></div>
			</td>
		</tr>
	   	{include file="inc/contact_dynamic_fields.tpl"}
	   	<tr>
			<td colspan="2" style="padding: 0px; font-size: 10px;">&nbsp;</td>
		</tr>	
		</table>
	</td>
	<td width="6" class="TableHeaderCenter_Gray" style="border-right:1px solid #dddddd;border-bottom:1px solid #BBBBBB;background-color:#F9F9F9;">&nbsp;</td>
</tr>
</tbody>
</table>

{if $disclose}
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="7"><div class="TableHeaderLeft_Gray"></div></td>
	<td>
	<div id="webta_table_header{$header_id}" class="SettingsHeader_Gray">
		<strong>{t}Whois disclose options{/t}</strong>
	</div>
	</td>
	<td width="7"><div class="TableHeaderRight_Gray"></div></td>
</tr>
<tbody id="Webta_InnerTable_{$header}">
<tr>
	<td width="6" class="TableHeaderCenter_Gray" style="border-left:1px solid #dddddd;border-bottom:1px solid #BBBBBB;background-color:#F9F9F9;">&nbsp;</td>
	<td class="Inner_Gray" style="border-bottom:1px solid #BBBBBB;background-color:#F9F9F9;">
		<table width="100%" style="margin:10px;" cellspacing="0" cellpadding="2" {if $section_closed}style="display: none;"{/if}>		
		<tr id="error_{$type}" style="display:none;">
			<td colspan="2" width="100%" align="left">
				<div class="Webta_ErrMsg" style="width:auto;margin-right:10px;" id="error_text_{$type}"></div>
			</td>
		</tr>


        <tr>
           <td colspan="2">{t}Allow following details to appear in domain whois response:{/t}<br/><br/></td>
        </tr>
    	    {foreach key=dname item=dvalue from=$disclose}
    	    <tr>
    	        <td width="1%"><input type="checkbox" {if $dvalue.value == 1}checked{/if} name="disclose[{$dvalue.name}]" value="1" /></td>
        		<td>{$dname}</td>
        	</tr>
    	    {/foreach}
        </table>
    </td>
	<td width="6" class="TableHeaderCenter_Gray" style="border-right:1px solid #dddddd;border-bottom:1px solid #BBBBBB;background-color:#F9F9F9;">&nbsp;</td>
</tr>
</tbody>
</table>
{/if}


<table width="100%" style="border:1px solid #BBBBBB; border-top:1px; background-color:#F0F0F0;" cellspacing="0" cellpadding="0" style="padding:0px;">
	<tr>
   		<td colspan="2" style="padding-left:5px;border-right:1px solid white;border-bottom:1px solid white;padding:3px;">
   			<input id="contact_edit_button_{$type}" type="button" onclick="SaveContact('{$type}', '{$TLD}', domainid);" name="smbt_cnt" value="{t}Save changes{/t}" class="btn" style="vertical-align:middle;"> &nbsp;
   			<input id="contact_cancel_button_{$type}" type="button" onclick="ContactCancelSaving('{$type}', '{$TLD}');" name="smbt_cnt" value="{t}Cancel{/t}" class="btn" style="vertical-align:middle;">
   			<span id="contact_create_loader_{$type}" style="display:none;"><img style="vertical-align:middle;" src="images/snake-loader.gif"> {t}Updating contact. Please wait...{/t}</span>
   		</td>
   	</tr>
</table>
</div>