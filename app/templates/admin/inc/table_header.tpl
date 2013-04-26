{if !$nofilter}
<table border="0" width="100%" cellspacing="0" cellpadding="0" height="40">
	<tr>
		<td align="center" nowrap width="10">&nbsp;</td>
		<td width="310" align="left" valign="bottom">{if $filter}{$filter}{/if}</td>
		<td colspan="4" align="left" valign="bottom">{$paging}</td>
		<td align="center" width="60" nowrap>&nbsp;</td>
	</tr>
</table>
{/if}

{if $table_header_text}
<table border="0" width="100%" cellspacing="0" cellpadding="0" height="40">
	<tr>
		<td align="center" nowrap width="10">&nbsp;</td>
		<td width="310" align="left" valign="bottom">
		  <div>
            	<table border="0" cellpadding="0" cellspacing="0">
            		<tr>
            			<td width="7"><div class="TableHeaderLeft"></div></td>
            			<td><div class="TableHeaderCenter"></div></td>
            			<td><div class="TableHeaderCenter"></div></td>
            			<td width="7"><div class="TableHeaderRight"></div></td>
            		</tr>
            		<tr bgcolor="#C3D9FF">
            			<td width="7" class="TableHeaderCenter"></td>
            			<td nowrap style="padding-bottom:5px;">
            			 {$table_header_text}
            			</td>
            			<td align="left" nowrap></td>
            			<td width="7" class="TableHeaderCenter"></td>
            		</tr>
            	</table>
            </div>
		</td>
		<td colspan="4" align="left" valign="bottom"></td>
		<td align="center" nowrap>&nbsp;</td>
	</tr>
</table>
{/if}

<table border="0" cellpadding="0" cellspacing="0" class="Webta_Table" width="100%">
<tr>
	<td width="7"><div class="TableHeaderLeft"></div></td>
	<td><div class="TableHeaderCenter"></div></td>
	<td width="7"><div class="TableHeaderRight"></div></td>
</tr>
<tr>
	<td width="7" class="TableHeaderCenter"></td>
	<td><table width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td>
		
		<table id="Webta_Settings" width="100%" cellpadding="0" cellspacing="0">
		<tr><td valign="top">
