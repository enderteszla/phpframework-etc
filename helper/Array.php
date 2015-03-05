<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

function is_assoc($a){
	return !empty(array_filter(array_keys($a),function($v){return !is_numeric($v);}));
}