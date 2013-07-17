{include file="admin/inc/header.tpl"}


<div id="gridviewer-ct"></div>

<script type="text/javascript">

var _t = new Object();
_t.colCLID = "{t}CLID{/t}";
_t.colClient = "{t}Client{/t}";
_t.colExtension = "{t}Extension{/t}";
_t.title = "{t}View contacts{/t}";
_t.emptyText = "{t}No contacts found{/t}";
_t.menuRelDomain = "{t}View related domains{/t}";
_t.menuContacts = "{t}View contact details{/t}";
_t.menuChangeOwner = "{t}Change owner{/t}";

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
       			"id", "clid", "userid", "userlogin", "tld" 
            ]
        }),
    	remoteSort: true,
        sortInfo: {
			field: "clid",
			direction: "ASC"
        },      	
		url: 'ajax/contacts_view.php',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));

	function renderCLID (value, p, record) {
		return Ext.DomHelper.markup({tag: 'a', href: 'contact_full.php?clid=' + value, html: value});
	}
	
	function renderClient (value, p, record) {
		return Ext.DomHelper.markup({tag: 'a', href: 'users_view.php?id=' + value, html: record.data.userlogin});
	}

    var renderers = Ext.ux.webta.GridViewer.columnRenderers;
	var grid = new Ext.ux.webta.GridViewer({
        renderTo: "gridviewer-ct",
        id: "contactsGrid",
        height: 400,
        title: _t.title,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: _t.emptyText
        },
	    // Columns
        columns:[
			{header: _t.colCLID, width: 30, dataIndex: 'clid', sortable: true, renderer: renderCLID},
			{header: _t.colClient, width: 30, dataIndex: 'userid', sortable: true, renderer: renderClient},
			{header: _t.colExtension, width: 30, dataIndex: 'tld', sortable: false}
		],
    	// Row menu
	   	rowOptionsMenu: [
			{href: 'domains_view.php?clid={clid}', text: _t.menuRelDomain},
			{href: 'contact_full.php?clid={clid}', text: _t.menuContacts},
			{href: 'contact_change_owner.php?id={id}', text: _t.menuChangeOwner}
     	]
    });
    grid.render();
    store.load();
});
{/literal}
</script>
{include file="admin/inc/footer.tpl"}
