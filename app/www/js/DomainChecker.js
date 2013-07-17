DomainChecker = Class.create({
	/**
	 * @enum
	 */
	ResultStatus: {
		AVAIL: 1,
		UNAVAIL: 0,
		FAILED: 2
	},
	
	/**
	 * @cfg {String/HTMLElement} renderTo
	 * @cfg {String} baseUrl
	 * @cfg {String} requestMethod
	 * @cfg {String} formField
	 * @cfg {Array} queue
	 * @cfg {Function} dataReader must return structure {{Number} status,  {String} message}
	 * @cfg {Number} numWorkers
	 * @cfg {String} availMessage
	 * @cfg {String} unavailMessage
	 * @cfg {String} failedMessage
	 * 
	 * @event {Function} onComplete
	 */
	baseUrl: '',
	requestMethod: 'POST',
	formField: 'domains',
	queue: [],	
	numWorkers: 2,
	
	/**
	 * @private
	 */
	workersRuntime: {},	
	
	initialize: function (config) {
		// Copy configuration
		Object.extend(this, config);

		// Init
		this.query = this.baseUrl + (this.baseUrl.indexOf('?') != -1 ? '&' : '?') + 'name=#{name}'; 

		this.tips = {};
		['availMessage', 'unavailMessage', 'failedMessage'].each(function (p) {
			if (this[p]) {
				this.tips[p] = this.renderTooltip(this[p], p == 'availMessage');
			}
		}, this);
		
		// Render HTML widget
		try {
			this.render();
		} catch (e) {
			alert('Failed to render html widget. ' + e.message);
		}
	},

	
	addDomain: function (name) {
		this.queue.push(name);
		this.items[name] = this.renderItem(name);
	},
	
	start: function () {
		// Run workers
		for (var i=0; i<this.numWorkers; i++) {
			this.launchWorker();
		}
	},
	
	stop: function () {
		for (var workerId in this.workersRuntime) {
			this.killWorker(workerId);
		}
	},
	
	
	// Queue handling methods
	
	launchWorker: function () {
		var workerId = parseInt(Math.random()*100000);
		this.queueWorker.bind(this, workerId).defer();
	},
	
	/**
	 * Process one element from queue
	 */
	queueWorker: function (workerId) {
		var item = this.queue.shift();
		if (item != undefined) {
			// Initialize runtime
			var request = new Ajax.Request(this.query.interpolate({name: encodeURIComponent(item)}), {
				method: this.requestMethod.toLowerCase(),
				domainName: item,
				workerId: workerId,
				onSuccess: function (response, xhr) {
					this.responseHandler(true, response, xhr) 
				}.bind(this), 
				onFailure: function (response, xhr) {
					this.responseHandler(false, response, xhr)
				}.bind(this)
			});
			var rt = {
				item: item,
				workerId: workerId,
				request: request 
			};
			this.workersRuntime[workerId] = rt;
			
			// Change item UI to checking state.. 
			this.updateUI ("checking", {
				item: item
			});
		} else if (this.queue.length == 0 && Object.keys(this.workersRuntime).length == 0 && this.onComplete) {
			// Fire complete event
			this.onComplete();
		}
	},
	
	/**
	 * @private
	 */
	responseHandler: function (success, response, xhr) {
		if (success) {
			var chkData;
			if (this.dataReader) {
				chkData = this.dataReader(response, xhr); 
			} else {
				try {
					eval("chkData = " + response.responseText + ";");
				} catch (e) {
					return;
				}				
			}
		} else {
			// Failed to check item 
			chkData = {status: 2};
		}
		
		// Update UI
		this.updateUI("checked", {
			item: response.request.options.domainName,
			chkData: chkData
		});
		
		// Finalize current task
		delete this.workersRuntime[response.request.options.workerId];
		
		// Run new worker task
		this.launchWorker();
	},
	
	/**
	 * @private
	 */
	killWorker: function (workerId) {
		var rt = this.workersRuntime[workerId];
		if (rt) {
			// Abort request
			rt.request.transport.abort();
			
			// Restore UI
			this.updateUI("reset", {
				item: rt.item
			});
		
			// Finalize task
			delete this.workersRuntime[workerId];
		}
	},
	
	// UI constructing methods
	

	/**
	 * @private
	 */
	findElForItem: function (name) {
		var id = this.items[name];
		return id ? $(id) : null; 
	},
	
	findItemForEl: function (el) {
		var id = el.id;
		for (var name in this.items) {
			if (this.items[name] == id) {
				return name;
			}
		}
	},
	
	/**
	 * @private
	 */
	render: function () {
		
		// Prepare event handlers
		this.domListeners = {};		
		['onRemoveClicked', 'onWhoisClicked'].each(function (methodName) {
			this.domListeners[methodName] = this[methodName].bindAsEventListener(this);
		}, this);
		// Render widget el	
		this.el = $(document.createElement("div"));
		$(this.renderTo).insert(this.el);
		// Render items
		this.items = {};
		for (var i=0; i<this.queue.length; i++) {
			var item = this.queue[i];
			this.items[item] = this.renderItem(item);
		}
	},
	
	// TODO: podmandit' template 
	itemTemplate: 
		'<div id="#{id}" class="domain_row_container" style="overflow: hidden; height: 26px;">' +
			'<div height: 24px;">' +
				'<div style="float: left; margin-top: 3px;">' +
					'<img class="icon" style="vertical-align: middle; margin-right: 10px; margin-left: 5px;" src="#{icon}"/>' +
					'<span style="vertical-align: middle;">#{name}</span>' +
				'</div>' +
				'<div style="float: right; margin-top: 3px;">' +
					'<a href="javascript:void(0);" class="remove" style="margin: 2px; vertical-align: middle;">' +
					'<img border="0" align="middle" src="#{trashIcon}" style="margin: 2px 3px 0px 0px; vertical-align: middle;"/>' +
					'<span style="vertical-align: middle;">Remove</span></a>' +
					'<a href="javascript:void(0);" class="whois" style="margin: 2px 2px 2px 5px; display: none; vertical-align: middle;">' +
					'<img border="0" src="/images/whois.gif" style="margin: 2px 3px 0px 5px; vertical-align: middle;"/>' +
					'<span style="vertical-align: middle;">Whois</span></a>' +
				'</div>' +
				'<input type="hidden" style="margin: 0px; padding: 0px;" value="#{name}" name="#{formField}[#{name}][name]"/>' +
				'<input type="hidden" style="margin: 0px; padding: 0px;" value="0" class="avail_hidden" name="#{formField}[#{name}][avail]"/>' +
		'</div>',
	
	renderItem: function (item) {
		var id = Element.uniqId();
		// Render DOM
		this.el.insert(this.itemTemplate.interpolate({
			id: id,
			name: item,
			formField: this.formField,
			icon: "/images/wait.gif",
			trashIcon: "/images/trash.gif"
		}));
		var itemEl = $(id);
		// Add event listeners
		itemEl.down('.remove').observe("click", this.domListeners.onRemoveClicked);
		itemEl.down('.whois').observe("click", this.domListeners.onWhoisClicked);
		return id;
	},

	/**
	 * @private
	 */
	renderTooltip: function (message, okFlag) {
		var tipId = Element.uniqId();
		$(document.body).insert("<div id=#{id} class=#{className} style=#{style}>#{text}</div>".interpolate({
			id: tipId,
			className: okFlag ? 'ok_hint' : 'error_hint',
			text: message,
			style: 'display:none'
		}));
		return tipId;
	},

	
	/**
	 * @private
	 */
	updateUI: function (event, drawData) {
		itemEl = drawData.item ? this.findElForItem(drawData.item) : null;
		if (event == "checking") {
			// snakee			
			itemEl.down('.icon').src = "/images/load.gif";
		} else if (event == "checked") {
			// render check results			
			var chkData = drawData.chkData;
			var icon, messageId, avail;
			if (chkData.status == 0) {
				icon = "/images/unavail.gif";
				messageId = "unavailMessage";
			} else if (chkData.status == 1) {
				icon = "/images/avail.gif";
				messageId = "availMessage";
			} else {
				icon = "/images/fail.gif";
				messageId = "failedMessage";
			}
			avail = chkData.status == this.ResultStatus.AVAIL ? 1 : 0;
			// TODO: create tooltip for chkData.message
			
			// Update icon
			var iconImg = itemEl.down('.icon');
			iconImg.src = icon;
			// Set tooltip
			new Tooltip(iconImg, this.tips[messageId]);
			// Set avail hidden value
			itemEl.down('input.avail_hidden').value = avail;
			
		} else if (event == "reset") {
			// reset item UI			
			itemEl.down('.icon').src = "/images/wait.gif";
		}
	},
	
	
	// UI listeners

	/**
	 * Remove item from check list
	 */	
	onRemoveClicked: function (e) {
		var itemEl = e.element().up('.domain_row_container');
		var item = this.findItemForEl(itemEl);
		if (item) {
			// Item may be processed right now..
			// In this case kill his worker and launch new for the next queue element
			Object.values(this.workersRuntime).each(function (rt) {
				if (rt.item == item) {
					this.killWorker(rt.workerId);
					this.launchWorker();
				}
			}, this);
			
			// Remove item from queue. 
			this.queue = this.queue.without(item);
			
			// update UI
			itemEl.remove();	
		}
	},
	
	/**
	 * Show WHOIS information for domain.
	 */
	onWhoisClicked: function (e) {
		//TODO:
	}
});


/**
 * Extend Ajax.Request with timeout controlling functionality.
 * Additional options:
 * 	{Function} onAbort
 * 	{Number} timeout 
 */
XhrAbortPlugin = {
	timeout: 30000,
	
	onCreate: function (request, xhr) {
		request._timeoutId = setTimeout(
			this.abortRequest.bind(this, request, xhr), 
			request.options.timeout || this.timeout
		); 
	},
	
	onComplete: function (request) {
		this.stopWaiting(request);
	},
	
	abortRequest: function (request, xhr) {
		if (this.callInProgress) {
			this.stopWaiting(request);
			xhr.abort();
			if (request.options.onAbort) request.options.onAbort(request, xhr);
			if (request.options.onFailure) request.options.onFailure(request, xhr);
		}
	},
	
	callInProgress: function (xhr) {
		return [1, 2, 3].include(xhr.status); 
	},
	
	stopWaiting: function (request) {
		if (request._timeoutId) {
			clearTimeout(request._timeoutId);
			delete request._timeoutId;			
		}
	}
}
Ajax.Responders.register(XhrAbortPlugin);

Object.extend(Element, {
	_uniqId: 1000,
	uniqId: function () {
		return "webta-" + (++Element._uniqId);
	}
})
