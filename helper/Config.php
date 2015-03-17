<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

function config($key,$type = null,$value = null){
	return is_null($value) ?
		Config::_getInstance()->getValue($key,$type) :
		Config::_getInstance()->setValue($key,$type,$value);
}