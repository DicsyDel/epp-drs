
var webtacp = new LibWebta({ load_calendar: load_calendar, load_treemenu: load_treemenu });
	webtacp.loadDefautls();

function addField(element) {
	element = $(element);
	if (!element) return;
	
	var parentNode = element.parentNode;

	var clone = parentNode.cloneNode(true);
	var inpus = clone.getElementsByTagName('INPUT');
	$A(inpus).each(function(input){
		if (input.type && input.type == 'text')
			input.value = '';
	});
	
	parentNode.parentNode.appendChild(clone);
}

function removeField(element) {
	element = $(element);
	if (!element) return;

	var parentNode = element.parentNode;
	
	// count of childs
	var childs = parentNode.parentNode.getElementsByTagName('DIV');
	var len = childs.length;
	
	if (len > 1) 
		Element.remove(parentNode);
}

