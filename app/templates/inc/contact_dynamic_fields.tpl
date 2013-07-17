{if $fields}
	{foreach from=$fields item=v key=k}
		{assign var="fname" value=$v.name}
		    {assign var="fieldname" value="$field_name_start$fname$field_name_end"}
			{if $v.type|lower == 'text'}
				<tr id="{$v.jsname}" style="display:{$v.display};">
				    {if $add_td == 1}<td width="3%">&nbsp; </td>{/if}
					<td style="padding-left:{if $padding === false}20{else}{$padding}{/if}px;vertical-align:middle;{$additional_row_style}">{$k}: {if $v.isrequired}*{/if}</TD>
					<td><input name="{$fieldname}" id="{$fieldname}" size=20 {if $v.iseditable === 0 || $disable_change}disabled{/if} {$dsb} type="text" class="{$classname}" value="{$contactinfo.$fname}">{if $v.note} <i style="font-size:10px;">ex. {$v.note}</i>{/if}</td>
				</tr>
			{elseif $v.type|lower == 'phone'}
				<tr id="{$v.jsname}" style="display:{$v.display};">
				    {if $add_td == 1}<td width="3%">&nbsp; </td>{/if}
					<td style="padding-left:{if $padding === false}20{else}{$padding}{/if}px;vertical-align:middle;{$additional_row_style}">{$k}: {if $v.isrequired}*{/if}</TD>
					<td>
						{assign var=nm value=$v.display_name}
						{if !$contactinfo.$nm}
							{assign var=nm value=$fname}
						{/if}
						{assign var="rnum" value=1|@rand:10000}
					
						{foreach from=$v.items item=vv key=ii}
							{if $vv.type == 'select'}
								<select name="{$fieldname}{$ii}" class="phone-field phone-{$rnum}" {if $v.iseditable === 0 || $disable_change}disabled{/if}>
									{html_options options=$vv.values}
								</select>
							{elseif $vv.type == 'text'}
								<input type="text" name="{$fieldname}{$ii}" size="{$vv.maxlength}" maxlength="{$vv.maxlength}" class="phone-{$rnum} {$classname}" {if $v.iseditable === 0 || $disable_change}disabled{/if}>
							{elseif $vv.type == 'label'}
								<span class="phone-{$rnum}">{$vv.value}</span>
							{/if}
						{/foreach}
						
						<script type="text/javascript">
						var fc = new Object();
						fc.wname = 'phone-{$rnum}'
						fc.name = '{$fieldname}';
						fc.format = '{$v.format}';
						fc.value = '{$contactinfo.$nm}';
						createPhoneField(fc);
						</script>
					</td>
				</tr>
			{elseif $v.type|lower == 'select'}
				<tr id="{$v.jsname}" style="display:{$v.display};">
				    {if $add_td == 1}<td width="3%">&nbsp; </td>{/if}
					<td style="padding-left:{if $padding === false}20{else}{$padding}{/if}px;{$additional_row_style}">{$k}: {if $v.isrequired}*{/if}</TD>
					<td><select name="{$fieldname}" id="{$fieldname}" {$dsb} {if $v.iseditable === 0 || $disable_change}disabled{/if} class="{$classname}">
						{foreach from=$v.values item=vv key=kk}
							<option {if ($contactinfo.$fname && $contactinfo.$fname == $kk) || (!$contactinfo.$fname && $v.default_selected == $kk)}selected{/if} value="{$kk}">{$vv}</option>
						{/foreach}
						</select>
					</td>
				</tr>
			{elseif $v.type|lower == 'bool'}
				<tr id="{$v.jsname}" style="display:{$v.display};">
				    {if $add_td == 1}<td width="3%">&nbsp; </td>{/if}
					<td style="padding-left:{if $padding === false}20{else}{$padding}{/if}px;vertical-align:middle;{$additional_row_style}">{$k}: {if $v.isrequired}*{/if}</TD>
					<td><input name="{$fieldname}" id="{$fieldname}" {if $v.iseditable === 0 || $disable_change}disabled{/if} {$dsb} type="checkbox" value="{$contactinfo.$fname}">{if $v.note} <i style="font-size:10px;">ex. {$v.note}</i>{/if}</td>
				</tr>
			{/if}
			{if $v.js}
			<script id="js_{$v.ctype}" language="Javascript" type="text/javascript">
			 var fnamestart_{$type} = '{$field_name_start}';
			 var fnameend_{$type} = '{$field_name_end}';
			 {$v.js}
			</script>
			{/if}
	{/foreach}
{/if}