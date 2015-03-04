<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

require_once SHELL_PATH . 'DB.php';

trait Shell {
	protected $_errors = array();
	protected $_errorsNumber = 0;
	protected $result = null;
	private static $instance = null;

	private function __construct() { if(method_exists($this,'__init')) $this->__init(); /* ... @return Singleton */ }  // Защищаем от создания через new Singleton
	private function __clone() { /* ... @return Singleton */ }  // Защищаем от создания через клонирование
	private function __wakeup() { /* ... @return Singleton */ }  // Защищаем от создания через unserialize

	public static function &getInstance(){
		if(empty(self::$instance)){
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function errors(){
		return $this->_errors;
	}
	public function addError($key,$code,$details = array()){
		$this->_errors[] = Lang::getInstance()->getError($key,$code,$details);
		$this->_errorsNumber ++;
		return $this;
	}
	public function flushErrors(){
		$this->_errors = array();
		$this->_errorsNumber = 0;
		return $this;
	}
	public function putResult(&$to){
		$to = $this->result;
		return $this;
	}
	private function setResult($result){
		$this->result = $result;
		return $this;
	}
	public function getResult(){
		return $this->result;
	}
	public function addErrors($errors){
		$this->_errors = array_merge($this->_errors,$errors);
		$this->_errorsNumber += count($errors);
		return $this;
	}
}

trait Controller {
	use Shell;

	private $lang = null;
	private $type = null;

	private function __init(){
		$this->type = get_class($this);
		return $this;
	}

	public function _setLang($lang){
		$this->lang = $lang;
		return $this;
	}
	public function _upsert($data = null, $id = null){
		return $this->addErrors(DB::getInstance()->upsert($this->type, $this->lang, $data, $id)->putResult($this->result)->errors());
	}
	public function _set($ids,$value = true,$field = 'Active'){
		return $this->addErrors(DB::getInstance()->set($this->type, $ids, $value, $field)->errors());
	}
	public function _get($filter = null,$key = null,$checkActive = false){
		return $this->addErrors(DB::getInstance()->get($this->type, $this->lang, $filter, $key, $checkActive)->putResult($this->result)->errors());
	}
	public function _drop($ids){
		if(method_exists($this,'preDrop')){
			call_user_func(array($this,'preDrop'),$ids);
			if($this->_errorsNumber){
				return $this;
			}
		}
		return $this->addErrors(DB::getInstance()->drop($this->type, $ids)->errors());
	}
	public function _find($key,$value){
		foreach ($this->result as $element) {
			if($element[$key] == $value){
				return $element;
			}
		}
		return null;
	}
}