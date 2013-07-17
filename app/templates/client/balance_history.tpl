{include file="client/inc/header.tpl"}

<div id="gridviewer-ct"></div>

<script type="text/javascript">

var _t = new Object();
_t.gridTitle = "{t}Balance history{/t}";
_t.empty = "{t}No history found{/t}";
_t.colType = "{t}Type{/t}";
_t.colAmount = "{t}Amount{/t}";
_t.colDate = "{t}Date{/t}";
_t.colDescription = "{t}Description{/t}";
_t.deposit = "{t}Deposit{/t}";
_t.withdraw = "{t}Withdraw{/t}";

var currency = "{$Currency}";

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
            fields: ["id", "type", "name", "amount", "operation_date", "description"]
        }),
    	remoteSort: true,
		url: 'ajax/balance_history.php',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));

	function renderOperationType (value, p, record) {
		if (value == "Deposit") {
			return '<span style="color:green">'+_t.deposit+'</span>';
		} else if (value == "Withdraw") {
			return '<span style="color:red">'+_t.withdraw+'</span>';
		} else {
			return '';
		}
	}

	function renderMoney (value, p, record) {
		return '<span style="font-size:90%;font-weight:bold">'+value+'</span>';
	}
	
    var renderers = Ext.ux.webta.GridViewer.columnRenderers;
	var grid = new Ext.ux.webta.GridViewer({
        renderTo: "gridviewer-ct",
        id: "historyGrid",
        height: 400,
        title: _t.gridTitle,
        store: store,
        maximize: true,
        enableFilter: false,
        viewConfig: { 
        	emptyText: _t.empty
        },
	    // Columns
        columns:[
			{header: _t.colType, width: 20, dataIndex: 'type', renderer: renderOperationType, sortable: false},                 
			{header: _t.colAmount + ', '+currency, width: 30, dataIndex: 'amount', align: 'right', renderer: renderMoney, sortable: false},
			{header: _t.colDate, width: 30, dataIndex: 'operation_date', sortable: true},
			{header: _t.colDescription, width: 60, dataIndex: 'description', sortable: false}
		]
    });
    grid.render();
    store.load();
});
{/literal}
</script>

		
{include file="client/inc/footer.tpl"}