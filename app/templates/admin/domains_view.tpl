{include file="admin/inc/header.tpl"}

<div id="gridviewer-ct"></div>

<script type="text/javascript" language="javascript">

// messages
var _t = new Object();
_t.colName = "{t}Name{/t}";
_t.colUsername = "{t}Username{/t}";
_t.colCreateDate = "{t}Create date{/t}";
_t.colExpirationDate = "{t}Expiration date{/t}"
_t.colContacts = "{t}Contacts{/t}";
_t.colInvoices = "{t}Invoices{/t}";
_t.colStatus = "{t}Status{/t}";
_t.colPassword = "{t}Password{/t}";
_t.colNoRenew = "{t}No renew{/t}";
_t.emptyText = "{t}No domains found{/t}";
_t.renewDisabled = "{t}Renew disabled{/t}";
_t.renewEnabled = "{t}Renew enabled{/t}";

_t.title = "{t}View domains{/t}";
_t.menuTransfer = "{t}Transfer this domains to another client{/t}";
_t.menuRenew = "{t}Renew{/t}";

_t.optDeleteFromDatabase = "{t}Delete from database{/t}";
_t.optSendNotification = "{t}Send notification{/t}";

{literal}
Ext.onReady(function () {
	var queryParams = Ext.ux.parseQueryString(window.location.href);

	// create the Data Store
    var store = new Ext.ux.webta.Store({
        reader: new Ext.ux.webta.JsonReader({
	        root: 'data',
	        totalProperty: 'total',
	        errorProperty: 'error',
	        id: 'id',
	        fields: [
				{name: 'id', type: 'int'},
				'userid', 'userlogin',
				'name', 'status', 
				{name: 'date_create', type: 'date'},
				{name: 'date_expire', type: 'date'},
				{name: 'renew_disabled', type: 'bool'},
				'num_contacts',	'num_invoices', 'pw'
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
	Ext.apply(store.baseParams, queryParams);

	var rowOptionsMenu =  [{id: "option.deldb", href: "#{id}", handler: deleteDomain, text: _t.optDeleteFromDatabase}];
	if (queryParams.act == "expsoon") {
		rowOptionsMenu.push({id: "option.notify", href: "?act=expsoon&task=send&domainid={id}", text: _t.optSendNotification});
	}

	function deleteDomain (menuitem, ev) {
		ev.stopEvent();
		if (confirm("Are you really want to delete domain from database?")) {
			var href = menuitem.el.dom.href;
			domainid = href.split(/\#/)[1];

			params = {
				task: "deldb", 
				domainid: domainid
			};
			if (store.lastOptions.params) {
				Ext.apply(params, {
					start: store.lastOptions.params.start,
					limit: store.lastOptions.params.limit
				});
			}
			store.load({params: params});
		}
	}
		
	function renderUsername (value, p, record) {
		return Ext.DomHelper.markup({
			tag: 'a', 
			href: 'users_view.php?userid='+record.data.userid, 
			html: value
		});
	}
	
	function renderContacts (value, p, record) {
		return value + ' [<a href="contacts_view.php?domainid='+record.id+'">View</a>]';
	}
	
	function renderNumInvoices (value, p, record) {
		return value + ' [<a href="inv_view.php?domainid='+record.id+'">View</a>]';
	}

	function renderStatus (value, p, record) {
		var ret = renderers.domainStatus(value, p, record);
		if ('Delegated|Transfer failed|Application recalled'.indexOf(record.data.status) != -1) {
			ret += ' [<a href="domains_view.php?task=delete&domainid='+record.id+'">Delete</a>]';
		}
		return ret;
	}

	function isRenewDisabled (value, p, record) {
		return Boolean(value);
	}	

    var renderers = Ext.ux.webta.GridViewer.columnRenderers;
	var grid = new Ext.ux.webta.GridViewer({
        renderTo: "gridviewer-ct",
        id: queryParams.act == "expsoon" ? "adminDomainsExpsoonGrid" : "adminDomainsGrid",
        height: 400,
        title: _t.title,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: _t.emptyText
        },
	    // Columns
        columns:[
			{header: _t.colName, width: 30, dataIndex: 'name', sortable: true},
			{header: _t.colUsername, width: 15, dataIndex: 'userlogin', sortable: true, renderer: renderUsername},
			{header: _t.colCreateDate, width: 12, dataIndex: 'date_create', renderer: renderers.dateMjY, sortable: true},
			{header: _t.colExpirationDate, width: 13, dataIndex: 'date_expire', renderer: renderers.dateMjY, sortable: true}, 
			{header: _t.colContacts, width: 10, dataIndex: 'num_contacts', renderer: renderContacts, sortable: false},
			{header: _t.colInvoices, width: 10, dataIndex: 'num_invoices', renderer: renderNumInvoices, sortable: false},
			{header: _t.colStatus, width: 20, dataIndex: 'status', 
				renderer: renderStatus, 
				sortable: false}, 
			{header: _t.colNoRenew, width: 7, dataIndex: 'renew_disabled',
				renderer: renderers.tick(isRenewDisabled, _t.renewDisabled, _t.renewEnabled), 
				sortable: false},				
			{header: _t.colPassword, width: 13,	dataIndex: 'pw', sortable: false}
		],
    	// Row menu
    	rowOptionsMenu: rowOptionsMenu,
    	withSelected: [
			{text: _t.menuTransfer, value: "transf", formAction: "transfer.php"},
    		{text: _t.menuRenew, value: "renew", formAction: "bulk_renew.php"}
		]
    });
    grid.render();
    store.load();
});
{/literal}
</script>

{include file="admin/inc/footer.tpl"}
