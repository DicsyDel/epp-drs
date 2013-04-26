{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="General" color="Gray"}
        <tr>
            <td nowrap="nowrap" width="20%">Nameserver IP address:</td>
            <td><input name="host" type="text" class="text" id="su_login" value="{$ns.host}" /></td>
        </tr>
        <tr>
            <td nowrap="nowrap">Port:</td>
            <td><input name="port" type="text" class="text" id="ssh_port" value="{if $ns.port}{$ns.port}{else}22{/if}" /></td>
        </tr>
        <tr>
            <td nowrap="nowrap">System user:</td>
            <td><input name="username" type="text" class="text" id="su_pass" value="{if $ns.username}{$ns.username}{else}root{/if}" /></td>
        </tr>
        <tr>
            <td nowrap="nowrap">Password:</td>
            <td><input name="password" type="password" class="text" id="su_pass" value="{if $id}******{/if}" /></td>
        </tr>
        <tr>
            <td nowrap="nowrap">Path to rndc binary:</td>
            <td><input name="rndc_path" type="text" class="text"  id="ssh_port" value="{if $ns.rndc_path}{$ns.rndc_path}{else}/usr/sbin/rndc{/if}" /></td>
        </tr>
        <tr>
            <td nowrap="nowrap">Path to zone files folder:</td>
            <td><input name="named_path" type="text" class="text"  id="ssh_port" value="{if $ns.named_path}{$ns.named_path}{else}/var/named{/if}" /></td>
        </tr>
        <tr>
            <td nowrap="nowrap">Path to named.conf:</td>
            <td><input name="namedconf_path" type="text" class="text"  id="ssh_port" value="{if $ns.namedconf_path}{$ns.namedconf_path}{else}/etc/named.conf{/if}" /></td>
        </tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}