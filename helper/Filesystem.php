<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

/**
 * @param string $source
 * @param string $target
 * @return bool
 */
function copyContent($source,$target){
	if(!is_writable($target)){
		Error::_getInstance()->add('filesystem',0,array($target));
		return false;
	}
	$files = scandir($source);
	foreach($files as $file){
		switch(true){
			case $file == '.':
			case $file == '..':
				continue;
			case is_dir($source . $file):
				if(!is_dir($target . $file)){
					mkdir($target . $file);
				}
				copyContent("{$source}{$file}/", "{$target}{$file}/");
				break;
			default:
				copy($source . $file, $target . $file);
		}
	}
	return true;
}

/**
 * @param string $target
 * @return bool
 */
function removeContent($target){
	if(!is_writable($target)){
		Error::_getInstance()->add('filesystem',0,array($target));
		return false;
	}
	$files = scandir($target);
	foreach($files as $file){
		switch(true){
			case $file == '.':
			case $file == '..':
				continue;
			case is_dir($target . $file):
				removeContent("{$target}{$file}/");
				rmdir("{$target}{$file}/");
				break;
			default:
				unlink("{$target}{$file}");
		}
	}
	return true;
}