<?php
	if (!defined('NEX_EXEC')) {
		header("HTTP/1.0 404 Not Found");
		header("Location: ../");
		exit('This page does not exist.');
	}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<base href="http://<?=$_SERVER['HTTP_HOST'].$basepath?>" />
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?=DEFAULT_TITLE?></title>
	<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/flick/jquery-ui.css" rel="stylesheet" type="text/css"/>
	<link href="template/css/main.css" rel="stylesheet" type="text/css"/> 
	<link href="template/css/fonts.css" rel="stylesheet" type="text/css"/> 
	<link href="template/css/superfish.css" rel="stylesheet" type="text/css"/> 
	<link href="template/css/star_rating.css" rel="stylesheet" type="text/css"/> 
	<? if ($page['css']): ?>
	<style type="text/css"><?=$page['css']?></style>
	<? endif; ?>
</head>
<body>
	<div id="wrap">
		<div id="header">
			<div id="leftSide">
				<h1 class="logoBig"><a href="/" id="logo">NeXuS <sup>PRO</sup></a></h1>
				<div id="searchBar">
					<form action="cauta/index">
					<fieldset>
						<input type="text" name="s" id="s" value="<?=$_GET['s']?>" /> 
						<input type="submit" value="Caută" />
					</fieldset>
					</form>
				</div>
			</div>
			<div id="loginBox">
				<? if ($_SESSION['logat']): ?>
				<div id="logged">
					<p>Bine ai venit, <a href="ucp/profil/username/<?=$_SESSION['user_name']?>">
						<?=$_SESSION['user_first_name']?></a> !</p>
					<p><a href="auth/logout">Delogare</a></p>
				</div>
				<? else: ?>
				<form method="post" action="auth/index/">
				<table style="width:250px"><tr>
					<td style="width:100px"><label for="user">Username</label></td>
					<td><input type="text" name="user" id="user" tabindex="1" /></td></tr>
					<tr><td><label for="pass">Parola</label></td><td>
					<input type="password" name="pass" id="pass" tabindex="2" /></td></tr>
					<tr><td>
						<em>Nu ai cont?</em> <a href="auth/signup" tabindex="4">Înregistrează-te!</a>
					</td>
					<td><input type="submit" name="" value="Login" tabindex="3" /></td></tr>
				</table>
				</form>
				<? endif; ?>
			</div>
			<div class="clear"></div>
		</div>
		
		<div id="menus">
			<ul class="sf-menu">
				<li><a href="/">Home</a></li>
				<li><a href="work/listare">Proiecte</a>
					<ul>
						<li><a href="work/listare">Listare</a>
							<?=$page['categorii']?>
						</li>
						<li><a href="work/index">Workshop</a></li>
						<li><a href="bid/request">Cere proiect!</a></li>
						<?php if($_SESSION['usertype_id'] == 2):?>
							<li><a href="bid/evalueaza">Pre-evaluează!</a></li>
						<?php endif ?>
						<?php if($_SESSION['usertype_id'] != 1 && 
									$_SESSION['usertype_id'] != 6):?>
						<li><a href="bid/available">Licitează!</a></li>
						<li><a href="bid/mywork">În curs..</a></li>
						<?php endif ?>
						<li><a href="bid/myproj">Cumpărate</a></li>
					</ul>
				</li>
				<li><a class="chat_link" href="statice/eroare/nojs">Chat</a>
					<ul>
						<li><a class="chat_link" href="statice/eroare/nojs">Accesare chat</a></li>
						<li><a href="cauta/useri">Listă utilizatori</a></li>
					</ul>
				</li>
				<li><a href="ucp">Control Panel</a>
					<ul>
						<li><a href="ucp/index">Vizualizare</a></li>
						<li><a href="ucp/cumpara_credit">Cumpără credit</a></li>
						<li><a href="ucp/schimba_parola">Schimbă parola</a></li>
						<li><a href="ucp/schimba_contact">Date contact</a></li>
						<li><a href="ucp/schimba_info">Informaţii personale</a></li>
					</ul>
				</li>
				<li><a href="forum/index">Forum</a></li>
				<li><a href="app">Aplicaţii</a>
					<ul>
						<li><a href="app/index">Listare</a></li>
						<li><a href="app/add">Adăugare</a></li>
						<li><a href="app/my">Aplicaţiile mele</a></li>
					</ul>
				</li>
			</ul>
			
			<ul class="sf-menu" style="float: right">
				<li><a href="statice/echipa">Echipa</a></li>
				<li><a href="statice/contact">Contact</a></li>
				<li><a href="statice/suport">Suport</a></li>
			</ul>
			
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
		
		<div id="content"><?=$page['content']?></div>

		<div id="footer">
			<div style="float: left">
				<a href="http://validator.w3.org/check?uri=referer">Validează xhtml</a>
			</div>
			<h4>Copyright &copy; 2010 <span class="logoSmall">NeXuS <sup>PRO</sup></span></h4>
		</div>
	</div>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script> 
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js"></script>
	<script type="text/javascript" src="template/js/superfish.js"></script>
	<script type="text/javascript" src="template/js/jquery.goodies.js"></script>
	<? if ($page['jsf'] && is_array($page['jsf']))
		foreach ($page['jsf'] as $script)
			echo '<script type="text/javascript" src="template/js/'.$script.'"></script>';
	?>
	<? if(!empty($page['js'])): ?>
	<script type="text/javascript">
	<?=$page['js']?>
	</script>
	<? endif; ?>
	<script type="text/javascript">
		$(document).ready( function() {
			$('#clickMe').click(function(){
				$('#clickMe').effect("transfer", { to: $("#transfer") }, 1000);
				return false;
			})
		})
	</script>
</body>
</html>
<?php if ($_SESSION['logat'] && false): ?>
<div style="max-width: 100%">
	<div style="float: left; border: 1px solid #fff;padding:2px">
		<?php Zend_Debug::dump($_SESSION, 'SESSION'); ?>
	</div>
	<div style="float: left; border: 1px solid #fff;margin: 0 5px 0;padding:2px ">
		<?php Zend_Debug::dump($_GET, 'GET'); ?></div>
	<div style="float: left; border: 1px solid #fff;padding:2px">
		<?php Zend_Debug::dump($_POST, 'POST'); ?></div>
	<div style="clear: both">&nbsp;</div>
	<?php if($_FILES): ?>
	<div style="float: left; border: 1px solid #fff;padding:2px">
		<?php Zend_Debug::dump($_FILES, 'FILES'); ?></div>
	<div style="clear: both">&nbsp;</div>
	<?php endif; ?>
</div>
<?php endif;