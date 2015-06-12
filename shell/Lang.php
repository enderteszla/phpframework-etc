<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Lang extends Singleton {
	/**
	 * @var string
	 */
	private $_lang = null;
	/**
	 * @var array
	 */
	private $_vars = null;
	/**
	 * @var array
	 */
	private $_loaded = null;

	protected function __init(){
		$this->_lang = input('lang') . '/';
		$this->_loaded = array();
		$this->_vars = array();
	}

	/**
	 * @param string $key
	 * @param string|array $type
	 * @param array $args
	 * @return bool|string
	 */
	public function getValue($key,$type = null,$args = array()){
		switch(true){
			case is_null($type):
				$types = array();
				break;
			case is_array($type):
				$types = $type;
				break;
			default:
				$types = array($type);
		}
		$vars = $this->_vars;
		foreach($types as $type){
			if(!array_key_exists($type,$vars)){
				return false;
			}
			$vars = $vars[$type];
		}
		return array_key_exists($key,$vars) ? vsprintf($vars[$key],$args) : false;
	}

	/**
	 * @param string $key
	 * @param int $code
	 * @param array|string $details
	 * @return string
	 */
	public function getError($key,$code,$details = array()){
		return "{$this->_vars['Error'][$key]['name']} ({$code}):" . (is_array($details) ? vsprintf($this->_vars['Error'][$key][$code],$details) : $details);
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	public function load($type){
		if(!in_array($type,$this->_loaded) && is_file(LANG_PATH . "{$this->_lang}{$type}.php")){
			$Lang = array();
			include_once LANG_PATH . "{$this->_lang}{$type}.php";
			$this->_loaded[] = $type;
			$this->_vars[ $type ] = $Lang;
		}
		return $this;
	}
}