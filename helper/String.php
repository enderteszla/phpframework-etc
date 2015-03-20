<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

function prefix($prefix){
	return function($str) use($prefix){
		return "{$prefix}{$str}";
	};
}

function postfix($postfix){
	return function($str) use($postfix){
		return "{$postfix}{$str}";
	};
}