<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Config extends Singleton {
	/**
	 * @var array
	 */
	private $_vars = null;
	/**
	 * @var array
	 */
	private $_loaded = null;

	protected function __init(){
		$this->_loaded = array();
		$this->_vars = array();
	}

	/**
	 * @param string $key
	 * @param string $type
	 * @param mixed $value
	 * @return $this
	 */
	public function setValue($key,$type,$value){
		$this->_vars[$type][$key] = $value;
		return $this;
	}

	/**
	 * @param string $key
	 * @param string|null $type
	 * @return mixed|callback
	 */
	public function getValue($key,$type = null){
		return is_null($type) ?
			((array_key_exists($key,$this->_vars)) ? $this->_vars[$key] : false) :
			((array_key_exists($key,$this->_vars[$type])) ? $this->_vars[$type][$key] : false);
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	public function load($type){
		if(!in_array($type,$this->_loaded) && is_file(CONFIG_PATH . "$type.php")){
			$Config = array();
			include_once CONFIG_PATH . "$type.php";
			$this->_loaded[] = $type;
			$this->_vars[ $type ] = $Config;
		}
		return $this;
	}
}