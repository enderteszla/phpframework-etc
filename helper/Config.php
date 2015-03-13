<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

function config($key,$type = null,$value = null){
	return is_null($value) ?
		Config::getInstance()->getValue($key,$type) :
		Config::getInstance()->setValue($key,$type,$value);
}