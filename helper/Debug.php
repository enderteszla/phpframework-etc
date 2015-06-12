<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

/**
 * @param bool $switch
 * @return Debug|bool
 */
function debug($switch = null){
	switch(true){
		case $switch === true:
			return Debug::_getInstance()->start();
		case $switch === false:
			return Debug::_getInstance()->stop();
		default:
			return Debug::_getInstance()->status();
	}
}

/**
 * @return bool|string
 */
function trace(){
	return call_user_func_array(array(Debug::_getInstance(),'get'),func_get_args());
}

/**
 * @param int $n
 */
function linefeed($n = 1){
	while($n -- > 0) {
		echo config('lf', 'Debug');
	}
}