{include file="client/inc/header.tpl"}
<link rel="stylesheet" href="/css/SelectControl.css" type="text/css" />
<style type="text/css">
{literal}

{/literal}
</style>
<script type="text/javascript" src="/js/class.SelectControl.js"></script>
    
{if $SelectedDomain}
	{php}
    	// Do not edit PHP code below!
    	$this->assign('header_text',sprintf(_("Now managing %s"), $this->_tpl_vars["SelectedDomain"]->GetHostName()));
    {/php}

	{include file="client/inc/table_header.tpl" nofilter=1 table_header_text=$header_text}

<div style="padding:10px;">
<table width="100%" cellpadding="0" cellspacing="0">
      <thead>
      <tr>
	      <th width="33%" class="dashbord_th" align="left">
	        <table width="100%" border="0" cellpadding="0" cellspacing="0">
	                <tr>
	                  <td width="1%"><div  class="TableHeaderLeft_Gray"></div></td>
	                  <td class="SettingsHeader_Gray" style="cursor:default;"><img src="images/details.png" valign="middle" /> <strong>{t}Details{/t}</strong></td>
	                  <td width="1%"><div class="TableHeaderRight_Gray"></div></td>
	                </tr>
	        </table>
	      </th>
	      <th>&nbsp;</th>
	      <th width="33%" class="dashbord_th" align="left">
	        <table width="100%" border="0" cellpadding="0" cellspacing="0">
	                <tr>
	                  <td width="1%"><div  class="TableHeaderLeft_Gray"></div></td>
	                  <td class="SettingsHeader_Gray" style="cursor:default;"><img src="images/contact.png" valign="middle" /> <strong>{t}Contacts{/t}</strong></td>
	                  <td width="1%"><div class="TableHeaderRight_Gray"></div></td>
	                </tr>
	          </table>
	      </th>
	      <th>&nbsp;</th>
	      <th width="33%" class="dashbord_th" align="left">
	        <table width="100%" border="0" cellpadding="0" cellspacing="0">
	                <tr>
	                  <td width="1%"><div  class="TableHeaderLeft_Gray"></div></td>
	                  <td class="SettingsHeader_Gray" style="cursor:default;"><img src="images/wrench.png" valign="middle" /> <strong>{t}Tasks{/t}</strong></td>
	                  <td width="1%"><div class="TableHeaderRight_Gray"></div></td>
	                </tr>
	          </table>
	      </th>
      </tr>
      </thead>
      
      <tbody>
      	<tr valign="top">
      		<td style="background-color: #f9f9f9; padding:10px;">
      			<table width="100%" cellpadding="1" cellspacing="2">
                    <tr style="border-bottom:1px dotted #CCCCCC;">
                      <td>{t}Status{/t}: </td>
                      <td>{include file="inc/domain_status.tpl" status=$SelectedDomain->Status id=$SelectedDomain->ID}</td>
                    </tr>
                    <tr>
                      <td>{t}Created{/t}:</td>
                      <td>{if $SelectedDomain->CreateDate != 0}{$SelectedDomain->CreateDate|eppdrs_date_format}{/if}</td>
                    </tr>
                    <tr>
                      <td nowrap="nowrap">{t}Expiration date{/t}:</td>
                      <td>{if $SelectedDomain->ExpireDate != 0}{$SelectedDomain->ExpireDate|eppdrs_date_format}{/if}</td>
                    </tr>
                    <tr>
                      <td nowrap="nowrap">{t}Lock status{/t}:</td>
                      <td>
                      	{if $SelectedDomain->Status == "Delegated" && $SelectedDomain->IsLocked}
							<img alt="{t}Locked{/t}" src="images/true.gif"> {t}Locked{/t}
						{else}
							<img alt="{t}UnLocked{/t}" src="images/false.gif"> {t}Unlocked{/t}
						{/if}
				      </td>
                    </tr>
                    
                    {assign var="nameservers" value=$SelectedDomain->GetNameserverList()}
                    {section name=id loop=$nameservers}
						{if $nameservers[id]}
	                    <tr>
	                      <td>{t}Namesever{/t} #{$smarty.section.id.iteration}: </td>
	                      <td>{$nameservers[id]}</td>
	                    </tr>
	                    {/if}
                    {/section}
                    
                    {if $SelectedDomain->AuthCode}
                    <tr>
                      <td>{t}Password{/t}: </td>
                      <td>{$SelectedDomain->AuthCode}</td>
                    </tr>
                    {/if}
                    
                    {foreach from=$SelectedDomain->GetFlagList() item="flag"}
		    		<tr>
		    			<td>{$flag}:</td>
		    			<td><img alt="{t}Set{/t}" src="images/true.gif"></td>
		    		</tr>
                    {/foreach}
                    
                    {foreach from=$seldomain_extra_fields item="field"}
		    		<tr>
		    			<td>{$field.description}:</td>
		    			<td>{$field.value}</td>
		    		</tr>
                    {/foreach}
                    
                    {if $seldomain_pending_operations}
                    <tr>
                    	<td>{t}Pending operations{/t}: </td>
                    	<td>
                    	{section name=id loop=$seldomain_pending_operations}
                    	{if $smarty.section.id.last}
                    		<a href="domain_oper_details.php?op={$seldomain_pending_operations[id].operation}&id={$SelectedDomain->ID}">{$seldomain_pending_operations[id].operation_name|@ucfirst}</a>
                    	{else}
                    		<a href="domain_oper_details.php?op={$seldomain_pending_operations[id].operation}&id={$SelectedDomain->ID}">{$seldomain_pending_operations[id].operation_name|@ucfirst}</a>, 
                    	{/if}
                    	{/section}
                    	</td>
                    </tr>
                    {/if}                    
                    
              	</table>
      		</td>
      		<td>&nbsp;</td>
      		<td style="background-color: #f9f9f9; padding:10px;">
      			<table width="100%" cellpadding="1" cellspacing="2">
	              {foreach key=key item=Contact from=$SelectedDomain->GetContactList()}
	              <tr>
	                    <td>{$key|ucfirst}:</td>
	                    <td>
	                      {$Contact->GetFullName()}
	                      {if $SelectedDomain->Status == "Delegated"}
	                      <a href="manage_contact.php?c={$key}&domainid={$SelectedDomain->ID}"><img src="images/edit.png" /></a>
	                      {/if}
	                    </td>
	                </tr>
	  				{/foreach}
				</table>
      		</td>
      		<td>&nbsp;</td>
      		<td style="background-color: #f9f9f9; padding:10px;">
      				<ul style="color: #999999;padding:3px; margin:0px 0px 0px 20px;">
	                	{if $SelectedDomain->Status == "Delegated"}
	                	
		                {if $seldomain_allow.lock}
		                    <li><a href ='domains_view.php?task=lock&id={$SelectedDomain->ID}'>{t}Lock/Unlock{/t}</a></li>
		                {/if}
		                {if $seldomain_allow.flags}
		                    <li><a href ='manage_flags.php?id={$SelectedDomain->ID}'>{t}Options and permissions{/t}</a></li>
		                {/if}
		                {if $seldomain_allow.change_authcode}
		                	<li><a href ='domain_change_pwd.php?id={$SelectedDomain->ID}'>{t}Change authcode{/t}</a></li>
		                {/if}
		                <li><a href ='ns.php?domainid={$SelectedDomain->ID}'>{t}Manage nameservers{/t}</a></li>
		                {if $seldomain_allow.ns_hosts}
		                <li><a href ='nhosts_view.php?domainid={$SelectedDomain->ID}'>{t}Manage nameserver hosts{/t}</a></li>
		                {/if}
		                {if $SelectedDomain->IsManagedDNSEnabled}
		                    <li><a href ='dnszone_edit.php?zonename={$SelectedDomain->GetHostName()}'>{t}Edit DNS zone{/t}</a></li>{/if}	            
		                {if $SelectedDomain->Status == 'Delegated' || $SelectedDomain->Status == 'Transfer requested'}
		                    <li><a href ='update_status.php?id={$SelectedDomain->ID}'>{t}Update status{/t}</a></li>
		                {/if}
		                {if $SelectedDomain->Status == 'Transfer failed' || $SelectedDomain->Status == 'Awaiting transfer authorization'}
		                    <li><a href ='complete_transfer.php?id={$SelectedDomain->ID}'>{t}Resend transfer request{/t}</a></li>
		                {/if}
		                <li><a href ='bulk_renew.php?id[]={$SelectedDomain->ID}'>{t}Renew{/t}</a> </li>
		                <li><a href ='domain_whois.php?domainid={$SelectedDomain->ID}'>{t}WHOIS{/t}</a></li> 
		            	{/if}
		            	
	                	<li><a href ='domains_view.php?task=delete&id={$SelectedDomain->ID}'>{t}Delete{/t}</a></li>		            
     			 	</ul>
      		</td>
      	</tr>
      </tbody>      
 </table>
 </div>
	{include file="client/inc/table_footer.tpl" disable_footer_line=1}
{/if}

<div id="gridviewer-ct"></div>

<script type="text/javascript">
var _t = new Object();
_t.gridTitle = "{t}Manage domains{/t}";
_t.empty = "{t}No domains found{/t}";
_t.locked = "{t}Locked{/t}";
_t.unlocked = "{t}UnLocked{/t}";
_t.withSelected = "{t}Seleted{/t}";
_t.apply = "{t}Apply{/t}";
_t.manage = "{t}Manage{/t}";
_t.pageSize = "{t}{ldelim}0{rdelim} items per page{/t}"
_t.lockUnlock = "{t}Lock/Unlock{/t}";
_t.authCode = "{t}Change authcode{/t}";
_t.permissions = "{t}Options and permissions{/t}";
_t.nameservers = "{t}Manage nameservers{/t}";
_t.nameserverHosts = "{t}Manage nameserver hosts{/t}";
_t.dnsZone = "{t}Edit DNS zone{/t}";
_t.updateStatus = "{t}Update status{/t}";
_t.resendTransferRequest = "{t}Resend transfer request{/t}";
_t.renew = "{t}Renew{/t}";
_t.whois = "{t}WHOIS{/t}";
_t.del = "{t}Delete{/t}";
_t.completeRegistration = "{t}Complete registration{/t}";
_t.completeTransfer = "{t}Complete transfer{/t}";
_t.completeTrade = "{t}Complete trade{/t}";
_t.renew = "{t}Renew{/t}";
_t.renewDisabled = "{t}Renew disabled{/t}";
_t.renewEnabled = "{t}Renew enabled{/t}";
_t.setRenewEnabled = "{t}Enable renew{/t}";
_t.setRenewDisabled = "{t}Disable renew{/t}";

_t.colName = "{t}Name{/t}";
_t.colStatus = "{t}Status{/t}";
_t.colCreateDate = "{t}Creation date{/t}";
_t.colExpireDate = "{t}Expiration date{/t}";
_t.colLocked = "{t}Locked{/t}";
_t.colNoRenew = "{t}No renew{/t}";
_t.colPassword = "{t}Password{/t}";


{literal}
Ext.onReady(function () {
	// create the Data Store
    var store = new Ext.ux.webta.Store({
        reader: new Ext.ux.webta.JsonReader({
	        root: 'data',
	        totalProperty: 'total',
	        errorProperty: 'error',
	        id: 'id',
	        fields: [
	            'name', 'status', 
	            {name: 'date_create', type: 'date'},
	            {name: 'date_expire', type: 'date'},
	            'is_locked', 'renew_disabled', 'pw',
	            {name: 'id', type: 'int'},
	            {name: 'allow'},
	            {name: 'incomplete_operation'}
	        ]
        }),
        remoteSort: true,
        sortInfo: {
			field: "name",
			direction: "ASC"
        },        
		url: 'ajax/domains_view.php',
		listeners: {dataexception: Ext.ux.dataExceptionReporter}
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));
	

    function isDomainLocked (value, p, record) {
    	if (isDelegated(record.data) && record.data.allow.indexOf("lock") != -1) {
			return record.data.is_locked;
    	}
	}

	function isRenewDisabled (value, p, record) {
		return Boolean(value);
	}
    
    function isDelegated (data) { 
        return data.status == "Delegated"; 
	}

	function isPending (data) {
		return data.status == "Pending";
	}

	// Define grid
    var renderers = Ext.ux.webta.GridViewer.columnRenderers;
	var grid = new Ext.ux.webta.GridViewer({
        renderTo: "gridviewer-ct",
        id: "domainsGrid20100108",
        height: 400,
        title: _t.gridTitle,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: _t.empty
        },
	    // Columns
        columns:[
			{header: _t.colName, width: 35, dataIndex: 'name', sortable: true},
			{header: _t.colStatus, width: 16, dataIndex: 'status', renderer: renderers.domainStatus, sortable: true},
			{header: _t.colCreateDate, width: 15, dataIndex: 'date_create', renderer: renderers.dateMjY, sortable: true},
			{header: _t.colExpireDate, width: 15, dataIndex: 'date_expire', renderer: renderers.dateMjY, sortable: true}, 
			{header: _t.colLocked, width: 7, dataIndex: 'is_locked', 
				renderer:  renderers.tick(isDomainLocked, _t.locked, _t.unlocked), 
				sortable: false},
			{header: _t.colNoRenew, width: 7, dataIndex: 'renew_disabled',
				renderer: renderers.tick(isRenewDisabled, _t.renewDisabled, _t.renewEnabled), 
				sortable: false},
			{header: _t.colPassword, width: 10,	dataIndex: 'pw', sortable: false}
		],
    	// Row menu
    	rowOptionsMenu: [
			{id: "option.manage",		text: _t.manage,			href: "domain_change.php?id={id}"},
			new Ext.menu.Separator({id: "option.manage.sep"}),
			{id: "option.completeRegistration", text: _t.completeRegistration, href: "domain_reg.php?action=complete&id={id}"},
			{id: "option.completeTransfer", text: _t.completeTransfer, href: "complete_transfer.php?id={id}"},
			{id: "option.completeTrade",text: _t.completeTrade, href: "complete_trade.php?id={id}"},
			{id: "option.lockUnlock", 	text: _t.lockUnlock, 		href: "domains_view.php?task=lock&id={id}"},
			{id: "option.permissions", 	text: _t.permissions, 		href: "manage_flags.php?id={id}"},
			{id: "option.authCode", 	text: _t.authCode,			href: "domain_change_pwd.php?id={id}"},
			{id: "option.nameservers", 	text: _t.nameservers, 		href: "ns.php?domainid={id}"},
	        {id: "option.nameserverHosts", text: _t.nameserverHosts,href: "nhosts_view.php?domainid={id}"},
	        {id: "option.dnsZone", 		text: _t.dnsZone, 			href: "dnszone_edit.php?zonename={name}"},
			{id: "option.updateStatus", text: _t.updateStatus, 		href: "update_status.php?id={id}"},
			{id: "option.resendTransferRequest", text: _t.resendTransferRequest, href: "complete_transfer.php?id={id}"},
	        {id: "option.renew", 		text: _t.renew, 			href: "bulk_renew.php?id[]={id}"},
	        {id: "option.whois", 		text: _t.whois,				href: "domain_whois.php?domainid={id}"},
			{id: "option.del", 			text: _t.del, 				href: "domains_view.php?task=delete&id={id}"}
     	],
     	getRowOptionVisibility: function (item, record) {
			var data = record.data;
			if (item.id == "option.manage" || item.id == "option.manage.sep") {
				return isDelegated(data);
			} else if (item.id == "option.completeRegistration") {
				return isPending(data) && data.incomplete_operation == "Register";
			} else if (item.id == "option.completeTransfer") {
				return isPending(data) && data.incomplete_operation == "Transfer";
			} else if (item.id == "option.completeTrade") {
				return isPending(data) && data.incomplete_operation == "Trade";
			} else if (item.id == "option.lockUnlock") {
    			return isDelegated(data) && data.allow.indexOf("lock") != -1;
    		} else if (item.id == "option.permissions") {
    			return isDelegated(data) && data.allow.indexOf("manage_flags") != -1;
    		} else if (item.id == "option.authCode") {
        		return isDelegated(data) && data.allow.indexOf("change_authcode") != -1;
   			} else if (item.id == "option.nameserverHosts") {
   	   			return isDelegated(data) && data.allow.indexOf("ns_hosts") != -1; 
   			} else if (item.id == "option.dnsZone") {
   				return isDelegated(data) && data.allow.indexOf("managed_dns") != -1;
    		} else if (item.id == "option.updateStatus") {
    			return isDelegated(data) || data.status == "Transfer requested"; 
    		} else if (item.id == "option.resendTransferRequest") {
    			return data.status == "Transfer failed" || data.status == "Awaiting transfer authorization";
    		} else if (item.id == "option.renew") {
				return isDelegated(data) || data.status == "Expired";
        	}
    		else if (isDelegated(data) || item.id == "option.del") {
    			return true;
    		}
    		return false;	
		},
		selCheckboxRenderer: function (id, p, record) {
			return isDelegated(record.data) ? '<input type="checkbox" name="id[]" value="'+id+'"/>' : ''; 
		},
		withSelected: [
			{text: _t.renew, value: "renew", formAction: "bulk_renew.php"},
			'-',
			{text: _t.setRenewEnabled, value: "setRenewEnabled"},
			{text: _t.setRenewDisabled, value: "setRenewDisabled"}
		]
    });
    
    // Render and load data
    grid.render();
    store.load();
});
{/literal}
</script>


{include file="client/inc/footer.tpl"}
