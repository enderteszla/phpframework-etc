<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

function debug($switch = null){
	switch($switch){
		case true:
			return Debug::_getInstance()->start();
		case false:
			return Debug::_getInstance()->stop();
		default:
			return Debug::_getInstance()->status();
	}
}
function trace(){
	return call_user_func_array(array(Debug::_getInstance(),'get'),func_get_args());
}