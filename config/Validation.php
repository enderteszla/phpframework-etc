<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

$Config['rules']['Image'] = array(
	'Width' => '_int',
	'Height' => '_int',
	'URL' => '_textEmpty'
);
$Config['rules']['Role'] = array(
	'Name' => '_text'
);
$Config['rules']['Token'] = array(
	'Content' => '_text',
	'Type' => array('session','activate','restorePassword'),
	'UserID' => '_id'
);
$Config['rules']['UpgradeDB'] = array(
	'Type' => array('core','test'),
	'Version' => '_text'
);
$Config['rules']['User'] = array(
	'Email' => '_text',
	'Password' => '_text',
	'FirstName' => '_text',
	'LastName' => '_textEmpty',
	'RoleID' => '_idNull'
);

$Config['flags']['Image'] = array(
);
$Config['flags']['Role'] = array(
);
$Config['flags']['Token'] = array(
);
$Config['flags']['UpgradeDB'] = array(
);
$Config['flags']['User'] = array(
	'Active'
);

$Config['functions'] = array('COUNT','MAX','MIN','GROUP_CONCAT');