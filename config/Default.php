<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

$Config['accountActivation'] = true;

$Config['locales'] = array('ru','en');
$Config['controller'] = 'Main';
$Config['method'] = 'index';

$Config['contentPath'] = "/content/";
$Config['uploadFileIndex'] = 'file-upload';
$Config['uploadFileMaxSize'] = '1000000';