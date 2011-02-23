<?php
function redirect($to) {
	global $basepath;
	if (substr($to,0,4) == 'http')
		header('Location: '.$to);
	else 
		header('Location: '.$basepath.trim($to,'/'));
	exit();
}
