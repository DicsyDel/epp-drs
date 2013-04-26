{include file="inc/header.tpl"}
<div align="center">
<form name="frm1" method="post" action="" id="frm1" style="padding:0px;margin:0px;">
<input name="step" type="hidden" value="placeorder" />
<input type="hidden" name="direction" id="direction" value="" />
<input name="backstep" type="hidden" value="{$backstep}">
<div style="width:600px; height:500px;border-top:3px solid #6D9632; background-image: url('/images/wiz-main-grad.jpg'); background-repeat: repeat-x;">
	<div style="margin:20px;">
		<div align="left" style="margin-bottom:30px;">
			<div class="title">{t}New client registration{/t}</div>
			<div>{t}Please provide your personal account details.{/t}</div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Username:{/t}</div>
			<div style="float:left;"><input type="text" class="text"  name="login" value="{$login}"> *</div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Name:{/t}</div>
			<div style="float:left;"><input type="text" class="text" name="name" value="{$name}"> *</div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Organization name:{/t}</div>
			<div style="float:left;"><input type="text" class="text" name="org" value="{$org}"></div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}E-mail:{/t}</div>
			<div style="float:left;"><input type="text" class="text" name="email" value="{$email}"> *</div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Country:{/t}</div>
			<div style="float:left;"> <select  id="country" name="country" class="selectbox">
				{section name="id" loop=$countries}
					<option value="{$countries[id].code}" {if $country|strtolower == $countries[id].code|strtolower || (!$country|strtolower && $countries[id].code|strtolower == $smarty.server.GEOIP_COUNTRY_CODE)}selected{/if}>{$countries[id].name}</option>
				{/section}
				</select> *
			</div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}State/region:{/t}</div>
			<div style="float:left;"><input type="text" class="text" name="state" value="{$state}"> *</div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Town/City:{/t}</div>
			<div style="float:left;"><input type="text" class="text" name="city" value="{$city}"> *</div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Postal code:{/t}</div>
			<div style="float:left;"><input type="text" class="text"  name="zipcode" value="{$zipcode}"> *</div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Address 1:{/t}</div>
			<div style="float:left;"><input type="text" class="text" name="address" value="{$address}"> *</div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Address 2:{/t}</div>
			<div style="float:left;"><input type="text" class="text" name="address2" value="{$address2}"></div>
			<div style="clear:both;"></div>
		</div>
		<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Phone:{/t}</div>
			<div style="float:left;">
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
				fc.value = '{$phone}';
				createPhoneField(fc);
				</script>
			</div>
			<div style="clear:both;"></div>
		</div>
    	<div style="margin:2px;width:550px;">
			<div style="float:left;width:150px;" align="left">{t}Fax:{/t}</div>
			<div style="float:left;">
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
				fc.value = '{$fax}';
				createPhoneField(fc);
				</script>			
			</div>
			<div style="clear:both;"></div>
		</div>
		<!-- Custom client fields: Start -->
		{assign var=add_td value = 1}
		{assign var=padding value = 15}
		{assign var=field_name_start value = 'add['}
		{assign var=field_name_end value = ']'}
		{if $fields}
			{foreach from=$fields item=v key=k}
				{assign var="fname" value=$v.name}
				    {assign var="fieldname" value="$field_name_start$fname$field_name_end"}
					{if $v.type|lower == 'text'}
						<div style="margin:2px;width:550px;" id="{$v.jsname}" style="display:{$v.display};">
							<div style="float:left;width:150px;" align="left">{$k}:</div>
							<div style="float:left;"><input type="text" class="text" name="{$fieldname}" value="{$contactinfo.$fname}">{if $v.isrequired}*{/if} {if $v.note} <i style="font-size:10px;">ex. {$v.note}</i>{/if}</div>
							<div style="clear:both;"></div>
						</div>
					{elseif $v.type|lower == 'select'}
						<div style="margin:2px;width:550px;" id="{$v.jsname}" style="display:{$v.display};">
							<div style="float:left;width:150px;" align="left">{$k}:</div>
							<div style="float:left;"><select name="{$fieldname}" class="{$classname}">
								{foreach from=$v.values item=vv key=kk}
										<option {if $contactinfo.$fname == $kk}selected{/if} value="{$kk}">{$vv}</option>
								{/foreach}
								</select>{if $v.isrequired}*{/if}</div>
							<div style="clear:both;"></div>
						</div>
					{elseif $v.type|lower == 'bool'}
						<div style="margin:2px;width:550px;" id="{$v.jsname}" style="display:{$v.display};">
							<div style="float:left;width:150px;" align="left">{$k}:</div>
							<div style="float:left;"><input name="{$fieldname}" {$dsb} type="checkbox" {if $contactinfo.$fname == 1}checked{/if} value="1">{if $v.isrequired}*{/if} {if $v.note} <i style="font-size:10px;">ex. {$v.note}</i>{/if}</div>
							<div style="clear:both;"></div>
						</div>
					{/if}
					{if $v.js}
					<script id="js_{$v.ctype}" language="Javascript" type="text/javascript">
					 var fnamestart_{$v.ctype} = '{$field_name_start}';
					 var fnameend_{$v.ctype} = '{$field_name_end}';
					 {$v.js}
					</script>
					{/if}
			{/foreach}
		{/if}
		<!-- Custom client fields: End -->
		<div style="display:{if $operaton == 'Register'}none{/if};" align="left" id="next_btn">
			<br>
			<div style="display:{if $operaton == 'Register'}none{/if};width:176px;" align="left" id="next_btn">
					<br>
					<div style="float:right;"><input id="sbmt1" type="image" src="images/wiz_btn_next.gif" onclick="SubmitForm('next')" name="sbmt1" value="{t}Next >>{/t}"></div>
					<div style="float:right;"><input id="sbmt2" type="image" src="images/wiz_btn_prev.gif" onclick="SubmitForm('back')" name="sbmt2" value="{t}<< Back{/t}"></div>
				</div>
		</div>
</div>
</div>
</form>
</div>
{include file="inc/footer.tpl"}