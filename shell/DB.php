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
		$v = Validation::_getInstance()->setTable($table)->setLang()->setMode('required')->processID($id,true);
		if(!is_null($data)) {
			if($lang){
				$dataLang = $data;
				$v->process($data)->setLang(false)->process($dataLang)->processLocale($lang);
			} else {
				$v->process($data);
			}
		}
		if($this->countErrors()){
			return $this;
		}
		$data['ID'] = $id;
		$QB = QueryBuilder::_getInstance()->clean();
		if(!($res = $this->link->query($QB->build('upsert',$table,$data)))){
			return $this->addError('insert/update',$this->link->errno,$this->link->error);
		}
		$id = is_null($id) ? $this->link->insert_id : $id;
		if($lang){
			$dataLang["{$table}ID"] = $id;
			$dataLang['Lang'] = $lang;
			$QB->_('lang',true);
			if(!($res = $this->link->query($QB->build('upsert',"{$table}Lang",$dataLang)))){
				return $this->addError('insert/update',$this->link->errno,$this->link->error);
			}
		}
		return $this->get($table,$lang,$id);
	}
	public function set($table,$ids,$flags = 'Active',$value = true){
		if(!is_array($ids)){
			$ids = array($ids);
		}
		switch(true){
			case !is_array($flags):
				$flags = array($flags => $value);
				break;
			case !is_assoc($flags):
				$flags = array_fill_keys($flags,$value);
				break;
		}
		Validation::_getInstance()->processID($ids)->processFlags($flags);
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
		$v = Validation::_getInstance()->setTable($table)->setLang($lang ? true : null)->setMode('flags');
		$QB = QueryBuilder::_getInstance()->clean();
		$QB->_('lang',$lang);
		if(!is_null($filter)) {
			if (!is_array($filter) || !is_assoc($filter)) {
				if(is_null($key)){
					$key = "{$table}.ID";
				}
				$filter = array($key => $filter);
			}
			if(array_key_exists("{$table}.ID",$filter)){
				$filter["{$table}.ID"] = $v->processID($filter["{$table}.ID"])->process($filter)->__();
			} else {
				$v->process($filter);
			}
		}
		if($lang) {
			$v->processLocale($filter["{$table}Lang.Lang"] = $lang);
		}
		if($with){
			$v->processWith($with);
			$QB->with($table,$with);
		}
		if($aggregate){
			$v->processAggregate($aggregate);
			$QB->aggregate($table,$aggregate);
		}
		if($this->countErrors()){
			return $this;
		}
		if(!($res = $this->link->query($QB->build('get',$table,$filter)))){
			return $this->addError('select',$this->link->errno,$this->link->error);
		}
		if(is_array($filter) && array_key_exists("{$table}.ID",$filter) && !is_array($filter["{$table}.ID"])){
			if($res->num_rows == 0){
				return $this->addError('select',0,array($table,$filter["{$table}.ID"]));
			}
			$result = $res->fetch_assoc();
		} else {
			if($res->num_rows > 0) {
				$result = array();
				while($row = $res->fetch_assoc()){
					$result[] = $row;
				}
			} else {
				$result = null;
			}
		}
		$res->free();
		return $this->result($result);
	}
	public function drop($table,$ids,$lang = false){
		if(!is_array($ids)){
			$ids = array($ids);
		}
		Validation::_getInstance()->processID($ids);
		if($this->countErrors()) {
			return $this;
		}
		$QB = QueryBuilder::_getInstance()->clean();
		if($lang && !($res = $this->link->query($QB->build('drop', "`{$table}Lang`", array("`{$table}ID`" => implode(',', $ids)))))){
			return $this->addError('drop', $this->link->errno, $this->link->error);
		}
		if(!($res = $this->link->query($QB->build('drop',"`{$table}`",array("`ID`"  => implode(',',$ids)))))){
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
}
