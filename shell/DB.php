<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class DB {
	use Shell;

	private $link = null;

	private function __init(){
		Config::_getInstance()->load('DB');
		$this->link = new mysqli(config('host','DB'),config('user','DB'),config('password','DB'),config('db','DB'));
		if($this->link->connect_error){
			$this->addError('connection',$this->link->connect_errno,$this->link->connect_error);
		}
		$this->link->set_charset(config('encoding','DB'));
	}

	public function upsert($table,$lang = false,$data = null,$id = null){
		$v = Validation::_getInstance()->clean()->setLang()->setMode('required')->processID($id,true);
		if(!is_null($data)) {
			if($lang){
				$dataLang = $data;
				$v->process($table,$data)->setLang(false)->process($table,$dataLang)->processLocale($lang);
			} else {
				$v->process($table,$data);
			}
		}
		if($this->countErrors()){
			return $this;
		}
		$data['ID'] = $id;
		$QB = QueryBuilder::_getInstance();
		if(!($res = $this->link->query($QB->clean()->build('upsert',$table,$data)))){
			return $this->addError('insert/update',$this->link->errno,$this->link->error);
		}
		$id = is_null($id) ? $this->link->insert_id : $id;
		if($lang){
			$dataLang["{$table}ID"] = $id;
			$dataLang['Lang'] = $lang;
			$QB->_('lang',true);
			if(!($res = $this->link->query($QB->clean()->build('upsert',"{$table}Lang",$dataLang)))){
				return $this->addError('insert/update',$this->link->errno,$this->link->error);
			}
		}
		return $this->get($table,$lang,$id);
	}
	public function set($table,$ids,$flags = 'Active',$value = true){
		if(!is_array($ids)){
			$ids = array($ids);
		}
		$flags = is_array($flags) ? $flags : array($flags);
		$flags = is_assoc($flags) ? $flags : array_fill_keys($flags,$value);
		Validation::_getInstance()->clean()->setLang()->processID($ids)->processFlags($table,$flags);
		if($this->countErrors()) {
			return $this;
		}
		$QB = QueryBuilder::_getInstance()->clean();
		if(!($res = $this->link->query($QB->build('set',$table,array('flags' => $flags,'ids' => $ids))))){
			return $this->addError('set',$this->link->errno,$this->link->error);
		}
		return $this;
	}
	public function get($table,$lang = false,$filter = null,$key = null,$with = null,$aggregate = null){
		$this->result(null);
		$v = Validation::_getInstance()->clean()->setLang($lang ? true : null)->setMode('flags');
		$QB = QueryBuilder::_getInstance()->clean();
		$QB->_('lang',$lang);
		if($with){
			$v->processWith($table,$with);
			$QB->with($with);
		}
		if($aggregate){
			$v->processAggregate($table,$aggregate);
			$QB->aggregate($aggregate);
		}
		if(!is_null($filter)) {
			if (!is_array($filter) || !is_assoc($filter)) {
				if(is_null($key)){
					$key = "{$table}.ID";
				}
				$filter = array($key => $filter);
			}
			if(array_key_exists("{$table}.ID",$filter)){
				$filter["{$table}.ID"] = $v->processID($filter["{$table}.ID"])->process($table,$filter)->__();
			} else {
				$v->process($table,$filter);
			}
		}
		if($lang) {
			$v->processLocale($filter["{$table}Lang.Lang"] = $lang);
		}
		if($this->countErrors()){
			return $this;
		}
		$result = Result::_getInstance()->fetch(
			$this->link->query($QB->build('get',$table,$filter)),
			is_array($filter) && array_key_exists("{$table}.ID",$filter) && !is_array($filter["{$table}.ID"])
		);
		switch(true){
			case $result === null:
				return $this->addError('select',$this->link->errno,$this->link->error);
			case $result === false:
				return $this->addError('select',0,array($table,$filter["{$table}.ID"]));
			case $result === array():
				return $this->result(null);
			default:
				return $this->result($result);
		}
	}
	public function drop($table,$ids,$lang = false){
		if(!is_array($ids)){
			$ids = array($ids);
		}
		Validation::_getInstance()->clean()->processID($ids);
		if($this->countErrors()) {
			return $this;
		}
		$QB = QueryBuilder::_getInstance();
		if($lang && !($res = $this->link->query($QB->clean()->build('drop', "{$table}Lang", array("{$table}ID" => $ids))))){
			return $this->addError('drop', $this->link->errno, $this->link->error);
		}
		if(!($res = $this->link->query($qb = $QB->clean()->build('drop',$table,array("ID"  => $ids))))){
			return $this->addError('drop',$this->link->errno,$this->link->error);
		}
		return $this;
	}
	public function makeIndexedArray(){
		if(!is_assoc($this->_result) || empty($this->_result)){
			return $this->result(null);
		}
		$return = array();
		foreach($this->_result as $element){
			$return[$element['ID']] = $element;
		}
		return $this->result($return);
	}
	public function query($query){
		if($res = $this->link->multi_query($query)) {
			do {
				if ($result = mysqli_store_result($this->link)) {
					mysqli_free_result($result);
				}
				if (mysqli_more_results($this->link));
			} while (mysqli_next_result($this->link));
		}
		return $res;
	}
	public function escape($string){
		return $this->link->real_escape_string($string);
	}
}