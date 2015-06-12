<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

/**
 * @param string $key
 * @param string $value
 * @return Input|bool|string
 */
function input($key,$value = null){
	return is_null($value) ?
		Input::_getInstance()->getValue($key) :
		Input::_getInstance()->setValue($key,$value);
}