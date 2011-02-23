var myChatPopup = null

function openChatWindow(url) {
	if (!myChatPopup || myChatPopup.closed) {
			myChatPopup = window.open(url,'Chat',
				'menubar=no,width=800,height=600,toolbar=no')
			if (myChatPopup.focus) myChatPopup.focus()
		}
	else if (myChatPopup.focus) myChatPopup.focus()
	return false
}

jQuery(function($){
	$.datepicker.regional['ro'] = {
		closeText: 'Închide',
		prevText: '&laquo; Luna precedentă',
		nextText: 'Luna următoare &raquo;',
		currentText: 'Azi',
		monthNames: ['Ianuarie','Februarie','Martie','Aprilie','Mai','Iunie',
		'Iulie','August','Septembrie','Octombrie','Noiembrie','Decembrie'],
		monthNamesShort: ['Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun',
		'Iul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		dayNames: ['Duminică', 'Luni', 'Marţi', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'],
		dayNamesShort: ['Dum', 'Lun', 'Mar', 'Mie', 'Joi', 'Vin', 'Sâm'],
		dayNamesMin: ['Du','Lu','Ma','Mi','Jo','Vi','Sâ'],
		dateFormat: 'yy-mm-dd', firstDay: 1,
		isRTL: false};
	$.datepicker.setDefaults($.datepicker.regional['ro']);
});


$(document).ready(function() {
	$("#s").focus()
	$('a.chat_link').click( function() { return openChatWindow('/chat') })
	$('.goChat').click(function() {
		if (!myChatPopup || myChatPopup.closed) {
			openChatWindow('/chat')
			myChatPopup.goChat = $(this).attr('rel')
		}
		else
			myChatPopup.openChat($(this).attr('rel'),true)
		if (myChatPopup.focus) myChatPopup.focus()
		return false
	})
	
	$("#nav-one li").hover(
		function() { $("ul", this).fadeIn("fast") },
		function() { } 
	)
	
	$("ul.sf-menu").superfish({
		 delay:       1000,
		animation:   {opacity:'show',height:'show'},
		speed:       'fast',
		autoArrows:  false,
		pathClass:  'current' 
	})
	
})

