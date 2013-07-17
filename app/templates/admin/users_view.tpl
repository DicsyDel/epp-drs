{include file="admin/inc/header.tpl"}

<div id="gridviewer-ct"></div>

<script type="text/javascript">

var currencyHTML = '{$Currency}';
var authHash = '{$authHash}';

{literal}
Ext.onReady(function () {
		
	// create the Data Store
    var store = new Ext.ux.webta.Store({
        reader: new Ext.ux.webta.JsonReader({
            root: 'data',
            successProperty: 'success',
            errorProperty: 'error',
            totalProperty: 'total',
            id: 'id',
            fields: [
       			{name: "id", type: "int"}, "login", "email", {name: "date_reg", type: 'date'}, 
       			"num_domains", "num_contacts", "num_invoices", "isactive",
       			"balance", "package", "packageid"
            ]
        }),
    	remoteSort: true,
        sortInfo: {
			field: "login",
			direction: "ASC"
        },    	
		url: 'ajax/users_view.php',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));


	function renderDomains (value, p, record) {
		return value + ' [<a href="domains_view.php?userid='+record.id+'">View</a>]';
	}
	
	function renderContacts (value, p, record) {
		return value + ' [<a href="contacts_view.php?userid='+record.id+'">View</a>]';
	}

	function renderInvoices (value, p, record) {
		return value + 
			'[<a href="inv_view.php?userid='+record.id+'">View</a>] ' +
			'[<a href="inv_create.php?userid='+record.id+'">Create</a>]';
	}
	
	function renderBalance (value, p, record) {
		return ''+currencyHTML+value + ' '+
			'[<a href="balance_history.php?userid='+record.id+'">History</a>] ' +
			'[<a href="balance_operation.php?userid='+record.id+'">Funds</a>]';
	}
	
	function renderPackage (value, p, record) {
		return record.data['package'];
	}

	function isActive (value, p, record) {
		return value;
	}

    var renderers = Ext.ux.webta.GridViewer.columnRenderers;
	var grid = new Ext.ux.webta.GridViewer({
        renderTo: "gridviewer-ct",
        id: "adminUsersGridv2",
        height: 400,
        title: "View clients",
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No clients found"
        },
	    // Columns
        columns:[
			{header: "Login", width: 15, dataIndex: 'login', sortable: true},
			{header: "Email", width: 18, dataIndex: 'email', sortable: true},
			{header: "Registration date", width: 10, dataIndex: 'date_reg', renderer: renderers.dateMjY, sortable: true},
			{header: "Domains", width: 10, dataIndex: 'num_domains', renderer: renderDomains, sortable: false},
			{header: "Contacts", width: 10, dataIndex: 'num_contacts', renderer: renderContacts, sortable: false},
			{header: "Invoices", width: 15, dataIndex: 'num_invoices', renderer: renderInvoices, sortable: false},
			{header: "Balance", width: 15, dataIndex: 'balance', renderer: renderBalance, sortable: false},
			{header: "Pricing package", width: 10, dataIndex: 'packageid', renderer: renderPackage, sortable: true},
			{header: "Active", width: 8, dataIndex: 'isactive', renderer: renderers.tick(isActive, "Active", "Inactive"), sortable: true}
		],
        // Top toolbar
        tbar: [
        	{text: 'Add new', handler: function () {window.location.href = 'users_add.php'; }, icon: '/images/ico-add.png', cls: 'x-btn-text-icon'}
        ],
    	// Row menu
	   	rowOptionsMenu: [
	   		{text: "Switch", href: "/client/login.php?hash="+authHash+"&u={login}", icon: '/images/ico-key.png'},
	   		'-',
	   		{text: "Edit", href: "users_edit.php?id={id}"},
	   		{text: "Activate", href: "users_view.php?id={id}&action=activate"},
	   		{text: "Deactivate", href: "users_view.php?id={id}&action=deactivate"}
     	],
     	// With selected
     	withSelected: [
     		{text: 'Delete', value: 'del'},
     		{text: 'Send welcome email', value: 'mail'}
     	]
    });
    
    // Render and load data
    grid.render();
    store.load();
});
{/literal}
</script>
	
{include file="admin/inc/footer.tpl"}
