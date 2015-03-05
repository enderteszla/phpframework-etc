<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

$Validation['Image'] = array(
	'Width' => '_int',
	'Height' => '_int',
	'URL' => '_text'
);
$Validation['Token'] = array(
	'Content' => '_text',
	'Type' => array('session','activate','restorePassword'),
	'UserID' => '_id'
);
$Validation['User'] = array(
	'Email' => '_text',
	'Password' => '_text',
	'FirstName' => '_text',
	'LastName' => '_text'
);
$locales = array('ru','en');