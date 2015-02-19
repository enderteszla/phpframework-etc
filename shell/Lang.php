<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Lang {
	use Shell;

	private $_lang = null;
	private $_vars = null;
	private $_loaded = null;

	private function __init(){
		$this->_lang = Input::getInstance()->getValue('lang') . '/';
		$this->_loaded = array();
		$this->_vars = array();
		$Lang = array();
		include LANG_PATH . $this->_lang . "Default.php";
		include LANG_PATH . $this->_lang . "Error.php";
		$this->_vars = $Lang;
	}

	public function getValue($key,$type = null,$args = array()){
		return vsprintf(is_null($type) ?
			((array_key_exists($key,$this->_vars)) ? $this->_vars[$key] : false) :
			((array_key_exists($key,$this->_vars[$type])) ? $this->_vars[$type][$key] : false),$args);
	}
	public function getError($key,$code,$details = array()){
		return "{$this->_vars['error'][$key]['name']} ({$code}):" . (is_array($details) ? vsprintf($this->_vars['error'][$key][$code],$details) : $details);
	}
	public function setValue($key,$value){
		$this->_vars[$key] = $value;
		return $this;
	}
	public function load($type){
		if(!in_array($type,$this->_loaded) && is_file(LANG_PATH . $this->_lang . $type . ".php")){
			$Lang = array();
			include LANG_PATH . $this->_lang . $type . ".php";
			$this->_loaded[] = $type;
			$this->_vars[ $type ] = $Lang;
		}
		return $this;
	}
}