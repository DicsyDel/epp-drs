{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Billing options" color="Gray"}
		<tr>
    		<td valign="top">Pricing package:</td>
    		<td>
    		  <select name="packageid" class="text">
    		      <option {if $attr.packageid == 0}selected{/if} value="0">Defaut</option>
    		      {section name=id loop=$packages}
    		          <option {if $attr.packageid == $packages[id].id}selected{/if} value="{$packages[id].id}">{$packages[id].name}</option>
    		      {/section}
    		  </select><br/>
    		  <label><input type="checkbox" name="package_fixed" value="1" {if $attr.package_fixed}checked{/if}/> Fixed. No automatic upgrade/downgrade</label>
    		</td>
    	</tr>
    	<tr>
    		<td>VAT:</td>
    		<td><input type="text" class="text" name="vat" value="{$attr.vat}" size="2" />%</td>
    	</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{php}$this->assign('ENABLE_PREREGISTRATION', ENABLE_EXTENSION::$PREREGISTRATION);{/php}
		{if $ENABLE_PREREGISTRATION}
		{include file="admin/inc/intable_header.tpl" header="Enable the following features for this client" color="Gray"}
		<tr>
    		<td colspan="2">
    			<input type="checkbox" {if $Client->GetSettingValue('domain_preorder') == 1}checked{/if} name="settings[domain_preorder]" value="1" style="vertical-align:middle;"> Expiring domains pre-ordering
    		</td>
    	</tr>
    	<tr>
    		<td colspan="2">
    			<input type="checkbox" {if $Client->GetSettingValue('bill_for_domain_preorder') == 1}checked{/if} name="settings[bill_for_domain_preorder]" value="1" style="vertical-align:middle;"> Bill for pre-ordered domains
    		</td>
    	</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		{/if}
	
		{include file="admin/inc/intable_header.tpl" header="Account information" color="Gray"}
		<tr>
    		<td>Login:</td>
    		<td><input type="text" class="text" name="login" value="{$attr.login}" /></td>
    	</tr>
    	<tr>
    		<td>Password:</td>
    		<td><input type="password" class="text" name="password" value="******" /></td>
    	</tr>
    	<tr>
    		<td>E-mail:</td>
    		<td><input type="text" class="text" name="email" value="{$attr.email}" /></td>
    	</tr>
    	
    	<tr>
    		<td>Name:</td>
    		<td><input type="text" class="text" name="name" value="{$attr.name}" /></td>
    	</tr>
    	<tr>
    		<td>Organization:</td>
    		<td><input type="text" class="text" name="org" value="{$attr.org}" /></td>
    	</tr>
    	<tr>
    		<td>Business or Occupation:</td>
    		<td><input type="text" class="text" name="business" value="{$attr.business}" /></td>
    	</tr>
    	<tr>
    		<td>Address:</td>
    		<td><input type="text" class="text" name="address" value="{$attr.address}" /></td>
    	</tr>
    	<tr>
    		<td>Address 2:</td>
    		<td><input type="text" class="text" name="address2" value="{$attr.address2}" /></td>
    	</tr>
    	<tr>
    		<td>City / Town:</td>
    		<td><input type="text" class="text" name="city" value="{$attr.city}" /></td>
    	</tr>
    	<tr>
    		<td>State:</td>
    		<td><input type="text" class="text" name="state" value="{$attr.state}" /></td>
    	</tr>
    	<tr>
    		<td>Country:</td>
    		<td><select class="text" id="country" name="country">
			{section name="id" loop=$countries}
				<option value="{$countries[id].code}" {if $attr.country|strtolower == $countries[id].code|strtolower || (!$attr.country|strtolower && $countries[id].code|strtolower == 'us')}selected{/if}>{$countries[id].name}</option>
			{/section}
		</select></td>
    	</tr>
    	<tr>
    		<td>Postal code:</td>
    		<td><input type="text" class="text" name="zipcode" value="{$attr.zipcode}" /></td>
    	</tr>
    	<tr>
			<td>{t}Phone:{/t}</TD>
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
				fc.wname = 'phone-{$rnum}'
				fc.name = '{$fieldname}';
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
				fc.wname = 'phone-{$rnum}';
				fc.name = '{$fieldname}';
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
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}