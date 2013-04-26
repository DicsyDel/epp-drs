function _debug(v, level, name) 
{
	window.document.body.appendChild(window.document.createElement('br')); 
	window.document.body.appendChild(window.document.createTextNode(v)); 
};

function SubmitForm(tp)
{
	if ($('sbmt1'))
		$('sbmt1').disabled = true;
		
	if ($('sbmt2'))
		$('sbmt2').disabled = true;
		
	$('direction').value = tp;
	$('frm1').submit();
	
	return false;
}
