Checklist = Class.create({
	
	// Constructor
	initialize : function (el) {
		this.el = $(el);
		this.sourceListEl = this.el.select('.w-checklist-sidebar-left .w-checklist-list')[0];
		this.selectedListEl = this.el.select('.w-checklist-sidebar-right .w-checklist-list')[0];
		this.inputName = (this.sourceListEl.select('input')[0] || {name: null}).name;
		
		this.toggleHandler = this.toggle.bindAsEventListener(this);
		this.sourceListEl.select('input').each(function (chEl) {
			Event.observe(chEl, 'click', this.toggleHandler);
			chEl.removeAttribute("name");
		}, this);
		this.selectedListEl.select('input').each(function (chEl) {
			Event.observe(chEl, 'click', this.toggleHandler);
		}, this);
	},
	
	/**
	 * Toggle item between selected/source list
	 */
	toggle: function (ev) {
		var chEl = ev.element();
		var itemEl = chEl.up('li');
		
		if (chEl.checked) {
			this.selectedListEl.insert(itemEl);
			chEl.setAttribute("name", this.inputName);
		} else {
			this.sourceListEl.insert(itemEl);
			chEl.removeAttribute("name");
		}
	}
});

Event.observe(window, 'load', function () {
	$$('.w-checklist').each(function (el) {
		new Checklist(el);
	});
});
