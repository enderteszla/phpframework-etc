<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

$Config['Image'] = array(
	'Width' => '_int',
	'Height' => '_int',
	'URL' => '_emptyText'
);
$Config['Token'] = array(
	'Content' => '_text',
	'Type' => array('session','activate','restorePassword'),
	'UserID' => '_id'
);
$Config['User'] = array(
	'Email' => '_text',
	'Password' => '_text',
	'FirstName' => '_text',
	'LastName' => '_emptyText'
);