<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

function lang($key,$type = null,$args = array()){
	echo Lang::_getInstance()->getValue($key,$type,$args);
}