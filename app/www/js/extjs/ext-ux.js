
Ext.ux.FilterField = Ext.extend(Ext.form.TwinTriggerField, {
    initComponent : function(){
        Ext.ux.FilterField.superclass.initComponent.call(this);
        this.on('specialkey', function(f, e){
            if(e.getKey() == e.ENTER){
                e.stopEvent();
                this.hasSearch ? this.onTrigger1Click() : this.onTrigger2Click();      	
            }
        }, this);
    },

    validationEvent:false,
    validateOnBlur:false,
    trigger1Class:'x-form-clear-trigger',
    trigger2Class:'x-form-search-trigger',
    hideTrigger1:true,
    width:180,
    hasSearch : false,
    paramName : 'query',

    onTrigger1Click : function(){
        if(this.hasSearch){
            this.el.dom.value = '';
            var o = {start: 0};
            this.store.baseParams = this.store.baseParams || {};
            this.store.baseParams[this.paramName] = '';
            this.store.reload({params:o});
            this.triggers[0].hide();
            this.hasSearch = false;
        }
    },

    onTrigger2Click : function(){
        var v = this.getRawValue();
        if(v.length < 1){
            this.onTrigger1Click();
            return;
        }
        var o = {start: 0};
        this.store.baseParams = this.store.baseParams || {};
        this.store.baseParams[this.paramName] = v;
        this.store.reload({params:o});
        this.hasSearch = true;
        this.triggers[0].show();
    }
});

/**
 * @class Ext.ux.SliderTip
 * @extends Ext.Tip
 * Simple plugin for using an Ext.Tip with a slider to show the slider value
 */
Ext.ux.SliderTip = Ext.extend(Ext.Tip, {
    minWidth: 10,
    offsets : [0, -10],
    init : function(slider){
        slider.on('dragstart', this.onSlide, this);
        slider.on('drag', this.onSlide, this);
        slider.on('dragend', this.hide, this);
        slider.on('destroy', this.destroy, this);
    },

    onSlide : function(slider){
        this.show();
        this.body.update(this.getText(slider));
        this.doAutoWidth();
        this.el.alignTo(slider.thumb, 'b-t?', this.offsets);
    },

    getText : function(slider){
        return slider.getValue();
    }
});

Ext.namespace("Ext.ux.webta");

Ext.ux.webta.GridViewer = Ext.extend(Ext.grid.GridPanel, {
	/**
	 * @cfg {Array} pageSizes Array of available page sizes
	 */
	/**
	 * @cfg {Array|Ext.menu.Menu} rowOptionsMenu Row options menu
	 */
	/**
	 * @cfg {Boolean} enableFilter
	 */
	/**
	 * @cfg {Boolean} enablePaging
	 */
	/**
	 * @cfg {Function} getRowMenuVisibility 
	 */
	/**
	 * @cfg {Function} getRowOptionVisibility
	 */
	/**
	 * @cfg {Array|Ext.menu.Menu} withSelected.menu
	 */
	/**
	 * @cfg {String} withSelected.actionName
	 */
	/**
	 * @cfg {Object} withSelected.hiddens
	 */
	/**
	 * @cfg {Function} selCheckboxRenderer
	 */
	/**
	 * @cfg {Boolean} maximize
	 */
	
	messages: {
		pageSize: "{0} items per page",
		options: "Options",
		tickTrue: "Yes",
		tickFalse: "No",
		withSelected: "With selected",
		blankSelection: "Please select at least one item",
		filter: "Filter"
	},
	
	enableFilter: true,
	enablePaging: true,	
	
	defaultPageSize: 20,
	
	linkTplsCache: {},
	
	initComponent: function () {
		// Create options menu
		if (this.rowOptionsMenu) {
			if (Ext.isArray(this.rowOptionsMenu)) {
				this.rowOptionsMenu = new Ext.menu.Menu(this.rowOptionsMenu); 
			}
			// Add options column
			this.columns.push({
				dataIndex: this.store.idProperty || this.store.reader.meta.id,
	        	width: 125,
	        	resizable: false,
	        	fixed: true,
	        	renderer: this.renderOptions.createDelegate(this),
	        	menuDisabled: true
			});
		}

		if (this.enablePaging) {
			// Create page size select
			var pageSizes = this.pageSizes || [10, 20, 50, 100];
			delete this.pageSizes;
			var menu = [];
			var h = this.changePageSize.createDelegate(this);		
			for (var i=0; i<pageSizes.length; i++) {
				menu.push({
					group: 'pagesize', 
					text: pageSizes[i].toString(), 
					checked: pageSizes[i] == this.defaultPageSize, 
					handler: h
				});
			}
			var bbar = new Ext.PagingToolbar({
		        store: this.store,
		    	items: ['-', {
		    		cls: "pagesize-btn",
		    		text: String.format(this.messages.pageSize, this.defaultPageSize),
		    		menu: menu
		    	}]
		    });
			this.bbar = bbar;
			
			if (this.withSelected) {
				if (!this.withSelected.menu) {
					this.withSelected = {menu:this.withSelected};
				}
				var ws = this.withSelected;
				if (Ext.isArray(ws.menu)) {
					for (var i=0, len=ws.menu.length; i<len; i++) {
						Ext.applyIf(ws.menu[i], {
							handler: this.submit, 
							scope: this
						});
					}
				}
				
				if (!ws.actionName) {
					ws.actionName = "action";
				}
				var hiddensHtml = '';
				if (ws.hiddens) {
					for (var k in ws.hiddens) {
						if (typeof ws.hiddens[k] != "function") {
							hiddensHtml += '<input type="hidden" name="'+k+'" value="'+ws.hiddens[k]+'" />';
						}
					}
				}
	
				var withSelectedTb = new Ext.Toolbar({
					actionName: ws.actionName,
					htmlBeforeEnd: hiddensHtml,
					buttons: [
			          	{text: this.messages.withSelected, menu: ws.menu}
					],
					onRender: function () {
						this.el = bbar.getEl();
						var table = this.el.insertHtml('beforeEnd', 
								'<table class="ux-tbar-right" cellspacing="0"><tr></tr></table>', 
								true);
						this.tr = table.child('tr');
						this.actionEl = this.el.createChild({tag: "input", type: "hidden", name: this.actionName});
						this.el.insertHtml("beforeEnd", this.htmlBeforeEnd);
					},
					afterRender: function () {
				        if(this.buttons){
				            this.add.apply(this, this.buttons);
				            delete this.buttons;
				        }
					}
				});
				bbar.on("render", withSelectedTb.render, withSelectedTb);
				this.withSelectedTb = withSelectedTb;
				
				// Add checkbox column
				this.columns.push({
					header: '<input type="checkbox" />',
					dataIndex: this.store.idProperty || this.store.reader.meta.id,
					width:25,
					resizable: false,
					fixed: true,
					menuDisabled: true,
					renderer: this.selCheckboxRenderer || Ext.ux.webta.GridViewer.columnRenderers.checkbox  
				});
			}
		}
			
		// Set cookie state provider if state manager configured with abstract provider
		if (Ext.state.Manager.getProvider().constructor == Ext.state.Provider.prototype.constructor) {
			Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
		}

		if (this.enableFilter) {
			var tbitems = [
               this.messages.filter +': ', 
               new Ext.ux.FilterField({
            	   store: this.store
               })
            ];
			if (this.tbar) {
				tbitems.push('-');
				Array.prototype.unshift.apply(this.tbar, tbitems);
			} else {
				this.tbar = tbitems;
			}
		}
			
		Ext.apply(this, {
			cls: "ux-gridviewer",
	        frame: true,
	        loadMask: true,
	        stateful: true,
	        stripeRows: true,
	        selModel: new Ext.ux.webta.CheckboxSelectionModel()
		});

		// Configure view
		this.viewConfig = Ext.apply(this.viewConfig || {}, {
			forceFit: true,
			templates: {
				// Cell is selectable
				cell: new Ext.Template(
					'<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} {css}" style="{style}" tabIndex="0" {cellAttr}>',
                    '<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
                    "</td>"				
				)
			}
		});
		this.getView().on("refresh", this.onViewRefresh, this, {delay: 50});
	
		Ext.ux.webta.GridViewer.superclass.initComponent.call(this);

		this.addEvents(
			/**
			 * @event beforeshowoptions
			 * Fires before options menu is shown
			 * @param {Ext.grid.GridPanel} grid
			 * @param {Ext.data.Record} record The selected grid record
			 * @param {Ext.menu.Menu} menu The row menu			 
			 * @param {Ext.EventObject} ev DOM click event
			 */
			"beforeshowoptions"
		);
	},
	
	onRender : function(container, position){
		// Setup select/deselect all handler
		if (this.withSelected) {
			(function () {
				var selHeader = this.getView().getHeaderCell(this.colModel.getColumnCount()-1);
				if (selHeader) {
					var ch = Ext.fly(selHeader).child("input[type=checkbox]");
					if (ch) {
						ch.on("click", this.toggleSelection, this);
					}
				}
			}).defer(50, this);
		}
		
		// Render row options menu when neded
		if (this.rowOptionsMenu) {
			this.rowOptionsMenu.render();
		}
		
		// call super
		Ext.ux.webta.GridViewer.superclass.onRender.call(this, container, position);

		// Clip document view
		if (this.maximize) {
			Ext.select("html").setStyle("overflow-x", "hidden");
			Ext.select("body").setStyle("overflow-x", "hidden");
			this.autoSize();
			(function () { Ext.EventManager.onWindowResize(this.autoSize, this); }).defer(1, this);
		}
	},
	
	toggleSelection: function (ev, node) {
		var col = this.colModel.getColumnCount()-1;
		var view = this.getView();
		var checked = node.checked;
		for (var row=0, rowCnt=this.store.getCount(); row<rowCnt; row++) {
			var el = view.getCell(row, col);
			var ch = Ext.DomQuery.selectNode("input[type=checkbox]", el);
			if (ch) {
				ch.checked = checked;
				if (checked) {
					// Select only rows with checkboxes					
					this.selModel.selectRow(row, true);
				}
			}
		}
		if (!checked) {
			// Clear all selections
			this.selModel.clearSelections();
		}
	},
	
	autoSize: function () {
		if (this.rendered) {
	   		var gridCt = this.getEl().parent();
	    	this.setWidth(Ext.lib.Dom.getViewWidth() - gridCt.getPadding("lr") - gridCt.getBorderWidth("lr"));
	    	this.setHeight(Math.max(300, Ext.lib.Dom.getViewHeight() - gridCt.getY() - gridCt.getPadding("tb") - gridCt.getBorderWidth("tb")));
		}
	},

/*

TODO: save/restore pageSize state
	getState: function () {
		var o = Ext.ux.webta.GridViewer.superclass.getState.call(this);
		o.pageSize = this.bottomToolbar.pageSize;
		return o;
	},

	applyState: function (o) {
		Ext.ux.webta.GridViewer.superclass.applyState.call(this, o);
		if (o.pageSize) {
			var bbar = this.bottomToolbar;
			bbar.pageSize = o.pageSize;
			console.log(this.getPageSizeBtn());
			var mi = this.getPageSizeBtn().menu.items.find(function (mi) {
				return Number(mi.getText()) == o.pageSize;
			});
			console.log(mi);
		}
	},
*/

	onViewRefresh: function () {
		var view = this.getView();
		view.mainBody.select(".ux-row-options-btn").on("click", this.showOptions, this);
		view.mainBody.select(".x-grid3-cell-last input").on("click", this.onCheckRow, this);
	},
	
	onCheckRow: function (ev) {
		var view = this.getView(), sm = this.getSelectionModel(), ch = ev.getTarget();
		var i = view.findRowIndex(ch);
		ch.checked ? sm.selectRow(i, true) : sm.deselectRow(i);
	},
	
	submit: function (menuItem, ev) {
		if (this.getSelectionModel().getCount() == 0) {
			alert(this.messages.blankSelection);
			return;
		}
			
		var action = this.withSelectedTb.actionEl.dom;
		action.value = menuItem.value;
		if (menuItem.formAction) {
			if (Ext.isIE) {
				action.form.attributes["action"].value = menuItem.formAction;  
			} else {
				action.form.action = menuItem.formAction;
			}
		}
		action.form.submit();
	},

	showOptions: function (ev) {
		var i = this.getView().findRowIndex(ev.getTarget());
		var record = this.store.getAt(i), data = record.data;
    	this.fireEvent("beforeshowoptions", this, record, this.rowOptionsMenu, ev);
    	
    	this.rowOptionsMenu.items.each(function (item) {
    		var display = this.getRowOptionVisibility(item, record);
    		item[display ? "show" : "hide"]();
    		if (display && item.href) { // Update item link
    			if (!this.linkTplsCache[item.id]) {
    				this.linkTplsCache[item.id] = new Ext.Template(item.href).compile();
    			}
    			var tpl = this.linkTplsCache[item.id];
    			item.el.dom.href = tpl.apply(record.data);
    		}
    	}, this);
    	
     	var btnEl = Ext.get(ev.getTarget("a"));
    	var xy = btnEl.getXY();
    	this.rowOptionsMenu.showAt([xy[0] - (this.rowOptionsMenu.getEl().getWidth() - btnEl.getWidth()), xy[1] + btnEl.getHeight()]);		
	},
	
	getRowMenuVisibility: function (record) {
		return true;
	},
	
	getRowOptionVisibility: function (menuItem, record) {
		return true;
	},
	
	changePageSize: function (cmp) {
		var bbar = this.bottomToolbar;
    	bbar.pageSize = Number(cmp.text);
		this.store.baseParams["limit"] = bbar.pageSize;
		if (!this.pageSizeBtn) {
    		this.pageSizeBtn = this.bottomToolbar.items.find(function (item) {
    			return typeof item.el.hasClass == "function" && item.el.hasClass("pagesize-btn");
    		}, this);
    	}
		this.pageSizeBtn.setText(String.format(this.messages.pageSize, bbar.pageSize));	
    	bbar.changePage(0);
	},

	renderOptions: function (value, p, record) {
		return this.getRowMenuVisibility(record) ? 
			'<a href="javascript:void(0)" class="ux-row-options-btn">'
			+ this.messages.options
			+ '<div class="ux-row-options-trigger"></div></a>' : '';    
    }
});

(function () {
	Ext.ux.webta.GridViewer.columnRenderers = {
		// Messages for translation
	    messages: {
	    	status: {
				// Defined in ext-ux.lang.php
	    	}
	    },
		// Create tick renderer 
	    tick: function (boolFn, trueMsg, falseMsg) {
	    	return (function (value, p, record) {
	        	p.css += " ux-cell-center";
	       		var b = boolFn(value, p, record);
	       		if (typeof b == "boolean") {
	       			var ret = String.format('<img alt="{0}" src="images/{1}" />', 
		        			b ? trueMsg || Ext.ux.webta.GridViewer.prototype.messages.tickTrue : 
		        				falseMsg || Ext.ux.webta.GridViewer.prototype.messages.tickFalse,
		        			b ? "true.gif" : "false.gif");
	           		return ret;
	       		}
	        	return "";    		
	    	}).createDelegate(this);
	    },
	    // Render checkbox
	    checkbox: function (value, p, record) {
	    	var dh = {tag: "input", type: "checkbox", value: record.id, name: "id[]"};
	    	return Ext.DomHelper.markup(dh);
	    },
		// Render domain status
	    domainStatus: function (value, p, record) {
		    var _t = Ext.ux.webta.GridViewer.columnRenderers.messages.status;
			var titles = {
				"Delegated": _t.delegated,
				"Preregistration delegated": _t.delegatedCaught,	
				"Transferred": _t.transferred,
				"Awaiting preregistration": _t.awaitingPreregistration,
				"Awaiting payment": _t.awaitingPayment,
				"Awaiting transfer authorization": _t.awaitingTransferAuthorization, 
				"Registration pending": _t.registrationPending,
				"Registration failed": _t.registrationFailed,
				"Transfer failed": _t.transferFailed,
				"Rejected": _t.rejected,
				"Pending delete": _t.pendingDelete,
				"Deleted": _t.deleted,
				"Pending": _t.pending,
				"Transfer requested": _t.transferRequested,
				"Expired": _t.expired
			};
			var className;
			if (["Delegated", "Transferred", "Preregistration delegated"].indexOf(value) != -1) {
				className = "status-ok";
			} else if (["Registration pending", "Awaiting preregistration"].indexOf(value) != -1) {
				className = "status-ok-pending"
			} else if (["Registration failed", "Transfer failed", "Rejected", "Expired", "Pending delete", "Deleted"].indexOf(value) != -1) {
				className = "status-fail";
			} else {
				className = "status-pending";
			}
			var title = titles[value] || value;
			
			return '<span class="'+className+'">'+title+'</span>';
	    }
	};
	
	var formats = {'MjY': 'M j, Y', 'Ymd' : 'Y-m-d'};
	for (var k in formats) {
		Ext.ux.webta.GridViewer.columnRenderers["date"+k] = 
			new Function("value", "p", "record", "try { return value ? value.dateFormat('"+formats[k]+"') : ''; } catch (e) { return e.message; }");
	}
})();

Ext.ux.webta.CheckboxSelectionModel = Ext.extend(Ext.grid.RowSelectionModel, {
	initEvents: function () {
	    var view = this.grid.view;
	    view.on("refresh", this.onRefresh, this);
	    view.on("rowupdated", this.onRowUpdated, this);
	    view.on("rowremoved", this.onRemove, this);		
	}
});

/**
 * @class Ext.ux.webta.JsonReader
 */
Ext.ux.webta.JsonReader = Ext.extend(Ext.data.JsonReader, {
	readRecords: function (o) {
		var dataBlock = Ext.ux.webta.JsonReader.superclass.readRecords.call(this, o);
		var meta = this.meta;
		if (meta.errorProperty) {
			if (!this.getError) {
				this.getError = this.getJsonAccessor(meta.errorProperty);
			}
			dataBlock.error = this.getError(o);
		}
		return dataBlock;
	}
});

/**
 * @class Ext.ux.webta.Store
 */
Ext.ux.webta.Store = function (config) {
	Ext.ux.webta.Store.superclass.constructor.call(this, config);
	this.addEvents(
		/**
		 * @event dataexception
		 * Fires if server returns errors understanded by reader.
		 */
		"dataexception"
	);
} 
Ext.extend(Ext.ux.webta.Store, Ext.data.Store, {
	loadRecords: function (dataBlock, options, success) {
		if (dataBlock.error) {
			this.fireEvent("dataexception", this, dataBlock.error);
		}
		Ext.ux.webta.Store.superclass.loadRecords.call(this, dataBlock, options, success);
	}
});

Ext.ux.parseQueryString = function (string) {
	var ret = {};
	var i;
	if (-1 != (i = string.search(/\?/))) {
		var parts = string.substr(i+1).split(/\&/);
		for (var j=0, len=parts.length; j<len; j++) {
			var keypair = parts[j].split(/\=/);
			ret[keypair[0]] = typeof keypair[1] != "undefined" ? keypair[1] : "";
		}
	}
	return ret;
}

Ext.ux.dataExceptionReporter = function (store, error) {
	var msgDh = {id: "Webta_ErrMsg", cls: "Webta_ErrMsg"};
	if (Ext.isArray(error)) {
		var inner = {tag: "span", style: "color: #CB3216", children: []};
		for (var i=0; i<error.length; i++) {
			inner.children.push('&bull;&nbsp;&nbsp;'+error[i]+'<br/>'); 
		}
		msgDh.children = [{tag: 'table', style: 'margin-top:0px', width: '100%', cellpadding:5, cellspacing:1, children: [
			{tag: 'tr', children: [{tag: 'td', children:[inner]}]}
		]}]
	} else {
		msgDh.html = error;
	}
	var msgEl = Ext.get('Webta_ErrMsg');
	if (msgEl) {
		msgEl.replaceWith(msgDh);
	} else {
		msgEl = Ext.fly('message-holder').insertFirst(msgDh);
	}
	new Effect.Pulsate(msgEl.dom);
}


// Localization

Ext.form.DateField.prototype.format = 'Y/m/d';


/**
 * @cfg {String/HTMLElement/Ext.Element} el
 * @cfg {String} hostname
 * @cfg {String} ipFieldName
 */
Ext.ux.NameserverField = function (config) {
	Ext.apply(this, config);
	
	this.el = Ext.get(this.el);
	this.el.on("keydown", this.onKeyDown, this, {delay: 50});
	
	this.hostRe = new RegExp("\\."+this.hostname.replace(/\./, "\\.")+"$");
	
	var val = this.el.dom.value, i;
	if ((i = val.indexOf(">")) != -1) {
		this.el.dom.value = val.substring(0, i);
		this.showIPField();
		this.ipField.dom.value = val.substring(i+1);
	}
}
Ext.ux.NameserverField.prototype = {
	messages: {
		ip: "IP"
	},
	
	onKeyDown: function (ev) {
		this.isGlueRecord(this.el.dom.value) ? this.showIPField() : this.hideIPField();  
	},
	
	isGlueRecord: function (ns) {
		return this.hostRe.test(ns);
	},

	showIPField: function () {
		if (!this.ipField) {
			this.ipFieldWrap = this.el.insertHtml("afterEnd", '<div class="ux-field-nameserver-ip">'
					 + '<label>' + this.messages.ip + ':</label><input type="text" name="' 
					 + (this.ipFieldName ? this.ipFieldName : this.el.dom.name + "_ip") + '" size="15" disabled></div>', true);
			this.ipField = this.ipFieldWrap.child("input");
		}
		this.ipField.dom.disabled = false;
		this.ipFieldWrap.show();
	},
	
	hideIPField: function () {
		if (this.ipField) {
			this.ipFieldWrap.hide();
			this.ipField.dom.disabled = true;
		}
	},
	
	/**
	 * @return {String} ex: ns.hostdad.com, ns.tellus.nl>154.78.33.197
	 */
	getValue: function () {
		var val = this.el.dom.value;
		return this.isGlueRecord(val) ? val+">"+this.ipField.dom.value : val;
	}
}
		


