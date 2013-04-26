{include file="client/inc/header.tpl"}


<div id="gridviewer-ct"></div>

<script type="text/javascript">

var _t = new Object();
_t.gridTitle = "{t}Manage contacts{/t}";
_t.empty = "{t}No contacts found{/t}";
_t.colExtension = "{t}Extension{/t}";
_t.colName = "{t}Name{/t}";
_t.colEmail = "{t}E-mail{/t}";
_t.colStatus = "{t}Status{/t}";
_t.rejectUpdate = "{t}Reject update{/t}";
_t.rejectCreate = "{t}Reject create{/t}";
_t.edit = "{t}Edit{/t}";
_t.del = "{t}Delete{/t}";
_t.status = new Object();
_t.status.awaitUpdateApprove = "{t}Await update approve{/t}";
_t.status.awaitCreateApprove = "{t}Await create approve{/t}";

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
       			"id", "clid", "name", "email", "tld", "status", "allows" 
            ]
        }),
    	remoteSort: true,
		url: 'ajax/contacts_view.php',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));

    function renderStatus (value, p, record) {
        if (value == null || !value.length) {
            return;
		}
		
        var _tt = _t.status;
    	var titles = {
    	    "Await update approve": _tt.awaitUpdateApprove,
    	    "Await create approve": _tt.awaitCreateApprove
    	};
    	var className = "status-pending";
    	
    	var title = titles[value] || value;
    	p.css += " "+className;
    	return title;
    }

    var renderers = Ext.ux.webta.GridViewer.columnRenderers;
	var grid = new Ext.ux.webta.GridViewer({
        renderTo: "gridviewer-ct",
        id: "contactsGrid",
        height: 400,
        title: _t.gridTitle,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: _t.empty
        },
	    // Columns
        columns:[
			{header: _t.colExtension, width: 20, dataIndex: 'tld', sortable: false},                 
			{header: _t.colName, width: 30, dataIndex: 'name', sortable: false},
			{header: _t.colEmail, width: 30, dataIndex: 'email', sortable: false},
			{header: _t.colStatus, width: 16, dataIndex: 'status', renderer: renderStatus, sortable: false}
		],
    	// Row menu
	   	rowOptionsMenu: [
			{id: "option.rejectCreate", text: _t.rejectCreate, 	href: "contacts_view.php?task=reject_changes&id={id}"},
			{id: "option.rejectUpdate", text: _t.rejectUpdate, 	href: "contacts_view.php?task=reject_changes&id={id}"},
			{id: "option.edit",			text: _t.edit,			href: "contacts.php?id={id}"},
			{id: "option.del",			text: _t.del,			handler: deleteContact}
     	],
     	getRowOptionVisibility: function (item, record) {
			var data = record.data;
			if (item.id == "option.rejectCreate") {
				return data.status == "Await create approve";
			} else if (item.id == "option.rejectUpdate") {
				return data.status == "Await update approve";
			} else if (item.id == "option.edit") {
				return data.allows.indexOf("edit") != -1;
			} else {
				if (item.id == "option.del") {
					item.record = record;
				}
				
				return true;
			}
		}
    });
    grid.render();
    store.load();

    function deleteContact (menuItem) {
		var formEl = Ext.fly(document.body).createChild(
			{tag: "form", action: "", method: "POST", children: [
				{tag: "input", name: "task", value: "delete"},
				{tag: "input", name: "id", value: menuItem.record.id}
			]}
		);
		formEl.dom.submit();
	}
});
{/literal}
</script>

{include file="client/inc/footer.tpl"}
