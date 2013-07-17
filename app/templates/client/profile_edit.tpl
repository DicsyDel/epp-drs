{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Profile information"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
    	<tr>
    		<td>{t}Name:{/t} *</td>
    		<td><input type="text" class="text" name="name" value="{$attr.name}" /></td>
    	</tr>
    	<tr>
    		<td>{t}Organization:{/t}</td>
    		<td><input type="text" class="text" name="org" value="{$attr.org}" /></td>
    	</tr>
    	<tr>
    		<td>{t}Business or Occupation:{/t}</td>
    		<td><input type="text" class="text" name="business" value="{$attr.business}" /></td>
    	</tr>
    	<tr>
    		<td>{t}Address:{/t} *</td>
    		<td><input type="text" class="text" name="address" value="{$attr.address}" /></td>
    	</tr>
    	<tr>
    		<td>{t}Address 2:{/t}</td>
    		<td><input type="text" class="text" name="address2" value="{$attr.address2}" /></td>
    	</tr>
    	<tr>
    		<td>{t}City / Town:{/t} *</td>
    		<td><input type="text" class="text" name="city" value="{$attr.city}" /></td>
    	</tr>
    	<tr>
    		<td>{t}State:{/t} *</td>
    		<td><input type="text" class="text" name="state" value="{$attr.state}" /></td>
    	</tr>
    	<tr>
    		<td>{t}Country:{/t} *</td>
    		<td><select class="text" id="country" name="country">
			{section name="id" loop=$countries}
				<option value="{$countries[id].code}" {if $attr.country|strtolower == $countries[id].code|strtolower || (!$attr.country|strtolower && $countries[id].code|strtolower == 'gr')}selected{/if}>{$countries[id].name}</option>
			{/section}
		</select></td>
    	</tr>
    	<tr>
    		<td>{t}Postal code:{/t} *</td>
    		<td><input type="text" class="text" name="zipcode" value="{$attr.zipcode}" /></td>
    	</tr>
    	<tr>
			<td>{t}Phone:{/t} *</TD>
			<td>
				{assign var="fieldname" value="phone"}
				{assign var="rnum" value=1|@rand:10000}
				{foreach from=$phone_widget.items item=vv key=ii}
					{if $vv.type == 'select'}
						<select name="{$fieldname}{$ii}" class="phone-field phone-{$rnum}">
							{html_options options=$vv.values}
						</select>
					{elseif $vv.type == 'text'}
						<input type="text" name="{$fieldname}{$ii}" size="{$vv.maxlength}" maxlength="{$vv.maxlength}" class="phone-{$rnum} text">
					{elseif $vv.type == 'label'}
						<span class="phone-{$rnum}">{$vv.value}</span>
					{/if}
				{/foreach}
				
				<script type="text/javascript">
				var fc = new Object();
				fc.name = '{$fieldname}';
				fc.wname = 'phone-{$rnum}';
				fc.format = '{$phone_widget.format}';
				fc.value = '{$attr.phone}';
				createPhoneField(fc);
				</script>
			</td>
		</tr>
    	<tr>
			<td>{t}Fax:{/t}</TD>
			<td>
				{assign var="fieldname" value="fax"}
				{assign var="rnum" value=1|@rand:10000}
				{foreach from=$phone_widget.items item=vv key=ii}
					{if $vv.type == 'select'}
						<select name="{$fieldname}{$ii}" class="phone-field phone-{$rnum}">
							{html_options options=$vv.values}
						</select>
					{elseif $vv.type == 'text'}
						<input type="text" name="{$fieldname}{$ii}" size="{$vv.maxlength}" maxlength="{$vv.maxlength}" class="phone-{$rnum} text">
					{elseif $vv.type == 'label'}
						<span class="phone-{$rnum}">{$vv.value}</span>
					{/if}
				{/foreach}
				
				<script type="text/javascript">
				var fc = new Object();
				fc.name = '{$fieldname}';
				fc.wname = 'phone-{$rnum}';
				fc.format = '{$phone_widget.format}';
				fc.value = '{$attr.fax}';
				createPhoneField(fc);
				</script>				
			</td>
		</tr>
    	{assign var=add_td value = 0}
    	{assign var=padding value = 1}
    	{assign var=field_name_start value = 'add['}
    	{assign var=field_name_end value = ']'}
    	{assign var=classname value = 'text'}
    	{include file="inc/contact_dynamic_fields.tpl" fields=$additional_fields}
        {include file="client/inc/intable_footer.tpl" color="Gray"}
	{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}