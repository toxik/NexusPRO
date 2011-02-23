function validate_required(field,alertxt)
{
	with(field)
	if (value=="")
	{
		alert(alertxt);
		return false;

	if (alertxt=="N-ati completat descrierea proiectului!")
		if(value=="Descriere proiect...")
		{
			alert(alertxt);
			return false;
		}
	}
}

function validate_form(thisform)
{
	with(thisform)
	{
		if (validate_required(project_name,"N-ati completat numele proiectului!")==false)
		{
			project_name.focus();
			return false;
		}
		if (validate_required(project_description,"N-ati completat descrierea proiectului!")==false)
		{
			project_description.focus();
			return false;
		}
	}
}