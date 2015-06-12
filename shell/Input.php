<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Input extends Singleton {
	/**
	 * @var array
	 */
	private $_vars = null;

	protected function __init(){
		Config::_getInstance()->load('Input');
		$this->_vars = array_merge(config('Input'),$_COOKIE,$_REQUEST);
	}

	/**
	 * @param string $key
	 * @return string|bool
	 */
	public function getValue($key){
		return (array_key_exists($key,$this->_vars)) ? $this->_vars[$key] : false;
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return $this
	 */
	public function setValue($key,$value){
		$this->_vars[$key] = $value;
		return $this;
	}
}