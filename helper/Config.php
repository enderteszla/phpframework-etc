<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

/**
 * @param string $key
 * @param string $type
 * @param mixed $value
 * @return Config|callable|mixed
 */
function config($key,$type = null,$value = null){
	return is_null($value) ?
		Config::_getInstance()->getValue($key,$type) :
		Config::_getInstance()->setValue($key,$type,$value);
}