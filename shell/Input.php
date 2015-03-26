<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Input {
	use Shell;

	private $_vars = null;

	private function __init(){
		Config::_getInstance()->load('Input');
		$this->_vars = array_merge(config('Input'),$_COOKIE,$_REQUEST);
	}

	public function getValue($key){
		return (array_key_exists($key,$this->_vars)) ? $this->_vars[$key] : false;
	}
	public function setValue($key,$value){
		$this->_vars[$key] = $value;
		return $this;
	}
}