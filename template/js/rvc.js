function validate_required(field,alertxt)
{
	with(field)
	if (value.length!=13)
	{
		alert(alertxt);
		return false;
	}
}

function validate_form(thisform)
{
	with(thisform)
	{
		if (validate_required(cnp,"CNP completat incorect!")==false)
		{
			cnp.focus();
			return false;
		}
	}
}