function validate_required(field,alertxt)
{
	with(field)
	if (value==null||value=="")
	{
		alert(alertxt);
		return false;
	}
}

function validate_form(thisform)
{
	with(thisform)
	{
		if (validate_required(password,"N-ati completat casuta password!")==false)
		{
			password.focus();
			return false;
		}
	}
}