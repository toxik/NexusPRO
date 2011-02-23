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
		if (validate_required(user,"N-ati completat casuta username!")==false)
		{
			user.focus();
			return false;
		}
		if (validate_required(pass,"N-ati completat casuta password!")==false)
		{
			pass.focus();
			return false;
		}
	}
}