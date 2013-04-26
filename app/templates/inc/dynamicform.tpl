{foreach from=$fields key=key item=field}
		{if ($field.type == 'text')}
		<tr>
			<td style="padding-left:20px; width: 15%">{$key}: </td>
			<td><input type="text" class="text" name="{$field.name}" value="{if $post.$key}{$post.$key}{elseif $field.value}{$field.value}{/if}"/> {if $field.required}*{/if}
			{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
			</td>
		</tr>
		{elseif $field.type == 'checkbox'}
		<tr>
    		<td style="padding-left:20px; width: 15%">{$key}:</td>
    		<td><input type="checkbox" name="{$field.name}" {if $post.$key == 1}checked{/if} value="1" />
    		{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}</td>
    	</tr>		
		{elseif $field.type == 'phone'}
		<tr>
			<td style="padding-left:20px; width: 15%">{$key}:</TD>
			<td>
				{assign var="fieldname" value=$field.name}
				{assign var="rnum" value=1|@rand:10000}
				{foreach from=$phone_widget.items item=vv key=ii}
					{if $vv.type == 'select'}
						<select name="{$fieldname}{$ii}" class="phone-field phone-{$rnum}">
							{html_options options=$vv.values}
						</select>
					{elseif $vv.type == 'text'}
						<input type="text" class="text" name="{$fieldname}{$ii}" size="{$vv.maxlength}" maxlength="{$vv.maxlength}" class="phone-{$rnum} {$classname}">
					{elseif $vv.type == 'label'}
						<span class="phone-{$rnum}">{$vv.value}</span>
					{/if}
				{/foreach}
				
				<script type="text/javascript">
				var fc = new Object();
				fc.name = '{$fieldname}';
				fc.wname = 'phone-{$rnum}';
				fc.format = '{$phone_widget.format}';
				fc.value = '{$post.$key}';
				createPhoneField(fc);
				</script>
				
				{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
			</td>
		</tr>
		{elseif $field.type == 'select'}
		<tr>
			<td style="padding-left:20px; width: 15%">{$key}: </td>
			<td><select class="text" name="{$field.name}">
					{foreach from=$field.values key=vkey item=vfield}
						<option {if $vkey == $post.$key}selected{/if} value="{$vkey}">{$vfield}</option>
					{/foreach}
				</select> {if $field.required}*{/if}
				
				{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
			</td>
		</tr>
		{elseif $field.type == 'date'}
	    <tr>
			<td style="padding-left:20px; width: 15%">{$key}: </td>
			<td style="vertical-align:middle;">
            <div style="display:inline;vertical-align:middle;"><select name="{$field.name}_m" class="text">
                <option {if $post.$key == '01'}selected{/if} value="01">01</option>
                <option {if $post.$key == '02'}selected{/if} value="02">02</option>
                <option {if $post.$key == '03'}selected{/if} value="03">03</option>
                <option {if $post.$key == '04'}selected{/if} value="04">04</option>
                <option {if $post.$key == '05'}selected{/if} value="05">05</option>
                <option {if $post.$key == '06'}selected{/if} value="06">06</option>
                <option {if $post.$key == '07'}selected{/if} value="07">07</option>
                <option {if $post.$key == '08'}selected{/if} value="08">08</option>
                <option {if $post.$key == '09'}selected{/if} value="09">09</option>
                <option {if $post.$key == '10'}selected{/if} value="10">10</option>
                <option {if $post.$key == '11'}selected{/if} value="11">11</option>
                <option {if $post.$key == '12'}selected{/if} value="12">12</option>
            </select></div>
            <div style="display:inline;margin-left:5px;vertical-align:middle;"> / </div>
            <div style="display:inline;margin-left:5px;vertical-align:middle;">
            <select name="{$field.name}_Y" class="text">
                <option {if $post.$key == '08'}selected{/if} value="08">2008</option>
                <option {if $post.$key == '09'}selected{/if} value="09">2009</option>
                <option {if $post.$key == '10'}selected{/if} value="10">2010</option>
                <option {if $post.$key == '11'}selected{/if} value="11">2011</option>
                <option {if $post.$key == '12'}selected{/if} value="12">2012</option>
                <option {if $post.$key == '13'}selected{/if} value="13">2013</option>
                <option {if $post.$key == '14'}selected{/if} value="14">2014</option>
                <option {if $post.$key == '15'}selected{/if} value="15">2015</option>
                <option {if $post.$key == '16'}selected{/if} value="16">2016</option>
                <option {if $post.$key == '17'}selected{/if} value="17">2017</option>
                <option {if $post.$key == '18'}selected{/if} value="18">2018</option>
                <option {if $post.$key == '19'}selected{/if} value="19">2019</option>
                <option {if $post.$key == '20'}selected{/if} value="20">2020</option>
            </select>
            </div>
			<div style="display:inline;vertical-align:middle;">{if $field.required}*{/if}</div>
			{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
	        </td>
		</tr>
		{/if}
{/foreach}