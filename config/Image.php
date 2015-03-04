<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

$Image['file_index'] = 'file-upload';
$Image['contentPath'] = BASE_PATH . "/content/";
$Image['filters'] = array(
	'default' => array(
		'w' => "*",
		'h' => "*",
		'ext' => "png",
		'alpha' => true
	)
);