<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

$Config['rules']['Image'] = array(
	'Width' => '_int',
	'Height' => '_int',
	'URL' => '_emptyText'
);
$Config['rules']['Token'] = array(
	'Content' => '_text',
	'Type' => array('session','activate','restorePassword'),
	'UserID' => '_id'
);
$Config['rules']['User'] = array(
	'Email' => '_text',
	'Password' => '_text',
	'FirstName' => '_text',
	'LastName' => '_emptyText'
);

$Config['flags']['Image'] = array(
);
$Config['flags']['Token'] = array(
);
$Config['flags']['User'] = array(
	'Active',
);

$Config['functions'] = array('COUNT','MAX','MIN','GROUP_CONCAT');