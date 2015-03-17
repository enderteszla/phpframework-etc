<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

trait Singleton {
	private static $instance = null;
	private $vars = null;

	private function __construct() { if(method_exists($this,'__init')) $this->__init(); /* ... @return Singleton */ }  // Защищаем от создания через new Singleton
	private function __clone() { /* ... @return Singleton */ }  // Защищаем от создания через клонирование
	private function __wakeup() { /* ... @return Singleton */ }  // Защищаем от создания через unserialize

	public static function &_getInstance(){
		if(empty(self::$instance)){
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function &_($var,$val = null){
		if(!is_null($val)) {
			$this->vars[ $var ] = $val;
		}
		return $this->vars[$var];
	}
}

require_once SHELL_PATH . 'Error.php';

trait Shell {
	use Singleton;

	protected $_result = null;

	private function errors(){
		return Error::_getInstance()->get();
	}
	private function countErrors(){
		return Error::_getInstance()->count();
	}
	private function addError($key,$code,$details = array()){
		Error::_getInstance()->add($key,$code,$details);
		return $this;
	}

	private function result($result){
		$this->_result = $result;
		return $this;
	}
	public function __(&$to = null){
		if(is_null($to)){
			return $this->_result;
		}
		$to = $this->_result;
		return $this;
	}
}

require_once SHELL_PATH . 'DB.php';

trait Controller {
	use Shell;

	private function __init(){
		$this->_('type',get_class($this));
		Lang::_getInstance()->load($this->_('type'));
		return $this;
	}

	public function _upsert($data = null, $id = null){
		return $this->result(DB::_getInstance()->upsert($this->_('type'), $this->_('lang'), $data, $id)->__());
	}
	public function _set($ids,$value = true,$field = 'Active'){
		DB::_getInstance()->set($this->_('type'), $ids, $value, $field);
		return $this;
	}
	public function _get($filter = null,$key = null,$with = null, $aggregate = null){
		return $this->result(DB::_getInstance()->get($this->_('type'), $this->_('lang'), $filter, $key, $with, $aggregate)->__());
	}
	public function _eq($n = 0){
		switch(true){
			case is_null($this->_result):
			case !is_array($this->_result):
			case is_assoc($this->_result):
			case $n >= count($this->_result):
			case $n < - count($this->_result):
				return $this->result(null);
			case $n < 0:
				$n += count($this->_result);
		}
		return $this->result($this->_result[$n]);
	}
	public function _drop(){
		if(empty($this->_result)){
			return $this;
		}
		if(is_assoc($this->_result)){
			$ids = array($this->_result['ID']);
		} else {
			$ids = array_map(function($i){return $i['ID'];},$this->_result);
		}
		DB::_getInstance()->drop($this->_('type'), $ids,$this->_('lang'));
		return $this;
	}
	public function _find($key,$value){
		foreach ($this->_result as $element) {
			if($element[$key] == $value){
				return $element;
			}
		}
		return null;
	}
	public function _fetchImages(){
		if(empty($this->_result)){
			return $this;
		}
		if(is_assoc($this->_result)){
			$ids = array_values(array_intersect_key(
				$this->_result,
				array_fill_keys(preg_grep('/^ImageID\d*$/',array_keys($this->_result)),true)
			));
		} else {
			$ids = array();
			foreach($this->_result as $element){
				$ids = array_unique(array_merge($ids, array_values(array_intersect_key(
					$element,
					array_fill_keys(preg_grep('/^ImageID\d*$/',array_keys($element)),true)
				))));
			}
		}
		DB::_getInstance()->get('Image',false,$ids)->makeIndexedArray()->__($this->_('images',false));
		if(is_assoc($this->_result)){
			foreach(preg_grep('/^ImageID\d*$/',array_keys($this->_result)) as $key){
				$n = str_replace('ImageID','',$key);
				if(!is_null($this->_result[$key])) {
					foreach ($this->_('images')[ $this->_result[ $key ] ] as $k => $v) {
						$this->_result["Image$k$n"] = $v;
					}
				}
			}
		} else {
			foreach($this->_result as &$element){
				foreach(preg_grep('/^ImageID\d*$/',array_keys($element)) as $key){
					$n = str_replace('ImageID','',$key);
					if(!is_null($element[$key])) {
						foreach ($this->_('images')[ $element[ $key ] ] as $k => $v) {
							$element["Image$k$n"] = $v;
						}
					}
				}
			}
		}
		return $this;
	}
	public function _dropImages(){
		if(empty($this->_result)){
			return $this;
		}
		if(is_assoc($this->_result)){
			$ids = array_values(array_intersect_key(
				$this->_result,
				array_fill_keys(preg_grep('/^ImageID\d*$/',array_keys($this->_result)),true)
			));
		} else {
			$ids = array();
			foreach($this->_result as $element){
				$ids = array_unique(array_merge($ids, array_values(array_intersect_key(
					$element,
					array_fill_keys(preg_grep('/^ImageID\d*$/',array_keys($element)),true)
				))));
			}
		}
		Image::_getInstance()->remove($ids);
		return $this;
	}
}