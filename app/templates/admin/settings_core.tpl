{include file="admin/inc/header.tpl" upload_files=true}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Service owner account" color="Gray"}
		<tr>
			<td width="18%">Login:</td>
			<td width="82%"><input name="login" type="text" class="text" id="login" value="{$login}" size="30"></td>
		</tr>
		<tr>
			<td>Password:</td>
			<td><input name="pass" type="password" class="text" id="pass" value="{$pass}" size="30" autocomplete="off"></td>
		</tr>
		<tr>
			<td>Confirm password:</td>
			<td><input name="pass2" type="password" class="text" id="pass2" value="{$pass}" size="30" autocomplete="off"></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="E-Mail delivery settings" color="Gray"}
		<tr>
			<td width="18%">SMTP connection:</td>
			<td width="82%"><input name="email_dsn" type="text" class="text" id="email_dsn" value="{$email_dsn}" size="30"> (user:password@host:port. Leave empty to use default <i>mail()</i> settings)</td>
		</tr>
		<tr>
			<td>Copy all messages to service owner:</td>
			<td><input type="checkbox" {if $email_copy}checked{/if} name="email_copy" value="1" /></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Service owner email settings" color="Gray"}
		<tr>
			<td width="18%">E-mail:</td>
			<td width="82%"><input name="email_admin" type="text" class="text" id="email_admin" value="{$email_admin}" size="30"></td>
		</tr>
		<tr>
			<td>Name:</td>
			<td><input name="email_adminname" type="text" class="text" id="email_adminname" value="{$email_adminname}" size="30">
		</td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Support email settings" color="Gray"}
		<tr>
			<td width="18%">E-mail:</td>
			<td width="82%"><input name="support_email" type="text" class="text" id="support_email" value="{$support_email}" size="30"></td>
		</tr>
		<tr>
			<td>Name:</td>
			<td><input name="support_name" type="text" class="text" id="support_name" value="{$support_name}" size="30">
		</td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		{include file="admin/inc/intable_header.tpl" header="Company" color="Gray"}
		<tr>
			<td width="18%">Company name:</td>
			<td width="82%"><input name="company_name" type="text" class="text" id="company_name" value="{$company_name}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Site URL:</td>
			<td width="82%"><select name="site_url_scheme">
				<option>http</option>
				<option{if $site_url_https} selected{/if}>https</option>
			</select>://
			<input name="site_url" type="text" class="text" id="site_url" value="{$site_url}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Company logo:</td>
			<td width="82%"><input name="logo" type="file" class="text" size="30"></td>
		</tr>
		
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Billing" color="Gray"}
		<tr>
			<td width="18%">Billing currency (HTML):</td>
			<td width="82%"><input name="currency" type="text" class="text" id="currency" value="{$currency}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Billing currency (ISO):</td>
			<td width="82%"><input name="currencyISO" type="text" class="text" id="currencyISO" value="{$currencyISO}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Minimum balance deposit amount:</td>
			<td width="82%"><input name="min_deposit" type="text" class="text" value="{$min_deposit}" size="30"  /></td>
		</tr>		
		<tr>
			<td width="18%">Bill only from balance:</td>
			<td width="82%"><input name="prepaid_mode" type="checkbox" value="1" {if $prepaid_mode}checked{/if}></td>
		</tr>
		<tr>
			<td width="18%">Invoice ID format:</td>
			<td width="82%"><input name="invoice_customid_format" value="{$invoice_customid_format}" class="text" size="30"> <span class="Webta_Ihelp">Example: ABC-%id%</span></td>
		</tr>
		
		
		
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Registry options" color="Gray"}
		<tr>
			<td width="18%">Default NS 1:</td>
			<td width="82%"><input name="ns1" type="text" class="text" id="ns1" value="{$ns1}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Default NS 2:</td>
			<td width="82%"><input name="ns2" type="text" class="text" id="ns2" value="{$ns2}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Enable managed DNS:<br>{if $enable_managed_dns}<span style="font-size:10px;">(<a href="ns_view.php" style="font-size:10px;">Manage Nameservers</a>)</span>{/if}</td>
			<td width="82%"><input name="enable_managed_dns" type="checkbox" {if $enable_managed_dns}checked{/if} value="1"><span class="Webta_Ihelp">Enable only if Managed DNS is configured and you know what you are doing!</span></td>
		</tr>
		<tr>
			<td width="18%">Enable expiring domains auto delete:</td>
			<td width="82%"><input name="auto_delete" type="checkbox" {if $auto_delete}checked{/if} value="1"></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
				
		{include file="admin/inc/intable_header.tpl" header="Registrant is allowed to add/edit the following types of DNS records:" color="Gray"}
		<tr>
			<td width="18%">A:</td>
			<td width="82%"><input name="allow_A_record" type="checkbox" {if $allow_A_record}checked{/if} id="ns2" value="1"></td>
		</tr>
		<tr>
			<td width="18%">MX:</td>
			<td width="82%"><input name="allow_MX_record" type="checkbox" {if $allow_MX_record}checked{/if} id="ns2" value="1"></td>
		</tr>
		<tr>
			<td width="18%">NS:</td>
			<td width="82%"><input name="allow_NS_record" type="checkbox" {if $allow_NS_record}checked{/if} id="ns2" value="1"></td>
		</tr>
		<tr>
			<td width="18%">CNAME:</td>
			<td width="82%"><input name="allow_CNAME_record" type="checkbox" {if $allow_CNAME_record}checked{/if} id="ns2" value="1"></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Client options" color="Gray"}
		<tr>
			<td width="18%">Default VAT %:</td>
			<td width="82%"><input name="user_vat" type="text" class="text" value="{$user_vat}"></td>
		</tr>
		<tr>
			<td width="18%">Manual approval of the client:</td>
			<td width="82%"><input name="client_manual_approval" type="checkbox" class="text" value="1" {if $client_manual_approval}checked{/if}></td>
		</tr>
		
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Logging settings" color="Gray"}
		<tr>
			<td width="18%">Rotate EPP-DRS log every:</td>
			<td width="82%"><input name="rotate_log_every" type="text" class="text" id="ns2" value="{$rotate_log_every}" size="5"> days</td>
		</tr>
		<tr>
			<td width="18%">Mail poll messages to admin:</td>
			<td width="82%"><input name="mail_poll_messages" type="checkbox" value="1" {if $mail_poll_messages}checked{/if} /></td>
		</tr>
		
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Binary tools" color="Gray"}
		<tr>
			<td width="18%">Path to zendid binary:</td>
			<td width="82%"><input name="zendid_path" type="text" class="text" id="zendid_path" value="{$zendid_path}" size="30"> <span class="Webta_Ihelp">Used to generate Zend Host ID</span></td>
		</tr>
		<tr>
			<td width="18%">Path to php binary:</td>
			<td width="82%"><input name="php_path" type="text" class="text" id="php_path" value="{$php_path}" size="30"></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}

		{include file="client/inc/intable_header.tpl" header="Interface settings" color="Gray"}
		<tr>
			<td width="18%">Display inline help:</td>
			<td width="82%"><input name="inline_help" {if $inline_help}checked{/if} type="checkbox" id="inline_help" value="1"></td>
		</tr>
		<tr>
			<td width="18%">Phone format:</td>
			<td width="82%"><input name="phone_format" type="text" class="text" id="phone_format" value="{$phone_format}" size="30"> <span class="Webta_Ihelp">Example: +[cc]-[2-4]-[4-10]</span></td>
		</tr>
		<tr>
			<td width="18%">Default country:</td>
			<td width="82%"><select name="default_country">
				<option></option>
				{html_options options=$country_list selected=$default_country}
			</select></td>
		</tr>
		
		
		{include file="client/inc/intable_footer.tpl" color="Gray"}

	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}
