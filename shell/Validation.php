<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Validation {
	use Shell;

	private $lang = null;
	private $mode = null;
	private $rules = null;

	private function __init(){
		$this->mode = array();
		Config::_getInstance()->load('Validation');
	}

	public function clean(){
		$this->rules = array();
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
	public function process($table,&$data){
		switch(true){
			case $this->lang === true: // Locale-sensitive data available; use both.
				$rules = array_merge(config('rules','Validation')[$table . 'Lang'],
					config('rules','Validation')[$table],$this->rules);
				break;
			case $this->lang === false: // Locale-sensitive data available; use only it.
				$rules = array_merge(config('rules','Validation')[$table . 'Lang'],$this->rules);
				break;
			default: // Locale-sensitive data unavailable.
				$rules = array_merge(config('rules','Validation')[$table],$this->rules);
		}
		if(in_array('flags',$this->mode)){
			$rules = array_merge(array_fill_keys(config('flags','Validation')[$table],'_bool'),$rules);
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
	public function processFlags($table,&$flags){
		foreach($flags as $k => &$v){
			if(!in_array($k,config('flags','Validation')[$table])
				|| !call_user_func_array(array($this,'_bool'),array(&$v))){
				unset($flags[$k]);
			}
		}
		return $this;
	}
	public function processWith($joinedTable,&$with){
		$return = array();
		foreach(is_array($with) ? $with : array($with) as $k => &$v){
			$item = is_numeric($k) ? $v : $k;
			if(!array_key_exists($item,config('rules','Validation'))
				|| !array_key_exists($item,config('flags','Validation'))){
				continue;
			}
			foreach(preg_grep("/^{$item}ID\d*$/",array_keys(config('rules','Validation')[end(explode('<',$joinedTable))])) as $key) {
				$d = str_replace("{$item}ID",'',$key);
				$fields = array();
				foreach (array_merge(array_fill_keys(config('flags','Validation')[$item],'_bool'),
					config('rules','Validation')[$item]) as $key => $rule) {
					$fields[$key] = implode('<',array_slice(explode('<',"{$joinedTable}<{$item}{$key}{$d}"),1));
					$this->rules["{$joinedTable}<{$item}{$d}.{$key}"] = $rule;
				}
				$return["{$joinedTable}<{$item}{$d}"] = array(
					'JoinedTable' => $joinedTable,
					'Table' => $item,
					'Key' => "{$item}ID{$d}",
					'Fields' => $fields
				);
				if(!is_null($this->lang) && array_key_exists($item . 'Lang',config('rules','Validation'))){
					$return["{$joinedTable}<{$item}{$d}"]['LangAlias'] = "{$joinedTable}<{$item}Lang{$d}";
					$return["{$joinedTable}<{$item}{$d}"]['LangFields'] = array();
					foreach(config('rules','Validation')[$item . 'Lang'] as $key => $rule) {
						$return["{$joinedTable}<{$item}{$d}"]['LangFields'][$key] = implode('<',array_slice(explode('<',"{$joinedTable}<{$item}{$key}{$d}"),1));
						$this->rules["{$joinedTable}<{$item}Lang{$d}.{$key}"] = $rule;
					}
				}
				if(!is_numeric($k)) {
					$v_ = $v;
					$this->processWith("{$joinedTable}<{$item}{$d}", $v_);
					$return = array_merge($return, $v_);
					unset($v_);
				}
			}
		}
		$with = $return;
		return $this;
	}
	public function processAggregate($joinedTable,&$aggregate){
		$return = array();
		$aggregate = is_array($aggregate) ? $aggregate : array($aggregate);
		$aggregate = is_assoc($aggregate) ? $aggregate : array_fill_keys($aggregate,"COUNT(ID)");
		foreach($aggregate as $joiningTable => $array){
			if(!array_key_exists($joiningTable,config('rules','Validation'))){
				continue;
			}
			$rules = config('rules','Validation')[$joiningTable];
			if(in_array('flags',$this->mode)) {
				if (!array_key_exists($joiningTable, config('flags', 'Validation'))) {
					continue;
				}
				$rules = array_merge(array_fill_keys(config('flags','Validation')[$joiningTable],'_bool'),$rules);
			}
			$rules['ID'] = '_id';
			$alias = "{$joinedTable}>{$joiningTable}";
			foreach($rules as $key => $rule){
				$this->rules["{$alias}.{$key}"] = $rule;
			}
			$fields = array();
			$array = is_array($array) ? $array : array($array);
			$array = is_assoc($array) ? $array : array('Fields' => $array);
			if(!array_key_exists('Fields',$array)){
				$array['Fields'] = array();
			}
			foreach(is_array($array['Fields']) ? $array['Fields'] : array($array['Fields']) as $signature){
				if (!preg_match("/^([^\(]+)\(([^\)]+)\)(\s(.*))?$/i", $signature, $m)) {
					continue;
				}
				list(, $function, $argument) = $m;
				if (!array_key_exists($argument, $rules) || !in_array($function, config('functions', 'Validation'))) {
					continue;
				}
				$fields[(array_key_exists(4, $m)) ? $m[4] : $signature] = "$function(`$alias`.`$argument`)";
			}
			$return[$alias] = array(
				'JoinedTable' => end(explode('>',$joinedTable)),
				'JoinedTableAlias' => $joinedTable,
				'JoiningTable' => $joiningTable,
				'Fields' => $fields
			);
			if(!array_key_exists('Aggregate',$array)){
				$array['Aggregate'] = array();
			}
			$this->processAggregate($alias,$array['Aggregate']);
			$return = array_merge($return,$array['Aggregate']);
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