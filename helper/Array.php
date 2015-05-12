<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

function is_assoc($a){
	return is_array($a) && count(array_filter(array_keys($a),function($v){return !is_numeric($v);}));
}

function keysUcFirst($a){
	return array_combine(
		array_map("ucfirst",array_keys($a)),
		array_values($a)
	);
}

function stringify($a){
	return var_export($a,true);
}