<?php
// if the application doesn't reside in root (nexuspro.info), but in a folder (nexuspro.info/example_app)
$basepath 	 = substr($_SERVER['SCRIPT_NAME'],0,-9);
$request_str = trim(substr($_SERVER['REQUEST_URI'],strlen($basepath)),'/');

// process the request
for ($i = 2, $arr = explode('/', $request_str), $n = count($arr); $i < $n; $i+=2) {
	if (!strstr($arr[$i],'[')) {
		if ($arr[$i][0] != '?')
			$_GET[$arr[$i]] = urldecode($arr[$i+1]);
		else unset($_GET[$arr[$i]]);
	} else
		$_GET[strstr($arr[$i],'[',true)][] = $arr[$i+1];
}
// process the module and action
$module 	 = !empty($arr[0]) ? $arr[0] : DEFAULT_MODULE;
$action		 = !empty($arr[1]) ? $arr[1] : DEFAULT_ACTION;
// maybe we have some ? in our URL.. let's clean it and do a redirect

if (strstr($request_str,'?')) {
	if (strstr($request_str,'/?') === false) {
		$request_str = str_replace('?', '/?', $request_str);
		redirect('/'.$request_str);
	}
	$path = '';
	foreach ($_GET as $key => $value)
		if (!is_array($value))
			$path .= '/' . $key . '/' . urlencode($value);
		else foreach ($value as $newKey => $newValue)
			$path .= '/' . $key . '[]/' . urlencode($newValue);
		
	$uri = $_SERVER['REQUEST_URI'];
	$path = $basepath . $module . '/' . $action . $path;
	redirect($path);
}

// sa purificam input-ul
foreach ($_GET as &$val)
	if (!is_array($val))
		$val = strip_tags($val);
foreach ($_POST as $id => &$val)
	if (!in_array($id, array('text') ) &&
			!is_array($val) )
		$val = strip_tags($val);

// initialize the main variable
$page = array();

// define the controller class
require 'inc/clase/Controller.class.php';

// include the specified module
if (!file_exists('modules/'.$module.'.php')) {
	header("HTTP/1.0 404 Not Found");
	header("Location: ".$basepath);
	exit('This page does not exist.');
}
require 'modules/'.$module.'.php';

// instantiate the module
$moduleName = ucfirst($module);
$controller = new $moduleName($page, $search);

// call the specified function
// we make sure the Controller's method exists..
if (!method_exists($moduleName,$action)) {
	header("HTTP/1.0 404 Not Found");
	header("Location: ".$basepath);
	exit('This page does not exist.');
}
//$controller->$action();
try {
	$controller->$action();
}
catch (Exception $e) {
	echo $e;
	header("HTTP/1.0 404 Not Found");
	header("Location: ".$basepath);
	exit('This page does not exist.');
}

// start the buffer
ob_start('pageContent');
	// include the view
	if (!$page['standalone'])
	require 'views/'.$module.'/'.$action.'.phtml';
	// clear the buffer
	ob_end_flush();
	function pageContent($content) { global $page; $page['content'] = $content; }

// include the template ( unless a flag is set )
if (!$page['single'] && !$page['standalone'])
	require 'template/index.php';
else
	if (!$page['standalone'])
		echo $page['content'];
