<div {if $intableid}id="{$intableid}"{/if} style="display:{$visible};padding: {if $intablepadding}{$intablepadding}{else}7{/if}px;">
<script type="text/javascript" src="/js/class.SelectControl.js"></script>
<form name="contactchoise-{$type}" class="contactchoise" onSubmit="return false;">
	<input type="hidden" name="type" value="{$type}">
	<input type="hidden" name="TLD" value="{$TLD}">
	<input type="hidden" name="groupname" value="{$groupname}">

<table border="0" cellpadding="0" cellspacing="0" width="100%">
{if !$noheaderline}
<tr>
	<td width="7"><div class="TableHeaderLeft_Gray"></div></td>
	<td>
	<div id="webta_table_header{$header_id}" class="SettingsHeader_Gray">
		<strong>{t ctype=$contact_name}Select %1 contact{/t}</strong>
	</div>
	</td>
	<td width="7"><div class="TableHeaderRight_Gray"></div></td>
</tr>
{/if}
<tbody id="Webta_InnerTable_{$header}">
<tr>
	<td width="6" class="TableHeaderCenter_Gray" style="border-left:1px solid #dddddd;border-bottom:1px solid #BBBBBB;background-color:#F9F9F9;">&nbsp;</td>
	<td class="Inner_Gray" style="border-bottom:1px solid #BBBBBB;background-color:#F9F9F9;">
		<table width="95%" style="margin:10px;" cellspacing="0" cellpadding="2" {if $section_closed}style="display: none;"{/if}>		
		<tr id="error_{$type}" style="display:none;">
			<td colspan="2" width="100%" align="left">
				<div class="Webta_ErrMsg" style="width:auto;margin-right:10px;" id="error_text_{$type}"></div>
			</td>
		</tr>
		
		
		<tr>
			<td style="height:270px; vertical-align:top;">
				
				<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:1px" style=" width: expression('97%')">
				  <tr>
				    <td width="200" align="left" valign="bottom" class="filter-wrapper">{$filter}</td>
				    <td>{if $paging}{$paging}{/if}</td>
				  </tr>
				</table>
			
				<table class="Webta_Items choise" rules="groups" width="100%" style="border:expression('1px solid #A2BBDD'); width: expression('97%')" cellpadding="4" id="Webta_Items">
				<tbody style="border:expression('none')">
					{foreach from=$rows item=row}
					<tr>
						<td class="Item" valign="top"
							style="cursor:pointer; -moz-user-select:none;"
							onmouseover="$(this).addClassName('Hover')" 
							onmouseout="$(this).removeClassName('Hover')"
							onclick="ContactList_OnClickItem(this)">
						  <div style="display:  inline"><input type="radio" name="clid" value="{$row.clid}"></div>
						  {$row.title}
						</td>
					</tr>
					{foreachelse}
					<tr>
						<td align="center">{t}No contacts found{/t}</td>
					</tr>
					{/foreach}
				</tbody>
				</table>
			</td>
		</tr>
		

	   	<tr>
			<td style="padding: 0px; font-size: 10px;">&nbsp;</td>
		</tr>	
		</table>
	</td>
	<td width="6" class="TableHeaderCenter_Gray" style="border-right:1px solid #dddddd;border-bottom:1px solid #BBBBBB;background-color:#F9F9F9;">&nbsp;</td>
</tr>
</tbody>
</table>
<table width="100%" style="border:1px solid #BBBBBB; border-top:1px; background-color:#F0F0F0;" cellspacing="0" cellpadding="0" style="padding:0px;">
	<tr>
   		<td colspan="2" style="padding-left:5px;border-right:1px solid white;border-bottom:1px solid white;padding:3px;">
   			<input type="button" onclick="ContactList_OnSelect(this);" name="smbt_cnt" value="{t}Select{/t}" class="btn" style="vertical-align:middle;">
   			<span class="loader" style="display:none;"><img style="vertical-align:middle;" src="images/snake-loader.gif"> {t}Loading. Please wait...{/t}</span>
   		</td>
   	</tr>
</table>
</div>