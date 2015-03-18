<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Loader {
	use Singleton;

	private function __init(){
		Config::_getInstance()->load('Loader');
		$this->autoload();
	}

	public function load($class){
		switch(true){
			case is_file(CONTROLLER_PATH . "$class.php"):
				include_once CONTROLLER_PATH . "$class.php";
				break;
			case is_file(SHELL_PATH . "$class.php"):
				include_once SHELL_PATH . "$class.php";
				break;
			default:
				include_once BASE_PATH . '/404.php';
		}
	}

	private function autoload(){
		foreach(Config::_getInstance()->getValue('helper','Loader') as $helper){
			include_once HELPER_PATH . "$helper.php";
		}
		foreach(Config::_getInstance()->getValue('lang','Loader') as $lang){
			Lang::_getInstance()->load($lang);
		}
		foreach(Config::_getInstance()->getValue('config','Loader') as $config){
			Config::_getInstance()->load($config);
		}
	}
}