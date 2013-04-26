{include file="admin/inc/header.tpl"}

<div id="gridviewer-ct"></div>

<script type="text/javascript">
currency = "{$Currency}";

_t = new Object();
_t.gridTitle = "{t}View invoices{/t}";
_t.empty = "{t}No invoices found{/t}";
_t.colId = "{t}Invoice ID{/t}";
_t.colOrderId = "{t}Order ID{/t}"
_t.colClient = "{t}Client{/t}";
_t.colIssuedFor = "{t}Issued for{/t}";
_t.colAmount = "{t}Amount{/t}";
_t.colCreateDate = "{t}Create date{/t}";
_t.colStatus = "{t}Status{/t}";
_t.colGate = "{t}Payment method{/t}";
_t.status = new Object();
_t.status.pending = "{t}Pending{/t}";
_t.status.paid = "{t}Paid{/t}";
_t.status.rejected = "{t}Rejected{/t}";
_t.pay = "{t}Make payment{/t}";
_t.reject = "{t}Reject{/t}";
_t.makePayment = "{t}Make payment for selected invoices{/t}";
_t.filterQuickRange = "{t}Quick date range{/t}";
_t.filterExactRange = "{t}Exact date range{/t}";
_t.filterIssuedFor = "{t}Issued for{/t}";
_t.filterOrderID = "{t}Order ID{/t}";
_t.filterEmptyText = "{t}Select filter...{/t}";
_t.quickToday = "{t}Today{/t}";
_t.quickYesterday = "{t}Yesterday{/t}";
_t.quickLast7days = "{t}Last 7 days{/t}";
_t.quickLastWeek = "{t}Last week (Mon-Sun){/t}";
_t.quickLastBusWeek = "{t}Last business week (Mon-Fri){/t}";
_t.quickThisMonth = "{t}This month{/t}";
_t.quickLastMonth = "{t}Last month{/t}";
_t.applyFilter = "{t}Apply filter{/t}";
_t.viewDetails = "{t}View details{/t}";
_t.viewPrintable = "{t}View printable version{/t}"; 
_t.markAsPaid = "{t}Mark as paid{/t}";
_t.markAsFailed = "{t}Mark as failed{/t}";
_t.del = "{t}Delete{/t}";

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
       			{name: "id", type: "int"},
       			"custom_id",
       			"order_id",
       			"userid", "userlogin",
				"description",
				"total",
				"vat",
				"dtcreated",
				{name: "status", type: "int"},
				"gate" 
            ]
        }),
    	remoteSort: true,
    	sortInfo: {
			field: "dtcreated",
			direction: "DESC"
    	},
		url: 'ajax/inv_view.php',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));

	var purposeDS = new Ext.data.JsonStore({
		root: 'data',
		fields: ['value', 'text'],
		url: 'ajax/inv_view.php?purposes'
	});
	purposeDS.load();

	
	var tbar = new Ext.Toolbar([{
			xtype: 'combo',
			id: 'tb.filter',
			width: 120,
			store:  [
				['Q', _t.filterQuickRange + ':'],
				['E', _t.filterExactRange + ':'],
				['P', _t.filterIssuedFor + ':'],
				['O', _t.filterOrderID + ':']
			],
			value: 'Q',
			editable: false,
			triggerAction: 'all',
			emptyText: _t.filterEmptyText,
			listeners: {select: selectFilter}
		},
		' ',
		// Q group 
		{
			tbgroup: 'Q',
			xtype: 'combo',
			id: 'tb.quick_date',
			name: 'quick_date',
			store: [
				['today', _t.quickToday],
				['yesterday', _t.quickYesterday],
				['last7days', _t.quickLast7days],
				['lastweek', _t.quickLastWeek],
				['lastbusinessweek', _t.quickLastBusWeek],
				['thismonth', _t.quickThisMonth],
				['lastmonth', _t.quickLastMonth]					
			],
			editable: false,
			triggerAction: 'all'
		},
		// E group
		{
			xtype: 'datefield',
			tbgroup: 'E',
			id: 'tb.dt',
			hidden: true,
			value: new Date()
		}, {
			xtype: 'datefield',
			tbgroup: 'E',
			id: "tb.dt2",
			hidden: true,
			value: new Date()
		},
		// P group 
		{
			xtype: 'combo',
			tbgroup: 'P',
			id: 'tb.purpose',
			hidden: true,
			displayField: 'text',
			valueField: 'value',
			mode: 'local',
			store: purposeDS,
			editable: false,
			triggerAction: 'all'
		},
		// O group 
		{
			xtype: 'textfield',
			tbgroup: 'O',
			id: 'tb.order_id',
			hidden: true
		}, {
			xtype: 'tbbutton',
			text: _t.applyFilter,
			enableToggle: true,
			listeners: {
				toggle: function (btn, pressed) {
					var cmpNames = ['quick_date', 'dt', 'dt2', 'purpose', 'order_id', 'filter'];			
					if (pressed) {
						Ext.each(cmpNames, function (name) {
							store.baseParams[name] = Ext.getCmp("tb." + name).getValue();
						});
					} else {
						Ext.each(cmpNames, function (name) {
							delete store.baseParams[name];
						});
					}
					store.load();
				}
			}
		}
	]);
	
	function selectFilter (combo, record, index) {
		store.baseParams.filter = record.data.value;
		tbar.items.each(function (item) {
			if (item.tbgroup) {
				item[item.tbgroup == record.data.value ? "show" : "hide"]();
			}
		})
	}

	function renderClient (value, p, record) {
		return Ext.DomHelper.markup({tag: 'a', href: 'users_view.php?id=' + value, html: record.data.userlogin});
	}
	
	function renderStatus (value, p, record) {
        var _tt = _t.status;		
    	var titles = {
       	    0 : _tt.pending,
       	    1 : _tt.paid,
       	    2 : _tt.rejected
    	};
    	var className = value == 0 ? "status-pending" : value == 1 ? "status-ok" : "status-fail";
    	var title = titles[value] || value;
    	p.css += " "+className;
    	return title;		
	}

	function renderTotal (value, p, record) {
		var ret = currency+""+value;
		if (record.data.vat > 0)
			ret += " (Incl. Vat "+record.data.vat+"%)";
		return ret;
	}
	
    var renderers = Ext.ux.webta.GridViewer.columnRenderers;
	var grid = new Ext.ux.webta.GridViewer({
        renderTo: "gridviewer-ct",
        id: "invGrid",
        height: 400,
        title: _t.gridTitle,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: _t.empty
        },
        enableFilter: false,
        tbar: tbar,
	    // Columns
        columns:[
			{header: _t.colId, width: 12, dataIndex: 'custom_id', sortable: true},
			{header: _t.colOrderId, width: 12, dataIndex: 'order_id', sortable: true},
			{header: _t.colClient, width: 10, dataIndex: 'userid', renderer: renderClient, sortable: true},
			{header: _t.colIssuedFor, width: 50, dataIndex: 'description', sortable: false},
			{header: _t.colAmount+', '+currency, width: 20, dataIndex: 'total', sortable: true},
			{header: _t.colCreateDate, width: 20, dataIndex: 'dtcreated', sortable: true},
			{header: _t.colStatus, width: 12, dataIndex: 'status', renderer: renderStatus, sortable: true},
			{header: _t.colGate, width: 12, dataIndex: 'gate', sortable: false}
		],
    	// Row menu
	   	rowOptionsMenu: [
			{id: "option.details",		text: _t.viewDetails, 			href: "inv_details.php?id={id}"},
			{id: "option.print",		text: _t.viewPrintable,	href: "inv_print.php?id={id}"}
     	],
		selCheckboxRenderer: function (id, p, record) {
			return record.data.status != 1 ? '<input type="checkbox" name="id[]" value="'+id+'"/>' : '';
		},
		withSelected: [
			{text: _t.markAsFailed, value: 'unappr'},
			{text: _t.markAsPaid, value: 'appr'},
			{text: _t.del, value: 'del'}
		]
    });
    grid.render();
    store.load();
});
{/literal}
</script>

{include file="admin/inc/footer.tpl"}