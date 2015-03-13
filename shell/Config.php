<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Config {
	use Shell;

	private $_vars = null;
	private $_loaded = null;

	private function __init(){
		$this->_loaded = array();
		$this->_vars = array();
		$this->load('Default');
	}

	public function setValue($key,$type,$value){
		$this->_vars[$type][$key] = $value;
		return $this;
	}
	public function getValue($key,$type = null){
		return is_null($type) ?
			((array_key_exists($key,$this->_vars)) ? $this->_vars[$key] : false) :
			((array_key_exists($key,$this->_vars[$type])) ? $this->_vars[$type][$key] : false);
	}
	public function load($type){
		if(!in_array($type,$this->_loaded) && is_file(CONFIG_PATH . $type . ".php")){
			$Config = array();
			include_once CONFIG_PATH . $type . ".php";
			$this->_loaded[] = $type;
			$this->_vars[ $type ] = $Config;
		}
		return $this;
	}
}