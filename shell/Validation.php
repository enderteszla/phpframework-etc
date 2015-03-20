<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Validation {
	use Shell;

	private $table = null;
	private $lang = null;
	private $mode = null;
	private $rules = null;
	private $flags = null;

	public function __init(){
		$this->mode = array();
		Config::_getInstance()->load('Validation');
		$this->rules = config('rules','Validation');
		$this->flags = config('flags','Validation');
	}

	public function setTable($table){
		if(!array_key_exists($table,$this->rules) || !array_key_exists($table,$this->flags)){
			$this->addError('validation',0,array($table));
		} else {
			$this->table = $table;
		}
		return $this;
	}
	public function setLang($lang = null){
		$this->lang = $lang;
		return $this;
	}
	public function setMode($mode = null){
		switch(true){
			case is_null($mode):
				$this->mode = array();
				break;
			case is_array($mode):
				$this->mode = $mode;
				break;
			default:
				$this->mode = array($mode);
		}
		return $this;
	}
	public function process(&$data){
		switch(true){
			case $this->lang === true: // Locale-sensitive data available; use both.
				$rules = array_merge($this->rules[$this->table . 'Lang'],$this->rules[$this->table]);
				break;
			case $this->lang === false: // Locale-sensitive data available; use only it.
				$rules = $this->rules[$this->table . 'Lang'];
				break;
			default: // Locale-sensitive data unavailable.
				$rules = $this->rules[$this->table];
		}
		if(in_array('flags',$this->mode)){
			$rules = array_merge(array_fill_keys($this->flags[$this->table],'_bool'),$rules);
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
						$this->addError('validation',2,array($key,stringify($value)));
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
					$this->addError('validation',2,array($key,stringify($value)));
					break;
				default:
			}
		}
		if(in_array('non-empty',$this->mode) && empty($data)){
			$this->addError('validation',3);
		}
		if(in_array('required',$this->mode)){
			foreach (array_keys($rules) as $key) {
				if (!array_key_exists($key, $data)) {
					$this->addError('validation', 4, array($key));
				}
			}
		}
		return $this;
	}
	public function processID(&$ids,$null = false){
		$filter = $null ? '_idNull' : '_id';
		if(is_array($ids)){
			$returnArray = true;
		} else {
			$returnArray = false;
			$ids = array($ids);
		}
		foreach($ids as $i => &$id){
			if(!call_user_func_array(array($this,$filter),array(&$id))){
				unset($ids[$i]);
			}
		}
		if(empty($ids)){
			return $this->addError('validation',5,array(stringify($ids)));
		}
		return $this->result($ids = $returnArray ? $ids : $ids[0]);
	}
	public function processLocale($lang){
		if(!in_array($lang,config('locales','Default'))){
			$this->addError('validation',6);
		}
		return $this;
	}
	public function processFlags(&$flags){
		foreach($flags as $k => &$v){
			if(!in_array($k,$this->flags[$this->table]) || call_user_func_array(array($this,'_bool'),array(&$v))){
				unset($flags[$k]);
			}
		}
		return $this;
	}
	public function processWith(&$with){
		$return = array();
		foreach(is_array($with) ? $with : array($with) as $item){
			if(!array_key_exists($item,$this->rules) || !array_key_exists($item,$this->flags)){
				continue;
			}
			foreach(preg_grep("/^{$item}ID\d*$/",array_keys($this->rules[$this->table])) as $key) {
				$d = str_replace("{$item}ID",'',$key);
				$fields = array();
				foreach (array_merge(array_fill_keys($this->flags[$item],'_bool'),$this->rules[$item]) as $key => $rule) {
					$fields[$key] = "{$item}{$key}{$d}";
				}
				if(!is_null($this->lang) && array_key_exists($item . 'Lang',$this->rules)){
					$langFields = array();
					foreach ($this->rules[$item . 'Lang'] as $key => $rule) {
						$langFields[$key] = "{$item}{$key}{$d}";
					}
					$return[] = array(
						'Table' => $item,
						'Key' => "{$item}ID{$d}",
						'Alias' => "{$item}{$d}",
						'LangAlias' => "{$item}Lang{$d}",
						'Fields' => $fields,
						'LangFields' => $langFields
					);
				} else {
					$return[] = array(
						'Table' => $item,
						'Key' => "{$item}ID{$d}",
						'Alias' => "{$item}{$d}",
						'Fields' => $fields
					);
				}
			}
		}
		$with = $return;
		return $this;
	}

	public function processAggregate(&$aggregate){
		$return = array();
		foreach(is_array($aggregate) ? $aggregate : array($aggregate => 'MAX(ID)') as $item => $signature){
			if(!array_key_exists($item,$this->rules) || !array_key_exists($item,$this->flags) || !preg_match("/^([^\(]+)\(([^\)]+)\)$/i",$signature,$m)){
				continue;
			}
			list(,$func,$arg) = $m;
			switch(true){
				case $this->lang === true: // Locale-sensitive data available; use both.
					$rules = array_merge($this->rules[$item . 'Lang'],$this->rules[$item]);
					break;
				case $this->lang === false: // Locale-sensitive data available; use only it.
					$rules = $this->rules[$item . 'Lang'];
					break;
				default: // Locale-sensitive data unavailable.
					$rules = $this->rules[$item];
			}
			if(in_array('flags',$this->mode)){
				$rules = array_merge(array_fill_keys($this->flags[$item],'_bool'),$rules);
			}
			if(!array_key_exists($arg,$rules) || !in_array($func,config('functions','Validation'))){
				continue;
			}
			$return[] = array(
				'Table' => $item,
				'Function' => "$func(`$item`.`$arg`)",
				'Alias' => $signature
			);
		}
		$aggregate = $return;
		return $this;
	}

	private function _id(&$field) {
		$field = (preg_match("/^\d+$/","$field",$m)) ? $m[0] : 0;
		return settype($field,'int') && $field > 0;
	}
	private function _idNull(&$field){
		if(empty($field)){
			$field = null;
			return true;
		}
		return $this->_id($field);
	}
	private function _int(&$field) {
		$field = (preg_match("/^\d+$/","$field",$m)) ? $m[0] : 0;
		return settype($field,'int');
	}
	private function _text(&$field) {
		$field = htmlentities($field,ENT_QUOTES,"UTF-8");
		return !empty($field);
	}
	private function _textEmpty(&$field) {
		$field = htmlentities($field,ENT_QUOTES,"UTF-8");
		return true;
	}
	private function _bool(&$field) {
		return settype($field,'bool');
	}
}