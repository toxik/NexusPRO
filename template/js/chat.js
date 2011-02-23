$.extend({URLEncode:function(c){var o='';var x=0;c=c.toString();var r=/(^[a-zA-Z0-9_.]*)/;
  while(x<c.length){var m=r.exec(c.substr(x));
    if(m!=null && m.length>1 && m[1]!=''){o+=m[1];x+=m[1].length;
    }else{if(c[x]==' ')o+='+';else{var d=c.charCodeAt(x);var h=d.toString(16);
    o+='%'+(h.length<2?'0':'')+h.toUpperCase();}x++;}}return o;},
URLDecode:function(s){var o=s;var binVal,t;var r=/(%[^%]{2})/;
  while((m=r.exec(o))!=null && m.length>1 && m[1]!=''){b=parseInt(m[1].substr(1),16);
  t=String.fromCharCode(b);o=o.replace(m[1],t);}return o;}
});
/**
 * jQuery.ScrollTo - Easy element scrolling using jQuery.
 * Copyright (c) 2007-2009 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 5/25/2009
 * @author Ariel Flesler
 * @version 1.4.2
 *
 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html
 */
;(function(d){var k=d.scrollTo=function(a,i,e){d(window).scrollTo(a,i,e)};k.defaults={axis:'xy',duration:parseFloat(d.fn.jquery)>=1.3?0:1};k.window=function(a){return d(window)._scrollable()};d.fn._scrollable=function(){return this.map(function(){var a=this,i=!a.nodeName||d.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!i)return a;var e=(a.contentWindow||a).document||a.ownerDocument||a;return d.browser.safari||e.compatMode=='BackCompat'?e.body:e.documentElement})};d.fn.scrollTo=function(n,j,b){if(typeof j=='object'){b=j;j=0}if(typeof b=='function')b={onAfter:b};if(n=='max')n=9e9;b=d.extend({},k.defaults,b);j=j||b.speed||b.duration;b.queue=b.queue&&b.axis.length>1;if(b.queue)j/=2;b.offset=p(b.offset);b.over=p(b.over);return this._scrollable().each(function(){var q=this,r=d(q),f=n,s,g={},u=r.is('html,body');switch(typeof f){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(f)){f=p(f);break}f=d(f,this);case'object':if(f.is||f.style)s=(f=d(f)).offset()}d.each(b.axis.split(''),function(a,i){var e=i=='x'?'Left':'Top',h=e.toLowerCase(),c='scroll'+e,l=q[c],m=k.max(q,i);if(s){g[c]=s[h]+(u?0:l-r.offset()[h]);if(b.margin){g[c]-=parseInt(f.css('margin'+e))||0;g[c]-=parseInt(f.css('border'+e+'Width'))||0}g[c]+=b.offset[h]||0;if(b.over[h])g[c]+=f[i=='x'?'width':'height']()*b.over[h]}else{var o=f[h];g[c]=o.slice&&o.slice(-1)=='%'?parseFloat(o)/100*m:o}if(/^\d+$/.test(g[c]))g[c]=g[c]<=0?0:Math.min(g[c],m);if(!a&&b.queue){if(l!=g[c])t(b.onAfterFirst);delete g[c]}});t(b.onAfter);function t(a){r.animate(g,j,b.easing,a&&function(){a.call(this,n,b)})}}).end()};k.max=function(a,i){var e=i=='x'?'Width':'Height',h='scroll'+e;if(!d(a).is('html,body'))return a[h]-d(a)[e.toLowerCase()]();var c='client'+e,l=a.ownerDocument.documentElement,m=a.ownerDocument.body;return Math.max(l[h],m[h])-Math.min(l[c],m[c])};function p(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);

function str_replace (search, replace, subject, count) {
    var i = 0, j = 0, temp = '', repl = '', sl = 0, fl = 0,
            f = [].concat(search),
            r = [].concat(replace),
            s = subject,
            ra = r instanceof Array, sa = s instanceof Array;
    s = [].concat(s);
    if (count) {
        this.window[count] = 0;
    }
 
    for (i=0, sl=s.length; i < sl; i++) {
        if (s[i] === '') {
            continue;
        }
        for (j=0, fl=f.length; j < fl; j++) {
            temp = s[i]+'';
            repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
            s[i] = (temp).split(f[j]).join(repl);
            if (count && s[i] !== temp) {
                this.window[count] += (temp.length-s[i].length)/f[j].length;}
        }
    }
    return sa ? s : s[0];
}

/* closable tabs */
(function(){var c=$.ui.tabs.prototype._tabify;$.extend($.ui.tabs.prototype,{_tabify:function(){var a=this;c.apply(this,arguments);a.options.closable===true&&this.lis.filter(function(){return $("span.ui-icon-circle-close",this).length===0}).each(function(){$(this).append('<a href="#"><span class="ui-icon ui-icon-circle-close"></span></a>').find("a:last").hover(function(){$(this).css("cursor","pointer")},function(){$(this).css("cursor","default")}).click(function(){var b=a.lis.index($(this).parent());
b>-1&&a.remove(b);return false}).end()})}})})(jQuery);
var timer, userName, lastTimestamp = 0, newStuff = true, myH,
	smKey  = [':))',':)',':(',':d',':o',':|',':*',':p',':">',';))',';)',':x','=))',':x'],
	smCode = ['<span class="smile bigsmiley">&nbsp;</span>',
				'<span class="smile smiley">&nbsp;</span>',
				'<span class="smile sadface">&nbsp;</span>',
				'<span class="smile bigsmiley">&nbsp;</span>',
				'<span class="smile surprised">&nbsp;</span>',
				'<span class="smile speechless">&nbsp;</span>',
				'<span class="smile kiss">&nbsp;</span>',
				'<span class="smile tonque">&nbsp;</span>',
				'<span class="smile blush">&nbsp;</span>',
				'<span class="smile chuckle">&nbsp;</span>',
				'<span class="smile wink">&nbsp;</span>',
				'<span class="smile inlove">&nbsp;</span>',
				'<span class="smile ROFL">&nbsp;</span>',
				'<span class="smile inlove">&nbsp;</span>']

$(document).ready( function() {
	$("input:submit").button()
	$('#chatWrap').tabs({
		select: function(event, ui) {
			var receiver = ui.tab.toString().split('#')
			receiver = receiver[1]
			$("#receiver").attr('value',receiver)
			$('input[name=message]').focus();
			$('li > a[href="#all"]').next().remove()
			$('li > a[href!="#all"]').next().css({'padding' : '2px 2px 0px 0px'})
			$('a[href=#'+receiver+']').css('color', '')
		},
		show: function(event, ui) {
			var receiver = ui.tab.toString().split('#')
			receiver = receiver[1]
			$('#'+receiver).scrollTo('100%',0)
			$('li > a[href="#all"]').next().remove()
		},
		remove: function(event, ui) {
			$('li > a[href="#all"]').next().remove()
		},
		closable: true
	})
	$('li > a[href="#all"]').next().remove()
	updateMainChat()
	if (goChat)
		openChat(goChat, true)
})

$(window).unload(function() {
	clearTimeout(timer)
	$.getJSON('/chat/update/getOut')
})

function wEmoticons(chatLine) {
	return str_replace(smKey,smCode,chatLine.toLowerCase())
}

function updateMainChat() {
	$.getJSON("/chat/update/timestamp/"+lastTimestamp+"/callback/=?", function(data) {
		if (data[0] == 'Logged out.')
			self.close()
		updateUsers(data["chatters"])
		/*var chatters = ""
		for (var i in data["chatters"])
			chatters += '<li> '+
				'<a onclick="openChat(\''+data["chatters"][i]+'\', true)">'+data["chatters"][i]+'</a></li>'
		$('#userList').html(chatters)*/
		$('#userList > li > a').button()
		
		if ( data["messages"].length )
			newStuff = true
		else newStuff = false
		
		for ( var i in data["messages"] )
			if (data["messages"][i]["receiver"] == "all") {
				if ( !$('#all:visible').length ) {
					$('a[href=#all]')
						.css('color','red')
				}
				$('#all').append('<div> ['+ data["messages"][i]["timestamp"] + '] <b>'+
					data["messages"][i]["sender"] + '</b>: '+
					wEmoticons(data["messages"][i]["message"]) + '</div>') 
				lastTimestamp = data["messages"][i]["timestamp"] 
			}
			else chatWithMe(data["messages"][i])
		if (newStuff) 
			$('#chatWrap > div:visible').scrollTo(
				'100%',700,{easing:'easeInQuint'}).effect('highlight', 1000)
	
	})
	timer = setTimeout("updateMainChat()",2000)
}

function sendMessage() {
	if ($("input[name=message]").val()=="") return false;
	
	if ($('#receiver').attr('value') != 'all')
		$('#'+$('#receiver').attr('value')).append(
			'<div> ['+ dateF(new Date()) + '] <b>'+
					$("input[name=whoami]").val() + '</b>: '+
					wEmoticons($("input[name=message]").val()) + '</div>'
		)
	$('#'+$('#receiver').attr('value')).scrollTo('100%',0)
	$.post("/chat/update", $("#messageForm").serialize());
	$("input[name=message]").val("");
	$('input[name=message]').focus();
	return false;
}

function dateF( d ) {
	return d.getUTCFullYear()+"-"+(d.getUTCMonth()+1<10?"0"+(d.getUTCMonth()+1):(d.getUTCMonth()+1))+"-"+
			(d.getUTCDate()<10?"0"+d.getUTCDate():d.getUTCDate())+" "
			+(d.getHours()<10?"0"+d.getHours():d.getHours())+":"+
			(d.getMinutes()<10?"0"+d.getMinutes():d.getMinutes())+":"+
			(d.getSeconds()<10?"0"+d.getSeconds():d.getSeconds())
}

function openChat( nick, openNow ) {
	// verificam daca tabul exista
	if ($("#"+nick).length == 0) {
		// adauga div + tab
		$('#chatWrap')
			.append('<div id="'+nick+'" style="overflow: auto; height: 100%"></div>')
			.tabs("add","#"+nick,nick)
		$("#chatWrap > div").css('height', (myH-160) + "px")
	}
	
	if (openNow) { 
		$('#chatWrap').tabs("select",'#'+nick)
	}
}

function chatWithMe( varMesaj ) {
	openChat( varMesaj["sender"], false )
	// adauga mesajul
	if (varMesaj["message"] != "") {
		// sa coloram in rosu
		if ( !$('#'+varMesaj['sender']+':visible').length ) {
			$('a[href=#'+varMesaj['sender']+']')
				.css('color','red')
		}
		
		$('#'+varMesaj["sender"]).append('<div> ['+ varMesaj["timestamp"] + '] <b>'+
					varMesaj["sender"] + '</b>: '+
					wEmoticons(varMesaj["message"]) + '</div>')
	}
}

function updateUsers( newU ) {
	$('#userList li a').attr('marked', 'false')
	for ( i in newU ) {
		user = newU[i]
		if ($('#userList li a[href=#'+user+']').length)
			$('#userList li a[href=#'+user+']').attr('marked', 'true')
		else if ($('input[name=whoami]').attr("value") != user)
				$('#userList').append(
					'<li><a marked="true" href="#'+user+'" onclick="openChat(\''+user+'\', true)">'+user+'</a></li>'
				)
			else $('#userList').append('<li><a marked="true">'+user+' (Eu)</a></li>')
	}
	
	$('#userList li a[marked=false]').remove()
}