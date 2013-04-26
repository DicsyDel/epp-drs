{include file="admin/inc/header.tpl"}

<div id="search-ct" style="margin:10px 5px 10px 5px"></div>
   
<div id="gridviewer-ct" style="margin-top:10px"></div>

<style>
{literal}
.x-grid3-cell-inner {
	text-overflow:clip !important;
}
{/literal}
</style>

<script type="text/javascript">

{literal}
Ext.onReady(function () {
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
			xtype: 'checkboxgroup',
			width: 500,
			fieldLabel: 'Severity',
			columns: 3,
            items: {/literal}{$severities}{literal},
			listeners: {
				render: {
					fn: function (cmp) {
						if (Ext.isIE) {
							cmp.el.select('.x-form-element').setStyle('width', '166px');
						}
					},
					delay: 20
				}
			}
		}, {
			xtype: 'datefield',
			width: 230,
			name: 'dt',
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
            id: 'transactionid',
            fields: [
       			"dtadded", "firstEntry", "warns", {name: "errors", type: "int"}, "transactionid"
            ]
        }),
        baseParams: {
        	sort: 'dtadded',
        	dir: 'DESC'
        },
    	remoteSort: true,
		url: 'ajax/logs_view.php',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));

	function renderViewEntries (value, p, record) {
		return Ext.DomHelper.markup({tag: 'a', href: 'log_transaction_details.php?trnid='+value, html: 'View log entries'});
	}
	
	//function renderFirstEntry (value, p, record) {
	//	return Ext.util.Format.ellipsis(value, 100);
	//}
	
    var renderers = Ext.ux.webta.GridViewer.columnRenderers;
	var grid = new Ext.ux.webta.GridViewer({
        renderTo: "gridviewer-ct",
        id: "logsGrid",
        height: 400,
        title: "View logs",
        store: store,
        maximize: true,
        enableFilter: false,
        viewConfig: { 
        	emptyText: "No logs found",
        	getRowClass: function (record) {
        		if (record.data.errors > 0) {
        			return 'ux-row-red';
        		}
        	}
        },
	    // Columns
        columns:[
			{header: "Date", width: 15, dataIndex: 'dtadded', sortable: false},
			{header: "Warnings", width: 8, dataIndex: 'warns', sortable: false},
			{header: "Errors", width: 7, dataIndex: 'errors', sortable: false},
			{header: "First log entry", width: 58, dataIndex: 'firstEntry', sortable: false, css: 'white-space: normal !important;'},
			{header: '', dataIndex: 'transactionid', width: 12, renderer: renderViewEntries, menuDisabled: true, draggable: false, hideable: false}
		],
		withSelected: [{
			text: 'Send report to developers',
			value: 'report'
		}]
    });
    store.load();
});
{/literal}
</script>

{include file="admin/inc/footer.tpl"}
