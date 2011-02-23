<?php
$scriptExecutiontime = microtime(true);
define('NEX_EXEC',true);

// load the configuration
require 'inc/config.php';

// load default set of functions
require 'inc/functions.php';


// load the Zend's Framework autoloader
require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->suppressNotFoundWarnings(false);

// deschidem o sesiune
session_start();

// include the search class, maybe it's needed
require 'inc/clase/Search.class.php';

// setam management-ul erorilor
if ($_SERVER['APPLICATION_ENV'] == 'development') {
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', 'stdout');
}

// load the routing system
require 'inc/router.php';

// time the app
$scriptExecutiontime = microtime(true) - $scriptExecutiontime ;
if (!isset($page['noMeasure']))
	echo '<!-- timp executie: ' . $scriptExecutiontime . ' secunde -->';