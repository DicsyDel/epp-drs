{include file="admin/inc/header.tpl"}

<div id="search-ct" style="margin:10px 5px 10px 5px"></div>
   
<div id="gridviewer-ct" style="margin-top:10px"></div>

<script type="text/javascript">

{literal}
Ext.onReady(function () {

	var userStore = new Ext.data.JsonStore({
		url: "ajax/users_list.php",
		root: "data",
		idProperty: "id",
		fields: ["id", "title"]
	});
	
	// ---- Init search form
	var searchPanel = new Ext.FormPanel({
		renderTo: document.body,
        labelWidth: 150,
        frame:true,
        title: 'Search',
        bodyStyle:'padding:5px 5px 0',
        defaultType: 'textfield',	
        
		items: [{
			width: 500,
			name: 'query',
			fieldLabel: 'Search string'
		}, {
			name: 'transaction_id',
			fieldLabel: 'Transaction-ID',
			width: 300
		}, {
			xtype: 'combo',
			name: 'user',
			hiddenName: 'userid',			
			fieldLabel: "User",
			typeAhead: true,
			triggerAction: 'all',
			store: userStore,
			valueField: 'id',
			displayField: 'title'
		}, {
			xtype: 'datefield',
			width: 230,
			name: 'added_date',
			fieldLabel: 'Date'
		}],
		listeners: {
			render: {
				fn:	function () {
					// XXX: Direct renderTo: search-ct doesn't works with FormPanel
					Ext.get("search-ct").appendChild(this.el);
				},
				delay: Ext.isIE ? 20 : 0
			}
		},
		buttons: [
			{text: 'Filter', handler: doFilter}
		]
	});
	
	function doFilter () {
		Ext.apply(store.baseParams, searchPanel.getForm().getValues(false));
		store.load();
	}
	// ---- Init grid
	
	// create the Data Store
    var store = new Ext.ux.webta.Store({
        reader: new Ext.ux.webta.JsonReader({
            root: 'data',
            successProperty: 'success',
            errorProperty: 'error',
            totalProperty: 'total',
            id: 'id',
            fields: [
       			"id", "added_date", "user", "user_id", "action", "request", "response", "transaction_id", 
       			{name: "transaction_failed", type: "boolean"}
            ]
        }),
        baseParams: {
        	sort: 'added_date',
        	dir: 'DESC'
        },
    	remoteSort: true,
		url: 'ajax/api_logs_view.php',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));

	function renderViewEntry (value, p, record) {
		return Ext.DomHelper.markup({tag: 'a', href: 'api_logs_transaction_details.php?id='+value, html: 'Detailed view'});
	}

	function renderUser (value, p, record) {
		return parseInt(record.data.user_id, 10) > 0 ? 
				'<a href="users_view.php?userid='+record.data.user_id+'">'+value+'</a>' : 
				value;
	}
	
    var renderers = Ext.ux.webta.GridViewer.columnRenderers;
	var grid = new Ext.ux.webta.GridViewer({
        renderTo: "gridviewer-ct",
        id: "apiLogsGrid",
        height: 400,
        title: "View api logs",
        store: store,
        maximize: true,
        enableFilter: false,
        viewConfig: { 
        	emptyText: "No logs found",
        	getRowClass: function (record) {
        		if (record.data.transaction_failed) {
        			return 'ux-row-red';
        		}
        	}
        },
	    // Columns
        columns:[
			{header: "Date", width: 12, dataIndex: 'added_date', sortable: false},
			{header: "User", width: 6, dataIndex: 'user', sortable: false, renderer: renderUser},
			{header: "Action", width: 12, dataIndex: 'action', sortable: false},
			{header: "Request", width: 31, dataIndex: "request", sortable: false},
			{header: "Response", width: 31, dataIndex: "response", sortable: false, css: "white-space: normal !important;"},
			{header: '', dataIndex: 'id', width: 8, renderer: renderViewEntry, menuDisabled: true, draggable: false, hideable: false}			
		]
    });
    store.load();
});
{/literal}
</script>

{include file="admin/inc/footer.tpl"}
