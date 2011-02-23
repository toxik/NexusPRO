<?php
class Json extends Controller {
	function index() {
		//header('Content-type: text/plain');
		//$this->d->setFetchMode(Zend_Db::FETCH_OBJ);
		$userTypes = $this->d->fetchAll('Select * From usertypes');
		echo  $_GET['_dc'].'{data: ' . json_encode($userTypes) . '}';
		exit();
	}
}