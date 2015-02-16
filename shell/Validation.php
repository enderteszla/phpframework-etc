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
			$this->addError("Validation Error (0): No validation rule for '$table' class");
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
			if(!array_key_exists($key,$rules)) {
				unset($data[ $key ]);
			} elseif(is_array($value)){
				foreach($value as $i => &$element){
					if(!call_user_func_array(array($this,$rules[$key]),array(&$element))) {
						unset($value[$i]);
					}
				}
				if(empty($value)){
					$this->addError("Validation Error (1): Array value for field '$key' has no valid elements");
				}
			} elseif(!call_user_func_array(array($this,$rules[$key]),array(&$value))) {
				$this->addError("Validation Error (2): Field '$key' has invalid value");
			}
		}
		switch($mode){
			case 'non-empty':
				if(empty($data)){
					$this->addError("Validation Error (3): Empty request");
				}
				break;
			case 'required':
				foreach (array_keys($rules) as $key) {
					if (!array_key_exists($key, $data)) {
						$this->addError("Validation Error (4): Field '$key' required");
					}
				}
				break;
			default:
		}
		return $this;
	}
	public function processID($id){
		if(!call_user_func_array(array($this,'_id'),array(&$id))){
			$this->addError("Validation Error (5): ID has invalid value");
		} else {
			$this->result = $id;
		}
		return $this;
	}
	public function processLocale($lang){
		if(!in_array($lang,$this->locales)){
			$this->addError("Validation Error (6): Invalid locale");
		}
		return $this;
	}

	private function _id(&$field) {
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