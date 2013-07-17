
createPhoneField = function () {
	var parsePhone = function (config) {
		if (!config.value.length)
			return;
			
		var parsed_format = [];
	
		var number_re = /\[(\d+)\]/;
		var number2_re = /\[(\d+)-(\d+)\]/;

		if (Prototype.Browser.IE) {
			var re = /(\[cc\]|\[\d+\]|\[\d+-\d+\])/gi;
			var delimiters = config.format.split(re);
			var numbers = [];
			var match;
			
			var re = /(\[cc\]|\[\d+\]|\[\d+-\d+\])/gi;
			while (match = re.exec(config.format)) {
				numbers.push(RegExp.$1);
			}
			
			var parts = [];
			for (var i=0; i<delimiters.length; i++) {
				parts.push(delimiters[i]);
				parts.push(numbers[i]);
			}
		}
		else {
			var re = /(\[cc\]|\[\d+\]|\[\d+-\d+\])/;
 			var parts = config.format.split(re);			
		}
		
 		for (var i=0; i<parts.length; i++) {
 			if (parts[i] == '[cc]') {
 				parsed_format.push({
 					type: 'cc'
 				});
 			} 
 			else if (number_re.test(parts[i])) {
 				var m = number_re.exec(parts[i]);
 				parsed_format.push({
 					type: 'number',
 					minlength: m[1],
 					maxlength: m[1]
 				});
 			}
 			else if (number2_re.test(parts[i])) {
 				var m = number2_re.exec(parts[i]);
 				parsed_format.push({
 					type: 'number',
 					minlength: m[1],
 					maxlength: m[2]
 				});
 			}
 			else {
 				parsed_format.push({
 					type: 'delimiter',
 					value: parts[i]
 				})
 			}
 		}
		
 		
 		//
 		var phone = new String(config.value);
 		//console.log(phone);
 		for (var i=0; i<parsed_format.length; i++) {
 			var part = parsed_format[i];
 			var re = skiplen = v = null;
 			if (part.type == 'cc' || part.type == 'number') {
 				re = /^(\d+)/;
 			}
 			/*
 			else if () {
 				re = /(\d+)/ 
 				
 				new RegExp('^(\\d{' + part.minlength + ',' + part.maxlength + '})');
 			}
 			*/
 			else if (part.type == 'delimiter' && part.value.length) {
 				skiplen = part.value.length;
 			}
 			else {
 				continue;
 			}
 			
			if (re) {
 				var m = re.exec(phone);
 				v = m[1];
 				phone = phone.substr(v.length);
 			} else if (skiplen) {
 				v = phone.substr(0, skiplen);
 				phone = phone.substr(skiplen);
 			}
 			else {
 				continue;
 			}
 			
 			part.value = v;
 		}
 		
 		// 
 		var elems = $$(config.wname);
 		for (var i=0; i<elems.length; i++) {
 			var el = elems[i];
 			var elName = el.nodeName.toLowerCase();
 			if (elName == 'select' || elName == 'input') {
				el.value = parsed_format[i].value;
 			}
 		}
		
	}
	
	var getValue = function (wname) {
		var elems = $$(wname);
		var ret = ''; var notFilled = false;
		for (var i=0; i<elems.length; i++) {
			var el = elems[i];
			var elName = el.nodeName.toLowerCase(); 
			if (elName == 'span') {
				ret += el.innerHTML;
			} else if (elName == 'input' || elName == 'select') {
				if (!el.value.length) {
					notFilled = true;
					break;
				}
				ret += el.value;
			}
		}
		
		return notFilled ? '' : ret;
	}
	
	return function (config) {
		setTimeout(function () {
			//try {
				config.wname = '.' + config.wname;	
			
				parsePhone(config);
				var controls = $$(config.wname);
				
				var field = document.createElement('input'); 
				field.type = 'hidden';
				field.name = config.name;
				field.value = config.value;
				controls[0].parentNode.appendChild(field);
				
				for (var i=0; i<controls.length; i++) {
					var name = controls[i].nodeName.toLowerCase();
					if (name == 'select' || name == 'input') {
						Event.observe(controls[i], 'change', function () {
							var v = getValue(config.wname);
							
							field.value = v;
						});
					}
				}
				
				Event.observe(field.form, 'submit', function (event) {
					field.value = getValue(config.wname);
					//alert(field.value);
				});
			//}
			//catch (e) {
			///	alert(e.message);
			//}
		
		}, 50);
	
	}
}();