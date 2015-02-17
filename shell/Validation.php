<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Validation {
	use Shell;

	private $table = null;
	private $rules = null;
	private $locales = null;

	public function __init(){
		$Validation = array();
		$locales = array();
		require_once CONFIG_PATH . 'Validation.php';
		$this->rules = $Validation;
		$this->locales = $locales;
	}

	public function setTable($table){
		if(!array_key_exists($table,$this->rules)){
			$this->addError('validation',0,array($table));
		} else {
			$this->table = $table;
		}
		return $this;
	}
	public function process(&$data,$lang = null,$mode = null){
		switch(true){
			case $lang === true: // Locale-sensitive data available; use both.
				$rules = array_merge($this->rules[$this->table . 'Lang'],$this->rules[$this->table]);
				break;
			case $lang === false: // Locale-sensitive data available; use only it.
				$rules = $this->rules[$this->table . 'Lang'];
				break;
			default: // Locale-sensitive data unavailable.
				$rules = $this->rules[$this->table];
		}
		foreach ($data as $key => &$value) {
			switch(true){
				case !array_key_exists($key,$rules):
					unset($data[ $key ]);
					break;
				case is_array($rules[$key]) && is_array($value):
					foreach($value as $i => &$element){
						if(!in_array($element,$rules[$key])) {
							unset($value[$i]);
						}
					}
					if(empty($value)){
						$this->addError('validation',1,array($key));
					}
					break;
				case is_array($rules[$key]):
					if(!in_array($value,$rules[$key])) {
						$this->addError('validation',2,array($key));
					}
					break;
				case is_array($value):
					foreach($value as $i => &$element){
						if(!call_user_func_array(array($this,$rules[$key]),array(&$element))) {
							unset($value[$i]);
						}
					}
					if(empty($value)){
						$this->addError('validation',1,array($key));
					}
					break;
				case !call_user_func_array(array($this,$rules[$key]),array(&$value)):
					$this->addError('validation',2,array($key));
					break;
				default:
			}
		}
		switch($mode){
			case 'non-empty':
				if(empty($data)){
					$this->addError('validation',3);
				}
				break;
			case 'required':
				foreach (array_keys($rules) as $key) {
					if (!array_key_exists($key, $data)) {
						$this->addError('validation',4,array($key));
					}
				}
				break;
			default:
		}
		return $this;
	}
	public function processID($ids){
		if(is_array($ids)){
			$returnArray = true;
		} else {
			$returnArray = false;
			$ids = array($ids);
		}
		foreach($ids as $i => &$id){
			if(!call_user_func_array(array($this,'_id'),array(&$id))){
				unset($ids[$i]);
			}
		}
		if(empty($ids)){
			$this->addError('validation',5);
		} else {
			$this->result = $returnArray ? $ids : $ids[0];
		}
		return $this;
	}
	public function processLocale($lang){
		if(!in_array($lang,$this->locales)){
			$this->addError('validation',6);
		}
		return $this;
	}

	private function _id(&$field) {
		if(is_null($field)){
			return true;
		}
		$field = (preg_match("/^\d+$/","$field",$m)) ? $m[0] : 0;
		return settype($field,'int') && $field > 0;
	}
	private function _int(&$field) {
		$field = (preg_match("/^\d+$/","$field",$m)) ? $m[0] : 0;
		return settype($field,'int');
	}
	private function _text(&$field) {
		$field = htmlentities($field,ENT_QUOTES,"UTF-8");
		return !empty($field);
	}
	private function _bool(&$field) {
		return settype($field,'bool');
	}
}