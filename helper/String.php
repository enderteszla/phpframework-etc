<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

/**
 * @param string $prefix
 * @return callable
 */
function prefix($prefix){
	return function($str) use($prefix){
		return "{$prefix}{$str}";
	};
}

/**
 * @param string $postfix
 * @return callable
 */
function postfix($postfix){
	return function($str) use($postfix){
		return "{$str}{$postfix}";
	};
}