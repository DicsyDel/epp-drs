{include file="admin/inc/header.tpl"}

<div id="gridviewer-ct"></div>

<script type="text/javascript" language="javascript">

// messages
var _t = new Object();
_t.colName = "{t}Name{/t}";
_t.colUsername = "{t}Username{/t}";
_t.colCreateDate = "{t}Create date{/t}";
_t.colExpirationDate = "{t}Expiration date{/t}"
_t.colStatus = "{t}Status{/t}";
_t.emptyText = "{t}No domains found{/t}";
_t.title = "{t}View await delete confirmation domains{/t}";
_t.menuDel = "{t}Approve delete{/t}";

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
				{name: 'overdue', type: 'bool'}
		    ]
        }),
        remoteSort: false,
        sortInfo: {
        	field: "date_expire",
			direction: "DESC"
        },
		url: 'ajax/domains_await_delete_confirmation.php',
		listeners: {dataexception: Ext.ux.dataExceptionReporter}
    });
	Ext.apply(store.baseParams, queryParams);


	function renderUsername (value, p, record) {
		return Ext.DomHelper.markup({
			tag: 'a', 
			href: 'users_view.php?userid='+record.data.userid, 
			html: value
		});
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
        	emptyText: _t.emptyText,
           	getRowClass: function (record) {
           		if (record.data.overdue > 0) {
					return 'ux-row-red';
            	}
           	}
        },
	    // Columns
        columns:[
			{header: _t.colName, width: 30, dataIndex: 'name'},
			{header: _t.colUsername, width: 15, dataIndex: 'userlogin', renderer: renderUsername},
			{header: _t.colCreateDate, width: 12, dataIndex: 'date_create', renderer: renderers.dateMjY},
			{header: _t.colExpirationDate, width: 13, dataIndex: 'date_expire', renderer: renderers.dateMjY}, 
			{header: _t.colStatus, width: 20, dataIndex: 'status',	renderer: renderers.domainStatus} 
		],
    	withSelected: [
			{text: _t.menuDel, value: "appr"}
		]
    });
    grid.render();
    store.load();
});
{/literal}
</script>

{include file="admin/inc/footer.tpl"}
